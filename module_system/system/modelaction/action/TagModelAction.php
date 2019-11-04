<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Action;

use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerActionNameForModelException;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerForModelException;
use Kajona\System\System\FeatureDetectorInterface;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;
use Kajona\System\System\StringUtil;

final class TagModelAction implements ModelActionInterface
{
    private const JAVASCRIPT_CLICK_HANDLER_TEMPLATE = <<<'JS'
        Folderview.dialog.setContentIFrame('%s');
        Folderview.dialog.setTitle('%s');
        Folderview.dialog.init();
        return false;
JS;

    /**
     * @var FeatureDetectorInterface
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

    public function __construct(FeatureDetectorInterface $featureDetector, ToolkitAdmin $toolkit, Lang $lang)
    {
        $this->featureDetector = $featureDetector;
        $this->toolkit = $toolkit;
        $this->lang = $lang;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        try {
            return $this->featureDetector->isTagsFeatureEnabled()
                && !$model->getIntRecordDeleted()
                && $model->rightView();
        } catch (Exception $exception) {
            return false;
        }
    }

    private function createJavascriptClickHandler(Model $model): string
    {
        return \sprintf(
            self::JAVASCRIPT_CLICK_HANDLER_TEMPLATE,
            Link::getLinkAdminHref(
                'tags',
                'genericTagForm',
                [
                    'systemid' => $model->getStrSystemid(),
                ]
            ),
            StringUtil::jsSafeString($model->getStrDisplayName())
        );
    }

    /**
     * @param Model $model
     * @return string
     * @throws UnableToRetrieveControllerActionNameForModelException
     * @throws UnableToRetrieveControllerForModelException
     */
    private function renderTagAction(Model $model): string
    {
        return $this->toolkit->listButton(
            \sprintf(
                '<a href="#" onclick="%s" title="%s" rel="tagtooltip" data-systemid="%s">%s</a>',
                $this->createJavascriptClickHandler($model),
                $this->lang->getLang('commons_edit_tags', 'commons'),
                $model->getStrSystemid(),
                AdminskinHelper::getAdminImage(
                    'icon_tag',
                    $this->lang->getLang('commons_edit_tags', 'commons'),
                    true
                )
            )
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRenderActionForModelException($model);
        }

        try {
            return $this->renderTagAction($model);
        } catch (Exception $exception) {
            throw new UnableToRenderActionForModelException($model, $exception);
        }
    }
}
