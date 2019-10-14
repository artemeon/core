<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Throwable;

final class FeatureDetector
{
    private const CACHE_TYPE = CacheManager::TYPE_PHPFILE;

    private const CACHE_TTL = 180;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var Session
     */
    private $session;

    public function __construct(CacheManager $cacheManager, Session $session)
    {
        $this->cacheManager = $cacheManager;
        $this->session = $session;
    }

    private function determineAndCacheAvailability(
        string $featureName,
        callable $cacheKeyGenerator,
        callable $featureAvailabilityProvider
    ): bool {
        try {
            $cacheKey = $cacheKeyGenerator($featureName);

            if ($this->cacheManager->containsValue($cacheKey, self::CACHE_TYPE)) {
                return $this->cacheManager->getValue($cacheKey, self::CACHE_TYPE);
            }

            $determinedFeatureAvailability = $featureAvailabilityProvider();
            $this->cacheManager->addValue($cacheKey, $determinedFeatureAvailability, self::CACHE_TTL, self::CACHE_TYPE);
        } catch (Throwable $exception) {
            $determinedFeatureAvailability = false;
        }

        return $determinedFeatureAvailability;
    }

    private function useUnscopedCacheKeyGenerator(): callable
    {
        return static function (string $featureName): string {
            return \sprintf('feature-availability-%s', $featureName);
        };
    }

    private function useScopedCacheKeyGenerator(): callable
    {
        $userId = '';
        try {
            $userId = $this->session->getUserID();
        } catch (Exception $exception) {
        }

        return static function (string $featureName) use ($userId): string {
            return \sprintf('feature-availability-%s-%s', $featureName, $userId);
        };
    }

    public function isChangeHistoryFeatureEnabled(): bool
    {
        return $this->determineAndCacheAvailability(
            'changeHistory',
            $this->useUnscopedCacheKeyGenerator(),
            static function (): bool {
                return SystemSetting::getConfigValue('_system_changehistory_enabled_') === 'true';
            }
        );
    }

    public function isTagsFeatureEnabled(): bool
    {
        return $this->determineAndCacheAvailability(
            'tags',
            $this->useScopedCacheKeyGenerator(),
            static function (): bool {
                try {
                    $tagsModule = SystemModule::getModuleByName('tags');

                    return $tagsModule instanceof SystemModule
                        && $tagsModule->rightView();
                } catch (Exception $e) {
                    return false;
                }
            }
        );
    }
}
