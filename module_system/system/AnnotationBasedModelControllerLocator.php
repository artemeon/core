<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\Admin\AdminSimple;
use Kajona\System\System\Exceptions\UnableToRetrieveControllerForModelException;
use LogicException;
use Throwable;

final class AnnotationBasedModelControllerLocator implements ModelControllerLocatorInterface
{
    /**
     * @param Model $model
     * @return string
     * @throws Throwable
     */
    private function getModuleNameForModel(Model $model): string
    {
        $modelClassReflection = new Reflection($model);
        $moduleAnnotations = $modelClassReflection->getAnnotationValuesFromClass(Model::STR_MODULE_ANNOTATION);

        if (empty($moduleAnnotations)) {
            throw new LogicException(
                \sprintf('model class "%s" does not contain @module annotation', \get_class($model))
            );
        }

        return \trim($moduleAnnotations[0]);
    }

    public function getControllerForModel(Model $model): AdminSimple
    {
        try {
            $moduleName = $this->getModuleNameForModel($model);
            $module = SystemModule::getModuleByName($moduleName);
            $modelController = $module->getAdminInstanceOfConcreteModule($model->getStrSystemid());
            if (!($modelController instanceof AdminSimple)) {
                throw new LogicException(
                    \sprintf('controller for model class "%s" does not inherit from AdminSimple', \get_class($model))
                );
            }

            return $modelController;
        } catch (Throwable $exception) {
            throw new UnableToRetrieveControllerForModelException($model, $exception);
        }
    }
}
