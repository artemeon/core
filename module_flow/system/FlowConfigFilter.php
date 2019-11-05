<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Flow\System;

use Kajona\System\System\FilterBase;

/**
 * Filter for flow config model objects, works irrespective of current system status.
 *
 * @author mike.marschall@artemeon.de
 * @since 7.2
 */
final class FlowConfigFilter extends FilterBase
{
    /**
     * @var string|null
     * @fieldType Kajona\System\Admin\Formentries\FormentryText
     * @tableColumn agp_flow.flow_target_class
     * @filterCompareOperator EQ
     */
    private $targetClass;

    public function getTargetClass(): ?string
    {
        return $this->targetClass;
    }

    public function setTargetClass(?string $targetClass): void
    {
        $this->targetClass = $targetClass;
    }

    public static function createForTargetClass(string $targetClass): self
    {
        $instance = new self();
        $instance->setTargetClass($targetClass);

        return $instance;
    }
}
