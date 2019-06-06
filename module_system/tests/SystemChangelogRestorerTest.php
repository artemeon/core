<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Classloader;
use Kajona\System\System\Date;
use Kajona\System\System\Reflection;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\Root;
use Kajona\System\System\SystemChangelogRestorer;
use Kajona\System\System\VersionableInterface;

/**
 * Tries to restore every versionable entity
 *
 * @author christoph.kappestein@artemeon.de
 * @package module_system
 */
class SystemChangelogRestorerTest extends Testbase
{
    /**
     * @dataProvider entityProvider
     */
    public function testRestore($model)
    {
        // restore which calls all setter with null since we have no past values
        try {
            $restorer = new SystemChangelogRestorer();
            $restorer->restoreObject($model, new Date());
        } catch (\InvalidArgumentException $e) {
            // the AuswertungConfig::setObjEnddate throws an exception if the restorer tries to set null as value
        }

        // now call all getter which also checks whether each getter has a fitting return type
        $reflection = new Reflection($model);
        $properties = $reflection->getPropertiesWithAnnotation(SystemChangelogRestorer::ANNOTATION_PROPERTY_VERSIONABLE);

        foreach ($properties as $property => $annotation) {
            $method = $reflection->getGetter($property);
            $model->$method();
        }

        $this->assertInstanceOf(VersionableInterface::class, $model);
    }

    public function entityProvider()
    {
        $filter = function (&$strOneFile, $strPath) {
            $instance = Classloader::getInstance()->getInstanceFromFilename($strPath, Root::class);
            if ($instance instanceof VersionableInterface) {
                $strOneFile = get_class($instance);
            } else {
                $strOneFile = null;
            }
        };

        $classes = Resourceloader::getInstance()->getFolderContent("/system", array(".php"), false, null, $filter);
        $classes = array_values(array_filter($classes));
        $classes = array_map(function($class){ return [ new $class() ]; }, $classes);

        return $classes;
    }
}

