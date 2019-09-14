<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Mediamanager\View\Components\Formentry\Inputuploadinline;

use Kajona\Mediamanager\System\MediamanagerRepo;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\StringUtil;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;
use Kajona\System\View\Components\Listbutton\ListButton;
use Kajona\System\View\Components\Popover\Popover;

/**
 * Inputuploadinline - the a multi-upload field inline component, so directly within a form.
 * Only to be used in combination with FormentryMultiUpload.
 * The dir-param is the directory in the filesystem. The Mediamanager-Backend takes
 * care of validating permissions and creating the relevant MediamanagerFile entries on the fly.
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_mediamanager/view/components/formentry/inputuploadinline/template.twig
 */
class InputUploadInline extends FormentryComponentAbstract
{

    private $config;
    private $lang;

    /**
     * @var MediamanagerRepo
     */
    protected $mediamanagerRepo;

    /**
     * @var mixed
     */
    protected $targetDir;

    /**
     * @var bool
     */
    protected $readOnly = false;

    /**
     * @var bool
     */
    protected $versioning = true;

    /**
     * @var bool
     */
    protected $multiUpload = true;

    /**
     * @var bool
     */
    protected $showArchive = false;

    /**
     * @var string
     */
    protected $targetSystemId = null;


    /**
     * Inputuploadinline constructor.
     * @param string $name
     * @param string $title
     * @param MediamanagerRepo $repo
     * @param string $targetDir
     */
    public function __construct(string $name, string $title, MediamanagerRepo $repo, string $targetDir)
    {
        parent::__construct($name, $title);

        $this->mediamanagerRepo = $repo;
        $this->targetDir = $targetDir;

        $this->config = Carrier::getInstance()->getObjConfig();
        $this->lang = Carrier::getInstance()->getObjLang();
    }


    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();
        $context["mediamanagerRepoId"] = $this->mediamanagerRepo->getStrSystemid();
        $context["folder"] = $this->targetDir;
        $context["readOnly"] = $this->readOnly;
        $context["multiUpload"] = $this->multiUpload;

        if (!$this->readOnly) {
            $listAddButton = new ListButton('<i class=\'kj-icon fa fa-plus-circle\'></i>');
            $context["addButton"] = $listAddButton->renderComponent();
        } else {
            $context["addButton"] = "";
        }
        if (!$this->readOnly && $this->isVersioning()) {
            $listMoveButton = new ListButton(AdminskinHelper::getAdminImage("icon_archive", $this->lang->getLang("version_files", "mediamanager")));
            $context["moveButton"] = "<a id='version_{$this->name}'>" . $listMoveButton->renderComponent() . "</a>";
        } else {
            $context["moveButton"] = "";
        }

        if (!$this->readOnly && $this->isShowArchive()) {
            $listArchButton = new ListButton(AdminskinHelper::getAdminImage("icon_phar", $this->lang->getLang("archive_files", "mediamanager")));
            $context["archiveButton"] = "<a id='archive_{$this->name}'>" . $listArchButton->renderComponent() . "</a>";
        } else {
            $context["archiveButton"] = "";
        }

        $context["archiveTitle"] = $this->lang->getLang("archive_files", "mediamanager");
        $context["archiveBody"] = $this->lang->getLang("archive_files_notice", "mediamanager");
        $context["targetSystemId"] = $this->targetSystemId;

        $allowedFileRegex = StringUtil::replace(array(".", ","), array("", "|"), $this->mediamanagerRepo->getStrUploadFilter());
        $allowedFileTypes = StringUtil::replace(array(".", ","), array("", "', '"), $this->mediamanagerRepo->getStrUploadFilter());

        $context["allowedExtensions"] = $allowedFileTypes != ""
            ? $this->lang->getLang("upload_allowed_extensions", "mediamanager") . ": '" . $allowedFileTypes . "'"
            : $allowedFileTypes;
        $context["maxFileSize"] = $this->config->getPhpMaxUploadSize();
        $context["acceptFileTypes"] = $allowedFileRegex != ""
            ? "/(\.|\/)(" . $allowedFileRegex . ")$/i"
            : "''";
        $context["upload_multiple_errorFilesize"] = $this->lang->getLang("upload_multiple_errorFilesize", "mediamanager") .
            " " . bytesToString($this->config->getPhpMaxUploadSize());

        if (!$this->readOnly) {
            $popover = new Popover();
            $popover->setLink(AdminskinHelper::getAdminImage("icon_question", "", true));
            $popover->setTitle($this->lang->getLang("mediamanager_upload", "mediamanager"));
            $popover->setContent($this->lang->getLang("upload_dropArea_extended", "mediamanager", [$allowedFileTypes, bytesToString($this->config->getPhpMaxUploadSize())]));
            $popover->setTrigger("hover");

            $listHelpButton = new Listbutton("<a>" . $popover->renderComponent() . "</a>");
            $context["helpButton"] = $listHelpButton->renderComponent();
        } else {
            $context["helpButton"] = "";
        }

        return $context;
    }

    /**
     * @return MediamanagerRepo
     */
    public function getMediamanagerRepo(): MediamanagerRepo
    {
        return $this->mediamanagerRepo;
    }

    /**
     * @param MediamanagerRepo $mediamanagerRepo
     */
    public function setMediamanagerRepo(MediamanagerRepo $mediamanagerRepo): void
    {
        $this->mediamanagerRepo = $mediamanagerRepo;
    }

    /**
     * @return mixed
     */
    public function getTargetDir()
    {
        return $this->targetDir;
    }

    /**
     * @param mixed $targetDir
     */
    public function setTargetDir($targetDir): void
    {
        $this->targetDir = $targetDir;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * @param bool $readOnly
     */
    public function setReadOnly(bool $readOnly): void
    {
        $this->readOnly = $readOnly;
    }

    /**
     * @return bool
     */
    public function isVersioning(): bool
    {
        return $this->versioning;
    }

    /**
     * @param bool $versioning
     */
    public function setVersioning(bool $versioning): void
    {
        $this->versioning = $versioning;
    }

    /**
     * @return bool
     */
    public function isMultiUpoad(): bool
    {
        return $this->multiUpload;
    }

    /**
     * @param bool $multiUpoad
     */
    public function setMultiUpoad(bool $multiUpload): void
    {
        $this->multiUpload = $multiUpload;
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
    public function setShowArchive(bool $showArchive): void
    {
        $this->showArchive = $showArchive;
    }

    /**
     * @return string|null
     */
    public function getTargetSystemId(): ?string
    {
        return $this->targetSystemId;
    }

    /**
     * @param string|null $targetSystemId
     */
    public function setTargetSystemId(?string $targetSystemId): void
    {
        $this->targetSystemId = $targetSystemId;
    }


}
