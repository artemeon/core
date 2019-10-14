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

final class EditModelAction implements ModelAction
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

    /**
     * @var bool
     */
    private $showDialog = false;

    public function __construct(ModelControllerProvider $modelControllerProvider, ToolkitAdmin $toolkit, Lang $lang)
    {
        $this->modelControllerProvider = $modelControllerProvider;
        $this->toolkit = $toolkit;
        $this->lang = $lang;
    }

    public function isShowDialog(): bool
    {
        return $this->showDialog;
    }

    public function setShowDialog(bool $showDialog): void
    {
        $this->showDialog = $showDialog;
    }

    public function isAvailable(Model $model, ModelActionContext $context): bool
    {
        try {
            return !$model->getIntRecordDeleted()
                && $model->rightEdit();
        } catch (Exception $exception) {
            return false;
        }
    }

    private function renderEditActionLocked(): string
    {
        return $this->toolkit->listButton(
            AdminskinHelper::getAdminImage(
                'icon_editLocked',
                $this->lang->getLang('commons_locked', 'commons')
            )
        );
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
     * @throws UnableToRetrieveControllerActionNameForModelException
     * @throws UnableToRetrieveControllerForModelException
     */
    private function renderEditActionDialog(Model $model): string
    {
        return $this->toolkit->listButton(
            Link::getLinkAdminDialog(
                $model->getArrModule('module'),
                $this->getActionNameForClass($model, 'edit'),
                [
                    'systemid' => $model->getStrSystemid(),
                    'folderview' => 1,
                ],
                $this->lang->getLang('commons_list_edit', 'commons'),
                $this->lang->getLang('commons_list_edit', 'commons'),
                'icon_edit',
                $model->getStrDisplayName()
            )
        );
    }

    /**
     * @param Model $model
     * @return string
     * @throws UnableToRetrieveControllerActionNameForModelException
     * @throws UnableToRetrieveControllerForModelException
     */
    private function renderEditAction(Model $model): string
    {
        return $this->toolkit->listButton(
            Link::getLinkAdmin(
                $model->getArrModule('module'),
                $this->getActionNameForClass($model, 'edit'),
                [
                    'systemid' => $model->getStrSystemid(),
                ],
                $this->lang->getLang('commons_list_edit', 'commons'),
                $this->lang->getLang('commons_list_edit', 'commons'),
                'icon_edit'
            )
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->isAvailable($model, $context)) {
            throw new UnableToRenderActionForModelException('edit', $model);
        }

        try {
            if (!$model->getLockManager()->isAccessibleForCurrentUser()) {
                return $this->renderEditActionLocked();
            }
            if ($this->showDialog) {
                return $this->renderEditActionDialog($model);
            }

            return $this->renderEditAction($model);
        } catch (Exception $exception) {
            throw new UnableToRenderActionForModelException('edit', $model, $exception);
        }
    }
}
