<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;

final class UnlockModelAction implements ModelAction
{
    /**
     * @var ToolkitAdmin
     */
    private $toolkit;

    /**
     * @var Lang
     */
    private $lang;

    public function __construct(ToolkitAdmin $toolkit, Lang $lang)
    {
        $this->toolkit = $toolkit;
        $this->lang = $lang;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        try {
            return !$model->getIntRecordDeleted()
                && $model->rightView()
                && !$model->getLockManager()->isAccessibleForCurrentUser()
                && $model->getLockManager()->isUnlockableForCurrentUser();
        } catch (Exception $exception) {
            return false;
        }
    }

    private function renderUnlockAction(Model $model): string
    {
        return $this->toolkit->listButton(
            Link::getLinkAdmin(
                $model->getArrModule('module'),
                'list',
                [
                    'systemid' => $model->getStrSystemid(),
                    'unlockid' => $model->getStrSystemid(),
                ],
                '',
                $this->lang->getLang('commons_unlock', 'commons'),
                'icon_lockerOpen'
            )
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRenderActionForModelException($model);
        }

        return $this->renderUnlockAction($model);
    }
}
