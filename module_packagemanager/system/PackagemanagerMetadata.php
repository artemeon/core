<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                  *
********************************************************************************************************/

namespace Kajona\Packagemanager\System;

use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\PharModule;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Zip;
use Phar;

/**
 * Helper class, used to read the metadata-files from packages or the filesystem.
 * Read access only!
 *
 * @author sidler@mulchprod.de
 * @since 4.0
 * @package module_packagemanager
 */
class PackagemanagerMetadata implements AdminListableInterface, \JsonSerializable
{

    private $strTitle;

    /**
     * @var
     * @deprecated
     */
    private $strTarget;
    private $strDescription;
    private $strVersion;
    private $strAuthor;

    /**
     * @var string
     * @deprecated
     */
    private $strType = "MODULE";
    private $bitProvidesInstaller;
    private $arrRequiredModules = array();
    private $arrScreenshots = array();
    private $constants = array();

    private $strContentprovider;
    private $strPath;

    private $bitIsPhar = false;


    /**
     * Returns the icon the be used in lists.
     * Please be aware, that only the filename should be returned, the wrapping by getImageAdmin() is
     * done afterwards.
     *
     * @return string the name of the icon, not yet wrapped by getImageAdmin(). Alternatively, you may return an array containing
     *         [the image name, the alt-title]
     */
    public function getStrIcon()
    {
        return "icon_module";
    }

    /**
     * @return mixed
     */
    public function getStrDisplayName()
    {
        return $this->getStrTitle();
    }

    /**
     * Only to remain compatbible with the common list rendering
     *
     * @return int
     */
    public function getIntRecordDeleted()
    {
        return 0;
    }

    /**
     * In nearly all cases, the additional info is rendered left to the action-icons.
     *
     * @return string
     */
    public function getStrAdditionalInfo()
    {
        return Carrier::getInstance()->getObjLang()->getLang("type_".$this->getStrType(), "packagemanager").", V ".$this->getStrVersion();
    }

    /**
     * If not empty, the returned string is rendered below the common title.
     *
     * @return string
     */
    public function getStrLongDescription()
    {
        return $this->getStrDescription();
    }

    /**
     * @return mixed
     */
    public function getSystemid()
    {
        return $this->getStrTitle();
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return "Title: ".$this->getStrTitle()." Version: ".$this->getStrVersion()." Type: ".$this->getStrType()." Target: ".$this->getStrTarget()." Dependencies: ".print_r($this->getArrRequiredModules(), true);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $manager = new PackagemanagerManager();
        $handler = $manager->getPackageManagerForPath($this->getStrPath());

        return [
            "title" => $this->strTitle,
            "description" => $this->strDescription,
            "versionAvailable" => $this->strVersion,
            "author" => $this->strAuthor,
            "requires" => (object) $this->arrRequiredModules,
            "path" => $this->strPath,
            "providesInstaller" => $this->bitProvidesInstaller,
            "versionInstalled" => $handler->getVersionInstalled(),
            "isInstallable" => $handler->isInstallable()
        ];
    }

    /**
     * @param string $strPath
     *
     * @return void
     * @throws Exception
     */
    public function autoInit($strPath)
    {
        if (StringUtil::substring($strPath, -4) == ".zip") {
            $this->initFromPackage($strPath);
        }
        elseif (PharModule::isPhar($strPath)) {
            $this->initFromPhar($strPath);
        }
        else {
            $this->initFromFilesystem($strPath);
        }

        $this->setStrPath($strPath);
    }

    /**
     * Reads the metadata-file saved with along with a packages located at the filesystem.
     *
     * @param string $strPackage
     *
     * @throws Exception
     * @return void
     */
    private function initFromFilesystem($strPackage)
    {

        if (!is_file(_realpath_.$strPackage."/metadata.xml")) {
            throw new Exception("file not found: "._realpath_.$strPackage."/metadata.xml", Exception::$level_ERROR);
        }

        $strMetadata = file_get_contents(_realpath_.$strPackage."/metadata.xml");
        $this->parseXMLDocument($strMetadata);
    }

    /**
     * @param string $strPackage
     *
     * @throws Exception
     */
    private function initFromPhar($strPackage)
    {
        $this->bitIsPhar = true;

        if (substr($strPackage, 0, 7) == "phar://") {
            $strFile = _realpath_.substr($strPackage, 7);
        } else {
            $strFile = _realpath_.$strPackage;
        }

        $strMetadata = "";
        //if its a project phar, we need to set another alias
        if (StringUtil::indexOf($strPackage, "/project") !== false) {
            //load the metadata without registering the phar, this could lead to multiple registered aliases
            $strMetadata = file_get_contents("phar://{$strFile}/metadata.xml");

        } else {
            $objPhar = new Phar($strFile);
            if (isset($objPhar["metadata.xml"])) {
                $strMetadata = file_get_contents($objPhar["metadata.xml"]->getPathname());
            }
        }

        if ($strMetadata == "") {
            throw new Exception("file not found: "._realpath_.$strPackage."/metadata.xml", Exception::$level_ERROR);
        }
        $this->parseXMLDocument($strMetadata);
    }

