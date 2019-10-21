<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Search\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Search\System\SearchCommons;
use Kajona\Search\System\SearchResult;
use Kajona\Search\System\SearchSearch;
use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\Link;
use Kajona\System\System\Session;
use Kajona\System\System\SystemModule;
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
    const MAX_NR_OF_RESULTS_FULLSEARCH = 100;

    /**
     * returns filtered search results
     *
     * @QueryParam(name="search_query", type="string", description="the search query")
     * @QueryParam(name="filtermodules", type="array", description="array containing the ids of the required modules")
     * @QueryParam(name="search_changestartdate", type="string", description="start date filter")
     * @QueryParam(name="search_changeenddate", type="string", description="end date filter")
     * @QueryParam(name="search_formfilteruser_id", type="string", description="id of the required user")
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
        $search = new SearchSearch();

        if ($search_query != '') {
            $search->setStrQuery($search_query);
        }
        if ($filtermodules != '') {
            $search->setFilterModules($filtermodules);
        }

        if (!empty($search_changestartdate)) {
            $startDate = new \DateTime($search_changestartdate);
            $search->setObjChangeStartdate(Date::fromDateTime($startDate));
        }

        if (!empty($search_changeenddate)) {
            $endDate = new \DateTime($search_changeenddate);
            $search->setObjChangeEnddate(Date::fromDateTime($endDate));
        }

        if ($search_formfilteruser_id != '') {
            $search->setStrFormFilterUser($search_formfilteruser_id);
        }

        $objSearchCommons = new SearchCommons();
        $arrResult = $objSearchCommons->doIndexedSearch($search, 0, self::MAX_NR_OF_RESULTS_FULLSEARCH);

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
        $search = new SearchSearch();
        $modules = $search->getPossibleModulesForFilter();
        $return = [];
        foreach ($modules as $key => $value) {
            $return[] = array('module' => $value, 'id' => $key);
        }
        return new JsonResponse($return);
    }

    /**
     * Parses SearchResult objects into json
     * @param array $results
     * @param HttpContext $context
     * @return array
     * @throws Exception
     */
    private function createSearchJson(array $results, HttpContext $context): array
    {

        $items = array();
        /** @var  SearchResult $oneResult */
        foreach ($results as $oneResult) {
            $item = array();
            //create a correct link
            if ($oneResult->getObjObject() === null || !$oneResult->getObjObject()->rightView()) {
                continue;
            }

            $icon = '';
            if ($oneResult->getObjObject() instanceof AdminListableInterface) {
                $icon = $oneResult->getObjObject()->getStrIcon();
                if (is_array($icon)) {
                    $icon = $icon[0];
                }
            }

            $link = $oneResult->getStrPagelink();
            if (empty($link)) {
                $link = Link::getLinkAdminHref($oneResult->getObjObject()->getArrModule('modul'), 'edit', '&systemid=' . $oneResult->getStrSystemid());
            }

            $item['module'] = Carrier::getInstance()->getObjLang()->getLang('modul_titel', $oneResult->getObjObject()->getArrModule('modul'));
            $item['systemid'] = $oneResult->getStrSystemid();
            $item['icon'] = AdminskinHelper::getAdminImage($icon, '', true);
            $item['score'] = $oneResult->getStrSystemid();
            $item['description'] = $oneResult->getObjObject()->getStrDisplayName();
            if ($oneResult->getObjObject() instanceof AdminListableInterface) {
                $item['additionalInfos'] = $oneResult->getObjObject()->getStrAdditionalInfo();

                //call the original module to render the action-icons
                $objAdminInstance = SystemModule::getModuleByName($oneResult->getObjObject()->getArrModule('modul'))->getAdminInstanceOfConcreteModule();
                if ($objAdminInstance instanceof AdminSimple) {
                    $item['actions'] = $objAdminInstance->getActionIcons($oneResult->getObjObject());
                }
            }
            $item['lastModifiedBy'] = $oneResult->getObjObject()->getLastEditUser($context->getHeader(Session::getInstance()->getUserID()));
            $item['lastModifiedTime'] = dateToString(new Date($oneResult->getObjObject()->getIntLmTime()));
            $item['link'] = html_entity_decode($link);

            $items[] = $item;
        }

        return $items;
    }
}
