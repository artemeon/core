<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

/**
 * The orm object init class is used to init an object from the database.
 * Pass an object with a given systemid using the constructor and call
 * initObjectFromDb() afterwards.
 * The mapper will take care to fill all properties with the matching values
 * from the database.
 * Therefore it is essential to have getters and setters for each mapped
 * property (java bean syntax).
 *
 * @author sidler@mulchprod.de
 * @since 4.6
 */
class OrmObjectinit extends OrmBase
{

    /**
     * Initializes the object from the database.
     * Loads all mapped columns to the properties.
     * Requires that the object is identified by its systemid.
     *
     * @return void
     * @throws Exception
     * @throws OrmException
     */
    public function initObjectFromDb()
    {
        //try to do a default init
        $reflection = new Reflection($this->getObjObject());

        if (validateSystemid($this->getObjObject()->getSystemid()) && $this->hasTargetTable()) {
            if (OrmRowcache::getCachedInitRow($this->getObjObject()->getSystemid()) !== null) {
                $row = OrmRowcache::getCachedInitRow($this->getObjObject()->getSystemid());
            } else {
                $query = 'SELECT *
                          ' . $this->getQueryBase() . '
                           AND agp_system.system_id = ? ';

                $row = Carrier::getInstance()->getObjDB()->getPRow($query, array($this->getObjObject()->getSystemid()));
            }

            if (method_exists($this->getObjObject(), 'setArrInitRow')) {
                $this->getObjObject()->setArrInitRow($row);
            }

            //get the mapped properties
            $properties = $reflection->getPropertiesWithAnnotation(OrmBase::STR_ANNOTATION_TABLECOLUMN);

            foreach ($properties as $propertyName => $column) {
                $columnParts = explode('.', $column);

                if (count($columnParts) === 2) {
                    $column = $columnParts[1];
                }

                if (!isset($row[$column])) {
                    continue;
                }

                //skip columns from the system-table, they are set later on
                if (count($columnParts) === 2 && $columnParts[0] === 'agp_system') {
                    continue;
                }

                $setter = $reflection->getSetter($propertyName);
                $value = $this->convertToDatatype($reflection->getAnnotationValueForProperty($propertyName, "@var"), $row[$column]);
                $this->getObjObject()->{$setter}($value);
            }

            $this->initAssignmentProperties($reflection);
        }
    }

    /**
     * Casts the values' datatype based on the value of the var annotation
     * @param string $varDatatype
     * @param $value
     * @return bool|float|int|Date|string|null
     */
    private function convertToDatatype(string $varDatatype, $value)
    {
        if ($value === null) {
            return $value;
        }
        if ($varDatatype === 'string') {
            return (string)$value;
        }
        if ($varDatatype === 'Date') {
            return !empty($value) ? new Date($value) : null;
        }
        if ($varDatatype === 'int' || $varDatatype === 'long') {
            //different casts on 32bit / 64bit
            if ($value > PHP_INT_MAX) {
                return (float)$value;
            }

            return (int)$value;
        }
        if ($varDatatype === 'bool' || $varDatatype === 'boolean') {
            return (bool)$value;
        }
        if ($varDatatype === 'float') {
            return (float)$value;
        }

        return $value;
    }

    /**
     * Injects the lazy loading objects for assignment properties into the current object
     *
     * @param Reflection $reflection
     * @return void
     */
    private function initAssignmentProperties(Reflection $reflection): void
    {
        $object = $this->getObjObject();
        //get the mapped properties
        $properties = $reflection->getPropertiesWithAnnotation(OrmBase::STR_ANNOTATION_OBJECTLIST, ReflectionEnum::PARAMS);

        foreach ($properties as $propertyName => $values) {
            $propertyLazyLoader = new OrmAssignmentArray($object, $propertyName, $this->getIntCombinedLogicalDeletionConfig());

            $setter = $reflection->getSetter($propertyName);
            if ($setter !== null) {
                try {
                    $object->{$setter}($propertyLazyLoader);
                } catch (\TypeError $exception) {
                    $object->{$setter}($propertyLazyLoader->getArrayCopy());
                }
            }
        }
    }
}
