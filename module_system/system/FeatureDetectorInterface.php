<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

interface FeatureDetectorInterface
{
    public function isChangeHistoryFeatureEnabled(): bool;

    public function isTagsFeatureEnabled(): bool;
}
