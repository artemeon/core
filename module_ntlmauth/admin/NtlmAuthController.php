<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Ntlmauth\Admin;

use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\Admin\LoginAdmin;
use Kajona\System\System\AuthenticationException;
use Kajona\System\System\Config;
use Kajona\System\System\Link;
use Kajona\System\System\Logger;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\UserSourcefactory;

/**
 * Controller to handle NTLM SSO authentication and callback
 *
 * @author stefan.idler@artemeon.de
 * @since 7.0
 * @module ntlmauth
 * @moduleId _ntlmauth_module_id_
 */
class NtlmAuthController extends AdminEvensimpler implements AdminInterface
{

    /**
     * Redirects the user to the fitting authorization url depending on the given provider id
     *
     * @permissions anonymous
     * @responseType html
     */
    public function actionRedirect()
    {
        // redirect the user to the authorization url
        $url = Config::getInstance('module_ntlmauth')->getConfig('authUrl');
        return <<<HTML
            <script type="text/javascript">
                location.href = {$url};
            </script>
HTML;
    }

    /**
     * Action where the user lands after successful authorization at the auth part. Creates a session for the user
     *
     * @permissions anonymous
     * @responseType html
     */
    public function actionCallback()
    {
        $REMOTE_USER = Config::getInstance('module_ntlmauth')->getConfig('userParam');
        //$REMOTE_USER = "sir";

        //fallback anonymous
        if (StringUtil::indexOf($REMOTE_USER, 'ANONYMOUS') !== false) {
            return true;
        }

        //strip the domain part
        if (StringUtil::indexOf($REMOTE_USER, "\\") !== false) {
            $REMOTE_USER = StringUtil::substring($REMOTE_USER, StringUtil::indexOf($REMOTE_USER, "\\")+1);
        }


        if (!empty($REMOTE_USER) /* && !$this->objSession->isLoggedin()*/) {
            Logger::getInstance('ntlmauth.log')->info('logging in user with id ' . $REMOTE_USER);

            //search the user via the ldap backend
            $objUsersources = new UserSourcefactory();
            $objUser = $objUsersources->getUserByUsername($REMOTE_USER);

            if ($objUser !== null /* && $objUser->getObjSourceUser() instanceof UsersourcesUserLdap*/ ) {
                if (!$this->objSession->loginUser($objUser)) {
                    throw new AuthenticationException('Failed to authenticate user ' . $REMOTE_USER);
                }
                Logger::getInstance('ntlmauth.log')->info('logging succeeded ' . $REMOTE_USER);
            } else {
                Logger::getInstance('ntlmauth.log')->info('user not found in ldap');
            }


            // trigger redirect after we have authenticated
            $strRefer = $this->objSession->getSession(LoginAdmin::SESSION_REFERER);
            if (!empty($strRefer) && strpos($strRefer, 'module=login') === false) {
                $strUrl = StringUtil::replace('&contentFill=1', '', $strRefer);
                $this->objSession->sessionUnset(LoginAdmin::SESSION_REFERER);
                $this->objSession->setSession(LoginAdmin::SESSION_LOAD_FROM_PARAMS, 'true');

                return Link::clientRedirectManual(_indexpath_ . '?' . $strUrl);
            } else {
                //route to the default module
                $strModule = 'dashboard';
                if (Session::getInstance()->isLoggedin()) {
                    $objUser = Session::getInstance()->getUser();
                    if ($objUser->getStrAdminModule() !== '') {
                        $strModule = $objUser->getStrAdminModule();
                    }
                }

                // at the moment it is required to use the "old" url style since otherwise it could happen that the
                // location.href call does not trigger a redirect (in case only the url hash has changed) and thus we would
                // not load a different template and see the main content inside the login template
                return Link::clientRedirectManual(_indexpath_ . '?admin=1&module=' . $strModule);
            }
        } else {
            throw new \InvalidArgumentException('Missing Remote User');
        }
    }
}
