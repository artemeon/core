<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                        *
********************************************************************************************************/

namespace Kajona\Dashboard\System;

use Kajona\Dashboard\Admin\Widgets\Adminwidget;
use Kajona\Dashboard\Admin\Widgets\AdminwidgetInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\ObjectBuilder;
use Kajona\System\System\OrmCondition;
use Kajona\System\System\OrmObjectlist;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;

/**
 * Class to represent a single adminwidget
 *
 * @package module_dashboard
 * @author sidler@mulchprod.de
 *
 * @targetTable agp_dashboard.dashboard_id
 * @module dashboard
 * @moduleId _dashboard_module_id_
 *
 * @sortManager Kajona\System\System\CommonSortmanager
 */
class DashboardWidget extends \Kajona\System\System\Model implements \Kajona\System\System\ModelInterface
{

    /**
     * @var string
     * @tableColumn agp_dashboard.dashboard_column
     * @tableColumnDatatype char254
     */
    private $strColumn = "";

    /**
     * @var string
     * @tableColumn agp_dashboard.dashboard_class
     * @tableColumnDatatype char254
     */
    private $strClass = "";

    /**
     * @var string
     * @tableColumn agp_dashboard.dashboard_content
     * @tableColumnDatatype text
     * @blockEscaping
     */
    private $strContent = "";

    /**
     * @return string
     */
    private function getWidgetClassName()
    {
        $class = $this->getStrClass();
        if (empty($class) || !class_exists($class)) {
            return "";
        }

        $adminWidgetAmtasks = new $class;
        return $adminWidgetAmtasks->getWidgetName();
    }

    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName()
    {
        return 'Dashboard Widget "' . $this->getWidgetClassName() . '"';
    }

    /**
     * Looks up all widgets available in the filesystem.
     * ATTENTION: returns the class-name representation of a file, NOT the filename itself.
     *
     * @return Adminwidget[]
     */
    public static function getListOfWidgetsAvailable()
    {

        $arrWidgets = Resourceloader::getInstance()->getFolderContent("/admin/widgets", array(".php"));

        $arrReturn = array();
        foreach ($arrWidgets as $strPath => $strFilename) {
            $objInstance = Classloader::getInstance()->getInstanceFromFilename($strPath, Adminwidget::class, AdminwidgetInterface::class);

            if ($objInstance !== null) {
                $arrReturn[] = $objInstance;
            }
        }

        uasort($arrReturn, function (Adminwidget $w1, Adminwidget $w2) {
            if ($w1->getModuleName() != $w2->getModuleName()) {
                return strcmp($w1->getModuleName(), $w2->getModuleName());
            }
            return strcmp($w1->getWidgetName(), $w2->getWidgetName());
        });

        return $arrReturn;
    }


    /**
     * Creates the concrete widget represented by this model-element
     *
     * @return AdminwidgetInterface|Adminwidget
     */
    public function getConcreteAdminwidget()
    {
        /** @var ObjectBuilder $builder */
        $builder = Carrier::getInstance()->getContainer()->offsetGet(\Kajona\System\System\ServiceProvider::STR_OBJECT_BUILDER);

        /** @var $objWidget AdminwidgetInterface|Adminwidget */
        $objWidget = $builder->factory($this->strClass);
        //Pass the field-values
        $objWidget->setFieldsAsString($this->getStrContent());
        $objWidget->setSystemid($this->getSystemid());
        return $objWidget;
    }




    /**
     * Searches the root-node for a users' widgets.
     * If not given, the node is created on the fly.
     * Those nodes are required to ensure a proper sort-handling on the system-table
     *
     * @param string $strUserid
     * @param string $strAspectId
     *
     * @deprecated
     *
     * @return string
     * @throws \Kajona\System\System\Exception
     * @throws \Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException
     * @static
     */
    public static function getWidgetsRootNodeForUser($strUserid, $strAspectId = "")
    {

        if ($strAspectId == "") {
            $strAspectId = SystemAspect::getCurrentAspectId();
        }

        $strQuery = "SELECT system_id
        			  FROM agp_dashboard,
        			  	   agp_system
        			 WHERE dashboard_id = system_id
        			   AND system_prev_id = ?
        			   AND dashboard_user = ?
        			   AND dashboard_aspect = ?
        			   AND dashboard_class = ?
        	     ORDER BY system_sort ASC ";

        $arrRow = Carrier::getInstance()->getObjDB()->getPRow($strQuery, array(
            SystemModule::getModuleByName("dashboard")->getSystemid(),
            $strUserid,
            $strAspectId,
            "root_node"
        ));

        if (!isset($arrRow["system_id"]) || !validateSystemid($arrRow["system_id"])) {
            //Create a new root-node on the fly
            $objWidget = new DashboardWidget();
            $objWidget->setStrClass("root_node");

            ServiceLifeCycleFactory::getLifeCycle(get_class($objWidget))->update($objWidget, SystemModule::getModuleByName("dashboard")->getSystemid());

            $strReturnId = $objWidget->getSystemid();
        } else {
            $strReturnId = $arrRow["system_id"];
        }

        return $strReturnId;
    }


    /**
     * @param string $strColumn
     *
     * @return void
     */
    public function setStrColumn($strColumn)
    {
        $this->strColumn = $strColumn;
    }

    /**
     * @return string
     */
    public function getStrColumn()
    {
        return $this->strColumn;
    }

    /**
     * @param string $strClass
     *
     * @return void
     */
    public function setStrClass($strClass)
    {
        $this->strClass = $strClass;
    }

    /**
     * @return string
     */
    public function getStrClass()
    {
        return $this->strClass;
    }

    /**
     * @param string $strContent
     *
     * @return void
     */
    public function setStrContent($strContent)
    {
        $this->strContent = $strContent;
    }

    /**
     * @return string
     */
    public function getStrContent()
    {
        return $this->strContent;
    }
}
