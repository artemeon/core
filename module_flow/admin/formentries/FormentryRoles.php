<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Flow\Admin\Formentries;

use Kajona\Flow\System\FlowStatus;
use Kajona\System\Admin\Formentries\FormentryBase;
use Kajona\System\Admin\Formentries\FormentryCheckboxarray;
use Kajona\System\System\Carrier;
use Kajona\System\System\Lang;
use Kajona\System\System\Permissions\PermissionHandlerInterface;
use Kajona\System\System\Root;
use Kajona\System\System\Validators\DummyValidator;

/**
 * Form entry which lists all roles which are configured through a permission handler. At the moment this works only
 * if the source object is an instance of FlowStatus
 *
 * @author christoph.kappestein@gmail.de
 * @since 7.0
 * @package module_flow
 */
class FormentryRoles extends FormentryBase
{
    /**
     * Contains all child form entries
     *
     * @var array
     */
    protected $arrEntries;

    /**
     * @var PermissionHandlerInterface|null
     */
    protected $objPermissionHandler;

    /**
     * @inheritdoc
     */
    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null)
    {
        // build form entries
        $this->arrEntries = [];

        if ($objSourceObject instanceof FlowStatus) {
            $this->objPermissionHandler = $objSourceObject->getPermissionHandler();

            // build the form entries
            $this->buildFormEntries($strFormName, $strSourceProperty, $objSourceObject);
        }

        // call parent which triggers a setStrValue call
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        // set the default validator
        $this->setObjValidator(new DummyValidator());
    }

    /**
     * Returns false if at least for defined group type the array is not empty
     *
     * @inheritdoc
     */
    public function isFieldEmpty()
    {
        $bitReturn = true;

        $arrRoles = json_decode($this->getStrValue(), true);
        if (is_array($arrRoles)) {
            foreach ($arrRoles as $strRole => $arrRights) {
                if (is_array($arrRights) && count($arrRights) > 0) {
                    $bitReturn = false;
                    break;
                }
            }
        }

        return $bitReturn;
    }

    /**
     * @inheritdoc
     */
    public function setStrValue($strValue)
    {
        if (empty($strValue)) {
            return $this;
        }

        $arrRoles = json_decode($strValue, true);
        foreach ($this->arrEntries as $strRole => $objEntry) {
            /** @var FormentryCheckboxarray $objEntry */
            if (isset($arrRoles[$strRole])) {
                $arrValues = array_combine($arrRoles[$strRole], array_fill(0, count($arrRoles[$strRole]), "checked"));
                $objEntry->setStrValue($arrValues);
            }
        }

        return parent::setStrValue($strValue);
    }

    /**
     * @inheritdoc
     */
    public function renderField()
    {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";

        if (!empty($this->arrEntries)) {
            if ($this->getStrHint() != null) {
                $strReturn .= $objToolkit->formTextRow($this->getStrHint());
            }

            $strReturn .= $objToolkit->formHeadline(Lang::getInstance()->getLang("form_flow_headline_roles", "flow"));

            foreach ($this->arrEntries as $objEntry) {
                /** @var FormentryCheckboxarray $objEntry */
                $strReturn .= $objEntry->renderField();
            }
        }

        return $strReturn;
    }

    /**
     * @inheritdoc
     */
    protected function updateValue()
    {
        if (!$this->objPermissionHandler instanceof PermissionHandlerInterface) {
            return;
        }

        $arrRoles = $this->objPermissionHandler->getRoles();
        $arrValues = [];

        $arrParams = $this->getFormParams();
        $bitFound = false;
        foreach ($arrRoles as $strRole) {
            $strKey = strtolower("{$this->getStrEntryName()}_{$strRole}");
            $strKeyPrescheck = strtolower("{$this->getStrEntryName()}_{$strRole}_prescheck");
            if (isset($arrParams[$strKey]) || isset($arrParams[$strKeyPrescheck])) {
                $bitFound = true;
                break;
            }
        }

        if ($bitFound) {
            // on submit we load the values from the post request
            foreach ($arrRoles as $strRole) {
                $strKey = strtolower("{$this->getStrEntryName()}_{$strRole}");

                if (isset($arrParams[$strKey])) {
                    if (is_array($arrParams[$strKey])) {
                        $arrValues[$strRole] = array_keys($arrParams[$strKey]);
                    } else {
                        $arrValues[$strRole] = [];
                    }
                }
            }

            $this->setStrValue(json_encode($arrValues));
        } else {
            // otherwise we read the user ids from the assigned groups
            $objSource = $this->getObjSourceObject();
            if ($objSource instanceof FlowStatus) {
                foreach ($arrRoles as $strRole) {
                    $arrValues[$strRole] = $objSource->getRightsForRole($strRole);
                }
            }

            $this->setStrValue(json_encode($arrValues));
        }
    }

    /**
     * @inheritdoc
     */
    public function getValueAsText()
    {
        return "";
    }

    /**
     * @param string $strFormName
     * @param string $strSourceProperty
     * @param mixed $objObject
     */
    private function buildFormEntries($strFormName, $strSourceProperty, FlowStatus $objObject)
    {
        // in case we have no permision handler we cant render fields
        if (!$this->objPermissionHandler instanceof PermissionHandlerInterface) {
            return;
        }

        $arrRoles = $this->objPermissionHandler->getRoles();
        foreach ($arrRoles as $strRole) {
            $arrRights = $this->objPermissionHandler->getRoleRights($strRole);
            $objTargetModel = $objObject->getFlowConfig()->getStrTargetClass();
            /** @var Root $objModel */
            $objModel = new $objTargetModel();

            $strLabel = $this->objPermissionHandler->getRoleLabel($strRole);
            if ($strLabel === null) {
                $strLabel = Lang::getInstance()->getLang("{$strFormName}_{$strSourceProperty}_{$strRole}", $objModel->getArrModule("module"));
            }

            $objEntry = new FormentryCheckboxarray($strFormName, "{$strSourceProperty}_{$strRole}");
            $objEntry->setStrLabel($strLabel);
            $objEntry->setArrKeyValues($arrRights);
            $objEntry->setBitInline(true);
            $this->arrEntries[$strRole] = $objEntry;
        }
    }
}
