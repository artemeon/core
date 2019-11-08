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
use Kajona\System\System\Modelaction\Container\ModelActionsContainerInterface;

/**
 * Central registry of model actions containers. Allows association of a model class name (including subclasses in its'
 * inheritance chain) with a model actions container and subsequent retrieval given a concrete model instance.
 *
 * @author mike.marschall@artemeon.de
 * @since 7.2
 */
interface ModelActionsContainerRegistryInterface
{
    /**
     * Associates a model class name with a model actions container. All sub classes of the given model class will also
     * be implicitly associated, until a subclass more specific to the model requested in {@see find} is registered.
     *
     * @param string $modelClassName
     * @param ModelActionsContainerInterface $modelActionsContainer
     * @throws UnableToRegisterModelActionsContainerException
     * @throws ModelActionsContainerHasAlreadyBeenRegisteredException
     */
    public function register(string $modelClassName, ModelActionsContainerInterface $modelActionsContainer): void;

    /**
     * Given a concrete model instance, retrieves the model actions container instance previously associated (via
     * {@see register}) with this model's class or a parent class of this model. More specific subclasses (i.e. those
     * nearer in the inheritance chain) will always be preferred.
     *
     * @param Model $model
     * @return ModelActionsContainerInterface
     * @throws UnableToFindModelActionsContainerException
     */
    public function find(Model $model): ModelActionsContainerInterface;
}
