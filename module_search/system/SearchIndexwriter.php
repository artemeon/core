<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                  *
********************************************************************************************************/

namespace Kajona\Search\System;

use Kajona\System\System\Carrier;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Reflection;
use Kajona\System\System\SystemModule;


/**
 * General object to build / rebuild / update the search-index.
 * Registers for record-updated events in order to update the index of an object.
 *
 * @package module_search
 * @author tim.kiefer@kojikui.de
 */
class SearchIndexwriter
{

    const STR_ANNOTATION_ADDSEARCHINDEX = "@addSearchIndex";

    private $objConfig = null;
    private $objDB = null;

    private static $isIndexAvailable = null;

    /**
     * Internal flag to avoid explicit delete statements on a full index rebuild. since
     * the index is flushed before, the delete statements are useless and only time-consuming.
     * @var bool
     */
    private $bitSkipDeletes = false;

    /**
     * Plain constructor
     */
    public function __construct()
    {
        //Generating all the needed objects. For this we use our cool cool carrier-object
        //take care of loading just the necessary objects
        $this->objConfig = Carrier::getInstance()->getObjConfig();
        $this->objDB = Carrier::getInstance()->getObjDB();
    }

    /**
     * Validates if the search module is installed with a supported index
     * @return bool
     */
    private static function isIndexAvailable()
    {
        if (self::$isIndexAvailable === null) {
            self::$isIndexAvailable = SystemModule::getModuleByName("search") != null;
        }

        return self::$isIndexAvailable;
    }

    /**
     * Returns the number of documents currently in the index
     * @return int
     */
    public function getNumberOfDocuments()
    {
        if (!self::isIndexAvailable()) {
            return 0;
        }

        $arrRow = $this->objDB->getPRow("SELECT COUNT(*) AS cnt FROM "._dbprefix_."search_ix_document", []);
        return $arrRow["cnt"];
    }

    /**
     * Returns the number of entries currently in the index
     * @return int
     */
    public function getNumberOfContentEntries()
    {
        if (!self::isIndexAvailable()) {
            return 0;
        }

        $arrRow = $this->objDB->getPRow("SELECT COUNT(*) AS cnt FROM "._dbprefix_."search_ix_content", []);
        return $arrRow["cnt"];
    }

    /**
     * Removes an entry from the index, based on the systemid. Removes the indexed content and the document.
     * @param string $strSystemid
     *
     * @return bool
     */
    public function removeRecordFromIndex($strSystemid)
    {

        if (!self::isIndexAvailable()) {
            return true;
        }

        $arrRow = $this->objDB->getPRow("SELECT * FROM "._dbprefix_."search_ix_document WHERE search_ix_system_id = ?", [$strSystemid]);

        if (isset($arrRow["search_ix_document_id"])) {
            $this->objDB->_pQuery("DELETE FROM "._dbprefix_."search_ix_content WHERE search_ix_content_document_id = ?", [$arrRow["search_ix_document_id"]]);
            $this->objDB->_pQuery("DELETE FROM "._dbprefix_."search_ix_document WHERE search_ix_document_id = ?", [$arrRow["search_ix_document_id"]]);
        }

        return true;
    }

    /**
     * Triggers the indexing of a single object.
     *
     * @param \Kajona\System\System\Model $objInstance
     *
     * @return void
     */
    public function indexObject(\Kajona\System\System\Model $objInstance = null)
    {

        if (!self::isIndexAvailable()) {
            return;
        }

        if ($objInstance == null) {
            return;
        }

        $objSearchDocument = new SearchDocument();
        $objSearchDocument->setDocumentId(generateSystemid());
        $objSearchDocument->setStrSystemId($objInstance->getSystemid());

        $objReflection = new Reflection($objInstance);
        $arrProperties = $objReflection->getPropertiesWithAnnotation(self::STR_ANNOTATION_ADDSEARCHINDEX);
        foreach ($arrProperties as $strPropertyName => $strAnnotationValue) {
            $getter = $objReflection->getGetter($strPropertyName);
            $strContent = $objInstance->$getter();
            $objSearchDocument->addContent($strPropertyName, $strContent);
        }

        //trigger event-listeners
        CoreEventdispatcher::getInstance()->notifyGenericListeners(SearchEventidentifier::EVENT_SEARCH_OBJECTINDEXED, [$objInstance, $objSearchDocument]);

        $this->updateSearchDocumentToDb($objSearchDocument);
    }

