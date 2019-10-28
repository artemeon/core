<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Search\System;

use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\Date;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\SystemModule;

/**
 * Model-Class for search queries.
 *
 * @package module_search
 * @author tim.kiefer@kojikui.de
 * @since 3.4
 * @targetTable agp_search_search.search_search_id
 *
 * @module search
 * @moduleId _search_module_id_
 */
class SearchSearch extends Model implements ModelInterface, AdminListableInterface
{
    /**
     * @var string
     * @tableColumn agp_search_search.search_search_query
     * @tableColumnDatatype char254
     * @listOrder
     * @fieldMandatory
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     */
    private $strQuery;

    /**
     * @var array
     * @tableColumn agp_search_search.search_search_filter_modules
     * @tableColumnDatatype char254
     */
    private $arrFilterModules = array();


    /**
     * For form-generation only
     *
     * @var string
     * @fieldType Kajona\System\Admin\Formentries\FormentryUser
     * @fieldLabel search_users
     */
    private $strFormFilterUser = null;

    /**
     * @var Date
     * @fieldType Kajona\System\Admin\Formentries\FormentryDate
     * @tableColumn agp_search_search.search_change_start
     * @tableColumnDatatype long
     */
    private $objChangeStartdate = null;

    /**
     * @var Date
     * @fieldType Kajona\System\Admin\Formentries\FormentryDate
     * @tableColumn agp_search_search.search_change_end
     * @tableColumnDatatype long
     */
    private $objChangeEnddate = null;

    /**
     * @var array
     */
    private $arrObjectTypes;

    public function getStrDisplayName()
    {
        return $this->getStrQuery();
    }

    /**
     * Sets the filter modules
     *
     * @param array $filterModules
     */
    public function setFilterModules(array $filterModules)
    {
        $this->arrFilterModules = $filterModules;
    }

    /**
     * Returns the filter modules to edit the filter modules
     *
     * @return array
     */
    public function getFilterModules(): array
    {
        return $this->arrFilterModules;
    }

    /**
     * Returns the user id of the record owner
     *
     * @return string
     */
    public function getFilterUser()
    {
        if (!empty($this->strFormFilterUser)) {
            return $this->strFormFilterUser;
        }
        return null;
    }

    /**
     * Returns all modules available in the module-table.
     * Limited to those with a proper title, so
     * a subset of getModuleIds() / all module-entries
     *
     * @return array
     * @throws \Kajona\System\System\Exception
     */
    public function getPossibleModulesForFilter()
    {

        $arrFilterModules = array();

        $arrModules = SystemModule::getAllModules();
        $arrNrs = $this->getModuleNumbers();
        foreach ($arrModules as $objOneModule) {
            if (in_array($objOneModule->getIntNr(), $arrNrs) && $objOneModule->rightView()) {
                $strName = $this->getLang("modul_titel", $objOneModule->getStrName());
                if ($strName != "!modul_titel!") {
                    $arrFilterModules[$objOneModule->getIntNr()] = $strName;
                }
            }
        }

        return $arrFilterModules;
    }

    /**
     * Fetches the list of module-ids currently available in the system-table
     *
     * @return array
     */
    private function getModuleNumbers()
    {
        $strQuery = "SELECT DISTINCT system_module_nr FROM agp_system, agp_search_ix_document WHERE system_id = search_ix_system_id AND system_prev_id != '0' AND system_id != '0' AND system_deleted = 0";

        $arrRows = $this->objDB->getPArray($strQuery, array());

        $arrReturn = array();
        foreach ($arrRows as $arrOneRow) {
            $arrReturn[] = $arrOneRow["system_module_nr"];
        }

        return $arrReturn;
    }


    /**
     * Returns the icon the be used in lists.
     * Please be aware, that only the filename should be returned, the wrapping by getImageAdmin() is
     * done afterwards.
     *
     * @return string the name of the icon, not yet wrapped by getImageAdmin()
     */
    public function getStrIcon()
    {
        return "icon_lens";
    }


    /**
     * If not empty, the returned string is rendered below the common title.
     *
     * @return string
     */
    public function getStrLongDescription()
    {
        return "";
    }


    /**
     * In nearly all cases, the additional info is rendered left to the action-icons.
     *
     * @return string
     */
    public function getStrAdditionalInfo()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getStrQuery()
    {
        return $this->strQuery;
    }

    public function setStrQuery($strQuery)
    {
        $this->strQuery = trim($strQuery);
    }

    /**
     * @return array
     */
    public function getArrObjectTypes()
    {
        return $this->arrObjectTypes;
    }

    /**
     * @param array $arrObjectTypes
     */
    public function setArrObjectTypes($arrObjectTypes)
    {
        $this->arrObjectTypes = $arrObjectTypes;
    }

    /**
     * @param string $arrFormFilterModules
     */
    public function setStrFormFilterUser($strFormFilterUser)
    {
        $this->strFormFilterUser = $strFormFilterUser;
    }

    /**
     * @return string
     */
    public function getStrFormFilterUser()
    {
        return $this->strFormFilterUser;
    }


    /**
     * @param Date $objChangeEnddate
     */
    public function setObjChangeEnddate($objChangeEnddate)
    {
        $this->objChangeEnddate = $objChangeEnddate;
    }

    /**
     * @return Date
     */
    public function getObjChangeEnddate()
    {
        return $this->objChangeEnddate;
    }

    /**
     * @param Date $objChangeStartdate
     */
    public function setObjChangeStartdate($objChangeStartdate)
    {
        $this->objChangeStartdate = $objChangeStartdate;
    }

    /**
     * @return Date
     */
    public function getObjChangeStartdate()
    {
        return $this->objChangeStartdate;
    }


}
