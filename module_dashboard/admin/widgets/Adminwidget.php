<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *    $Id$                                        *
 ********************************************************************************************************/

namespace Kajona\Dashboard\Admin\Widgets;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\ToolkitAdmin;
use Kajona\System\System\Carrier;
use Kajona\System\System\Database;
use Kajona\System\System\Filesystem;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Resourceloader;

/**
 * Base class to be extended by all adminwidgets.
 * Holds a few methods to create a framework-like behaviour
 *
 * @package module_dashboard
 * @author sidler@mulchprod.de
 */
abstract class Adminwidget
{
    const STR_IMG_SOURCE_PATH = "/admin/widgets/images/";
    const STR_IMG_FILE_PATH = "files/extract/core/module_dashboard/admin/widgets/images/";

    /**
     * @var string
     */
    private $imgFileName = "default.png";

    /**
     * @var string
     */
    private $moduleName = "module_dashboard";
    private $arrFields = array();
    private $arrPersistenceKeys = array();
    private $strSystemid = "";

    /**
     * instance of Database
     *
     * @var Database
     */
    private $objDb;

    /**
     *
     * @var ToolkitAdmin
     */
    protected $objToolkit;

    /**
     * instance of Lang
     *
     * @var Lang
     */
    private $objLang;

    private $bitBlockSessionClose = false;

    public function __construct()
    {

        $this->objDb = Carrier::getInstance()->getObjDB();
        $this->objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $this->objLang = Carrier::getInstance()->getObjLang();

    }

    /**
     * Use this method to tell the widgets whicht keys of the $arrFields should
     * be loaded from and be persitsted to the database
     *
     * @param array $arrKeys
     */
    final protected function setPersistenceKeys($arrKeys)
    {
        $this->arrPersistenceKeys = $arrKeys;
    }

    /**
     * Use this method to add a key
     *
     * @param string $strKey
     */
    final protected function addPersistenceKey($strKey)
    {
        $this->arrPersistenceKeys[] = $strKey;
    }

    /**
     * This method invokes the rendering of the widget. Calls
     * the implementing class.
     *
     * @return string
     */
    final public function generateWidgetOutput(): string
    {
        try {
            return $this->getWidgetOutput();
        } catch (\Throwable $e) {
            return $this->objToolkit->warningBox($e->getMessage(), 'alert-info');
        }
    }

    /**
     * Overwrite this method!
     *
     * @return string
     * @see AdminwidgetInterface::getWidgetOutput()
     */
    public function getWidgetOutput()
    {
        return "";
    }

    /**
     * Returns the current fields as a serialized array.
     *
     * @return string
     */
    final public function getFieldsAsString()
    {
        $arrFieldsToPersist = array();
        foreach ($this->arrPersistenceKeys as $strOneKey) {
            $arrFieldsToPersist[$strOneKey] = $this->getFieldValue($strOneKey);
        }

        $strArraySerialized = serialize($arrFieldsToPersist);
        return $strArraySerialized;
    }

    /**
     * Takes the current fields serialized and retransforms the contents
     *
     * @param string $strContent
     */
    final public function setFieldsAsString($strContent)
    {
        $arrFieldsToLoad = unserialize(stripslashes($strContent));
        foreach ($this->arrPersistenceKeys as $strOneKey) {
            if (isset($arrFieldsToLoad[$strOneKey])) {
                $this->setFieldValue($strOneKey, $arrFieldsToLoad[$strOneKey]);
            }
        }
    }

    /**
     * Pass an array of values. The method looks for fields to be loaded into
     * the internal arrays.
     *
     * @param array $arrFields
     */
    final public function loadFieldsFromArray($arrFields)
    {
        foreach ($this->arrPersistenceKeys as $strOneKey) {
            if (isset($arrFields[$strOneKey])) {
                $this->setFieldValue($strOneKey, $arrFields[$strOneKey]);
            } else {
                $this->setFieldValue($strOneKey, "");
            }
        }
    }

