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
use Kajona\System\Admin\Formentries\FormentryCheckboxarray;
use Kajona\System\System\Exception;
use Kajona\System\System\Link;

/**
 * @package module_dashboard
 */
class AdminwidgetTodo extends Adminwidget implements AdminwidgetInterface
{
    /**
     * @var string
     */
    private $imgFileName = 'todo.png';

    private const SELECTED_KEY = 'selected';

    public function __construct()
    {
        parent::__construct();

        //register the fields to be persisted and loaded
        $this->setPersistenceKeys([self::SELECTED_KEY]);
    }

    /**
     * @param AdminFormgenerator $form
     * @throws Exception
     */
    public function getEditFormContent(AdminFormgenerator $form)
    {
        $arrCategories = TodoRepository::getAllCategories();

        $arrCheckboxes = [];
        foreach ($arrCategories as $strTitle => $arrRows) {
            $strKey = md5($strTitle);
            $arrCheckboxes[$strKey] = $strTitle;
        }

        $form->addField(new FormentryCheckboxarray('', self::SELECTED_KEY), '')
            ->setStrLabel($strTitle)
            ->setArrKeyValues($arrCheckboxes)
            ->setStrValue($this->getFieldValue(self::SELECTED_KEY));
    }

    /**
     * This method is called, when the widget should generate it's content.
     * Return the complete content using the methods provided by the base class.
     * Do NOT use the toolkit right here!
     *
     * @return string
     * @throws Exception
     */
    public function getWidgetOutput()
    {
        $strReturn = '';

        $arrCategories = TodoRepository::getAllCategories();

        if (empty($arrCategories)) {
            return $this->getEditWidgetForm();
        }

        $bitConfiguration = !empty($this->getFieldValue(self::SELECTED_KEY));
        $arrValues = array();

        foreach ($arrCategories as $strProviderName => $arrTaskCategories) {
            if (empty($arrTaskCategories)) {
                continue;
            }

            // check whether the category is enabled for the user. If the user has not configured the widget all
            // categories are displayed
            if ($bitConfiguration && !array_key_exists(md5($strProviderName), $this->getFieldValue(self::SELECTED_KEY))) {
                continue;
            }

            foreach ($arrTaskCategories as $strKey => $strCategoryName) {
                $arrTodos = TodoRepository::getOpenTodos($strKey);

                if (count($arrTodos) > 0) {
                    $strLink = Link::getLinkAdmin('dashboard', 'todo', 'listfilter_category=' . $strKey, count($arrTodos));
                    $arrValues[] = array($strProviderName, $strCategoryName, $strLink);
                }
            }
        }

        if (empty($arrValues)) {
            $strReturn .= $this->objToolkit->warningBox($this->getLang('no_tasks_available'), 'alert-success');
            return $strReturn;
        }

        $strReturn .= $this->objToolkit->dataTable(array(), $arrValues);

        return $strReturn;
    }

    /**
     * Return a short (!) name of the widget.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return $this->getLang('todo_name');
    }

    /**
     * @inheritdoc
     */
    public function getWidgetDescription()
    {
        return $this->getLang('todo_description');
    }

    /**
     * @return string
     */
    public function getImgFileName(): string
    {
        return $this->imgFileName;
    }
}
