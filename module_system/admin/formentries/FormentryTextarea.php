<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Validators\TextValidator;
use Kajona\System\View\Components\Formentry\Inputtextarea\Inputtextarea;


/**
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_formgenerator
 */
class FormentryTextarea extends FormentryBase implements FormentryPrintableInterface
{

    private $strOpener = "";
    private $bitLarge = false;
    private $intNumberOfRows = 4;
    private $strPlaceholder;

    private $dataAttributes = [];

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
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        if ($this->getStrHint() != null) {
            $strReturn .= $objToolkit->formTextHint($this->getStrHint(), $this->getBitHideLongHints());
        }

        $cmp = new Inputtextarea($this->getStrEntryName(), $this->getStrLabel());
        $cmp->setValue($this->getStrValue());
        $cmp->setClass($this->bitLarge ? "input-large" : "");
        $cmp->setReadOnly($this->getBitReadonly());
        $cmp->setNumberOfRows($this->getIntNumberOfRows());
        $cmp->setOpener($this->getStrOpener());
        $cmp->setPlaceholder($this->getStrPlaceholder());
        $cmp->setDataArray($this->dataAttributes);

        $strReturn .= $cmp->renderComponent();
        return $strReturn;
    }

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText()
    {
        return nl2br(replaceTextLinks($this->getStrValue()));
    }

    /**
     * @param $strOpener
     * @return FormentryText
     */
    public function setStrOpener($strOpener)
    {
        $this->strOpener = $strOpener;
        return $this;
    }

    public function getStrOpener()
    {
        return $this->strOpener;
    }

    /**
     * @param boolean $bitLarge
     *
     * @return $this
     */
    public function setBitLarge($bitLarge)
    {
        $this->bitLarge = $bitLarge;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getBitLarge()
    {
        return $this->bitLarge;
    }

    /**
     * @param int $intNumberOfRows
     * @return FormentryTextarea
     */
    public function setIntNumberOfRows($intNumberOfRows)
    {
        $this->intNumberOfRows = $intNumberOfRows;
        return $this;
    }

    /**
     * @return int
     */
    public function getIntNumberOfRows()
    {
        return $this->intNumberOfRows;
    }

    /**
     * @return string
     */
    public function getStrPlaceholder()
    {
        return $this->strPlaceholder;
    }

    /**
     * @param string $strPlaceholder
     * @return FormentryTextarea
     */
    public function setStrPlaceholder($strPlaceholder)
    {
        $this->strPlaceholder = $strPlaceholder;
        return $this;
    }

    /**
     * @return array
     */
    public function getDataAttributes(): array
    {
        return $this->dataAttributes;
    }

    /**
     * @param array $dataAttributes
     * @return FormentryTextarea
     */
    public function setDataAttributes(array $dataAttributes): FormentryTextarea
    {
        $this->dataAttributes = $dataAttributes;
        return $this;
    }




}
