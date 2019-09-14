<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Lifecycle\User;

use Kajona\System\System\Carrier;
use Kajona\System\System\IdGenerator;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Lifecycle\ServiceLifeCycleImpl;
use Kajona\System\System\Permissions\PermissionHandlerFactory;
use Kajona\System\System\Root;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserSourcefactory;
use Psr\Log\LoggerInterface;

/**
 * @author stefan.idler@artemeon.de
 * @since 7.1
 */
class GroupLifecycle extends ServiceLifeCycleImpl
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
     * @param UserGroup $model
     * @param bool $prevId
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException
     * @throws \Kajona\System\System\Exception
     */
    public function update(Root $model, $prevId = false)
    {
        $isNew = empty($model->getSystemid());

        if ($isNew) {
            $model->setIntShortId(IdGenerator::generateNextId(UserGroup::INT_SHORTID_IDENTIFIER));
        }

        parent::update($model, $prevId);

        if ($isNew) {
            $this->logger->info('saved new group subsystem ' . $model->getStrSubsystem() . ' / ' . $model->getStrSystemid());
            //create the new instance on the remote-system
            $sources = new UserSourcefactory();
            $provider = $sources->getUsersource($model->getStrSubsystem());
            $targetGroup = $provider->getNewGroup();
            ServiceLifeCycleFactory::getLifeCycle(get_class($targetGroup))->update($targetGroup);
            $targetGroup->setNewRecordId($model->getSystemid());
            Carrier::getInstance()->getObjDB()->flushQueryCache();
            $model->setObjSourceGroup($targetGroup);
        }
    }
}
