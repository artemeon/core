<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

namespace Kajona\Devops\System;

use Doctrine\Common\Cache\Cache as DoctrineCache;
use Kajona\System\System\CacheManager;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\SysteminfoInterface;


/**
 * General information regarding the current timezone environment
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.5
 */
class DevopsPluginCache implements SysteminfoInterface
{
    /**
     * Returns the title of the info-block
     *
     * @return string
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("cache", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {
        $objLang = Carrier::getInstance()->getObjLang();
        $arrReturn = array();

        $arrTypes = array(
            CacheManager::TYPE_APC => $objLang->getLang("cache_apc", "devops"),
            CacheManager::TYPE_FILESYSTEM => $objLang->getLang("cache_filesystem", "devops"),
        );

        $arrKeys = array(
            DoctrineCache::STATS_HITS => $objLang->getLang("cache_hits", "devops"),
            DoctrineCache::STATS_MISSES => $objLang->getLang("cache_misses", "devops"),
            DoctrineCache::STATS_UPTIME => $objLang->getLang("cache_uptime", "devops"),
            DoctrineCache::STATS_MEMORY_USAGE => $objLang->getLang("cache_usage", "devops"),
            DoctrineCache::STATS_MEMORY_AVAILABLE => $objLang->getLang("cache_available", "devops"),
        );

        foreach ($arrTypes as $intType => $strType) {
            $arrStats = CacheManager::getInstance()->getStats($intType);
            if (!empty($arrStats)) {
                $arrReturn[] = array($strType, "");
                foreach ($arrKeys as $intKey => $strDescription) {
                    if (isset($arrStats[$intKey])) {
                        if ($intKey == DoctrineCache::STATS_MEMORY_USAGE || $intKey == DoctrineCache::STATS_MEMORY_AVAILABLE) {
                            $strValue = bytesToString($arrStats[$intKey]);
                        } elseif ($intKey == DoctrineCache::STATS_UPTIME) {
                            $strValue = dateToString(new Date($arrStats[$intKey]));
                        } else {
                            $strValue = $arrStats[$intKey];
                        }
                        $arrReturn[] = array($strDescription, $strValue);
                    }
                }
            }
        }

        return $arrReturn;
    }

    /**
     * Returns the name of extension/plugin the objects wants to contribute to.
     *
     * @return string
     */
    public static function getExtensionName()
    {
        return SysteminfoInterface::STR_EXTENSION_POINT;
    }

}
