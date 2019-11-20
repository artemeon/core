<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Validators\TextValidator;
use Kajona\System\View\Components\Formentry\Inputtext\Inputtext;


/**
 * @author  sidler@mulchprod.de
 * @since   4.0
 * @package module_formgenerator
 */
class FormentryText extends FormentryBase implements FormentryPrintableInterface
{

    private $strOpener = "";


    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //set the default validator
        $this->setObjValidator(new TextValidator());
    }

    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.
     *
     * @return string
     */
    public function renderField()
    {
        $toolkit = Carrier::getInstance()->getObjToolkit("admin");
        $return = "";
        if ($this->getStrHint() != null) {
            $return .= $toolkit->formTextHint($this->getStrHint(), $this->getBitHideLongHints());
        }

        $inputText = new Inputtext($this->getStrEntryName(), (string) $this->getStrLabel(), html_entity_decode((string) $this->getStrValue()));
        $inputText->setReadOnly($this->getBitReadonly());
        $inputText->setOpener($this->strOpener);
        $data = $this->getDataAttributes();
        if($this->getObjSourceObject()!==null && method_exists($this->getObjSourceObject(),'getSystemid')){
            $data['field-id'] = crc32($this->getStrEntryName());
            $data['system-id'] =$this->getObjSourceObject()->getSystemid();
        }


        $inputText->setDataArray($data);

        $return .= $inputText->renderComponent();

        return $return;
    }

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText()
    {
        return $this->getStrValue();
    }

    /**
     * @param string $strOpener
     * @return FormentryText
     */
    public function setStrOpener($strOpener)
    {
        $this->strOpener = $strOpener;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrOpener()
    {
        return $this->strOpener;
    }

}
