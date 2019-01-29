<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                    *
********************************************************************************************************/

namespace Kajona\System\System;

use ArrayAccess;

/**
 * The changelog is a global wrapper to the gui-based logging.
 * Changes should reflect user-changes and not internal system-logs.
 * For logging to the logfile, see Logger.
 * But: entries added to the changelog are copied to the systemlog leveled as information, too.
 * Changes are stored as a flat list in the database only and have no representation within the
 * system-table. This means there are no common system-id relations.
 * Have a look at the memento pattern by Gamma et al. to get a glance at the conceptional behaviour.
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @see Logger
 *
 * @module system
 * @moduleId _system_modul_id_
 */
class SystemChangelog
{

    const ANNOTATION_PROPERTY_VERSIONABLE = "@versionable";

    /**
     * A flag to enable / disable the changehistory programatically.
     * If not set to null, the value overwrites the global changelog constant.
     *
     * @var bool
     */
    public static $bitChangelogEnabled = null;

    private static $arrOldValueCache = array();

    private static $arrInitValueCache = array();
    private static $arrCachedProviders = null;

    private static $arrInsertCache = array();


    public static $STR_ACTION_EDIT = "actionEdit";
    public static $STR_ACTION_PERMISSIONS = "editPermissions";
    public static $STR_ACTION_DELETE = "actionDelete";


    private static $arrOldInstances = array();

    private static $arrTables;

    /**
     * Checks if an objects properties changed.
     * If the second params is passed, the set of changed properties is returned, too.
     *
     * @param VersionableInterface $objObject
     * @param array &$arrReducedSet
     * @param bool $bitUseInitValues if set to true, the initial values of the object will be used for comparison, not the ones of the last update
     *
     * @throws Exception
     * @return bool
     * @deprecated
     */
    public function isObjectChanged(VersionableInterface $objObject, &$arrReducedSet = array(), $bitUseInitValues = false)
    {
        if (!$this->isVersioningAvailable($objObject)) {
            throw new Exception("versioning not available", Exception::$level_ERROR);
        }

        //read the new values
        $arrChangeset = $this->createChangeArray($objObject, $bitUseInitValues);

        $this->createReducedChangeSet($arrReducedSet, $arrChangeset, "");
        return count($arrReducedSet) > 0;
    }


    /**
     * Reads all properties marked with the annotation @versionable.
     * The state is cached in a static array mapped to the objects systemid.
     * In consequence, this means that only objects with a valid systemid are scanned for properties under versioning.
     *
     * @param VersionableInterface|Model $objCurrentObject
     *
     * @return void
     */
    public function readOldValues(VersionableInterface $objCurrentObject)
    {
        //add only once to avoid stale entries, e.g. due to subsequent object instantiations
        if (!isset(self::$arrOldInstances[$objCurrentObject->getSystemid()])) {
            self::$arrOldInstances[$objCurrentObject->getSystemid()] = clone $objCurrentObject;
        }
        return null;
    }

    /**
     * Resets the old values for a given object, e.g. to have a clean map on new object persits
     * @param VersionableInterface|Model $objObject
     */
    public function resetOldValues(VersionableInterface $objObject)
    {
        self::$arrOldValueCache[$objObject->getSystemid()] = null;
        unset(self::$arrOldInstances[$objObject->getSystemid()]);
    }


    /**
     * Reads all properties marked with the annotation @versionable.
     * The state is cached in a static array mapped to the objects systemid.
     * In consequence, this means that only objects with a valid systemid are scanned for properties under versioning.
     *
     *
     * @param string $strSystemid
     *
     * @return array|null
     */
    private function readOldValuesInternal($strSystemid)
    {

        if (isset(self::$arrOldInstances[$strSystemid])) {
            $objCurrentObject = self::$arrOldInstances[$strSystemid];

            if (!$this->isVersioningAvailable($objCurrentObject) || !validateSystemid($objCurrentObject->getSystemid())) {
                return null;
            }

            $arrOldValues = $this->readVersionableProperties($objCurrentObject);
            $this->setOldValuesForSystemid($strSystemid, $arrOldValues);
            //since values have been read, remove the cloned instance to release memory
            unset(self::$arrOldInstances[$strSystemid]);
            return $arrOldValues;
        }
        return null;
    }


    /**
     * Sets the passed entry for a concrete objects' property to the set of old values
     *
     * @param string $strSystemid
     * @param $strProperty
     * @param $strValue
     */
    public function setOldValueForSystemidAndProperty($strSystemid, $strProperty, $strValue)
    {
        $this->getOldValuesForSystemid($strSystemid);
        if (!isset(self::$arrOldValueCache[$strSystemid])) {
            self::$arrOldValueCache[$strSystemid] = array();
        }

        self::$arrOldValueCache[$strSystemid][$strProperty] = $strValue;
    }

