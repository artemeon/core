<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Admin\Formentries;

use Kajona\System\Admin\AdminFormgeneratorContainerInterface;
use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\I18nTrait;
use Kajona\System\System\Validators\DummyValidator;

/**
 * Base class fot i18n based formentries
 *
 * @author  stefan.idler@artemeon.de
 * @since   7.1
 */
abstract class AbstractFormentryI18n extends FormentryBase implements FormentryPrintableInterface, AdminFormgeneratorContainerInterface
{
    use I18nTrait;

    private $strOpener = "";

    /**
     * Contains all child form entries
     *
     * @var FormentryBase[]
     */
    protected $arrEntries = [];


    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //set the default validator
        $this->setObjValidator(new DummyValidator());
        $this->buildFormEntries($strFormName, $strSourceProperty, $objSourceObject);
    }


    /**
     * @param string $strFormName
     * @param string $strSourceProperty
     * @param mixed $objObject
     */
    abstract protected function buildFormEntries($strFormName, $strSourceProperty, $objObject);


    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.x
     *
     * @return string
     */
    public function renderField()
    {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";
        $strReturn .= $objToolkit->formInputHidden($this->getStrEntryName(), "1");

        $values = $this->toI18nValueArray($this->getStrValue());
        /** @var FormentryBase|FormentryPrintableInterface $objEntry */
        foreach ($this->arrEntries as $lang => $objEntry) {
            $objEntry->setStrValue($values[$lang]);
            $strReturn .= $objEntry->renderField();
        }

        return $strReturn;
    }


    /**
     * @inheritDoc
     */
    protected function updateValue()
    {
        $arrParams = $this->getFormParams();
        if (isset($arrParams[$this->getStrEntryName()])) {
            $this->setStrValue($this->toI18nValueString($arrParams, $this->getStrEntryName()));
        } else {
            parent::updateValue();
        }
    }

    /**
     * Returns a textual representation of the formentries' value.
     * May contain html, but should be stripped down to text-only.
     *
     * @return string
     * @throws Exception
     */
    public function getValueAsText()
    {
        $lang = $this->getCurrentI18nLanguage();
        /** @var FormentryBase|FormentryPrintableInterface $entry */
        $entry = $this->arrEntries[$lang] ?? null;
        if ($entry == null) {
            return "";
        }
        $entry->setStrValue($this->getI18nValueForString($this->getStrValue() ?? "", null, true));
        return $entry->getValueAsText();
    }



    /**
     * @inheritdoc
     */
    public function getFields(): array
    {
        return $this->arrEntries;
    }

    /**
     * @inheritdoc
     */
    public function getField($name)
    {
        return $this->arrEntries[$name] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function hasField($name): bool
    {
        return isset($this->arrEntries[$name]);
    }

    /**
     * @inheritdoc
     */
    public function removeField($name)
    {
        if (isset($this->arrEntries[$name])) {
            unset($this->arrEntries[$name]);
        }
    }

    /**
     * @inheritDoc
     */
    public function setStrLabel($strLabel)
    {
        foreach ($this->arrEntries as $lang => $objEntry) {
            $objEntry->setStrLabel($strLabel." (".$lang.")");
        }
        return parent::setStrLabel($strLabel);
    }

    /**
     * @inheritDoc
     */
    public function setStrHint($strHint)
    {
        foreach ($this->arrEntries as $lang => $objEntry) {
            $objEntry->setStrHint($strHint);
        }
        return parent::setStrHint($strHint);
    }


}
