<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Reflection;
use Kajona\System\System\Validators\HashMapValidator;
use Kajona\System\System\Validators\JsonHashMapValidator;
use Kajona\System\View\Components\Formentry\Listeditor\Listeditor;

/**
 * Formentry which can be used to edit multiple text options. The formentry generates for each option a unique id which
 * is stable across the edit process
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.0
 * @package module_formgenerator
 */
class FormentryListeditor extends FormentryBase implements FormentryPrintableInterface
{

    private $arrValue = [];
    private $continuousIndexes = false;

    /**
     * @return mixed
     */
    public function getStrValue()
    {
        return json_encode((object)$this->arrValue);
    }

    /**
     * @param mixed $strValue
     */
    public function setStrValue($strValue): void
    {
        parent::setStrValue("set");
        if (!empty($strValue) && is_string($strValue)) {
            $strValue = json_decode($strValue, true);
            $this->arrValue = $strValue;
        }
        if (is_array($strValue)) {
            $this->arrValue = $strValue;
        }
    }



    /**
     * @inheritdoc
     */
    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        $this->setObjValidator(new JsonHashMapValidator());
    }

    /**
     * @inheritdoc
     */
    public function renderField()
    {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        if ($this->getStrHint() != null) {
            $strReturn .= $objToolkit->formTextRow($this->getStrHint());
        }

        $value = $this->arrValue;

        $listEditor = new Listeditor($this->getStrEntryName(), $this->getStrLabel(), is_array($value) ? $value : []);
        $listEditor->setContinuousIndexes($this->continuousIndexes);

        $strReturn .= $listEditor->renderComponent();
        return $strReturn;
    }


    /**
     * @inheritdoc
     */
    public function getValueAsText()
    {
        $value = $this->arrValue;

        return is_array($value) ? implode(", ", $value) : "-";
    }

    /**
     * @return bool
     */
    public function getContinuousIndexes(): bool
    {
        return $this->continuousIndexes;
    }

    /**
     * @param bool $continuousIndexes
     */
    public function setContinuousIndexes(bool $continuousIndexes): void
    {
        $this->continuousIndexes = $continuousIndexes;
    }


}
