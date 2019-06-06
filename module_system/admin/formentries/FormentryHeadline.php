<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Validators\DummyValidator;
use Kajona\System\View\Components\Headline\Headline;


/**
 * A fieldset may be used to group content
 *
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_formgenerator
 */
class FormentryHeadline extends FormentryBase implements FormentryPrintableInterface
{

    protected $strLevel = "h2";
    protected $strClass = "";
    private $dataAttributes = [];

    public function __construct($strName = "")
    {
        if ($strName == "") {
            $strName = generateSystemid();
        }
        parent::__construct("", $strName);

        //set the default validator
        $this->setObjValidator(new DummyValidator());
    }

    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.
     *
     * @return string
     */
    public function renderField()
    {
        $cmp = new Headline($this->getStrValue(), $this->getStrClass(), $this->getStrLevel());
        $cmp->setData($this->dataAttributes);
        return $cmp->renderComponent();
    }

    public function updateLabel($strKey = "")
    {
        return "";
    }

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     */
    public function getValueAsText()
    {
        $cmp = new Headline($this->getStrValue(), "", $this->getStrLevel());
        $cmp->setData($this->dataAttributes);
        return $cmp->renderComponent();
    }

    /**
     * @return string
     */
    public function getStrLevel()
    {
        return $this->strLevel;
    }

    /**
     * @param string $strLevel
     *
     * @return $this
     */
    public function setStrLevel($strLevel)
    {
        $this->strLevel = $strLevel;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrClass()
    {
        return $this->strClass;
    }

    /**
     * @param string $strClass
     */
    public function setStrClass($strClass)
    {
        $this->strClass = $strClass;
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
     * @return FormentryHeadline
     */
    public function setDataAttributes(array $dataAttributes): FormentryHeadline
    {
        $this->dataAttributes = $dataAttributes;
        return $this;
    }


}
