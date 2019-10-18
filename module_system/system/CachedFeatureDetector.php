<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Throwable;

final class CachedFeatureDetector implements FeatureDetector
{
    private const CACHE_TYPE = CacheManager::TYPE_PHPFILE;

    private const CACHE_TTL = 180;

    /**
     * @var FeatureDetector
     */
    private $wrappedFeatureDetector;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var Session
     */
    private $session;

    public function __construct(FeatureDetector $wrappedFeatureDetector, CacheManager $cacheManager, Session $session)
    {
        $this->wrappedFeatureDetector = $wrappedFeatureDetector;
        $this->cacheManager = $cacheManager;
        $this->session = $session;
    }

    private function generateCacheKey(string $featureName): string
    {
        $userId = '';
        try {
            $userId = $this->session->getUserID();
        } catch (Exception $exception) {
        }

        return \sprintf('feature-availability-%s-%s', $featureName, $userId);
    }

    private function determineAndCacheAvailability(string $featureName, callable $featureAvailabilityProvider): bool
    {
        try {
            $cacheKey = $this->generateCacheKey($featureName);

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

    public function isChangeHistoryFeatureEnabled(): bool
    {
        return $this->determineAndCacheAvailability('changeHistory', function (): bool {
            return $this->wrappedFeatureDetector->isChangeHistoryFeatureEnabled();
        });
    }

    public function isTagsFeatureEnabled(): bool
    {
        return $this->determineAndCacheAvailability('tags', function (): bool {
            return $this->wrappedFeatureDetector->isTagsFeatureEnabled();
        });
    }
}
