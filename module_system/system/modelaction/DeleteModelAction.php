<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerActionNameForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerForModelException;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ModelControllerProvider;
use ReflectionMethod;
use Throwable;

class DeleteModelAction implements ModelAction
{
    /**
     * @var ModelControllerProvider
     */
    private $modelControllerProvider;

    /**
     * @var ToolkitAdmin
     */
    private $toolkit;

    /**
     * @var Lang
     */
    private $lang;

    public function __construct(ModelControllerProvider $modelControllerProvider, ToolkitAdmin $toolkit, Lang $lang)
    {
        $this->modelControllerProvider = $modelControllerProvider;
        $this->toolkit = $toolkit;
        $this->lang = $lang;
    }

    public function isAvailable(Model $model, ModelActionContext $context): bool
    {
        try {
            return !$model->getIntRecordDeleted()
                && $model->rightDelete();
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param Model $model
     * @param string $actionName
     * @return string
     * @throws UnableToRetrieveControllerForModelException
     * @throws UnableToRetrieveControllerActionNameForModelException
     */
    private function getActionNameForClass(Model $model, string $actionName): string
    {
        $controller = $this->modelControllerProvider->getControllerForModel($model);

        try {
            $reflectionMethod = new ReflectionMethod($controller, 'getActionNameForClass');
            $reflectionMethod->setAccessible(true);

            return $reflectionMethod->invoke($controller, $actionName, $model);
        } catch (Throwable $exception) {
            throw new UnableToRetrieveControllerActionNameForModelException($model, $actionName, $exception);
        }
    }

    /**
     * @param Model $model
     * @return string
     * @throws UnableToRetrieveControllerForModelException
     * @throws UnableToRetrieveControllerActionNameForModelException
     */
    private function renderDeleteActionLocked(): string
    {
        return $this->toolkit->listButton(
            AdminskinHelper::getAdminImage(
                'icon_deleteLocked',
                $this->lang->getLang('commons_locked', 'commons')
            )
        );
    }

    /**
     * @param Model $model
     * @return string
     * @throws UnableToRetrieveControllerForModelException
     * @throws UnableToRetrieveControllerActionNameForModelException
     */
    private function renderDeleteAction(Model $model): string
    {
        $moduleName = $model->getArrModule('module');
        $deleteActionName = $this->getActionNameForClass($model, 'delete');

        return $this->toolkit->listDeleteButton(
            \strip_tags($model->getStrDisplayName()),
            $this->lang->getLang(
                $this->lang->stringToPlaceholder($deleteActionName . '_question'),
                $moduleName
            ),
            Link::getLinkAdminHref(
                $moduleName,
                $deleteActionName,
                [
                    'systemid' => $model->getSystemid(),
                ]
            )
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->isAvailable($model, $context)) {
            throw new UnableToRenderActionForModelException('permissions', $model);
        }

        $isAccessibleForCurrentUser = false;
        try {
            $isAccessibleForCurrentUser = $model->getLockManager()->isAccessibleForCurrentUser();
        } catch (Exception $exception) {
        }

        try {
            if (!$isAccessibleForCurrentUser) {
                return $this->renderDeleteActionLocked();
            }

            return $this->renderDeleteAction($model);
        } catch (Exception $exception) {
            throw new UnableToRenderActionForModelException('copy', $model, $exception);
        }
    }
}
