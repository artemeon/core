<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                        		*
********************************************************************************************************/

namespace Kajona\Dashboard\Admin\Widgets;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryTextarea;

/**
 * @package module_dashboard
 *
 */
class AdminwidgetNote extends Adminwidget implements AdminwidgetInterface
{

    /**
     * Basic constructor, registers the fields to be persisted and loaded
     *
     */
    public function __construct()
    {
        parent::__construct();
        //register the fields to be persisted and loaded
        $this->setPersistenceKeys(array("content"));
    }

    /**
     * Allows the widget to add additional fields to the edit-/create form.
     * Use the toolkit class as usual.
     *
     * @return string
     */
    public function getEditForm()
    {
        $strReturn = "";
        $strReturn .= $this->objToolkit->formInputTextArea("content", $this->getLang("note_content"), $this->getFieldValue("content"));
        return $strReturn;
    }

    /**
     * Allows the widget to add additional fields to the edit-/create form.
     * Use the toolkit class as usual.
     *
     * @param AdminFormgenerator $form
     * @return string
     */
    public function getEditFormContent(AdminFormgenerator $form)
    {
        $form->addField(new FormentryTextarea("content", ""), "")
        ->setStrValue($this->getFieldValue("content"));
    }

    /**
     * This method is called, when the widget should generate it's content.
     * Return the complete content using the methods provided by the base class.
     * Do NOT use the toolkit right here!
     *
     * @return string
     */
    public function getWidgetOutput()
    {
        if ($this->getFieldValue("content") == "") {
            return $this->getEditWidgetForm();
        }

        return $this->widgetText(nl2br($this->getFieldValue("content")));
    }

    /**
     * Return a short (!) name of the widget.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return $this->getLang("note_name");
    }

    /**
     * @inheritdoc
     */
    public function getWidgetDescription()
    {
        return $this->getLang("note_description");
    }

}