    /**
     * Scans the passed object and tries to find all properties marked with the annotation @versionable.
     *
     * @param VersionableInterface|Model $objCurrentObject
     *
     * @return array|null
     */
    private function readVersionableProperties(VersionableInterface $objCurrentObject)
    {
        if (!$this->isVersioningAvailable($objCurrentObject)) {
            return null;
        }

        if (validateSystemid($objCurrentObject->getSystemid())) {
            $arrCurrentValues = array();

            $objReflection = new Reflection($objCurrentObject);
            $arrProperties = $objReflection->getPropertiesWithAnnotation(self::ANNOTATION_PROPERTY_VERSIONABLE);


            foreach ($arrProperties as $strProperty => $strAnnotation) {
                $strValue = "";

                //all prerequisites match, start creating query
                $strGetter = $objReflection->getGetter($strProperty);
                if ($strGetter !== null) {
                    $strValue = $objCurrentObject->{$strGetter}();
                }

                if (is_array($strValue) || $strValue instanceof ArrayAccess) {
                    $arrNewValues = array();
                    foreach ($strValue as $objOneValue) {
                        if (is_object($objOneValue) && $objOneValue instanceof Root) {
                            $arrNewValues[] = $objOneValue->getSystemid();
                        } else {
                            $arrNewValues[] = $objOneValue."";
                        }
                    }
                    sort($arrNewValues);
                    $strValue = implode(",", $arrNewValues);
                }

                $arrCurrentValues[$strProperty] = $strValue;
            }

            return $arrCurrentValues;
        }
        return null;
    }

    /**
     * @param string $strSystemid
     *
     * @return null
     */
    public function getOldValuesForSystemid($strSystemid)
    {
        $this->readOldValuesInternal($strSystemid);
        if (isset(self::$arrOldValueCache[$strSystemid])) {
            return self::$arrOldValueCache[$strSystemid];
        } else {
            return null;
        }
    }

    /**
     * Reuturns all initial values of versionable properties, so the state before changing
     * an object. Useful if you change an object mutliple times within a single process and want to
     * fetch the initital values, too.
     *
     * @param string $strSystemid
     *
     * @return null
     */
    public function getInitValuesForSystemid($strSystemid)
    {
        if (isset(self::$arrInitValueCache[$strSystemid])) {
            return self::$arrInitValueCache[$strSystemid];
        } else {
            return null;
        }
    }

    /**
     * Sets the passed entry to the set of old values
     *
     * @param string $strSystemid
     * @param array $arrOldValues
     *
     * @return void
     */
    private function setOldValuesForSystemid($strSystemid, $arrOldValues)
    {
        self::$arrOldValueCache[$strSystemid] = $arrOldValues;
        if (!array_key_exists($strSystemid, self::$arrInitValueCache)) {
            self::$arrInitValueCache[$strSystemid] = $arrOldValues;
        }
    }

    /**
     * Builds the change-array based on the old- and new values
     *
     * @param VersionableInterface|Root $objSourceModel
     * @param bool $bitUseInitValues
     *
     * @return array
     */
    private function createChangeArray($objSourceModel, $bitUseInitValues = false)
    {

        $arrOldValues = $this->getOldValuesForSystemid($objSourceModel->getSystemid());
        if ($bitUseInitValues) {
            $arrOldValues = $this->getInitValuesForSystemid($objSourceModel->getSystemid());
        }

        //this are now the new ones
        $arrNewValues = $this->readVersionableProperties($objSourceModel);

        if ($arrOldValues == null) {
            $arrOldValues = array();
        }

        if ($arrNewValues == null) {
            $arrNewValues = array();
        }

        $arrReturn = array();
        foreach ($arrNewValues as $strPropertyName => $objValue) {
            $arrReturn[] = array(
                "property" => $strPropertyName,
                "oldvalue" => isset($arrOldValues[$strPropertyName]) ? $arrOldValues[$strPropertyName] : "",
                "newvalue" => isset($arrNewValues[$strPropertyName]) ? $arrNewValues[$strPropertyName] : ""
            );
        }

        return $arrReturn;
    }

