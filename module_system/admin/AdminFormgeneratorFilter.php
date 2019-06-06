<?php
/*"******************************************************************************************************
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                   *
 ********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\System\Admin\Formentries\FormentryHidden;
use Kajona\System\Admin\Formentries\FormentryPlaintext;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\FilterBase;
use Kajona\System\System\Link;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemModule;

/**
 * @author christoph.kappestein@gmail.com
 * @since  5.0
 * @module module_formgenerator
 */
class AdminFormgeneratorFilter extends AdminFormgenerator
{
    /**
     * Constants for filter form
     */
    const STR_FORM_PARAM_RESET = "reset";
    const STR_FORM_PARAM_FILTER = "setcontentfilter";
    const STR_FORM_PARAM_SESSION = "contentfilter_session";
    const STR_FILTER_REDIRECT = "redirect";

    /**
     * Set to true if filter shall be visible initially
     *
     * @var bool
     */
    private $bitInitiallyVisible = false;

    /**
     * @var string
     */
    protected $strLangActive;

    /**
     * @var string
     */
    protected $strLangInactive;

    /**
     * @param string $strFormname
     * @param FilterBase $objSourceobject
     *
     * @throws Exception
     */
    public function __construct($strFormname, $objSourceobject)
    {
        if (!$objSourceobject instanceof FilterBase) {
            throw new Exception("Source object must be an instance of FilterBase object", Exception::$level_ERROR);
        }

        parent::__construct($strFormname, $objSourceobject);
    }

    /**
     * @return FilterBase
     */
    public function getObjSourceobject()
    {
        return parent::getObjSourceobject();
    }

    /**
     * Renders a filter including session handling for the given filter
     *
     * @param string $strTargetURI
     * @param int $intButtonConfig
     *
     * @return string
     * @throws Exception
     */
    public function renderForm($strTargetURI, $intButtonConfig = 2)
    {
        $objCarrier = Carrier::getInstance();
        $objFilter = $this->getObjSourceobject();
        $this->setBitOnLeaveChangeDetection(false);

        /* Check if post request was send? */
        if ($objCarrier->getParam($this->getFormElementName(self::STR_FORM_PARAM_FILTER)) == "true") {
            $objCarrier->setParam("pv", "1");

            /* Check if filter was reset? */
            if ($objCarrier->getParam(self::STR_FORM_PARAM_RESET) != "") {
                $this->resetParams();
            }
        }

        /* Init the form */
        $this->generateFieldsFromObject();

        /* Update Filterform (specific filter form handling) */
        $objFilter->updateFilterForm($this);

        /* Add hidden specific filter param */
        $this->addField(new FormentryHidden($this->getStrFormname(), self::STR_FORM_PARAM_FILTER))->setStrValue("true");
        $this->addField(new FormentryHidden("", self::STR_FORM_PARAM_SESSION))->setStrValue($this->getStrFormname());

        /* Update sourceobject */
        if (!$this->validateForm()) {
            $errorField2Value = [];
            /*
             * If validation errors occur, set values of fields to "empty" and then update sourceobject.
             * This fixes a bug that invalid data is passed to the sourceobject/filter
             *
             * Foreach field which is not valid keep it's value to set it later back to the field
             */
            foreach ($this->getArrValidationFormErrors() as $key => $error) {
                $fieldKey = StringUtil::replace($this->getStrFormname() . "_", "", $key);
                if ($this->getField($fieldKey) !== null) {
                    $errorField2Value[$fieldKey] = $this->getField($fieldKey)->getStrValue();
                    $this->getField($fieldKey)->setStrValue("");
                }
            }
            $this->updateSourceObject();

            //Now set kept values from above to fields (so that the invalid values are shown in the form)
            foreach ($errorField2Value as $fieldKey => $value) {
                $this->getField($fieldKey)->setStrValue($value);
            }
        } else {
            $this->updateSourceObject();
        }

        /* Render filter form. */
        $strReturn = parent::renderForm($strTargetURI, AdminFormgenerator::BIT_BUTTON_SUBMIT | AdminFormgenerator::BIT_BUTTON_RESET);

        /* Display filter active/inactive */
        $bitFilterActive = false;
        foreach ($this->getArrFields() as $objOneField) {
            if (!$objOneField instanceof FormentryHidden && !$objOneField instanceof FormentryPlaintext) {
                $bitFilterActive = $bitFilterActive || !$objOneField->isFieldEmpty();
            }
        }

        return self::renderToolbarEntry($strReturn, $bitFilterActive, $this->getBitInitiallyVisible(), $this->getStrLangActive(), $this->getStrLangInactive());
    }

