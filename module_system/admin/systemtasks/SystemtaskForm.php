<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Admin\Systemtasks;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryCheckbox;
use Kajona\System\Admin\Formentries\FormentryCheckboxarray;
use Kajona\System\Admin\Formentries\FormentryDate;
use Kajona\System\Admin\Formentries\FormentryDatetime;
use Kajona\System\Admin\Formentries\FormentryDropdown;
use Kajona\System\Admin\Formentries\FormentryHidden;
use Kajona\System\Admin\Formentries\FormentryPassword;
use Kajona\System\Admin\Formentries\FormentryText;
use Kajona\System\Admin\Formentries\FormentryTextarea;

/**
 * Sample system task to show available form types
 *
 * @package module_system
 */
class SystemtaskForm extends SystemtaskBase implements AdminSystemtaskInterface, ApiSystemTaskInterface
{
    /**
     * @inheritdoc
     */
    public function getGroupIdentifier()
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public function getStrInternalTaskName()
    {
        return "form";
    }

    /**
     * @inheritdoc
     */
    public function getStrTaskName()
    {
        return $this->getLang("systemtask_form_name");
    }

    /**
     * @inheritdoc
     */
    public function executeTask()
    {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function execute($body)
    {
        return "Payload: " . json_encode($body, JSON_PRETTY_PRINT);
    }

    /**
     * @inheritdoc
     */
    public function getAdminForm()
    {
        $form = new AdminFormgenerator("", null);
        $form->setGroupStyle(AdminFormgenerator::GROUP_TYPE_TABS);
        $form->createGroup("first", "First");
        $form->createGroup("second", "Second");

        $field = new FormentryCheckbox("", "checkbox");
        $field->setStrLabel("Checkbox");
        $form->addField($field);
        $form->addFieldToGroup($field, "first");

        $field = new FormentryCheckboxarray("", "checkboxarray");
        $field->setStrLabel("Checkbox-Array");
        $field->setArrKeyValues(["foo", "bar"]);
        $field->setStrValue("foo");
        $form->addField($field);
        $form->addFieldToGroup($field, "first");

        $field = new FormentryDate("", "date");
        $field->setStrLabel("Date");
        $form->addField($field);
        $form->addFieldToGroup($field, "first");

        $field = new FormentryDatetime("", "datetime");
        $field->setStrLabel("Date-Time");
        $form->addField($field);
        $form->addFieldToGroup($field, "first");

        $field = new FormentryDropdown("", "dropdown");
        $field->setStrLabel("Dropdown");
        $field->setArrKeyValues(["foo", "bar"]);
        $field->setStrValue("foo");
        $form->addField($field);
        $form->addFieldToGroup($field, "second");

        $field = new FormentryHidden("", "hidden");
        $form->addField($field);
        $form->addFieldToGroup($field, "second");

        $field = new FormentryPassword("", "password");
        $field->setStrLabel("Password");
        $form->addField($field);
        $form->addFieldToGroup($field, "second");

        $field = new FormentryText("", "text");
        $field->setStrLabel("Text");
        $form->addField($field);
        $form->addFieldToGroup($field, "second");

        $field = new FormentryTextarea("", "textarea");
        $field->setStrLabel("Textarea");
        $form->addField($field);
        $form->addFieldToGroup($field, "second");

        return $form;
    }

    /**
     * @inheritdoc
     */
    public function getSubmitParams()
    {
        return "";
    }
}