    /**
     * Reads the metadata-file from a zipped package.
     *
     * @param string $strPackagePath
     *
     * @throws Exception
     * @return void
     */
    private function initFromPackage($strPackagePath)
    {
        if (!is_file(_realpath_.$strPackagePath)) {
            throw new Exception("file not found: "._realpath_.$strPackagePath, Exception::$level_ERROR);
        }

        $objZip = new Zip();
        $strMetadata = $objZip->getFileFromArchive($strPackagePath, "/metadata.xml");

        if ($strMetadata === false) {
            throw new Exception("error reading metadata from ".$strPackagePath, Exception::$level_ERROR);
        }

        $this->parseXMLDocument($strMetadata);
    }

    /**
     * Parses the xml-document and sets the internal properties.
     *
     * @param string $strXmlDocument
     *
     * @return void
     */
    private function parseXMLDocument($strXmlDocument)
    {
        $xml = new \SimpleXMLElement($strXmlDocument);

        $this->setStrTitle((string)$xml->title);
        $this->setStrDescription((string)$xml->description);
        $this->setStrVersion((string)$xml->version);
        $this->setStrAuthor((string)$xml->author);
        $this->setBitProvidesInstaller((string)$xml->providesInstaller == "TRUE");

        if (isset($xml->requiredModules)) {
            foreach ($xml->requiredModules->module as $module) {
                $strModule = (string)$module["name"];
                $strVersion = (string)$module["version"];
                $this->arrRequiredModules[$strModule] = $strVersion;
            }
        }

        if (isset($xml->screenshots)) {
            foreach ($xml->screenshots->screenshot as $screenshot) {
                $strImage = (string)$screenshot["path"];

                if (in_array(StringUtil::toLowerCase(StringUtil::substring($strImage, -4)), array(".jpg", ".jpeg", ".gif", ".png"))) {
                    $this->arrScreenshots[] = $strImage;
                }
            }

        }

        if (isset($xml->constants)) {
            foreach ($xml->constants->constant as $constant) {
                $name = (string)$constant["name"];
                $value = (string)$constant["value"];
                $this->constants[$name] = $value;
            }
        }
    }


    /**
     * @param string $strAuthor
     *
     * @return void
     */
    public function setStrAuthor($strAuthor)
    {
        $this->strAuthor = $strAuthor;
    }

    /**
     * @return mixed
     */
    public function getStrAuthor()
    {
        return $this->strAuthor;
    }

    /**
     * @param string $strContentprovider
     *
     * @return void
     */
    public function setStrContentprovider($strContentprovider)
    {
        $this->strContentprovider = $strContentprovider;
    }

    /**
     * @return mixed
     */
    public function getStrContentprovider()
    {
        return $this->strContentprovider;
    }

    /**
     * @param string $strDescription
     *
     * @return void
     */
    public function setStrDescription($strDescription)
    {
        $this->strDescription = $strDescription;
    }

    /**
     * @return mixed
     */
    public function getStrDescription()
    {
        return $this->strDescription;
    }

    /**
     * @param string $strPath
     *
     * @return void
     */
    public function setStrPath($strPath)
    {
        $this->strPath = $strPath;
    }

    /**
     * @return mixed
     */
    public function getStrPath()
    {
        return $this->strPath;
    }

    /**
     * @param string $strTitle
     *
     * @return void
     */
    public function setStrTitle($strTitle)
    {
        $this->strTitle = $strTitle;
    }

    /**
     * @return mixed
     */
    public function getStrTitle()
    {
        return $this->strTitle;
    }

    /**
     * @param string $strVersion
     *
     * @return void
     */
    public function setStrVersion($strVersion)
    {
        $this->strVersion = $strVersion;
    }

    /**
     * @return mixed
     */
    public function getStrVersion()
    {
        return $this->strVersion;
    }

    /**
     * @param string $strType
     *
     * @return void
     * @deprecated
     */
    public function setStrType($strType)
    {
        $this->strType = $strType;
    }

    /**
     * @return mixed
     * @deprecated
     */
    public function getStrType()
    {
        return $this->strType;
    }

    /**
     * @param string $strTarget
     *
     * @return void
     * @deprecated
     */
    public function setStrTarget($strTarget)
    {
        $this->strTarget = $strTarget;
    }

    /**
     * @return mixed
     * @deprecated
     */
    public function getStrTarget()
    {
        return $this->strTarget;
    }

    /**
     * @param bool $bitProvidesInstaller
     *
     * @return void
     */
    public function setBitProvidesInstaller($bitProvidesInstaller)
    {
        $this->bitProvidesInstaller = $bitProvidesInstaller;
    }

    /**
     * @return mixed
     */
    public function getBitProvidesInstaller()
    {
        return $this->bitProvidesInstaller;
    }

    /**
     * @param array $arrRequiredModules
     *
     * @return void
     */
    public function setArrRequiredModules($arrRequiredModules)
    {
        $this->arrRequiredModules = $arrRequiredModules;
    }

    /**
     * @return array
     */
    public function getArrRequiredModules()
    {
        return $this->arrRequiredModules;
    }

    /**
     * @return array
     */
    public function getArrScreenshots()
    {
        return $this->arrScreenshots;
    }

    /**
     * @return boolean
     */
    public function getBitIsPhar()
    {
        return $this->bitIsPhar;
    }

    /**
     * @return array
     */
    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * @param array $constants
     */
    public function setConstants(array $constants)
    {
        $this->constants = $constants;
    }

}
