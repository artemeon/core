<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System\Messageproviders;

/**
 * Messageprovider base class which provides some default implementations
 *
 * @author christoph.kappestein@artemeon.de
 * @package module_messaging
 * @since 7.1
 */
abstract class MessageproviderBase implements MessageproviderExtendedInterface
{
    /**
     * @inheritdoc
     */
    public function isAlwaysActive()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isAlwaysByMail()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isVisibleInConfigView()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getInitialStatus()
    {
        return self::INITIAL_DEFAULT;
    }
}
