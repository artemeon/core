<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

interface FeatureDetector
{
    public function isChangeHistoryFeatureEnabled(): bool;

    public function isTagsFeatureEnabled(): bool;
}
