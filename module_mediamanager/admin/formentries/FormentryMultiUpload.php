<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\Mediamanager\Admin\Formentries;

use Kajona\Mediamanager\System\MediamanagerFile;
use Kajona\Mediamanager\System\MediamanagerFileFilter;
use Kajona\Mediamanager\System\MediamanagerRepo;
use Kajona\Mediamanager\System\Validators\MediamanagerUploadValidator;
use Kajona\Mediamanager\View\Components\Formentry\Inputuploadinline\InputUploadInline;
use Kajona\System\Admin\Formentries\FormentryBase;
use Kajona\System\Admin\FormentryPrintableInterface;
use Kajona\System\System\Carrier;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Reflection;
use Kajona\System\System\SystemSetting;



/**
 * Integrates the multi-upload into a single form, queries the mediamanager for storing uploads.
 * The mapped database-field is a systemid, so make sure to have at least a varchar20 field available.
 *
 * @author sidler@mulchprod.de
 * @since 6.5
 */
class FormentryMultiUpload extends FormentryBase implements FormentryPrintableInterface
{

    const STR_MM_REPOID_ANNOTATION = "@fieldMMRepoIdConstant";

    private $strRepoId = "";

    private $showVersioning = true;
    private $multiUpload = true;

    /**
     * @var bool
     */
    private $showArchive = false;

    /**
     * @var string
     */
    private $targetSystemId;

