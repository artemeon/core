<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Action;

use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerActionNameForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerForModelException;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\ModelControllerLocatorInterface;
use ReflectionMethod;
use Throwable;
use function getRightsImageAdminName;

class PermissionsModelAction implements ModelActionInterface
{
    /**
     * @var ModelControllerLocatorInterface
     */
    private $modelControllerLocator;

    /**
     * @var ToolkitAdmin
     */
    private $toolkit;

    /**
     * @var Lang
     */
    private $lang;

    public function __construct(ModelControllerLocatorInterface $modelControllerProvider, ToolkitAdmin $toolkit, Lang $lang)
    {
        $this->modelControllerLocator = $modelControllerProvider;
        $this->toolkit = $toolkit;
        $this->lang = $lang;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        try {
            return !$model->getIntRecordDeleted()
                && $model->rightEdit();
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
        $controller = $this->modelControllerLocator->getControllerForModel($model);

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
    private function renderPermissionsAction(Model $model): string
    {
        return $this->toolkit->listButton(
            Link::getLinkAdminDialog(
                'right',
                $this->getActionNameForClass($model, 'change'),
                [
                    'systemid' => $model->getStrSystemid(),
                ],
                '',
                $this->lang->getLang('commons_edit_permissions', 'commons'),
                getRightsImageAdminName($model->getStrSystemid()),
                \strip_tags($model->getStrDisplayName()),
                true,
                true
            )
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRenderActionForModelException($model);
        }

        try {
            return $this->renderPermissionsAction($model);
        } catch (Exception $exception) {
            throw new UnableToRenderActionForModelException($model, $exception);
        }
    }
}
