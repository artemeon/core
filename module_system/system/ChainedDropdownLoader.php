<?php

declare(strict_types=1);

namespace Kajona\System\System;

/**
 * @since 7.1
 */
final class ChainedDropdownLoader implements DropdownLoaderInterface
{
    /**
     * @var DropdownLoaderInterface[]
     */
    private $dropdownLoaders;

    public function __construct(DropdownLoaderInterface ...$dropdownLoaders)
    {
        $this->dropdownLoaders = $dropdownLoaders;
    }

    /**
     * @inheritDoc
     */
    public function fetchValues(string $provider, array $params = []): array
    {
        foreach ($this->dropdownLoaders as $dropdownLoader) {
            if (($values = $dropdownLoader->fetchValues($provider, $params)) !== []) {
                return $values;
            }
        }

        throw new \RuntimeException('Unable to find dropdown for provider "' . $provider . '"');
    }
}