    /**
     * May be used to add changes to the change-track manually. In most cases, createLogEntry should be sufficient since
     * it takes care of everything automatically.
     * When using this method, pass an array of entries like:
     * array(
     *   array("property" => "", "oldvalue" => "", "newvalue" => ""),
     *   array("property" => "", "oldvalue" => "", "newvalue" => "")
     * )
     *
     * @param VersionableInterface $objSourceModel
     * @param string $strAction
     * @param array $arrEntries
     * @param bool $bitForceEntry if set to true, an entry will be created even if the values didn't change
     *
     * @throws Exception
     * @return bool
     */
    public function processChanges(VersionableInterface $objSourceModel, $strAction, $arrEntries, $bitForceEntry = false)
    {
        if (!$this->isVersioningAvailable($objSourceModel)) {
            return true;
        }

        return $this->processChangeArray($arrEntries, $objSourceModel, $strAction, $bitForceEntry);
    }

    /**
     * Generates a new entry in the modification log storing all relevant information.
     * Creates an entry in the systemlog leveled as information, too.
     * By default entries with same old- and new-values are dropped.
     * The passed object has to implement VersionableInterface.
     * If $bitDeleteAction isset to true, the change will behave in a way like deleting a record. This means the new-value will be empty on save.
     * If not set manually, the system will try to detect if it's a delete operation based on the current action.
     *
     * @param VersionableInterface $objSourceModel
     * @param string $strAction
     * @param bool $bitForceEntry if set to true, an entry will be created even if the values didn't change
     * @param bool $bitDeleteAction if set to true, the change will behave in a way like deleting a record. This means the new-value will be empty on save.
     *             If not set manually, the system will try to detect if it's a delete operation based on the current action.
     *
     * @throws Exception
     * @return bool
     */
    public function createLogEntry(VersionableInterface $objSourceModel, $strAction, $bitForceEntry = false, $bitDeleteAction = null)
    {
        if (!$this->isVersioningAvailable($objSourceModel)) {
            return true;
        }

        $arrChanges = $this->createChangeArray($objSourceModel);
        $bitReturn = $this->processChangeArray($arrChanges, $objSourceModel, $strAction, $bitForceEntry, $bitDeleteAction);
        $this->readOldValues($objSourceModel);
        return $bitReturn;
    }

    /**
     * Checks if version is enabled in general and for the passed object
     *
     * @param VersionableInterface $objSourceModel
     *
     * @return bool
     * @throws Exception
     */
    private function isVersioningAvailable(VersionableInterface $objSourceModel)
    {
        if (self::$bitChangelogEnabled === null) {
            self::$bitChangelogEnabled = SystemSetting::getConfigValue("_system_changehistory_enabled_") === "true";
        }


        if (!$objSourceModel instanceof VersionableInterface) {
            throw new Exception("object passed to create changelog not implementing VersionableInterface", Exception::$level_ERROR);
        }

        return self::$bitChangelogEnabled;
    }

    /**
     * Processes the internal change-array and creates all related records.
     *
     * @param array $arrChanges
     * @param VersionableInterface|Model $objSourceModel
     * @param string $strAction
     * @param bool $bitForceEntry
     * @param bool $bitDeleteAction
     *
     * @return bool
     */
    private function processChangeArray(array $arrChanges, VersionableInterface $objSourceModel, $strAction, $bitForceEntry = false, $bitDeleteAction = null)
    {
        $bitReturn = true;
        $strUserId = Carrier::getInstance()->getObjSession()->getUserID();
        $strModelClass = get_class($objSourceModel);

        if (is_array($arrChanges)) {
            $arrReducedChanges = array();
            $this->createReducedChangeSet($arrReducedChanges, $arrChanges, $strAction, $bitForceEntry, $bitDeleteAction);

            //collect all values in order to create a batch query
            foreach ($arrReducedChanges as $arrChangeSet) {
                $strOldvalue = $arrChangeSet["oldvalue"];
                $strNewvalue = $arrChangeSet["newvalue"];
                $strProperty = $arrChangeSet["property"];

                //Logger::getInstance()->info("change in class ".$strModelClass."@".$strAction." systemid: ".$objSourceModel->getSystemid()." property: ".$strProperty." old value: ".StringUtil::truncate($strOldvalue, 60)." new value: ".StringUtil::truncate($strNewvalue, 60));

                $arrValues = array(
                    generateSystemid(),
                    Date::getCurrentTimestamp(),
                    $objSourceModel->getSystemid(),
                    $objSourceModel->getPrevid(),
                    $strUserId,
                    $strModelClass,
                    $strAction,
                    $strProperty,
                    $strOldvalue,
                    $strNewvalue
                );

                self::$arrInsertCache[self::getTableForClass($strModelClass)][] = $arrValues;
            }

        }
        return $bitReturn;
    }

