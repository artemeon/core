<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace AGP\Search\Api;


use Kajona\Api\System\ApiControllerInterface;
use Kajona\Search\System\SearchCommons;
use Kajona\Search\System\SearchSearch;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\Link;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;
use Kajona\Api\System\Http\JsonResponse;

/**
 * SearchApiController
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.1
 */
class SearchApiController implements ApiControllerInterface
{
    const INT_MAX_NR_OF_RESULTS_FULLSEARCH = 100;

    /**
     * returns filtered search results
     *
     * @param array $requestBody
     * @param HttpContext $context
     * @return HttpResponse
     * @throws Exception
     * @api
     * @method POST
     * @path /v1/search
     * @authorization usertoken
     */
    public function getFilteredSearch(array $requestBody, HttpContext $context): HttpResponse
    {
        $search_query = $requestBody['search_query'];
        $filtermodules = $requestBody['filtermodules'];
        $search_changestartdate = $requestBody['search_changestartdate'];
        $search_changeenddate = $requestBody['search_changeenddate'] ;
        $search_formfilteruser_id = $requestBody['search_formfilteruser_id'];
        $objSearch = new SearchSearch();

        if ($search_query != "") {
            $objSearch->setStrQuery($search_query);
        }
        if ($filtermodules != "") {
            $objSearch->setStrInternalFilterModules(urldecode($filtermodules));
        }
        //todo dont user Date::generateDatefromParams
//        if ($search_changestartdate != "") {
//            $objDate = new \Kajona\System\System\Date();
//            $objDate->generateDateFromParams("search_changestartdate", Carrier::getAllParams());
//            $objSearch->setObjChangeStartdate($objDate);
//        }
//
//        if ($this->getParam("search_changeenddate") != "") {
//            $objDate = new \Kajona\System\System\Date();
//            $objDate->generateDateFromParams("search_changeenddate", Carrier::getAllParams());
//            $objSearch->setObjChangeEnddate($objDate);
//        }
//
        if ($search_formfilteruser_id != "") {
            $objSearch->setStrFormFilterUser($search_formfilteruser_id);
        }

        $objSearchCommons = new SearchCommons();
        $arrResult = $objSearchCommons->doIndexedSearch($objSearch, 0, self::INT_MAX_NR_OF_RESULTS_FULLSEARCH);

        return new JsonResponse($this->createSearchJson($arrResult));
    }

    private function createSearchJson($arrResults)
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
            //todo dont use getSystemid()
//            $arrItem["lastModifiedBy"] = $objOneResult->getObjObject()->getLastEditUser($this->getSystemid());
            $arrItem["lastModifiedTime"] = dateToString(new Date($objOneResult->getObjObject()->getIntLmTime()));
            $arrItem["link"] = html_entity_decode($strLink);

            $arrItems[] = $arrItem;
        }

        $objResult = $arrItems;
        return $objResult;
    }


}
