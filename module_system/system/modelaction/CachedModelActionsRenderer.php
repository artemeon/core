<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Exception;
use Kajona\System\System\CacheManager;
use Kajona\System\System\Exceptions\UnableToRenderModelActionsException;
use Kajona\System\System\Model;
use Kajona\System\System\ModelCacheKeyGenerator;

final class CachedModelActionsRenderer implements ModelActionsRenderer
{
    private const CACHE_TYPE = CacheManager::TYPE_PHPFILE;

    /**
     * @var ModelActionsRenderer
     */
    private $wrappedModelActionsRenderer;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ModelCacheKeyGenerator
     */
    private $modelCacheKeyGenerator;

    public function __construct(
        ModelActionsRenderer $wrappedModelActionsRenderer,
        CacheManager $cacheManager,
        ModelCacheKeyGenerator $modelCacheKeyGenerator
    ) {
        $this->wrappedModelActionsRenderer = $wrappedModelActionsRenderer;
        $this->cacheManager = $cacheManager;
        $this->modelCacheKeyGenerator = $modelCacheKeyGenerator;
    }

    /**
     * @param string $cacheKey
     * @param callable $valueGenerator
     * @return string
     * @throws Exception
     */
    private function retrieveValueFromCacheOrGenerateAnew(string $cacheKey, callable $valueGenerator): string
    {
        if ($this->cacheManager->containsValue($cacheKey, self::CACHE_TYPE)) {
            return $this->cacheManager->getValue($cacheKey, self::CACHE_TYPE);
        }

        $cacheValue = $valueGenerator();
        $this->cacheManager->addValue($cacheKey, $cacheValue, self::CACHE_TYPE);

        return $cacheValue;
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        try {
            $cacheKey = $this->modelCacheKeyGenerator->generate($model, $context->getListIdentifier() ?? '');

            return $this->retrieveValueFromCacheOrGenerateAnew($cacheKey, function () use ($model, $context): string {
                return $this->wrappedModelActionsRenderer->render($model, $context);
            });
        } catch (Exception $exception) {
            throw new UnableToRenderModelActionsException($model, $context, $exception);
        }
    }
}
