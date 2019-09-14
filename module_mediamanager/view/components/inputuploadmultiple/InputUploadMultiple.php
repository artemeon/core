<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Mediamanager\View\Components\Inputuploadmultiple;

use Kajona\Mediamanager\System\MediamanagerRepo;
use Kajona\System\System\Carrier;
use Kajona\System\System\StringUtil;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * Inputuploadmultiple - input-file component for uploading multiple files with progress bar.
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 * @componentTemplate core/module_mediamanager/view/components/inputuploadmultiple/template.twig
 */
class InputUploadMultiple extends FormentryComponentAbstract
{

    private $config;
    private $lang;

    /**
     * @var string
     */
    protected $mediamanagerRepoId;

    /**
     * @var string
     */
    protected $allowedFileTypes;

    /**
     * Inputuploadmultiple constructor.
     * @param string $name
     * @param string $title
     * @param string $allowedFileTypes
     * @param string $repoId
     */
    public function __construct(string $name, string $title, string $allowedFileTypes, string $repoId)
    {
        parent::__construct($name, $title);

        $this->mediamanagerRepoId = $repoId;
        $this->allowedFileTypes = $allowedFileTypes;

        $this->config = Carrier::getInstance()->getObjConfig();
        $this->lang = Carrier::getInstance()->getObjLang();
    }


    /**
     * @inheritdoc
     */
    public function buildContext()
    {
        $context = parent::buildContext();
        $context["mediamanagerRepoId"] = $this->mediamanagerRepoId;

        $allowedFileRegex = StringUtil::replace(array(".", ","), array("", "|"), $this->allowedFileTypes);
        $allowedFileTypes = StringUtil::replace(array(".", ","), array("", "', '"), $this->allowedFileTypes);


        $context["allowedExtensions"] = $allowedFileTypes != ""
            ? $this->lang->getLang("upload_allowed_extensions", "mediamanager") . ": '" . $allowedFileTypes . "'"
            : $allowedFileTypes;
        $context["maxFileSize"] = $this->config->getPhpMaxUploadSize();
        $context["acceptFileTypes"] = $allowedFileRegex != "" ? "/(\.|\/)(".$allowedFileRegex.")$/i" : "''";

        $context["upload_multiple_errorFilesize"] = $this->lang->getLang("upload_multiple_errorFilesize", "mediamanager")
            . " " . bytesToString($this->config->getPhpMaxUploadSize());

        return $context;
    }

    /**
     * @return string
     */
    public function getMediamanagerRepoId(): string
    {
        return $this->mediamanagerRepoId;
    }

    /**
     * @param string $mediamanagerRepoId
     */
    public function setMediamanagerRepoId(string $mediamanagerRepoId): void
    {
        $this->mediamanagerRepoId = $mediamanagerRepoId;
    }



}
