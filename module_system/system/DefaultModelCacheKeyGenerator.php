<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

final class DefaultModelCacheKeyGenerator implements ModelCacheKeyGeneratorInterface
{
    private const HASHING_ALGORITHM = 'md4';

    private const CONSIDERED_MODEL_RIGHTS = [
        'view',
        'edit',
        'delete',
        'right',
        'right1',
        'right2',
        'right3',
        'right4',
        'right5',
        'changelog',
    ];

    private function computeBasicModelData(Model $model): array
    {
        return [
            \get_class($model),
            $model->getStrSystemid(),
            $model->getStrPrevId(),
            ((bool) $model->getIntRecordDeleted()) ? '1' : '0',
            (string) $model->getIntRecordStatus(),
            $model->getLockManager()->getLockId(),
        ];
    }

    private function computeModelRightsData(Model $model): array
    {
        $rights = [];

        foreach (self::CONSIDERED_MODEL_RIGHTS as $right) {
            $accessor = 'right' . \ucfirst($right);

            try {
                $rights[$right] = $model->{$accessor}() ? '1' : '0';
            } catch (Exception $exception) {
                $rights[$right] = '0';
            }
        }

        return \array_values($rights);
    }

    private function hash(string ...$data): string
    {
        return \hash(self::HASHING_ALGORITHM, \implode('', $data));
    }

    public function generate(Model $model, string ...$additionalData): string
    {
        return $this->hash(
            ...$this->computeBasicModelData($model),
            ...$this->computeModelRightsData($model),
            ...$additionalData
        );
    }
}
