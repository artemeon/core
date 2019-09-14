<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Api\System\Http\JsonResponse;
use Kajona\System\Admin\AdminFormgeneratorFactory;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Root;
use PSX\Http\Environment\HttpContextInterface;

/**
 * A general API endpoint which can be used to get the form of a specific model
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.2
 */
class ObjectApiController implements ApiControllerInterface
{
    /**
     * @inject system_object_factory
     * @var Objectfactory
     */
    private $objectFactory;

    /**
     * @inject system_life_cycle_factory
     * @var ServiceLifeCycleFactory
     */
    private $lifeCycleFactory;

    /**
     * Returns a form for a specific model
     *
     * @api
     * @method GET
     * @path /v1/system/forms
     * @authorization usertoken
     */
    public function getForm(HttpContextInterface $context): JsonResponse
    {
        $systemId = $context->getParameter('systemid');
        if (validateSystemid($systemId)) {
            $model = $this->getModelBySystemId($systemId);
        } else {
            $model = $this->getModelByClass($context->getParameter('class'));
        }

        $form = AdminFormgeneratorFactory::createByModel($model);

        return new JsonResponse([
            'form' => $form,
        ]);
    }

    private function getModelByClass(?string $class): Root
    {
        if (!class_exists($class)) {
            throw new \RuntimeException('Provided class does not exist');
        }

        $model = new $class();

        if (!$model instanceof Root) {
            throw new \RuntimeException('Provided class is no model');
        }

        return $model;
    }

    private function getModelBySystemId(?string $systemId): Root
    {
        if (!validateSystemid($systemId)) {
            throw new \RuntimeException('No valid systemid provided');
        }

        $model = $this->objectFactory->getObject($systemId);

        if (!$model instanceof Root) {
            throw new \RuntimeException('Provided systemid does not exist');
        }

        return $model;
    }
}

