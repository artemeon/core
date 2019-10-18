<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Throwable;

final class SystemFeatureDetector implements FeatureDetector
{
    public function isChangeHistoryFeatureEnabled(): bool
    {
        try {
            return SystemSetting::getConfigValue('_system_changehistory_enabled_') === 'true';
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function isTagsFeatureEnabled(): bool
    {
        try {
            $tagsModule = SystemModule::getModuleByName('tags');
            return $tagsModule instanceof SystemModule
                && $tagsModule->rightView();
        } catch (Throwable $exception) {
            return false;
        }
    }
}
