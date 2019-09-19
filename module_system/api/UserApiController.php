<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Api\System\Http\JsonResponse;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Session;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserSourcefactory;
use Kajona\System\System\UserUser;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Environment\HttpResponse;

/**
 * UserApiController
 *
 * @author dhafer.harrathi@artemeon.de
 * @since 7.1
 */
class UserApiController implements ApiControllerInterface
{

    /**
     * delete report's field
     *
     * @param HttpContext $context
     * @return HttpResponse
     * @throws Exception
     * @api
     * @method GET
     * @path /v1/user/filter
     * @authorization usertoken
     */
    public function actionGetUserByFilter(HttpContext $context): HttpResponse
    {
        $strFilter = $context->getParameter('filter');
        $strCheckId = $context->getParameter('checkid');
        $strGroupId = $context->getParameter('groupid');
        $user = $context->getParameter('user');
        $group = $context->getParameter('group');
        $block = $context->getParameter('block');
        $arrCheckIds = json_decode($strCheckId);

        $arrUsers = [];
        $objSource = new UserSourcefactory();

        if ($user == "true") {
            $arrUsers = $objSource->getUserlistByUserquery($strFilter, 0, 25, $strGroupId);
        }

        if ($group == "true") {
            $arrUsers = array_merge($arrUsers, $objSource->getGrouplistByQuery($strFilter));
        }

        usort($arrUsers, function ($objA, $objB) {
            if ($objA instanceof UserUser) {
                $strA = $objA->getStrUsername();
            } else {
                $strA = $objA->getStrName();
            }

            if ($objB instanceof UserUser) {
                $strB = $objB->getStrUsername();
            } else {
                $strB = $objB->getStrName();
            }

            return strcmp(strtolower($strA), strtolower($strB));
        });

        $arrReturn = [];
        foreach ($arrUsers as $objOneElement) {
            if ($block == "current" && $objOneElement->getSystemid() == $context->getHeader(Session::getInstance()->getUserID())) {
                continue;
            }

            //if element is group and user is not superadmin
            if ($objOneElement instanceof UserGroup
                && !Carrier::getInstance()->getObjSession()->isSuperAdmin()
                && $objOneElement->getStrSystemid() === SystemSetting::getConfigValue("_admins_group_id_")
            ) {
                continue;
            }

            $bitUserHasRightView = true;
            if (!empty($arrCheckIds) && is_array($arrCheckIds) && $objOneElement instanceof UserUser) {
                foreach ($arrCheckIds as $strCheckId) {
                    if (!$this->hasUserViewPermissions($strCheckId, $objOneElement, $context)) {
                        $bitUserHasRightView = false;
                        break;
                    }
                }
            }

            if ($bitUserHasRightView) {
                $arrEntry = [];

                if ($objOneElement instanceof UserUser) {
                    $arrEntry["title"] = $objOneElement->getStrDisplayName();
                    $arrEntry["label"] = $objOneElement->getStrDisplayName();
                    $arrEntry["value"] = $objOneElement->getStrDisplayName();
                    $arrEntry["systemid"] = $objOneElement->getSystemid();
                    $arrEntry["icon"] = AdminskinHelper::getAdminImage("icon_user");
                } elseif ($objOneElement instanceof UserGroup) {
                    $arrEntry["title"] = $objOneElement->getStrName();
                    $arrEntry["value"] = $objOneElement->getStrName();
                    $arrEntry["label"] = $objOneElement->getStrName();
                    $arrEntry["systemid"] = $objOneElement->getSystemid();
                    $arrEntry["icon"] = AdminskinHelper::getAdminImage("icon_group");
                }

                $arrReturn[] = $arrEntry;
            }
        }

        return new JsonResponse($arrReturn);
    }

    /**
     * A internal helper to verify if the passed user is allowed to view the listed systemids
     *
     * @param $strValidateId
     * @param UserUser $objUser
     *
     * @param HttpContext $context
     * @return bool
     * @throws Exception
     */
    private function hasUserViewPermissions($strValidateId, UserUser $objUser, HttpContext $context)
    {
        $objInstance = Objectfactory::getInstance()->getObject($strValidateId);

        if ($objInstance != null) {
            $objCurUser = new UserUser($context->getHeader(Session::getInstance()->getUserID()));

            try {
                $context->getHeader(Session::getInstance()->switchSessionToUser($objUser, true));
                if ($objInstance->rightView()) {
                    $context->getHeader(Session::getInstance()->switchSessionToUser($objCurUser, true));
                    return true;
                }
            } catch (Exception $objEx) {
            }
            $context->getHeader(Session::getInstance()->switchSessionToUser($objCurUser, true));
        }

        return false;
    }
}
