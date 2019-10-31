<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System\Modelaction\Register;

use Kajona\System\System\Exceptions\ModelActionsContainerHasAlreadyBeenRegisteredException;
use Kajona\System\System\Exceptions\UnableToFindModelActionsContainerException;
use Kajona\System\System\Exceptions\UnableToRegisterModelActionsContainerException;
use Kajona\System\System\Model;
use Kajona\System\System\Modelaction\Actionlist\ModelActionsContainerInterface;

/**
 * Central register of model actions containers. Model actions containers registered with this class are not persisted.
 *
 * @author mike.marschall@artemeon.de
 * @since 7.2
 */
final class InMemoryModelActionsContainerRegistry implements ModelActionsContainerRegistryInterface
{
    /**
     * @var ModelActionsContainerInterface[]
     */
    private $modelActionsContainers = [];

    /**
     * @var array[][]
     */
    private $modelActionsContainersByDistanceCache = [];

    public function register(string $modelClassName, ModelActionsContainerInterface $modelActionsContainer): void
    {
        if (!\class_exists($modelClassName) || !\is_subclass_of($modelClassName, Model::class)) {
            throw new UnableToRegisterModelActionsContainerException();
        }
        if (isset($this->modelActionsContainers[$modelClassName])) {
            throw new ModelActionsContainerHasAlreadyBeenRegisteredException();
        }

        $this->modelActionsContainers[$modelClassName] = $modelActionsContainer;
        $this->modelActionsContainersByDistanceCache = [];
    }

    private function getDistanceOfConcreteModelClassToRegisteredModelClass(
        string $concreteModelClassName,
        string $registeredModelClassName
    ): ?int {
        if ($concreteModelClassName === $registeredModelClassName) {
            return 0;
        }
        if (!\is_subclass_of($concreteModelClassName, $registeredModelClassName)) {
            return null;
        }

        $depth = 0;
        while ($concreteModelClassName !== $registeredModelClassName) {
            $concreteModelClassName = \get_parent_class($concreteModelClassName);
            ++$depth;
        }

        return $depth;
    }

    private function getModelActionsContainersByDistance(string $modelClassName): array
    {
        if (isset($this->modelActionsContainersByDistanceCache[$modelClassName])) {
            return $this->modelActionsContainersByDistanceCache[$modelClassName];
        }

        $modelActionsContainersByDistance = [];
        foreach ($this->modelActionsContainers as $registeredModelClassName => $modelActionsContainer) {
            $distance = $this->getDistanceOfConcreteModelClassToRegisteredModelClass($modelClassName, $registeredModelClassName);
            if (isset($distance)) {
                $modelActionsContainersByDistance[$distance] = $modelActionsContainer;
            }
        }

        \ksort($modelActionsContainersByDistance, \SORT_NUMERIC);
        $this->modelActionsContainersByDistanceCache[$modelClassName] = $modelActionsContainersByDistance;

        return $modelActionsContainersByDistance;
    }

    private function getMostSpecificModelActionsContainer(string $modelClassName): ?ModelActionsContainerInterface
    {
        $modelActionsContainersByDistance = $this->getModelActionsContainersByDistance($modelClassName);
        if (empty($modelActionsContainersByDistance)) {
            return null;
        }

        $smallestDistance = \current(\array_keys($modelActionsContainersByDistance));

        return $modelActionsContainersByDistance[$smallestDistance];
    }

    public function find(Model $model): ModelActionsContainerInterface
    {
        $modelActionsContainer = $this->getMostSpecificModelActionsContainer(\get_class($model));
        if (!($modelActionsContainer instanceof ModelActionsContainerInterface)) {
            throw new UnableToFindModelActionsContainerException();
        }

        return $modelActionsContainer;
    }
}
