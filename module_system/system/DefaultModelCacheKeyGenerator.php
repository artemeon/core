<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

final class DefaultModelCacheKeyGenerator implements ModelCacheKeyGenerator
{
    private const HASHING_ALGORITHM = 'md4';

    /**
     * @var Rights
     */
    private $rights;

    public function __construct(Rights $rights)
    {
        $this->rights = $rights;
    }

    private function computeModelData(Model $model): array
    {
        $modelRights = $this->rights->getArrayRights($model->getStrSystemid());

        return [
            \get_class($model),
            $model->getStrSystemid(),
            $model->getStrPrevId(),
            ((bool) $model->getIntRecordDeleted()) ? '1' : '0',
            (string) $model->getIntRecordStatus(),
            \serialize($modelRights),
        ];
    }

    private function hash(string ...$data): string
    {
        return \hash(self::HASHING_ALGORITHM, \implode('', $data));
    }

    public function generate(Model $model, string ...$additionalData): string
    {
        return $this->hash(
            ...$this->computeModelData($model),
            ...$additionalData
        );
    }
}
