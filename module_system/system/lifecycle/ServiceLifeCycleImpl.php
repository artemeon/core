<?php

namespace Kajona\System\System\Lifecycle;

use Kajona\System\System\Database;
use Kajona\System\System\Permissions\PermissionHandlerFactory;
use Kajona\System\System\Permissions\PermissionHandlerInterface;
use Kajona\System\System\RedirectException;
use Kajona\System\System\Root;

/**
 * ServiceLifeCycleImpl
 *
 * @package Kajona\System\System
 * @author christoph.kappestein@gmail.com
 * @since 7.0
 */
class ServiceLifeCycleImpl implements ServiceLifeCycleInterface
{
    /**
     * @var PermissionHandlerFactory
     */
    protected $objPermissionFactory;

    /**
     * @param PermissionHandlerFactory $objPermissionFactory
     */
    public function __construct(PermissionHandlerFactory $objPermissionFactory)
    {
        $this->objPermissionFactory = $objPermissionFactory;
    }

    /**
     * @inheritdoc
     */
    public function update(Root $objModel, $strPrevId = false)
    {
        // read original state from the database. The new call does not trigger a SQL query since the data comes from
        // the OrmRowcache. Its important that we _don't_ use the factory since we want a different instance
        $strClass = get_class($objModel);
        $objOldModel = new $strClass($objModel->getSystemid());

        Database::getInstance()->transactionBegin();

        try {
            $bitReturn = $objModel->updateObjectToDb($strPrevId);

            if (!$bitReturn) {
                throw new ServiceLifeCycleUpdateException("Error updating object ".strip_tags($objModel->getStrDisplayName()), $objModel->getSystemid());
            }

            // call permission handler if available
            if ($objModel instanceof Root) {
                $this->invokePermissionHandler($objOldModel, $objModel);
            }

            Database::getInstance()->transactionCommit();
        } catch (RedirectException $objE) {
            Database::getInstance()->transactionCommit();

            throw $objE;
        } catch (\Exception $objE) {
            Database::getInstance()->transactionRollback();

            throw $objE;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(Root $objModel)
    {
        $bitReturn = $objModel->deleteObject();

        if (!$bitReturn) {
            throw new ServiceLifeCycleLogicDeleteException("Error logic deleting object ".strip_tags($objModel->getStrDisplayName()), $objModel->getSystemid());
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteObjectFromDatabase(Root $objModel)
    {
        $bitReturn = $objModel->deleteObjectFromDatabase();

        if (!$bitReturn) {
            throw new ServiceLifeCycleDeleteException("Error deleting object ".strip_tags($objModel->getStrDisplayName()), $objModel->getSystemid());
        }
    }

    /**
     * @inheritdoc
     */
    public function restore(Root $objModel)
    {
        $bitReturn = $objModel->restoreObject();

        if (!$bitReturn) {
            throw new ServiceLifeCycleRestoreException("Error restoring object ".strip_tags($objModel->getStrDisplayName()), $objModel->getSystemid());
        }
    }

    /**
     * @inheritdoc
     */
    public function copy(Root $objModel, $strNewPrevid = false, $bitChangeTitle = true, $bitCopyChilds = true)
    {
        $bitReturn = $objModel->copyObject($strNewPrevid ?: "", $bitChangeTitle, $bitCopyChilds);

        if (!$bitReturn) {
            throw new ServiceLifeCycleCopyException("Error creating a copy of object ".strip_tags($objModel->getStrDisplayName()), $objModel->getSystemid());
        }

        return $objModel;
    }

    /**
     * @param Root $objOldModel
     * @param Root $objNewModel
     * @throws \Kajona\System\System\Exception
     */
    private function invokePermissionHandler(Root $objOldModel, Root $objNewModel)
    {
        $objPermissionHandler = $this->objPermissionFactory->factory(get_class($objNewModel));

        if ($objPermissionHandler instanceof PermissionHandlerInterface) {
            if (!validateSystemid($objOldModel->getSystemid())) {
                $objPermissionHandler->onCreate($objNewModel);
            } else {
                $objPermissionHandler->onUpdate($objOldModel, $objNewModel);
            }
        }
    }
}
