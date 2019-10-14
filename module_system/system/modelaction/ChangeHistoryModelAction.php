<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\FeatureDetector;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\VersionableInterface;

final class ChangeHistoryModelAction implements ModelAction
{
    /**
     * @var FeatureDetector
     */
    private $featureDetector;

    /**
     * @var ToolkitAdmin
     */
    private $toolkit;

    /**
     * @var Lang
     */
    private $lang;

    public function __construct(FeatureDetector $featureDetector, ToolkitAdmin $toolkit, Lang $lang)
    {
        $this->featureDetector = $featureDetector;
        $this->toolkit = $toolkit;
        $this->lang = $lang;
    }

    public function isAvailable(Model $model, ModelActionContext $context): bool
    {
        try {
            return $this->featureDetector->isChangeHistoryFeatureEnabled()
                && $model instanceof VersionableInterface
                && $model->rightChangelog();
        } catch (Exception $exception) {
            return false;
        }
    }

    private function renderChangeHistoryAction(Model $model): string
    {
        return $this->toolkit->listButton(
            Link::getLinkAdminDialog(
                'system',
                'genericChangelog',
                [
                    'systemid' => $model->getSystemid(),
                    'folderview' => '1',
                ],
                $this->lang->getLang('commons_edit_history', 'commons'),
                $this->lang->getLang('commons_edit_history', 'commons'),
                'icon_history',
                $model->getStrDisplayName()
            )
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->isAvailable($model, $context)) {
            throw new UnableToRenderActionForModelException('changeHistory', $model);
        }

        return $this->renderChangeHistoryAction($model);
    }
}
