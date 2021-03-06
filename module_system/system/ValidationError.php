<?php
/*"******************************************************************************************************
*   (c) 2010-2017 ARTEMEON                                                                              *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;


/**
 * Class to hold validation error
 *
 * @author stefan.meyer@artemeon.de
 * @since 7.0
 */
class ValidationError
{
    /**
     * Contains the error message of the validation error
     *
     * @var string
     */
    private $strErrorMessage;

    /**
     * Contains the field name of the validation error
     *
     * @var string
     */
    private $strFieldName;

    /**
     * ValidationError constructor.
     * @param $strErrorMessages
     * @param $strFieldName
     */
    public function __construct($strErrorMessages, $strFieldName = null)
    {
        $this->strErrorMessage = $strErrorMessages;
        $this->strFieldName = $strFieldName;
    }

    /**
     * @return mixed
     */
    public function getStrErrorMessage()
    {
        return $this->strErrorMessage;
    }

    /**
     * @param mixed $strErrorMessage
     */
    public function setStrErrorMessage($strErrorMessage)
    {
        $this->strErrorMessage = $strErrorMessage;
    }

    /**
     * @return mixed
     */
    public function getStrFieldName()
    {
        return $this->strFieldName;
    }

    /**
     * @param mixed $strFieldName
     */
    public function setStrFieldName($strFieldName)
    {
        $this->strFieldName = $strFieldName;
    }

    /**
     * Transforms an array of validation objects into the old error array format
     *
     * @param array $arrErrors
     * @return array
     * @deprecated
     */
    public static function transform($arrErrors)
    {
        if (empty($arrErrors)) {
            return [];
        }

        $arrValidationErrors = [];
        foreach ($arrErrors as $objError) {
            /** @var ValidationError $objError */
            if (!array_key_exists($objError->getStrFieldName(), $arrValidationErrors)) {
                $arrValidationErrors[$objError->getStrFieldName()] = array();
            }
            $arrValidationErrors[$objError->getStrFieldName()][] = $objError->getStrErrorMessage();
        }

        return $arrValidationErrors;
    }
}