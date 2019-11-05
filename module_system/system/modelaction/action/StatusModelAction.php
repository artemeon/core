<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Action;

use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\UnableToRenderActionForModelException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Context\ModelActionContext;

final class StatusModelAction implements ModelActionInterface
{
    /**
     * @var ToolkitAdmin
     */
    private $toolkit;

    public function __construct(ToolkitAdmin $toolkit)
    {
        $this->toolkit = $toolkit;
    }

    public function supports(Model $model, ModelActionContext $context): bool
    {
        try {
            return !$model->getIntRecordDeleted()
                && $model->rightView();
        } catch (Exception $exception) {
            return false;
        }
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        if (!$this->supports($model, $context)) {
            throw new UnableToRenderActionForModelException($model);
        }

        try {
            return $this->toolkit->listStatusButton($model);
        } catch (Exception $exception) {
            throw new UnableToRenderActionForModelException($model, $exception);
        }
    }
}
