<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Search\Api;


use Kajona\Api\System\ApiControllerInterface;
use Kajona\Search\System\SearchCommons;
use Kajona\Search\System\SearchResult;
use Kajona\Search\System\SearchSearch;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\Link;
use Kajona\System\System\Session;
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
     * @QueryParam(name="search_query", type="string", description="the search query" , location="body")
     * @QueryParam(name="filtermodules", type="array", description="array containing the ids of the required modules" , location="body")
     * @QueryParam(name="search_changestartdate", type="string", description="start date filter" , location="body")
     * @QueryParam(name="search_changeenddate", type="string", description="end date filter" , location="body")
     * @QueryParam(name="search_formfilteruser_id", type="string", description="id of the required user" , location="body")
     * @param HttpContext $context
     * @return HttpResponse
     * @throws Exception
     * @throws \Exception
     * @api
     * @method GET
     * @path /v1/search
     * @authorization usertoken
     */
    public function getFilteredSearch(HttpContext $context): HttpResponse
    {
        $search_query = $context->getParameter('search_query');
        $filtermodules = $context->getParameter('filtermodules');
        $search_changestartdate = $context->getParameter('search_changestartdate');
        $search_changeenddate = $context->getParameter('search_changeenddate');
        $search_formfilteruser_id = $context->getParameter('search_formfilteruser_id');
        $objSearch = new SearchSearch();

        if ($search_query != "") {
            $objSearch->setStrQuery($search_query);
        }
        if ($filtermodules != "") {
            $objSearch->setFilterModules($filtermodules);
        }

        if (!empty($search_changestartdate)) {
            $startDate = new \DateTime($search_changestartdate);
            $objSearch->setObjChangeStartdate(Date::fromDateTime($startDate));
        }

        if (!empty($search_changeenddate)) {
            $endDate = new \DateTime($search_changeenddate);
            $objSearch->setObjChangeEnddate(Date::fromDateTime($endDate));
        }

        if ($search_formfilteruser_id != "") {
            $objSearch->setStrFormFilterUser($search_formfilteruser_id);
        }

        $objSearchCommons = new SearchCommons();
        $arrResult = $objSearchCommons->doIndexedSearch($objSearch, 0, self::INT_MAX_NR_OF_RESULTS_FULLSEARCH);

        return new JsonResponse($this->createSearchJson($arrResult, $context));
    }

    /**
     * Returns the possible modules and their ids as json for filter
     *
     * @return HttpResponse
     * @throws Exception
     * @api
     * @method GET
     * @path /v1/search/modules
     * @authorization usertoken
     */
    public function getModulesForFilter(): HttpResponse
    {
        $objSearch = new SearchSearch();
        $arrModules = $objSearch->getPossibleModulesForFilter();
        $arrReturn = [];
        foreach ($arrModules as $key => $value) {
            $arrReturn[] = array("module" => $value, "id" => $key);
        }
        return new JsonResponse($arrReturn);
    }

    /**
     * Parses SearchResult objects into json
     * @param array $arrResults
     * @param HttpContext $context
     * @return array
     * @throws Exception
     */
    private function createSearchJson(array $arrResults, HttpContext $context)
    {

        $arrItems = array();
        /** @var  SearchResult $objOneResult */
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
            $arrItem["lastModifiedBy"] = $objOneResult->getObjObject()->getLastEditUser($context->getHeader(Session::getInstance()->getUserID()));
            $arrItem["lastModifiedTime"] = dateToString(new Date($objOneResult->getObjObject()->getIntLmTime()));
            $arrItem["link"] = html_entity_decode($strLink);

            $arrItems[] = $arrItem;
        }

        $objResult = $arrItems;
        return $objResult;
    }


}
