<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Lifecycle\User;

use Kajona\System\System\Carrier;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Lifecycle\ServiceLifeCycleImpl;
use Kajona\System\System\Permissions\PermissionHandlerFactory;
use Kajona\System\System\Root;
use Kajona\System\System\SystemEventidentifier;
use Kajona\System\System\UserSourcefactory;
use Kajona\System\System\UserUser;
use Psr\Log\LoggerInterface;

/**
 * @author stefan.idler@artemeon.de
 * @since 7.1
 */
class UserLifecycle extends ServiceLifeCycleImpl
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UserLifecycle constructor.
     * @param PermissionHandlerFactory $permissionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(PermissionHandlerFactory $permissionFactory, LoggerInterface $logger)
    {
        parent::__construct($permissionFactory);
        $this->logger = $logger;
    }


    /**
     * @param UserUser $model
     * @param bool $prevId
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException
     * @throws \Kajona\System\System\Exception
     */
    public function update(Root $model, $prevId = false)
    {
        $isNew = empty($model->getSystemid());

        parent::update($model, $prevId);

        if ($isNew) {
            $this->logger->info('new user for subsystem ' . $model->getStrSubsystem() . ' / ' . $model->getStrUsername());
            $sources = new UserSourcefactory();
            $provider = $sources->getUsersource($model->getStrSubsystem());
            $targetUser = $provider->getNewUser();
            ServiceLifeCycleFactory::getLifeCycle(get_class($targetUser))->update($targetUser);
            $targetUser->setNewRecordId($model->getSystemid());
            Carrier::getInstance()->getObjDB()->flushQueryCache();
            $model->setObjSourceUser($targetUser);
        }

        CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_USERCREATED, [$model->getSystemid()]);
    }
}
