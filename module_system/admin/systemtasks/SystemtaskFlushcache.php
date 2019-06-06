<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                        *
********************************************************************************************************/

namespace Kajona\System\Admin\Systemtasks;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryDropdown;
use Kajona\System\System\CacheManager;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;

/**
 * Flushes the entries from the systemwide cache
 *
 * @package module_system
 */
class SystemtaskFlushcache extends SystemtaskBase implements AdminSystemtaskInterface, ApiSystemTaskInterface
{
    /**
     * @inheritdoc
     */
    public function getGroupIdentifier()
    {
        return "cache";
    }

    /**
     * @inheritdoc
     */
    public function getStrInternalTaskName()
    {
        return "flushcache";
    }

    /**
     * @inheritdoc
     */
    public function getStrTaskName()
    {
        return $this->getLang("systemtask_flushcache_name");
    }

    /**
     * @inheritdoc
     */
    public function executeTask()
    {

        if (!SystemModule::getModuleByName("system")->rightRight2()) {
            return $this->getLang("commons_error_permissions");
        }

        //increase the cachebuster, so browsers are forced to reload JS and CSS files
        $objCachebuster = SystemSetting::getConfigByName("_system_browser_cachebuster_");
        $objCachebuster->setStrValue((int)$objCachebuster->getStrValue() + 1);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objCachebuster))->update($objCachebuster);

        $intType = (int) $this->getParam("cache_source");
        $strNamespace = (int) $this->getParam("cache_namespace");
        if ($intType > 0) {
            CacheManager::getInstance()->flushCache($intType, $strNamespace, true);

            return $this->objToolkit->getTextRow($this->getLang("systemtask_flushcache_success"));
        }

        return $this->objToolkit->getTextRow($this->getLang("systemtask_flushcache_error"));
    }

    /**
     * @inheritDoc
     */
    public function execute($body)
    {
        $type = isset($body["cache_source"]) ? intval($body["cache_source"]) : null;
        $namespace = isset($body["cache_namespace"]) ? intval($body["cache_namespace"]) : CacheManager::NS_BOOTSTRAP;

        CacheManager::getInstance()->flushCache($type, $namespace);

        return $this->objToolkit->getTextRow($this->getLang("systemtask_flushcache_success"));
    }

    /**
     * @inheritdoc
     */
    public function getAdminForm()
    {
        $form = new AdminFormgenerator("", null);

        // show dropdown to select cache-source
        $sources = CacheManager::getAvailableDriver();
        $options = [];
        $options[CacheManager::TYPE_APC | CacheManager::TYPE_FILESYSTEM | CacheManager::TYPE_PHPFILE] = $this->getLang("systemtask_flushcache_all");
        foreach ($sources as $value => $label) {
            $options[$value] = $label;
        }

        $field = new FormentryDropdown("", "cache_source");
        $field->setStrLabel($this->getLang("systemtask_cacheSource_source"));
        $field->setArrKeyValues($options);
        $field->setStrValue(current(array_keys($options)));
        $form->addField($field);

        // show dropdown to select cache-namespace
        $namespaces = CacheManager::getAvailableNamespace();
        $options = [];
        foreach ($namespaces as $value => $label) {
            $options[$value] = $label;
        }

        $field = new FormentryDropdown("", "cache_namespace");
        $field->setStrLabel($this->getLang("systemtask_cacheSource_namespace"));
        $field->setArrKeyValues($options);
        $field->setStrValue(CacheManager::NS_GLOBAL);
        $form->addField($field);

        return $form;
    }

    /**
     * @inheritdoc
     */
    public function getSubmitParams()
    {
        $arrParams = array(
            "cache_source" => $this->getParam("cache_source"),
            "cache_namespace" => $this->getParam("cache_namespace"),
        );
        return "&" . http_build_query($arrParams, "", "&");
    }
}