    /**
     * @inheritDoc
     * The focus is only set in case the filter is visible
     */
    protected function renderBrowserFocus()
    {
        if ($this->getBitInitiallyVisible()) {
            return parent::renderBrowserFocus();
        }
        return "";
    }

    /**
     * @param string $strFilter
     * @param boolean $bitFilterActive
     * @param bool $bitInitiallyVisible
     * @param string $strLangActive
     * @param string $strLangInactive
     * @return string
     */
    public static function renderToolbarEntry($strFilter, $bitFilterActive, $bitInitiallyVisible = false, $strLangActive = null, $strLangInactive = null)
    {
        $objLang = Carrier::getInstance()->getObjLang();

        if ($strLangActive === null) {
            $strLangActive = $objLang->getLang("commons_filter_active", "system");
        }
        if ($strLangInactive === null) {
            $strLangInactive = $objLang->getLang("filter_show_hide", "system");
        }

        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $arrFolder = $objToolkit->getLayoutFolderPic(
            $strFilter,
            $bitFilterActive ? $strLangActive : $strLangInactive,
            "icon_folderOpen",
            $bitFilterActive ? "icon_filter" : "icon_folderClosed",
            $bitInitiallyVisible
        );
        $return = $objToolkit->addToContentToolbar($arrFolder[1]) . $arrFolder[0];

        if (SystemModule::getModuleByName("tinyurl") !== null) {
            $title = AdminskinHelper::getAdminImage("icon_treeLink") . " " . $objLang->getLang("commons_filter_url", "system");
            $strFilterUrlButton = Link::getLinkAdminManual(["href" => "#", "onclick" => "Forms.getFilterURL();return false"], $title);
            $return .= $objToolkit->addToContentToolbar(trim($strFilterUrlButton));
        }

        return $return;
    }

    /**
     * Removes all parameters so that all form fields are empty
     */
    protected function resetParams()
    {
        $arrParams = Carrier::getAllParams();
        $objFilter = $this->getObjSourceobject();

        // clear params
        foreach ($arrParams as $strKey => $strValue) {
            if (strpos($strKey, $objFilter->getFilterId()) !== false) {
                Carrier::getInstance()->setParam($strKey, null);
            }
        }
    }

    /**
     * If a formname is set, this method return <formname>_<fieldname>, else the given <fieldname>
     *
     * @param $strFieldName
     *
     * @return string
     */
    private function getFormElementName($strFieldName)
    {
        $strName = $strFieldName;

        if ($this->getStrFormname() != "") {
            $strName = $this->getStrFormname() . "_" . $strFieldName;
        }

        return $strName;
    }

    /**
     * @return boolean
     */
    public function getBitInitiallyVisible()
    {
        return $this->bitInitiallyVisible;
    }

    /**
     * @param boolean $bitInitiallyVisible
     */
    public function setBitInitiallyVisible($bitInitiallyVisible)
    {
        $this->bitInitiallyVisible = $bitInitiallyVisible;
    }

    /**
     * @return string
     */
    public function getStrLangActive()
    {
        return $this->strLangActive;
    }

    /**
     * @param string $strLangActive
     */
    public function setStrLangActive($strLangActive)
    {
        $this->strLangActive = $strLangActive;
    }

    /**
     * @return string
     */
    public function getStrLangInactive()
    {
        return $this->strLangInactive;
    }

    /**
     * @param string $strLangInactive
     */
    public function setStrLangInactive($strLangInactive)
    {
        $this->strLangInactive = $strLangInactive;
    }
}
