<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Flow\System\Modelaction\Action;

use Kajona\Flow\System\FlowManager;
use Kajona\Flow\System\FlowStatus;
use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Action\ModelActionInterface;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\View\Components\Dynamicmenu\DynamicMenu;

/**
 * Model action to render the current flow status.
 *
 * @author mike.marschall@artemeon.de
 * @since 7.2
 */
final class FlowStatusModelAction implements ModelActionInterface
{
    /**
     * @var FlowManager
     */
    private $flowManager;

    /**
     * @var ToolkitAdmin
     */
    private $toolkit;

    public function __construct(FlowManager $flowManager, ToolkitAdmin $toolkit)
    {
        $this->flowManager = $flowManager;
        $this->toolkit = $toolkit;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        try {
            return $this->flowManager->isFlowConfiguredForClass(\get_class($model))
                && !$model->getIntRecordDeleted()
                && $model->rightView();
        } catch (Exception $exception) {
            return false;
        }
    }

    private function renderFlowStatusAction(Model $model, FlowStatus $flowStatus): string
    {
        $menu = new DynamicMenu(
            $this->toolkit->listButton(
                AdminskinHelper::getAdminImage($flowStatus->getStrIcon(), $flowStatus->getStrDisplayName())
            ),
            Link::getLinkAdminXml(
                $model->getArrModule('module'),
                'showStatusMenu',
                ['systemid' => $model->getSystemid()]
            )
        );
        $menu->setClass('flow-status-icon');
        $menu->setSystemId($model->getSystemid());

        return $menu->renderComponent();
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRenderActionForModelException($model);
        }

        try {
            $currentFlowStatus = $this->flowManager->getCurrentStepForModel($model);
            if (!($currentFlowStatus instanceof FlowStatus)) {
                throw new UnableToRenderActionForModelException($model);
            }

            return $this->renderFlowStatusAction($model, $currentFlowStatus);
        } catch (Exception $exception) {
            throw new UnableToRenderActionForModelException($model, $exception);
        }
    }
}