    /**
     * Helper to process outstanding changelog entries.
     * Use Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_CHANGELOG) in order to trigger this method.
     *
     * @return bool
     * @see Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_CHANGELOG)
     */
    public function processCachedInserts()
    {
        $bitReturn = true;
        foreach (self::$arrInsertCache as $strTable => $arrRows) {
            if (count($arrRows) > 0) {
                $bitReturn = Carrier::getInstance()->getObjDB()->multiInsert(
                    $strTable,
                    array("change_id", "change_date", "change_systemid", "change_system_previd", "change_user", "change_class", "change_action", "change_property", "change_oldvalue", "change_newvalue"),
                    $arrRows,
                    [false, false, false, false, false, false, false, false, false, false]
                ) && $bitReturn;

                self::$arrInsertCache[$strTable] = array();
            }
        }
        return $bitReturn;
    }


    /**
     * Reduces the passed change-array to only the entries which really changed.
     *
     * @param array &$arrReturn
     * @param array $arrChanges
     * @param string $strAction
     * @param bool $bitForceEntry
     * @param null $bitDeleteAction
     *
     * @return void
     */
    private function createReducedChangeSet(array &$arrReturn, array $arrChanges, $strAction, $bitForceEntry = false, $bitDeleteAction = null)
    {

        foreach ($arrChanges as $arrChangeSet) {
            $strOldvalue = "";
            if (isset($arrChangeSet["oldvalue"])) {
                $strOldvalue = $arrChangeSet["oldvalue"];
            }

            $strNewvalue = "";
            if (isset($arrChangeSet["newvalue"])) {
                $strNewvalue = $arrChangeSet["newvalue"];
            }

            $strProperty = $arrChangeSet["property"];


            //array may be processed automatically, too
            if ((is_array($strOldvalue) || $strOldvalue instanceof ArrayAccess) && (is_array($strNewvalue) || $strNewvalue instanceof ArrayAccess)) {
                $arrArrayChanges = array();
                foreach ($strNewvalue as $strOneId) {
                    if (!in_array($strOneId, $strOldvalue)) {
                        $arrArrayChanges[] = array("property" => $strProperty, "oldvalue" => "", "newvalue" => $strOneId);
                    }
                }

                foreach ($strOldvalue as $strOneId) {
                    if (!in_array($strOneId, $strNewvalue)) {
                        $arrArrayChanges[] = array("property" => $strProperty, "oldvalue" => $strOneId, "newvalue" => "");
                    }
                }

                $this->createReducedChangeSet($arrReturn, $arrArrayChanges, $strAction, $bitForceEntry, $bitDeleteAction);
                continue;
            }


            if ($strOldvalue instanceof Date) {
                $strOldvalue = $strOldvalue->getLongTimestamp();
            }

            if ($strNewvalue instanceof Date) {
                $strNewvalue = $strNewvalue->getLongTimestamp();
            }

            if ($bitDeleteAction || ($bitDeleteAction === null && $strAction == self::$STR_ACTION_DELETE)) {
                $strNewvalue = "";
            }

            if (is_numeric($strOldvalue) || is_numeric($strNewvalue)) {
                $strOldvalue .= "";
                $strNewvalue .= "";
            }

            if (!$bitForceEntry && ($strOldvalue === $strNewvalue)) {
                continue;
            }

            //update the values
            $arrChangeSet["oldvalue"] = $strOldvalue;
            $arrChangeSet["newvalue"] = $strNewvalue;

            if (StringUtil::length($strOldvalue) > 3990 || StringUtil::length($strNewvalue) > 3990) {
                Logger::getInstance()->warning("Truncating changelog entries larger 3990 char, oldval: {$strOldvalue} newval: {$strNewvalue}");
                $arrChangeSet["oldvalue"] = StringUtil::truncate($strOldvalue, 3990, '');
                $arrChangeSet["newvalue"] = StringUtil::truncate($strNewvalue, 3990, '');
            }

            //add entry right here
            $arrReturn[] = $arrChangeSet;
        }
    }


    /**
     * Creates the list of logentries, either without a systemid-based and change-action-based filters
     * or limited to the given systemid.
     *
     * @param string $strSystemidFilter
     * @param null|int $intStart
     * @param null|int $intEnd
     * @param array $arrExcludeActionsFilter
     *
     * @return ChangelogContainer[]
     */
    public static function getLogEntries($strSystemidFilter, $intStart = null, $intEnd = null, array $arrExcludeActionsFilter = [])
    {

        $arrParams = array();

        if (validateSystemid($strSystemidFilter)) {
            $strQuery = "SELECT change_date, change_systemid, change_user, change_class, change_action, change_property, change_oldvalue, change_newvalue
                           FROM ".self::getTableForClass(Objectfactory::getInstance()->getClassNameForId($strSystemidFilter))."
                           WHERE change_systemid = ? ";

            $arrParams[] = $strSystemidFilter;

            if (!empty($arrExcludeActionsFilter)) {
                    $objRestriction = new OrmInCondition("change_action", $arrExcludeActionsFilter, OrmInCondition::STR_CONDITION_NOTIN);
                    $strQuery .= " AND " . $objRestriction->getStrWhere();
                    $arrParams = array_merge($arrParams, $objRestriction->getArrParams());
            }

        } else {
            return array();
        }
        $strQuery .= "ORDER BY change_date DESC";

        $arrRows = Carrier::getInstance()->getObjDB()->getPArray($strQuery, $arrParams, $intStart, $intEnd);

        $arrReturn = array();
        foreach ($arrRows as $arrRow) {
            $arrReturn[] = new ChangelogContainer(
                $arrRow["change_date"],
                $arrRow["change_systemid"],
                $arrRow["change_user"],
                $arrRow["change_class"],
                $arrRow["change_action"],
                $arrRow["change_property"],
                $arrRow["change_oldvalue"],
                $arrRow["change_newvalue"]
            );
        }

        return $arrReturn;
    }

