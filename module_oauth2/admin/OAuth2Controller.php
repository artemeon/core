<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare (strict_types = 1);

namespace Kajona\Oauth2\Admin;

use Kajona\Oauth2\System\ProviderManager;
use Kajona\System\Admin\AdminEvensimpler;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\Admin\LoginAdmin;
use Kajona\System\System\Link;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;

/**
 * Controller to handle OAuth2 authentication and callback
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.0
 * @module oauth2
 * @moduleId _oauth2_module_id_
 */
class OAuth2Controller extends AdminEvensimpler implements AdminInterface
{
    /**
     * @inject oauth2_provider_manager
     * @var ProviderManager
     */
    protected $providerManager;

    /**
     * Redirects the user to the fitting authorization url depending on the given provider id
     *
     * @permissions anonymous
     * @responseType html
     */
    public function actionRedirect()
    {
        $providerId = $this->getParam("provider_id");
        $provider = $this->providerManager->getProviderById($providerId);

        $url = $this->providerManager->buildAuthorizationUrl($provider);
        $url = json_encode($url);

        // redirect the user to the authorization url
        return <<<HTML
<script type="text/javascript">
Oauth2.redirect({$url});
</script>
HTML;
    }

    /**
     * Action where the user lands after successful authorization at the provider. The url contains a code parameter
     * which can be exchanged for an access token. The access token is a JWT which contains informations about the user.
     * The provider manager decodes the token and creates a new user if needed and also creates a session for this user
     *
     * @permissions anonymous
     * @responseType html
     */
    public function actionCallback()
    {
        // currently we can not identify the provider based on the url so we use simply the default provider. In the
        // future we may add the provider id to the callback url so that we can support also multiple provider
        $provider = $this->providerManager->getDefaultProvider();
        $code = $this->getParam("code");

        $this->providerManager->handleCallback($provider, $code);

        // trigger redirect after we have authenticated
        $strRefer = $this->objSession->getSession(LoginAdmin::SESSION_REFERER);
        if ($strRefer != "" && strpos($strRefer, "module=login") === false) {
            $strUrl = StringUtil::replace("&contentFill=1", "", $strRefer);
            $this->objSession->sessionUnset(LoginAdmin::SESSION_REFERER);
            $this->objSession->setSession(LoginAdmin::SESSION_LOAD_FROM_PARAMS, "true");

            return Link::clientRedirectManual(_indexpath_ . "?" . $strUrl);
        } else {
            //route to the default module
            $strModule = "dashboard";
            if (Session::getInstance()->isLoggedin()) {
                $objUser = Session::getInstance()->getUser();
                if ($objUser->getStrAdminModule() != "") {
                    $strModule = $objUser->getStrAdminModule();
                }
            }

            // at the moment it is required to use the "old" url style since otherwise it could happen that the
            // location.href call does not trigger a redirect (in case only the url hash has changed) and thus we would
            // not load a different template and see the main content inside the login template
            return Link::clientRedirectManual(_indexpath_ . "?admin=1&module=" . $strModule);
        }
    }
}
