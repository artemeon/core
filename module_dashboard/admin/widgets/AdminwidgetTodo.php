<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$		                        *
********************************************************************************************************/

namespace Kajona\Dashboard\Admin\Widgets;

use Kajona\Dashboard\System\TodoRepository;
use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryCheckbox;
use Kajona\System\System\Link;

/**
 * @package module_dashboard
 */
class AdminwidgetTodo extends Adminwidget implements AdminwidgetInterface
{
    /**
     * @var string
     */
    private $imgFileName = "todo.png";

    public function __construct()
    {
        parent::__construct();

        //register the fields to be persisted and loaded
        $arrCategories = TodoRepository::getAllCategories();
        $arrKeys = array();
        foreach ($arrCategories as $strTitle => $arrRows) {
            $arrKeys[] = md5($strTitle);
        }

        $this->setPersistenceKeys($arrKeys);
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
        $arrCategories = TodoRepository::getAllCategories();
        foreach ($arrCategories as $strTitle => $arrRows) {
            $strKey = md5($strTitle);
            $strReturn .= $this->objToolkit->formInputCheckbox($strKey, $strTitle, $this->getFieldValue($strKey));
        }

        return $strReturn;
    }

    /**
     * @param AdminFormgenerator $form
     */
    public function getEditFormContent(AdminFormgenerator $form)
    {
        $arrCategories = TodoRepository::getAllCategories();

        foreach ($arrCategories as $strTitle => $arrRows) {
            $strKey = md5($strTitle);
            $form->addField(new FormentryCheckbox($strKey, ""), "")
                ->setStrLabel($strTitle)
                ->setStrValue($this->getFieldValue($strKey));
        }
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
        $strReturn = "";

        $arrCategories = TodoRepository::getAllCategories();

        if (empty($arrCategories)) {
            return $this->getEditWidgetForm();
        }

        $bitConfiguration = $this->hasConfiguration();
        $arrValues = array();

        foreach ($arrCategories as $strProviderName => $arrTaskCategories) {
            if (empty($arrTaskCategories)) {
                continue;
            }

            // check whether the category is enabled for the user. If the user has not configured the widget all
            // categories are displayed
            if ($bitConfiguration && !$this->getFieldValue(md5($strProviderName))) {
                continue;
            }

            foreach ($arrTaskCategories as $strKey => $strCategoryName) {
                $arrTodos = TodoRepository::getOpenTodos($strKey);

                if (count($arrTodos) > 0) {
                    $strLink = Link::getLinkAdmin("dashboard", "todo", "listfilter_category=".$strKey, count($arrTodos));
                    $arrValues[] = array($strProviderName, $strCategoryName, $strLink);
                }
            }
        }

        if (empty($arrValues)) {
            $strReturn .= $this->objToolkit->warningBox($this->getLang("no_tasks_available"), "alert-success");
            return $strReturn;
        } else {
            $strReturn .= $this->objToolkit->dataTable(array(), $arrValues);
        }

        return $strReturn;
    }

    /**
     * Return a short (!) name of the widget.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return $this->getLang("todo_name");
    }

    protected function hasConfiguration()
    {
        $arrCategories = TodoRepository::getAllCategories();
        foreach ($arrCategories as $strTitle => $arrRows) {
            $strKey = md5($strTitle);
            $strValue = $this->getFieldValue($strKey);
            if ($strValue !== "") {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getWidgetDescription()
    {
        return $this->getLang("todo_description");
    }

    /**
     * @return string
     */
    public function getImgFileName(): string
    {
        return $this->imgFileName;
    }
}