    /**
     * Triggers a full rebuild of the index.
     *
     * @return void
     */
    public function indexRebuild()
    {

        if (!self::isIndexAvailable()) {
            return;
        }

        $this->clearIndex();
        $arrObj = $this->getIndexableEntries();

        $this->bitSkipDeletes = true;

        $intI = 0;
        foreach ($arrObj as $objObj) {
            $objInstance = Objectfactory::getInstance()->getObject($objObj["system_id"]);
            if ($objInstance != null) {
                $this->indexObject($objInstance);
            }

            //flush the caches each 4.000 objects in order to keep memory usage low
            if (++$intI > 4000) {
                Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_DBQUERIES | Carrier::INT_CACHE_TYPE_ORMCACHE | Carrier::INT_CACHE_TYPE_OBJECTFACTORY);
                $intI = 0;
            }
        }

        $this->bitSkipDeletes = false;
    }

    /**
     * @return array
     */
    private function getIndexableEntries()
    {
        //Load possible existing document if exists
        $strQuery = "SELECT * FROM "._dbprefix_."system WHERE system_deleted = 0";
        return $this->objDB->getPArray($strQuery, []);
    }

    /**
     * Clears the complete cache
     * @return void
     */
    public function clearIndex()
    {

        if (!self::isIndexAvailable()) {
            return;
        }

        // Delete existing entries
        $strQuery = "DELETE FROM "._dbprefix_."search_ix_document";
        $this->objDB->_pQuery($strQuery, []);

        $strQuery = "DELETE FROM "._dbprefix_."search_ix_content";
        $this->objDB->_pQuery($strQuery, []);
    }

    /**
     * @param SearchDocument $objSearchDoc
     * @return void
     */
    public function updateSearchDocumentToDb(SearchDocument $objSearchDoc)
    {

        if (!self::isIndexAvailable()) {
            return;
        }

        // Delete existing entries
        if (!$this->bitSkipDeletes) {
            $this->removeRecordFromIndex($objSearchDoc->getStrSystemId());
        }

        if (count($objSearchDoc->getContent()) == 0) {
            return;
        }

        //insert search document
        $strQuery = "INSERT INTO "._dbprefix_."search_ix_document
                        (search_ix_document_id, search_ix_system_id, search_ix_content_lang, search_ix_portal_object) VALUES
                        (?, ?, ?, ?)";
        $this->objDB->_pQuery($strQuery, [$objSearchDoc->getDocumentId(), $objSearchDoc->getStrSystemId(), $objSearchDoc->getStrContentLanguage(), $objSearchDoc->getBitPortalObject() ? 1 : 0]);

        $this->updateSearchContentsToDb($objSearchDoc->getContent());
    }

    /**
     * @param SearchContent[] $arrSearchContent
     *
     * @return void
     */
    private function updateSearchContentsToDb(array $arrSearchContent)
    {
        $arrValues = [];

        foreach ($arrSearchContent as $objOneContent) {
            $arrValues[] = [
                $objOneContent->getStrId(),
                $objOneContent->getFieldName(),
                $objOneContent->getContent(),
                $objOneContent->getScore(),
                $objOneContent->getDocumentId(),
            ];
        }

        //insert search document in a single query - much faster than single updates
        $this->objDB->multiInsert(
            "search_ix_content",
            ["search_ix_content_id", "search_ix_content_field_name", "search_ix_content_content", "search_ix_content_score", "search_ix_content_document_id"],
            $arrValues
        );
    }

    /**
     * Resets the internal check whether the search module is available with index support or not.
     * @return void
     */
    public static function resetIndexAvailableCheck()
    {
        self::$isIndexAvailable = null;
    }
}
