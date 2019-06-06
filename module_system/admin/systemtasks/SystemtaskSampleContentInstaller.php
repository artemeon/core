<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

namespace Kajona\System\Admin\Systemtasks;

use Kajona\Installer\System\SamplecontentInstallerHelper;
use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryDropdown;
use Kajona\System\System\SamplecontentInstallerInterface;

/**
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class SystemtaskSampleContentInstaller extends SystemtaskBase implements AdminSystemtaskInterface, ApiSystemTaskInterface
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
        return "samplecontent_installer";
    }

    /**
     * @inheritdoc
     */
    public function getStrTaskName()
    {
        return $this->getLang("systemtask_samplecontent_installer_name");
    }

    /**
     * @inheritdoc
     */
    public function executeTask()
    {
    }

    /**
     * @inheritDoc
     */
    public function execute($body)
    {
        $class = $body["sc_installer"] ?? null;

        if (class_exists($class)) {
            $installer = new $class;
            if ($installer instanceof SamplecontentInstallerInterface) {
                return SamplecontentInstallerHelper::install($installer);
            }
        }

        return $this->objToolkit->getTextRow($this->getLang("systemtask_samplecontent_installer_error"));
    }

    /**
     * @inheritdoc
     */
    public function getAdminForm()
    {
        $form = new AdminFormgenerator("", null);

        $installers = SamplecontentInstallerHelper::getSamplecontentInstallers();
        $options = [];
        foreach ($installers as $installer) {
            $options[get_class($installer)] = get_class($installer);
        }

        $field = new FormentryDropdown("", "sc_installer");
        $field->setStrLabel($this->getLang("systemtask_samplecontent_installer"));
        $field->setArrKeyValues($options);
        $field->setStrValue(current(array_keys($options)));
        $form->addField($field);

        return $form;
    }
}