    /**
     * Loads a text-fragement from the textfiles
     *
     * @param string $strKey
     * @param array $arrParameters
     *
     * @return string
     */
    final public function getLang($strKey, $arrParameters = array())
    {
        return $this->objLang->getLang($strKey, "adminwidget", $arrParameters);
    }

    /**
     * Looks up a value in the fields-array
     *
     * @param string $strFieldName
     *
     * @return mixed
     */
    final protected function getFieldValue($strFieldName)
    {
        if (isset($this->arrFields[$strFieldName])) {
            return $this->arrFields[$strFieldName];
        } else {
            return "";
        }
    }

    /**
     * Sets the value of a given field
     *
     * @param string $strFieldName
     * @param mixed $mixedValue
     */
    final protected function setFieldValue($strFieldName, $mixedValue)
    {
        $this->arrFields[$strFieldName] = $mixedValue;
    }

    /**
     * Sets the systemid of the current widget
     *
     * @param string $strSystemid
     */
    final public function setSystemid($strSystemid)
    {
        $this->strSystemid = $strSystemid;
    }

    /**
     * Returns the systemid of the current widget
     *
     * @return string
     */
    final public function getSystemid()
    {
        return $this->strSystemid;
    }

    /**
     * This method controls the elements-section used by the toolkit to render
     * the outer parts of the widget.
     * Overwrite this method in cases you need some special layouting - in most cases this shouldn't be
     * necessary.
     *
     * @return string
     */
    public function getLayoutSection()
    {
        return "adminwidget_widget";
    }

    //--- Layout/Content functions --------------------------------------------------------------------------

    /**
     * Use this method to place a formatted text in the widget
     *
     * @param string $strText
     *
     * @return string
     */
    final protected function widgetText($strText)
    {
        return $this->objToolkit->getTextRow($strText, "widgetText");
    }

    /**
     * Use this method to generate a separator / divider to split up
     * the widget in logical sections.
     *
     * @return string
     */
    final protected function widgetSeparator()
    {
        return $this->objToolkit->divider();
    }

    public function setBitBlockSessionClose($bitBlockSessionClose)
    {
        $this->bitBlockSessionClose = $bitBlockSessionClose;
    }

    public function getBitBlockSessionClose()
    {
        return $this->bitBlockSessionClose;
    }

    public function getWidgetNameAdditionalContent()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getWidgetDescription()
    {
        return "Artemeon Widget";
    }

    /**
     * @return string
     */
    public function getWidgetImg()
    {
        $fileName = $this->getImgFileName();

        $path = Resourceloader::getInstance()->getAbsolutePathForModule($this->getModuleName()) . self::STR_IMG_SOURCE_PATH . $fileName;
        $fs = new Filesystem();
        if (!file_exists(_realpath_ . self::STR_IMG_FILE_PATH . $fileName)) {
            $fs->fileCopy($path, self::STR_IMG_FILE_PATH . $fileName, true);
        }
        return self::STR_IMG_FILE_PATH . $fileName;
    }

    /**
     * @return mixed
     * @throws \Kajona\System\System\Exception
     */
    public function getEditWidgetForm()
    {
        // create the form
        $objFormgenerator = new AdminFormgenerator("edit" . $this->getWidgetName(), null);

$strAdditionalContent = $this->getWidgetNameAdditionalContent();
        if (!empty($strAdditionalContent)) {
            $objFormgenerator->setStrOnSubmit("Dashboard.updateWidget(this, '{$this->getSystemid()}', true);return false");
        } else {
            $objFormgenerator->setStrOnSubmit("Dashboard.updateWidget(this, '{$this->getSystemid()}');return false");
        }

        $this->getEditFormContent($objFormgenerator);

        //render filter
        $strReturn = $objFormgenerator->renderForm(Link::getLinkAdminHref("dashboard", "updateWidgetContent"), AdminFormgenerator::BIT_BUTTON_SUBMIT);

        return $strReturn;
    }

    /**
     * Should return false if a widget has not getEditFormContent method
     *
     * @return bool
     */
    public static function isEditable()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getImgFileName(): string
    {
        return $this->imgFileName;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

}
