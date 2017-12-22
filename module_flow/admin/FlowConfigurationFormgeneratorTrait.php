<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                   *
********************************************************************************************************/

namespace Kajona\Flow\Admin;

use Kajona\Flow\System\FlowActionAbstract;
use Kajona\System\Admin\Formentries\FormentryHeadline;
use Kajona\System\Admin\Formentries\FormentryHidden;
use Kajona\System\Admin\Formentries\FormentryTextrow;
use Kajona\System\System\Carrier;
use Kajona\System\System\Lang;
use Kajona\System\System\Objectfactory;

/**
 * FlowConfigurationFormgeneratorTrait
 *
 * @package module_flow
 * @author christoph.kappestein@gmail.com
 * @since 5.1
 */
trait FlowConfigurationFormgeneratorTrait
{
    /**
     * @inheritDoc
     */
    public function generateFieldsFromObject()
    {
        parent::generateFieldsFromObject();

        $strTransistionId = Carrier::getInstance()->getParam("systemid");
        $objTransition = Objectfactory::getInstance()->getObject($strTransistionId);
        if ($objTransition instanceof FlowActionAbstract) {
            $objTransition = Objectfactory::getInstance()->getObject($objTransition->getStrPrevId());
        }

        // add dynamic action fields
        $objSource = $this->getObjSourceobject();
        $arrParameters = null;
        if ($this->isValidSourceObject($objSource)) {
            $objType = $objSource;
            $strClass = $objSource->getStrRecordClass();
            $arrParameters = $objSource->getArrParameters();

            if (!empty($arrParameters)) {
                foreach ($arrParameters as $strKey => $strValue) {
                    $strVal = Carrier::getInstance()->getParam($strKey);
                    if (empty($strVal)) {
                        Carrier::getInstance()->setParam($strKey, $strValue);
                    }
                }
            }
        } else {
            $strClass = Carrier::getInstance()->getParam("class");
            $objType = new $strClass();
        }

        if (class_exists($strClass)) {
            $this->addField(new FormentryHidden("", "class"))
                ->setStrValue($strClass);

            if ($this->isValidSourceObject($objType)) {
                $this->addField(new FormentryTextrow("description"))
                    ->setStrValue($objType->getDescription());
                $this->addField(new FormentryHeadline("config_header"))
                    ->setStrValue(Lang::getInstance()->getLang("form_flow_config", "flow"));

                $objType->configureForm($this, $objTransition);
            }
        }
    }

    public function updateSourceObject()
    {
        parent::updateSourceObject();

        $objSource = $this->getObjSourceobject();
        if ($this->isValidSourceObject($objSource)) {
            $arrParams = [];
            $arrFields = $this->getArrFields();
            foreach ($arrFields as $strName => $objField) {
                $arrParams[$strName] = $objField->getStrValue();
            }
            unset($arrParams["class"]);
            unset($arrParams["description"]);
            unset($arrParams["config_header"]);

            $objSource->setStrParams(json_encode((object) $arrParams));
        }
    }
}
