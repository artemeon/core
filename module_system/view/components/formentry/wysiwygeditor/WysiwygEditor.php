<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\View\Components\Formentry\Wysiwygeditor;

use Kajona\System\System\Link;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\View\Components\Formentry\FormentryComponentAbstract;

/**
 * Returns a Wysiwyg editor text area
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.0
 * @componentTemplate core/module_system/view/components/formentry/wysiwygeditor/template.twig
 */
class WysiwygEditor extends FormentryComponentAbstract
{
    /**
     * @var string
     */
    protected $editorId;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $toolbarset;

    /**
     * @var string
     */
    protected $opener;

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $configFile;

    /**
     * WysiwygEditor constructor.
     * @param $strName
     * @param string $strTitle
     * @param string $strContent
     * @param string $strToolbarset
     * @param bool $bitReadonly
     * @param string $strOpener
     * @throws \Kajona\System\System\Exception
     */
    public function __construct($strName, $strTitle = "", $strContent = "", $strToolbarset = "standard", $bitReadonly = false, $strOpener = "")
    {
        parent::__construct($strName, $strTitle);

        $this->content = $strContent;
        $this->toolbarset = $strToolbarset;
        $this->readOnly = $bitReadonly;
        $this->opener = $strOpener;
        $this->editorId = generateSystemid();

        //set the language the user defined for the admin
        $this->language = Session::getInstance()->getAdminLanguage();
        if ($this->language == "") {
            $this->language = "en";
        }

        //check if a customized editor-config is available
        $this->configFile = "config_kajona_standard.js";

        if (is_file(_realpath_."project/module_system/scripts/admin/ckeditor/config_kajona_standard.js")) {
            $this->configFile = "KAJONA_WEBPATH+'/project/module_system/admin/scripts/ckeditor/config_kajona_standard.js'";
        }

        if (is_file(_realpath_."project/module_system/scripts/ckeditor/config_kajona_standard.js")) {
            $this->configFile = "KAJONA_WEBPATH+'/project/module_system/scripts/ckeditor/config_kajona_standard.js'";
        }
    }

    /**
     * @inheritdoc
     */
    public function renderComponent(): string
    {
        $data = [
            "name" => $this->name,
            "title" => $this->title,
            "opener" => $this->opener,
            "readonly" => $this->readOnly ? ' readonly ' : '',
            "content" => $this->content,
            "editorid" => $this->editorId,
            "toolbarSet" => $this->toolbarset,
            "language" => $this->language,
            "configFile" => $this->configFile,
            "modulepath" => _webpath_.Resourceloader::getInstance()->getWebPathForModule("module_system")."/view/components/formentry/wysiwygeditor/scripts/ckeditor/ckeditor.js",
        ];

        return $this->renderTemplate($data);
    }
}
