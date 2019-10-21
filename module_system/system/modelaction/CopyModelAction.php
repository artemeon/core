<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerActionNameForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerForModelException;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ModelControllerLocatorInterface;
use Kajona\System\System\StringUtil;
use ReflectionMethod;
use Throwable;

final class CopyModelAction implements ModelActionInterface
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
     * @throws UnableToRetrieveControllerActionNameForModelException
     * @throws UnableToRetrieveControllerForModelException
     */
    private function renderCopyAction(Model $model): string
    {
        return $this->toolkit->listConfirmationButton(
            $this->lang->getLang('commons_copy_record_question', 'system', [
                StringUtil::jsSafeString($model->getStrDisplayName()),
            ]),
            Link::getLinkAdminHref(
                $model->getArrModule('module'),
                $this->getActionNameForClass($model, 'copyObject'),
                [
                    'systemid' => $model->getStrSystemid(),
                ]
            ),
            'icon_copy',
            $this->lang->getLang('commons_edit_copy', 'system'),
            $this->lang->getLang('dialog_copyHeader', 'system'),
            $this->lang->getLang('dialog_copyButton', 'system')
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRenderActionForModelException($model);
        }

        try {
            return $this->renderCopyAction($model);
        } catch (Exception $exception) {
            throw new UnableToRenderActionForModelException($model, $exception);
        }
    }
}
