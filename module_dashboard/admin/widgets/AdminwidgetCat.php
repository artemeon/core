<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Dashboard\Admin\Widgets;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryRadiogroup;
use Kajona\System\System\Link;
use Kajona\System\System\Resourceloader;

/**
 * @package module_dashboard
 */
class AdminwidgetCat extends Adminwidget implements AdminwidgetInterface
{

    /**
     * @var array
     */
    private $arrCats = [];

    /**
     * @var array
     */
    private $arrGifs = ["acrobat.gif", "banjo.gif", "burp.gif", "facepalm.gif", "knead.gif", "meal.gif", "popcorn.gif", "sleepy.gif"];

    /**
     * Basic constructor, registers the fields to be persisted and loaded
     */
    public function __construct()
    {
        parent::__construct();
        //register the fields to be persisted and loaded
        $this->setPersistenceKeys(array("cat"));

        $path = _webpath_.Resourceloader::getInstance()->getWebPathForModule("module_dashboard")."/img/AdminwidgetCat";
        foreach ($this->arrGifs as $img) {
            $this->arrCats[] = "<img src='$path/$img' style='float: right;'/>";
        }
    }

    /**
     * Allows the widget to add additional fields to the edit-/create form.
     * Use the toolkit class as usual.
     *
     * @return string
     */
    public function getEditForm()
    {
        $strReturn = $this->objToolkit->formInputRadiogroup("cat", $this->arrCats, $this->getLang("cats"), $this->getFieldValue("cat"));

        return $strReturn;
    }

    /**
     * Allows the widget to add additional fields to the edit-/create form.
     * Use the toolkit class as usual.
     *
     * @return string
     */
    public function getEditFormNew(AdminFormgenerator $form)
    {
        $form->addField(new FormentryRadiogroup("cat", ""), "")
            ->setBitMandatory(true)
            ->setStrLabel( $this->getLang("cat_select"))
            ->setArrKeyValues($this->arrCats);
    }

    public function getEditDynamicForm()
    {
        // create the form
        $objFormgenerator = new AdminFormgenerator("editcatwidget", null);


        $objFormgenerator->setStrOnSubmit("require('dashboard').updateWidget(this, '{$this->getSystemid()}');return false");

        $this->getEditFormNew($objFormgenerator);

        //render filter
        $strReturn = $objFormgenerator->renderForm(Link::getLinkAdminHref("dashboard", "updateWidgetContent"), AdminFormgenerator::BIT_BUTTON_SUBMIT);


        return $strReturn;
    }

    /**
     * This method is called, when the widget should generate it's content.
     * Return the complete content using the methods provided by the base class.
     * Do NOT use the toolkit right here444!
     *
     * @return string
     */
    public function getWidgetOutput()
    {
        if ($this->getFieldValue("cat") == "") {
            return $this->getEditDynamicForm();
        }
        $strReturn = '<div id="cat-cage" style="height: 150px; width: 100%; background-color: white">';
        $strReturn .= $this->widgetText($this->arrCats[$this->getFieldValue("cat")]);
        $strReturn .= '</div><div style="clear: both;"></div>';

        return $strReturn;
    }

    /**
     * Return a short (!) name of the widget.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return $this->getLang("cat_name");
    }

}
