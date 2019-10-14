<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction;

use Kajona\System\System\CacheManager;
use Kajona\System\System\Model;
use Kajona\System\System\ModelCacheKeyGenerator;
use LogicException;
use Throwable;

final class CachedModelAction implements ModelAction
{
    private const CACHE_TYPE = CacheManager::TYPE_PHPFILE;

    private const DEFAULT_LIST_IDENTIFIER = 'list';

    /**
     * @var ModelAction
     */
    private $wrappedModelAction;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ModelCacheKeyGenerator
     */
    private $modelCacheKeyGenerator;

    public function __construct(
        ModelAction $wrappedModelAction,
        CacheManager $cacheManager,
        ModelCacheKeyGenerator $modelCacheKeyGenerator
    ) {
        $this->wrappedModelAction = $wrappedModelAction;
        $this->cacheManager = $cacheManager;
        $this->modelCacheKeyGenerator = $modelCacheKeyGenerator;
    }

    private function buildCacheId(Model $model, ModelActionContext $context, string $methodName): string
    {
        return $this->modelCacheKeyGenerator->generate(
            $model,
            $context->getListIdentifier() ?? self::DEFAULT_LIST_IDENTIFIER,
            \get_class($this->wrappedModelAction),
            $methodName
        );
    }

    /**
     * @param string $cacheId
     * @param callable $cacheValueGenerator
     * @return mixed
     * @throws LogicException
     */
    private function cached(string $cacheId, callable $cacheValueGenerator)
    {
        try {
            if ($this->cacheManager->containsValue($cacheId, self::CACHE_TYPE)) {
                return $this->cacheManager->getValue($cacheId, self::CACHE_TYPE);
            }

            $cacheValue = $cacheValueGenerator();
            $this->cacheManager->addValue($cacheId, $cacheValue, self::CACHE_TYPE);

            return $cacheValue;
        } catch (Throwable $exception) {
            throw new LogicException('Unable to use cache for model actions', 0, $exception);
        }
    }

    public function isAvailable(Model $model, ModelActionContext $context): bool
    {
        return $this->cached(
            $this->buildCacheId($model, $context, 'isAvailable'),
            function () use ($model, $context): bool {
                return $this->wrappedModelAction->isAvailable($model, $context);
            }
        );
    }

    public function render(Model $model, ModelActionContext $context): string
    {
        return $this->cached(
            $this->buildCacheId($model, $context, 'render'),
            function () use ($model, $context): string {
                return $this->wrappedModelAction->render($model, $context);
            }
        );
    }
}