    /**
     * @inheritDoc
     */
    public function __construct($strFormName, $strSourceProperty, $objSourceObject = null, $strRepoId = null)
    {
        parent::__construct($strFormName, $strSourceProperty, $objSourceObject);

        //default: files-repo-id
        if ($strRepoId !== null) {
            $this->strRepoId = $strRepoId;
        } else {
            $this->strRepoId = SystemSetting::getConfigValue("_mediamanager_default_filesrepoid_");
        }

        //may be overwritten with a dedicated repo-id
        if ($this->getObjSourceObject() != null && $this->getStrSourceProperty() != "") {
            $objReflection = new Reflection($this->getObjSourceObject());
            //try to find the matching source property
            $strSourceProperty = $this->getCurrentProperty(self::STR_MM_REPOID_ANNOTATION);
            if ($strSourceProperty != null) {
                $strId = $objReflection->getAnnotationValueForProperty($strSourceProperty, self::STR_MM_REPOID_ANNOTATION);
                if ($strId !== null) {
                    $this->strRepoId = SystemSetting::getConfigValue($strId);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getObjValidator()
    {
        return new MediamanagerUploadValidator($this->strRepoId, $this->getBitMandatory());
    }


    /**
     * Renders the field itself.
     * In most cases, based on the current toolkit.
     *
     * @return string
     */
    public function renderField()
    {
        $objToolkit = Carrier::getInstance()->getObjToolkit("admin");
        $strReturn = "";

        if (empty($this->getStrValue())) {
            $this->setStrValue(generateSystemid());
        }

        /** @var MediamanagerRepo $objRepo */
        $objRepo = Objectfactory::getInstance()->getObject($this->strRepoId);

        if ($objRepo === null) {
            return $objToolkit->warningBox("No mediamanager repo set");
        }

        //place the upload-repo id as a hidden form entry
        $strReturn .= $objToolkit->formInputHidden($this->getStrEntryName()."_id", $this->getStrValue());

        if ($this->getStrHint() != null) {
            $strReturn .= $objToolkit->formTextRow($this->getStrHint());
        }

        //and render the multiupload fields
        $inputUploadInline = new InputUploadInline($this->getStrEntryName(), $this->getStrLabel(), $objRepo, $this->getStrValue());
        $inputUploadInline->setReadOnly($this->getBitReadonly());
        $inputUploadInline->setVersioning($this->getShowVersioning());
        $inputUploadInline->setMultiUpoad($this->isMultiUpload());
        $inputUploadInline->setShowArchive($this->isShowArchive());
        $inputUploadInline->setTargetSystemId($this->getTargetSystemId());
        $strReturn .= $inputUploadInline->renderComponent();

        return $strReturn;
    }


    /**
     * Overwritten base method, processes the hidden fields, too.
     */
    protected function updateValue()
    {
        $arrParams = Carrier::getAllParams();
        if (isset($arrParams[$this->getStrEntryName()."_id"])) {
            $this->setStrValue($arrParams[$this->getStrEntryName()."_id"]);
        } else {
            $this->setStrValue($this->getValueFromObject());
        }
    }

    /**
     * @inheritdoc
     */
    public function getValueAsText()
    {

        /** @var MediamanagerRepo $objRepo */
        $objRepo = Objectfactory::getInstance()->getObject($this->strRepoId);
        $objMMFile = MediamanagerFile::getFileForPath($this->strRepoId, $objRepo->getStrPath()."/".$this->getStrValue());

        $arrLinks = [];
        if ($objMMFile != null) {
            /** @var MediamanagerFile $objFile */

            //1. Render files directly uploaded
            $filterFile = new MediamanagerFileFilter();
            $filterFile->setIntFileType(MediamanagerFile::$INT_TYPE_FILE);
            $files = MediamanagerFile::getObjectListFiltered($filterFile, $objMMFile->getSystemid());
            foreach ($files as $file) {
                $arrLinks[] = "<a href='"._webpath_."/download.php?systemid=".$file->getSystemid()."'>".$file->getStrName()."</a>";
            }

            //Render versionized folders/files
            $filterFolder = new MediamanagerFileFilter();
            $filterFolder->setIntFileType(MediamanagerFile::$INT_TYPE_FOLDER);
            $folders = MediamanagerFile::getObjectListFiltered($filterFolder, $objMMFile->getSystemid());
            foreach ($folders as $folder) {
                $arrLinks = array_merge($arrLinks, $this->renderFolderForSummary($folder));
            }
        }

        return implode("\r\n<br />", $arrLinks);
    }

    /**
     * Returns whether the configured repo has files
     *
     * @return bool
     */
    public function hasFiles()
    {
        /** @var MediamanagerRepo $repository */
        $repository = Objectfactory::getInstance()->getObject($this->strRepoId);
        $file = MediamanagerFile::getFileForPath($this->strRepoId, $repository->getStrPath()."/".$this->getStrValue());

        if ($file instanceof MediamanagerFile) {
            $filter = new MediamanagerFileFilter();
            $filter->setIntFileType(MediamanagerFile::$INT_TYPE_FILE);

            return MediamanagerFile::getObjectCountFiltered($filter, $file->getSystemid()) > 0;
        }

        return false;
    }

    /**
     * Renders Meidamanager folders as text
     *
     * @param MediamanagerFile $objFile
     * @return array
     */
    private function renderFolderForSummary(MediamanagerFile $objFile) {
        $arrLinks = [];

        if($objFile->getIntType() == MediamanagerFile::$INT_TYPE_FOLDER) {
            $files = MediamanagerFile::getObjectListFiltered(null, $objFile->getSystemid());
            $arrLinks[] = "";
            $arrLinks[] = $objFile->getStrDisplayName();
            foreach ($files as $file) {
                $arrLinks = array_merge($arrLinks, $this->renderFolderForSummary($file));

            }
        } else {
            $arrLinks[] = "- <a href='"._webpath_."/download.php?systemid=".$objFile->getSystemid()."'>".$objFile->getStrName()."</a>";
        }

        return $arrLinks;
    }

    /**
     * @param string $strRepoId
     * @return FormentryMultiUpload
     */
    public function setStrRepoId(string $strRepoId)
    {
        $this->strRepoId = $strRepoId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStrRepoId()
    {
        return $this->strRepoId;
    }

    /**
     * @return bool
     */
    public function getShowVersioning(): bool
    {
        return $this->showVersioning;
    }

    /**
     * @param bool $showVersioning
     * @return FormentryMultiUpload
     */
    public function setShowVersioning(bool $showVersioning)
    {
        $this->showVersioning = $showVersioning;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiUpload(): bool
    {
        return $this->multiUpload;
    }

    /**
     * @param bool $multiUpload
     */
    public function setMultiUpload(bool $multiUpload)
    {
        $this->multiUpload = $multiUpload;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowArchive(): bool
    {
        return $this->showArchive;
    }

    /**
     * @param bool $showArchive
     */
    public function setShowArchive(bool $showArchive)
    {
        $this->showArchive = $showArchive;
    }

    /**
     * @return string
     */
    public function getTargetSystemId()
    {
        return $this->targetSystemId;
    }

    /**
     * @param string $targetSystemId
     */
    public function setTargetSystemId(string $targetSystemId)
    {
        $this->targetSystemId = $targetSystemId;
    }
}
