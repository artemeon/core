<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                            *
 ********************************************************************************************************/

namespace Kajona\Search\Admin;

use Kajona\Search\System\SearchCommons;
use Kajona\Search\System\SearchResult;
use Kajona\Search\System\SearchSearch;
use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\HttpResponsetypes;
use Kajona\System\System\Link;
use Kajona\System\System\ResponseObject;

/**
 * Portal-class of the search.
 * Serves xml-requests, e.g. generates search results
 *
 * @package module_search
 * @author sidler@mulchprod.de
 *
 * @module search
 * @moduleId _search_module_id_
 */
class SearchAdmin extends AdminEvensimpler
{

    /**
     * The maximum number of records to return on xml/json requests
     */
    const INT_MAX_NR_OF_RESULTS_AUTOCOMPLETE = 30;

    const INT_MAX_NR_OF_RESULTS_FULLSEARCH = 100;


    /**
     * Returns the possible modules and their ids as json for filter
     * @responseType json
     * @permissions view
     * @return array
     * @throws \Kajona\System\System\Exception
     */
    protected function actionGetModulesForFilter()
    {
        $objSearch = new SearchSearch($this->getParam("systemid"));
        $arrModules = $objSearch->getPossibleModulesForFilter();
        $arrReturn = [];
        foreach ($arrModules as $key => $value) {
            $arrReturn[] = array("module" => $value, "id" => $key);
        }
        return $arrReturn;
    }

    /**
     * Returns search results as json
     * @permissions view
     * @return array
     * @responseType json
     * @throws \Kajona\System\System\Exception
     */
    public function actionGetFilteredSearch()
    {

        Carrier::getInstance()->getObjSession()->sessionClose();

        $objSearch = new SearchSearch();

        if ($this->getParam("search_query") != "") {
            $objSearch->setStrQuery(urldecode($this->getParam("search_query")));
        }
        if ($this->getParam("filtermodules") != "") {
            $objSearch->setStrInternalFilterModules(urldecode($this->getParam("filtermodules")));
        }

        if ($this->getParam("search_changestartdate") != "") {
            $objDate = new \Kajona\System\System\Date();
            $objDate->generateDateFromParams("search_changestartdate", Carrier::getAllParams());
            $objSearch->setObjChangeStartdate($objDate);
        }

        if ($this->getParam("search_changeenddate") != "") {
            $objDate = new \Kajona\System\System\Date();
            $objDate->generateDateFromParams("search_changeenddate", Carrier::getAllParams());
            $objSearch->setObjChangeEnddate($objDate);
        }

        if ($this->getParam("search_formfilteruser_id") != "") {
            $objSearch->setStrFormFilterUser(urldecode($this->getParam("search_formfilteruser_id")));
        }

        $objSearchCommons = new SearchCommons();
        $arrResult = $objSearchCommons->doIndexedSearch($objSearch, 0, self::INT_MAX_NR_OF_RESULTS_FULLSEARCH);
        return $this->createSearchJson($this->getParam("search_query"), $arrResult);
    }


    /**
     * @param string $strSearchterm
     * @param SearchResult[] $arrResults
     * @return array
     * @throws \Kajona\System\System\Exception
     */
    private function createSearchJson($strSearchterm, $arrResults)
    {

        $arrItems = array();
        foreach ($arrResults as $objOneResult) {
            $arrItem = array();
            //create a correct link
            if ($objOneResult->getObjObject() == null || !$objOneResult->getObjObject()->rightView()) {
                continue;
            }

            $strIcon = "";
            if ($objOneResult->getObjObject() instanceof AdminListableInterface) {
                $strIcon = $objOneResult->getObjObject()->getStrIcon();
                if (is_array($strIcon)) {
                    $strIcon = $strIcon[0];
                }
            }

            $strLink = $objOneResult->getStrPagelink();
            if ($strLink == "") {
                $strLink = Link::getLinkAdminHref($objOneResult->getObjObject()->getArrModule("modul"), "edit", "&systemid=" . $objOneResult->getStrSystemid(), true, true);
            }

            $arrItem["module"] = Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneResult->getObjObject()->getArrModule("modul"));
            $arrItem["systemid"] = $objOneResult->getStrSystemid();
            $arrItem["icon"] = AdminskinHelper::getAdminImage($strIcon, "", true);
            $arrItem["score"] = $objOneResult->getStrSystemid();
            $arrItem["description"] = $objOneResult->getObjObject()->getStrDisplayName();
            if ($objOneResult->getObjObject() instanceof AdminListableInterface) {

                $arrItem["additionalInfos"] = $objOneResult->getObjObject()->getStrAdditionalInfo();
            }
            $arrItem["lastModifiedBy"] = $objOneResult->getObjObject()->getLastEditUser($this->getSystemid());
            $arrItem["lastModifiedTime"] = dateToString(new Date($objOneResult->getObjObject()->getIntLmTime()));
            $arrItem["link"] = html_entity_decode($strLink);

            $arrItems[] = $arrItem;
        }

        $objResult = $arrItems;
        ResponseObject::getInstance()->setStrResponseType(HttpResponsetypes::STR_TYPE_JSON);
        return json_encode($objResult);
    }

}