    /**
     * Counts the number of logentries available
     *
     * @param string $strSystemidFilter
     * @param array $arrExcludeActionsFilter
     *
     * @return int
     */
    public static function getLogEntriesCount($strSystemidFilter, array $arrExcludeActionsFilter = [])
    {

        $arrParams = array();

        if (validateSystemid($strSystemidFilter)) {
            $strQuery = "SELECT COUNT(*) AS cnt
                           FROM ".self::getTableForClass(Objectfactory::getInstance()->getClassNameForId($strSystemidFilter))."
                          WHERE change_systemid = ? ";

            $arrParams[] = $strSystemidFilter;

            if (!empty($arrExcludeActionsFilter)) {
                    $objRestriction = new OrmInCondition("change_action", $arrExcludeActionsFilter, OrmInCondition::STR_CONDITION_NOTIN);
                    $strQuery .= " AND " . $objRestriction->getStrWhere();
                    $arrParams = array_merge($arrParams, $objRestriction->getArrParams());
            }


        } else {
            return 0;
        }

        $arrRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, $arrParams);
        return $arrRow["cnt"];
    }


    /**
     * Creates the list of logentries, based on a flexible but specific filter-list
     *
     * @param string $strSystemidFilter
     * @param string $strActionFilter
     * @param string $strPropertyFilter
     * @param string $strOldvalueFilter
     * @param string $strNewvalueFilter
     * @param Date $objStartDate
     * @param Date $objEndDate
     *
     * @return ChangelogContainer[]
     */
    public static function getSpecificEntries($strSystemidFilter = null, $strActionFilter = null, $strPropertyFilter = null, $strOldvalueFilter = null, $strNewvalueFilter = null, Date $objStartDate = null, Date $objEndDate = null)
    {

        $arrWhere = array();
        $arrParams = array();
        if ($strSystemidFilter !== null) {
            $arrWhere[] = " change_systemid = ? ";
            $arrParams[] = $strSystemidFilter;
        }
        if ($strActionFilter !== null) {
            $arrWhere[] = " change_action = ? ";
            $arrParams[] = $strActionFilter;
        }
        if ($strPropertyFilter !== null) {
            $arrWhere[] = " change_property = ? ";
            $arrParams[] = $strPropertyFilter;
        }
        if ($strOldvalueFilter !== null) {
            $arrWhere[] = " change_oldvalue LIKE ? ";
            $arrParams[] = $strOldvalueFilter;
        }
        if ($strNewvalueFilter !== null) {
            $arrWhere[] = " change_newvalue LIKE ? ";
            $arrParams[] = $strNewvalueFilter;
        }

        if ($objStartDate !== null) {
            $arrWhere[] = " change_date >= ? ";
            $arrParams[] = $objStartDate->getLongTimestamp();
        }

        if ($objEndDate !== null) {
            $arrWhere[] = " change_date <= ? ";
            $arrParams[] = $objEndDate->getLongTimestamp();
        }

        $strTable = "changelog";
        if ($strSystemidFilter != null) {
            $strTable = self::getTableForClass(Objectfactory::getInstance()->getClassNameForId($strSystemidFilter));
        }


        $strQuery = "SELECT *
                       FROM ".$strTable."
                      ".(count($arrWhere) > 0 ? " WHERE ".implode("AND", $arrWhere) : "")."
                   ORDER BY change_date DESC";

        $arrRows = Carrier::getInstance()->getObjDB()->getPArray($strQuery, $arrParams);

        $arrReturn = array();
        foreach ($arrRows as $arrRow) {
            $arrReturn[] = new ChangelogContainer(
                $arrRow["change_date"],
                $arrRow["change_systemid"],
                $arrRow["change_user"],
                $arrRow["change_class"],
                $arrRow["change_action"],
                $arrRow["change_property"],
                $arrRow["change_oldvalue"],
                $arrRow["change_newvalue"]
            );
        }

        return $arrReturn;
    }

    /**
     * Shifts the entries for a given system-id to a new date.
     * Please be aware of the consequences when shifting change-records!
     *
     * @param string $strSystemid
     * @param Date $objNewDate
     *
     * @static
     * @return bool
     */
    public static function shiftLogEntries($strSystemid, $objNewDate)
    {
        $strQuery = "UPDATE ".self::getTableForClass(Objectfactory::getInstance()->getClassNameForId($strSystemid))."
                        SET change_date = ?
                      WHERE change_systemid = ? ";
        return Carrier::getInstance()->getObjDB()->_pQuery($strQuery, array($objNewDate->getLongTimestamp(), $strSystemid));

    }


    /**
     * This method tries to change the value of a property for a given interval.
     * Therefore the records at the start / end date are loaded and adjusted.
     * All changes within the interval will be removed.
     * Example:
     * Time: 0  1   2   3   4   5   6
     * Old:  x      y       y   z   u
     * New:  x  w           w   z   u
     * --> w was injected from 1 to 4, including.
     *
     * @param string $strSystemid
     * @param string $strAction
     * @param string $strProperty
     * @param null|string $strPrevid
     * @param string $strClass
     * @param null|string $strUser
     * @param string $strNewValue
     * @param Date $objStartDate
     * @param Date $objEndDate
     *
     * @return void
     */
    public static function changeValueForInterval($strSystemid, $strAction, $strProperty, $strPrevid, $strClass, $strUser, $strNewValue, Date $objStartDate, Date $objEndDate)
    {

        Logger::getInstance()->warning("changed time-based history-entry: ".$strSystemid."/".$strProperty." to ".$strNewValue." from ".$objStartDate." until ".$objEndDate);

        $strQuery = "SELECT *
                       FROM ".self::getTableForClass($strClass)."
                      WHERE change_systemid = ?
                        AND change_property = ?
                        AND change_date <= ?
                   ORDER BY change_date DESC";

        $arrStartRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strSystemid, $strProperty, $objStartDate->getLongTimestamp()));

        $strQuery = "SELECT *
                       FROM ".self::getTableForClass($strClass)."
                      WHERE change_systemid = ?
                        AND change_property = ?
                        AND change_date >= ?
                   ORDER BY change_date ASC";

        $arrEndRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array($strSystemid, $strProperty, $objEndDate->getLongTimestamp()));

        //drop all changes between the start / end date
        $strQuery = "DELETE FROM ".self::getTableForClass($strClass)."
                           WHERE change_systemid = ?
                             AND change_property = ?
                             AND change_date >= ?
                             AND change_date <= ?";
        Carrier::getInstance()->getObjDB()->_pQuery($strQuery, array($strSystemid, $strProperty, $objStartDate->getLongTimestamp(), $objEndDate->getLongTimestamp()));


        //adjust the start-row, see if the dates are matching (update vs insert)
        $strQuery = "INSERT INTO ".self::getTableForClass($strClass)."
                 (change_id,
                  change_date,
                  change_systemid,
                  change_system_previd,
                  change_user,
                  change_class,
                  change_action,
                  change_property,
                  change_oldvalue,
                  change_newvalue) VALUES
                 (?,?,?,?,?,?,?,?,?,?)";

        Carrier::getInstance()->getObjDB()->_pQuery(
            $strQuery,
            array(
                generateSystemid(),
                $objStartDate->getLongTimestamp(),
                $strSystemid,
                $strPrevid,
                $strUser,
                $strClass,
                $strAction,
                $strProperty,
                (isset($arrStartRow["change_newvalue"]) ? $arrStartRow["change_newvalue"] : ""),
                $strNewValue
            )
        );

        //adjust the end-row, update vs insert
        $strQuery = "INSERT INTO ".self::getTableForClass($strClass)."
                 (change_id,
                  change_date,
                  change_systemid,
                  change_system_previd,
                  change_user,
                  change_class,
                  change_action,
                  change_property,
                  change_oldvalue,
                  change_newvalue) VALUES
                 (?,?,?,?,?,?,?,?,?,?)";

        Carrier::getInstance()->getObjDB()->_pQuery(
            $strQuery,
            array(
                generateSystemid(),
                $objEndDate->getLongTimestamp(),
                $strSystemid,
                $strPrevid,
                $strUser,
                $strClass,
                $strAction,
                $strProperty,
                $strNewValue,
                (isset($arrEndRow["change_oldvalue"]) ? $arrEndRow["change_oldvalue"] : "")
            )
        );


        Carrier::getInstance()->getObjDB()->flushQueryCache();
    }

    /**
     * Fetches a single value from the change-sets, if not unique the latest value for the specified date is returned.
     *
     * @param string $strSystemid
     * @param string $strProperty
     * @param Date $objDate
     *
     * @static
     * @return string
     */
    public static function getValueForDate($strSystemid, $strProperty, Date $objDate)
    {
        $strQuery = "SELECT change_newvalue
                       FROM ".self::getTableForClass(Objectfactory::getInstance()->getClassNameForId($strSystemid))."
                      WHERE change_systemid = ?
                        AND change_property = ?
                        AND change_date <= ?
                   ORDER BY change_date DESC ";

        $arrRow = Carrier::getInstance()->getObjDB()->getPArray($strQuery, array($strSystemid, $strProperty, $objDate->getLongTimestamp()), 0, 1);
        if (isset($arrRow[0]["change_newvalue"])) {
            return $arrRow[0]["change_newvalue"];
        } else {
            return false;
        }
    }

    /**
     * Fetches all change-sets within the specified period for the given property.
     *
     * @param string $strSystemid
     * @param string $strProperty
     * @param Date $objDateFrom
     * @param Date $objDateTo
     *
     * @static
     * @return array
     */
    public static function getValuesForDateRange($strSystemid, $strProperty, Date $objDateFrom, Date $objDateTo)
    {
        $strQuery = "SELECT change_oldvalue, change_newvalue
                       FROM ".self::getTableForClass(Objectfactory::getInstance()->getClassNameForId($strSystemid))."
                      WHERE change_systemid = ?
                        AND change_property = ?
                        AND change_date >= ?
                        AND change_date <= ?
                   ORDER BY change_date DESC ";

        $arrRow = Carrier::getInstance()->getObjDB()->getPArray($strQuery, array($strSystemid, $strProperty, $objDateFrom->getLongTimestamp(), $objDateTo->getLongTimestamp()), 0, 1);
        return $arrRow;
    }

    /**
     * Returns all date points where a change occured
     *
     * @param $strSystemid
     * @param Date $objDateFrom
     * @param Date $objDateTo
     *
     * @return array
     */
    public static function getDatesForSystemid($strSystemid, Date $objDateFrom, Date $objDateTo)
    {
        $strQuery = "SELECT change_date,
                            COUNT(change_id) AS cnt
                       FROM ".self::getTableForClass(Objectfactory::getInstance()->getClassNameForId($strSystemid))."
                      WHERE change_systemid = ?
                        AND change_date >= ?
                        AND change_date <= ?
                   GROUP BY change_date
                   ORDER BY change_date ASC ";
        return Carrier::getInstance()->getObjDB()->getPArray($strQuery, array($strSystemid, $objDateFrom->getLongTimestamp(), $objDateTo->getLongTimestamp()));
    }

    /**
     * Returns the count of an specific class and property for a given date range in the changelog. Counts optional
     * only the entries which are available in $arrNewValues
     *
     * @param $strClass
     * @param $strProperty
     * @param Date $objDateFrom
     * @param Date $objDateTo
     * @param $arrNewValues
     *
     * @return int
     */
    public static function getCountForDateRange($strClass, $strProperty, Date $objDateFrom, Date $objDateTo, array $arrNewValues = null, array $arrAllowedSystemIds = null)
    {
        $strQuery = "SELECT COUNT(DISTINCT change_systemid) AS num
                       FROM ".self::getTableForClass($strClass)."
                      WHERE change_class = ?
                        AND change_property = ?
                        AND change_date >= ?
                        AND change_date <= ?";

        $arrParameters = array($strClass, $strProperty);
        $arrParameters[] = $objDateFrom->getLongTimestamp();
        $arrParameters[] = $objDateTo->getLongTimestamp();

        if (!empty($arrNewValues)) {
            if (count($arrNewValues) > 1) {
                $objRestriction = new OrmInCondition("change_newvalue", $arrNewValues);
                $strQuery .= " AND " . $objRestriction->getStrWhere();
                $arrParameters = array_merge($arrParameters, $objRestriction->getArrParams());
            } else {
                $strQuery .= " AND change_newvalue = ?";
                $arrParameters[] = current($arrNewValues);
            }
        }

        if ($arrAllowedSystemIds !== null) {
            $objRestriction = new OrmInCondition("change_systemid", $arrAllowedSystemIds);
            if ($objRestriction->getStrWhere() !== "") {
                $strQuery .= " AND " . $objRestriction->getStrWhere();
                $arrParameters = array_merge($arrParameters, $objRestriction->getArrParams());
            }
        }

        $arrRow = Carrier::getInstance()->getObjDB()->getPArray($strQuery, $arrParameters, 0, 1);
        return isset($arrRow[0]["num"]) ? $arrRow[0]["num"] : 0;
    }

    /**
     * Returns the latest new_value in the date range per systemid
     *
     * @param $strClass
     * @param $strProperty
     * @param Date $objDateFrom
     * @param Date $objDateTo
     * @param array $arrAllowedSystemIds
     *
     * @return array
     */
    public static function getNewValuesForDateRange($strClass, $strProperty, Date $objDateFrom = null, Date $objDateTo = null, array $arrAllowedSystemIds = array())
    {
        $arrParams = array($strClass, $strProperty);
        $strQueryCondition = "";

        //system id filter
        $objRestriction = new OrmInCondition("log.change_systemid", $arrAllowedSystemIds);
        if ($objRestriction->getStrWhere() !== "") {
            $strQueryCondition .= " AND " . $objRestriction->getStrWhere();
            $arrParams = array_merge($arrParams, $objRestriction->getArrParams());
        }

        //filter by create date from
        if ($objDateFrom != null) {
            $objRestriction = new OrmCondition("( log.change_date >= ?)", array($objDateFrom->getLongTimestamp()));
            $strQueryCondition .= " AND " . $objRestriction->getStrWhere()." ";
            $arrParams[] = $objDateFrom->getLongTimestamp();
        }
        //filter by create end to
        if ($objDateTo != null) {
            $objRestriction = new OrmCondition("( log.change_date <= ?)", array($objDateTo->getLongTimestamp()));
            $strQueryCondition .= " AND " . $objRestriction->getStrWhere()." ";
            $arrParams[] = $objDateTo->getLongTimestamp();
        }

        $strQuery = "  SELECT change_systemid,
                              change_newvalue
                         FROM ".self::getTableForClass($strClass)." log
                        WHERE log.change_class = ?
                          AND log.change_property = ?
                          {$strQueryCondition}
                     ORDER BY log.change_systemid ASC, log.change_date DESC";

        $arrResult = Carrier::getInstance()->getObjDB()->getPArray($strQuery, $arrParams);
        $arrData = array();

        $strLastId = "";
        foreach ($arrResult as $arrRow) {
            if ($strLastId != $arrRow["change_systemid"]) {
                $arrData[] = array(
                    "change_systemid" => $arrRow["change_systemid"],
                    "change_newvalue" => $arrRow["change_newvalue"],
                );
            }
            $strLastId = $arrRow["change_systemid"];
        }

        return $arrData;
    }

    /**
     * Returns a list of objects implementing the changelog-provider-interface
     *
     * @return ChangelogProviderInterface[]
     */
    public static function getAdditionalProviders()
    {
        if (self::$arrCachedProviders != null) {
            return self::$arrCachedProviders;
        }

        /** @var CacheManager $objCache */
        $strKey = __METHOD__;
        $objCache = Carrier::getInstance()->getContainer()->offsetGet(\Kajona\System\System\ServiceProvider::STR_CACHE_MANAGER);
        $arrReturn = $objCache->getValue($strKey);

        if (!empty($arrReturn)) {
            return self::$arrCachedProviders = $arrReturn;
        }

        $arrReturn = Resourceloader::getInstance()->getFolderContent(
            "/system",
            array(".php"),
            false,
            null,
            function (&$strOneFile, $strPath) {
                $strOneFile = Classloader::getInstance()->getInstanceFromFilename($strPath, "", ChangelogProviderInterface::class);
            }
        );

        $arrReturn = array_filter($arrReturn, function ($objEl) {
            return $objEl != null;
        });

        $objCache->addValue($strKey, $arrReturn);

        self::$arrCachedProviders = $arrReturn;
        return $arrReturn;
    }

    /**
     * Retuns a list of additional objects mapped to tables
     *
     * @return array (class => table)
     */
    public static function getAdditionalTables()
    {
        $arrTables = array();
        foreach (self::getAdditionalProviders() as $objOneProvider) {
            foreach ($objOneProvider->getHandledClasses() as $strOneClass) {
                $arrTables[$strOneClass] = $objOneProvider->getTargetTable();
            }
        }
        return $arrTables;
    }

    /**
     * Returns the target-table for a single class
     * or the default table if not found.
     *
     * @param string $strClass
     *
     * @return string
     */
    public static function getTableForClass($strClass)
    {
        if (!self::$arrTables) {
            self::$arrTables = self::getAdditionalTables();
        }

        if ($strClass != null && $strClass != "" && isset(self::$arrTables[$strClass])) {
            return self::$arrTables[$strClass];
        } else {
            return "agp_changelog";
        }
    }
}

