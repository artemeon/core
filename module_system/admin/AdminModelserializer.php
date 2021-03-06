<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                   *
********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\System\Admin\Formentries\FormentryObjectlist;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\OrmBase;
use Kajona\System\System\Reflection;
use Kajona\System\System\Root;

/**
 * Serializer class which can convert model objects into a JSON string and vice versa
 *
 * @author christoph.kappestein@gmail.com
 * @since  4.8
 * @module module_system
 */
class AdminModelserializer
{
    /**
     * @var string
     */
    const STR_ANNOTATION_SERIALIZABLE = "@serializable";

    /**
     * @var string
     */
    const CLASS_KEY = "strRecordClass";

    /**
     * Returns all properties of a model as array. If the whitelist argument is provided returns only the provided
     * properties
     *
     * @param ModelInterface $objModel
     * @param array $arrWhitelist
     * @param string $strAnnotation
     * @return array
     * @throws Exception
     */
    public static function getProperties(ModelInterface $objModel, array $arrWhitelist = null, $strAnnotation = OrmBase::STR_ANNOTATION_TABLECOLUMN)
    {
        $objReflection = new Reflection(get_class($objModel));
        $arrProperties = $objReflection->getPropertiesWithAnnotation($strAnnotation);
        $arrFieldTypes = $objReflection->getPropertiesWithAnnotation(AdminFormgenerator::STR_TYPE_ANNOTATION);
        $arrJSON = [];

        foreach ($arrProperties as $strAttributeName => $strAttributeValue) {
            // in case we have a whitelist and the proeprty is not in the whitelist skip
            if ($arrWhitelist !== null && !in_array($strAttributeName, $arrWhitelist)) {
                continue;
            }

            $strGetter = $objReflection->getGetter($strAttributeName);
            if ($strGetter != null) {
                $strValue = $objModel->$strGetter();

                // check field type to better serialize the values
                $strFieldType = isset($arrFieldTypes[$strAttributeName]) ? $arrFieldTypes[$strAttributeName] : null;
                if ($strFieldType == FormentryObjectlist::class) {
                    $arrValues = [];
                    foreach ($strValue as $objValue) {
                        if ($objValue instanceof Root) {
                            $arrValues[] = $objValue->getSystemid();
                        } elseif (is_string($objValue) && validateSystemid($objValue)) {
                            $arrValues[] = $objValue;
                        }
                    }

                    $strValue = $arrValues;
                }

                if ($strValue instanceof Date) {
                    $strValue = $strValue->getLongTimestamp();
                }
                $arrJSON[$strAttributeName] = $strValue;
            }
        }

        return $arrJSON;
    }

    /**
     * Converts a model into a JSON string representation. Use the unserialize method to convert this string back into
     * an object
     *
     * @param ModelInterface $objModel
     * @param string $strAnnotation
     * @return string
     * @throws Exception
     */
    public static function serialize(ModelInterface $objModel, $strAnnotation = OrmBase::STR_ANNOTATION_TABLECOLUMN)
    {
        $arrJSON = self::getProperties($objModel, null, $strAnnotation);
        $arrJSON[self::CLASS_KEY] = get_class($objModel);

        return json_encode($arrJSON);
    }

    /**
     * Creates a model based on a serialized string. Returns an instance of the fitting model class
     *
     * @param string $strData
     * @param string $strAnnotation
     * @return ModelInterface
     * @throws Exception
     */
    public static function unserialize($strData, $strAnnotation = OrmBase::STR_ANNOTATION_TABLECOLUMN)
    {
        $arrData = json_decode($strData, true);
        $objModel = self::getObjectFromJson($arrData);

        $objReflection = new Reflection(get_class($objModel));
        $arrProperties = $objReflection->getPropertiesWithAnnotation($strAnnotation);

        foreach ($arrProperties as $strAttributeName => $strAttributeValue) {
            $strSetter = $objReflection->getSetter($strAttributeName);
            if ($strSetter != null && isset($arrData[$strAttributeName])) {
                $objModel->$strSetter($arrData[$strAttributeName]);
            }
        }

        return $objModel;
    }

    /**
     * Returns an object instance based on the provided array data. Looks in the array at the CLASS_KEY and creates a
     * new instance of this class. Throws an exception in case the class could not be determined
     *
     * @param array $arrData
     * @return ModelInterface
     * @throws Exception
     */
    protected static function getObjectFromJson(array $arrData)
    {
        if (isset($arrData[self::CLASS_KEY])) {
            $strClassName = $arrData[self::CLASS_KEY];
            if (class_exists($strClassName)) {
                $objInstance = new $strClassName();
                if ($objInstance instanceof ModelInterface) {
                    return $objInstance;
                }
            }
        }

        throw new Exception("Could not determine object type", Exception::$level_ERROR);
    }
}
