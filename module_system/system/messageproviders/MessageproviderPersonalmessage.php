<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System\Messageproviders;

use Kajona\System\System\Carrier;

/**
 * This messageprovider may be used to send messages directly to a user, so with
 * a kind of "direct messaging" style.
 *
 * @author sidler@mulchprod.de
 * @package module_messaging
 * @since 4.3
 */
class MessageproviderPersonalmessage extends MessageproviderBase
{
    /**
     * Returns the name of the message-provider
     *
     * @return string
     */
    public function getStrName()
    {
        return Carrier::getInstance()->getObjLang()->getLang("messageprovider_personalmessage_name", "system");
    }

    /**
     * If set to true, the messageprovider may not be disabled by the user.
     * Messages are always sent to the user.
     *
     * @return bool
     */
    public function isAlwaysActive()
    {
        return true;
    }

    /**
     * If set to true, all messages sent by this provider will be sent by mail, too.
     * The user is not allowed to disable the by-mail flag.
     * Set this to true with care.
     *
     * @return mixed
     */
    public function isAlwaysByMail()
    {
        return false;
    }

    /**
     * This method is queried when the config-view is rendered.
     * It controls whether a message-provider is shown in the config-view or not.
     *
     * @return mixed
     * @since 4.5
     */
    public function isVisibleInConfigView()
    {
        return true;
    }
}
