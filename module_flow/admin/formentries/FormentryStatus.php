<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Flow\Admin\Formentries;

use Kajona\Flow\System\FlowManager;
use Kajona\Flow\System\ServiceProvider;
use Kajona\System\Admin\Formentries\FormentryDropdown;
use Kajona\System\Admin\Formentries\FormentryToggleButtonbar;
use Kajona\System\System\Carrier;
use Kajona\System\System\Reflection;

/**
 * @author christoph.kappestein@gmail.de
 * @since 5.2
 * @package module_flow
 */
class FormentryStatus extends FormentryToggleButtonbar
{
    /**
     * This annotation can be used to specify a concrete model class in case the formentry is used in a filter.
     * Otherwise the class of the source object is used
     */
    const STR_MODEL_ANNOTATION = "@fieldModelClass";

    /**
     * @inheritDoc
     */
    public function __construct($formName, $sourceProperty, $sourceObject = null, ?string $modelClass = null)
    {
        parent::__construct($formName, $sourceProperty, $sourceObject);

        if ($modelClass !== null) {
            $this->setModelClass($modelClass);
        }
    }

    /**
     * Overwritten in order to load key-value pairs declared by annotations
     */
    protected function updateValue()
    {
        parent::updateValue();

        $sourceObject = $this->getObjSourceObject();
        if ($sourceObject !== null) {
            $modelClass = $this->getModelClassFromAnnotation($sourceObject);
            if ($modelClass !== null) {
                $this->setModelClass($modelClass);
            }
        }
    }

    protected function getModelClassFromAnnotation($sourceObject): ?string
    {
        // try to find the matching source property
        $sourceProperty = $this->getCurrentProperty(self::STR_MODEL_ANNOTATION);
        if ($sourceProperty === null) {
            return null;
        }

        // get model class
        $reflection = new Reflection($sourceObject);
        $modelClass = $reflection->getAnnotationValueForProperty($sourceProperty, self::STR_MODEL_ANNOTATION);
        if (empty($modelClass)) {
            return get_class($sourceObject);
        }

        return $modelClass;
    }

    protected function setModelClass(string $modelClass): void
    {
        /** @var FlowManager $flowManager */
        $flowManager = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_MANAGER);
        $keyValues = $flowManager->getArrStatusForClass($modelClass);

        $this->setArrKeyValues($keyValues);
    }
}
