<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *   $Id$                                 *
 ********************************************************************************************************/

namespace Kajona\System\Admin;

use Kajona\Mediamanager\System\MediamanagerRepo;
use Kajona\System\Admin\Formentries\FormentryTageditor;
use Kajona\System\System\AdminGridableInterface;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\HierarchicalListableInterface;
use Kajona\System\System\History;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemJSTreeConfig;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\Toolkit;
use Kajona\System\View\Components\Buttonwrapper\Buttonwrapper;
use Kajona\System\View\Components\Datatable\Datatable;
use Kajona\System\View\Components\Formentry\Buttonbar\Buttonbar;
use Kajona\System\View\Components\Formentry\Datesingle\Datesingle;
use Kajona\System\View\Components\Formentry\Datetime\Datetime;
use Kajona\System\View\Components\Formentry\Dropdown\Dropdown;
use Kajona\System\View\Components\Formentry\Inputcheckbox\Inputcheckbox;
use Kajona\System\View\Components\Formentry\Inputcolorpicker\Inputcolorpicker;
use Kajona\System\View\Components\Formentry\Inputonoff\Inputonoff;
use Kajona\System\View\Components\Formentry\Inputtext\Inputtext;
use Kajona\System\View\Components\Formentry\Inputtextarea\Inputtextarea;
use Kajona\System\View\Components\Formentry\Objectlist\Objectlist;
use Kajona\System\View\Components\Formentry\Radiogroup\Radiogroup;
use Kajona\System\View\Components\Formentry\Submit\Submit;
use Kajona\System\View\Components\Headline\Headline;
use Kajona\System\View\Components\Listbody\Listbody;
use Kajona\System\View\Components\Listbutton\ListButton;
use Kajona\System\View\Components\Popover\Popover;
use Kajona\System\View\Components\Tabbedcontent\Tabbedcontent;
use Kajona\System\View\Components\Textrow\TextRow;
use Kajona\System\View\Components\Warningbox\Warningbox;
use Kajona\Tags\System\TagsFavorite;
use Kajona\Tags\System\TagsTag;

/**
 * Admin-Part of the toolkit-classes
 *
 * @package module_system
 * @author sidler@mulchprod.de
 */
class ToolkitAdmin extends Toolkit
{

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        //Calling the base class
        parent::__construct();
    }

    /**
     * Returns a simple date-form element. By default used to enter a date without a time.
     *
     * @param string $strName
     * @param string $strTitle
     * @param Date $objDateToShow
     * @param string $strClass = inputDate
     * @param boolean $bitWithTime
     *
     * @throws Exception
     * @return string
     * @since 3.2.0.9
     * @deprecated
     */
    public function formDateSingle($strName, $strTitle, $objDateToShow, $strClass = "", $bitWithTime = false, $bitReadOnly = false)
    {
        if ($bitWithTime) {
            $date = new Datetime($strName, $strTitle, $objDateToShow);
        } else {
            $date = new Datesingle($strName, $strTitle, $objDateToShow);
        }

        $date->setReadOnly($bitReadOnly);
        $date->setClass($strClass);

        return $date->renderComponent();
    }

    /**
     * Returns a divider to split up a page in logical sections
     *
     * @param string $strClass
     *
     * @return string
     */
    public function divider($strClass = "divider")
    {
        $arrTemplate = array();
        $arrTemplate["class"] = $strClass;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "divider");
    }

    /**
     * Creates a percent-beam to illustrate proportions
     *
     * @param float $floatPercent
     *
     * @return string
     */
    public function percentBeam($floatPercent, $bitRenderAnimated = true)
    {
        $arrTemplate = array();
        $arrTemplate["percent"] = number_format($floatPercent, 2);
        $arrTemplate["animationClass"] = $bitRenderAnimated ? "progress-bar-striped" : "";
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "percent_beam");
    }

    // --- FORM-Elements ------------------------------------------------------------------------------------

    /**
     * Returns a checkbox
     *
     * @param string $strName
     * @param string $strTitle
     * @param bool $bitChecked
     * @param string $strClass
     * @param bool $bitReadOnly
     * @return string
     * @deprecated
     */
    public function formInputCheckbox($strName, $strTitle, $bitChecked = false, $strClass = "", $bitReadOnly = false)
    {
        $inputCheckbox = new Inputcheckbox($strName, $strTitle, $bitChecked);
        $inputCheckbox->setReadOnly($bitReadOnly);
        $inputCheckbox->setClass($strClass);

        return $inputCheckbox->renderComponent();
    }

    /**
     * Returns a On-Off toggle button
     *
     * @param string $strName
     * @param string $strTitle
     * @param bool $bitChecked
     * @param bool $bitReadOnly
     * @param string $strOnSwitchJSCallback
     * @param string $strClass
     * @return string
     * @deprecated
     */
    public function formInputOnOff($strName, $strTitle, $bitChecked = false, $bitReadOnly = false, $strOnSwitchJSCallback = "", $strClass = "")
    {
        $inputOnoff = new Inputonoff($strName, $strTitle, $bitChecked);
        $inputOnoff->setReadOnly($bitReadOnly);
        $inputOnoff->setClass($strClass);
        $inputOnoff->setCallback($strOnSwitchJSCallback);

        return $inputOnoff->renderComponent();
    }

    /**
     * Returns a regular hidden-input-field
     *
     * @param string $strName
     * @param string $strValue
     *
     * @return string
     */
    public function formInputHidden($strName, $strValue = "")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = htmlspecialchars($strValue, ENT_QUOTES, "UTF-8", false);
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_hidden");
    }

    /**
     * Returns a regular text-input field
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param string $strClass
     * @param string $strOpener
     * @param bool $bitReadonly
     * @param string $strInstantEditor
     * @return string
     * @deprecated
     */
    public function formInputText($strName, $strTitle = "", $strValue = "", $strClass = "", $strOpener = "", $bitReadonly = false, $strInstantEditor = "")
    {
        $inputCheckbox = new Inputtext($strName, (string) $strTitle, html_entity_decode((string) $strValue));
        $inputCheckbox->setClass($strClass);
        $inputCheckbox->setReadOnly($bitReadonly);
        $inputCheckbox->setOpener($strOpener);

        if (!empty($strInstantEditor)) {
            $inputCheckbox->setData("kajona-instantsave", $strInstantEditor);
        }

        return $inputCheckbox->renderComponent();
    }

    /**
     * Returns a field to enter hex-color values using a color picker
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param bool $bitReadonly
     * @return string
     * @deprecated
     */
    public function formInputColorPicker($strName, $strTitle = "", $strValue = "", $bitReadonly = false, $strInstantEditor = "")
    {
        $inputColorpicker = new Inputcolorpicker($strName, $strTitle, $strValue);
        $inputColorpicker->setReadOnly($bitReadonly);

        if (!empty($strInstantEditor)) {
            $inputColorpicker->setData("kajona-instantsave", $strInstantEditor);
        }

        return $inputColorpicker->renderComponent();
    }

    /**
     * Returns a regular text-input field
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param string $strClass
     * @param bool $bitElements
     * @param bool $bitRenderOpener
     * @param string $strAddonAction
     *
     * @throws Exception
     * @return string
     */
    public function formInputPageSelector($strName, $strTitle = "", $strValue = "", $strClass = "", $bitElements = true, $bitRenderOpener = true, $strAddonAction = "", $strInstantEditor = "")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = htmlspecialchars($strValue, ENT_QUOTES, "UTF-8", false);
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["instantEditor"] = $strInstantEditor;

        $arrTemplate["opener"] = "";
        if ($bitRenderOpener) {
            $arrTemplate["opener"] .= getLinkAdminDialog(
                "pages",
                "pagesFolderBrowser",
                "&pages=1&form_element=" . StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName) . (!$bitElements ? "&elements=false" : ""),
                Carrier::getInstance()->getObjLang()->getLang("select_page", "pages"),
                Carrier::getInstance()->getObjLang()->getLang("select_page", "pages"),
                "icon_externalBrowser",
                Carrier::getInstance()->getObjLang()->getLang("select_page", "pages")
            );
        }

        $arrTemplate["opener"] .= $strAddonAction;

        $arrTemplate["ajaxScript"] = "
	        <script type=\"text/javascript\">
            $(function() {
                var objConfig = new V4skin.defaultAutoComplete();
                objConfig.source = function(request, response) {
                    $.ajax({
                        url: '" . getLinkAdminXml("pages", "getPagesByFilter") . "',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            filter: request.term
                        },
                        success: response
                    });
                };

                $('#" . StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName) . "').autocomplete(objConfig);
            });
	        </script>
        ";

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_pageselector");
    }

    /**
     * Returns a regular text-input field.
     * The param $strValue expects a system-id.
     * The element creates two fields:
     * a text-field, and a hidden field for the selected systemid.
     * The hidden field is names as $strName, appended by "_id".
     * If you want to filter the list for users having at least view-permissions on a given systemid, you may pass the id as an optional param.
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param string $strClass
     * @param bool $bitUser
     * @param bool $bitGroups
     * @param bool $bitBlockCurrentUser
     * @param array|string $arrValidateSystemid If you want to check the view-permissions for a given systemid, pass the id here
     * @param string $strSelectedGroupId
     * @param bool $bitKeepOpen
     * @return string
     */
    public function formInputUserSelector($strName, $strTitle = "", $strValue = "", $strClass = "", $bitUser = true, $bitGroups = false, $bitBlockCurrentUser = false, array $arrValidateSystemid = null, $strSelectedGroupId = null, $bitKeepOpen = false)
    {
        $strUserName = "";
        $strUserId = "";

        //value is a systemid
        if (validateSystemid($strValue)) {
            $objUser = Objectfactory::getInstance()->getObject($strValue);
            $strUserName = $objUser->getStrDisplayName();
            $strUserId = $strValue;
        }

        $strCheckIds = json_encode($arrValidateSystemid);

        $params = [
            "form_element" => $strName,
            "checkid" => $strCheckIds,
        ];
        if ($bitUser) {
            $params["allowUser"] = "1";
        }
        if ($bitGroups) {
            $params["allowGroup"] = "1";
        }
        if ($bitBlockCurrentUser) {
            $params["filter"] = "current";
        }
        if (validateSystemid($strSelectedGroupId)) {
            $params["selectedGroup"] = $strSelectedGroupId;
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = htmlspecialchars($strUserName, ENT_QUOTES, "UTF-8", false);
        $arrTemplate["value_id"] = htmlspecialchars($strUserId, ENT_QUOTES, "UTF-8", false);
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["opener"] = $this->listButton(Link::getLinkAdminDialog(
            "user",
            "userBrowser",
            $params,
            Carrier::getInstance()->getObjLang()->getLang("user_browser", "user"),
            Carrier::getInstance()->getObjLang()->getLang("user_browser", "user"),
            "icon_externalBrowser",
            Carrier::getInstance()->getObjLang()->getLang("user_browser", "user")
        ));

        $strResetIcon = $this->listButton(Link::getLinkAdminManual(
            "href=\"#\" onclick=\"document.getElementById('" . $strName . "').value='';document.getElementById('" . $strName . "_id').value='';return false;\"",
            "",
            Carrier::getInstance()->getObjLang()->getLang("user_browser_reset", "user"),
            "icon_delete"
        ));

        $arrTemplate["opener"] .= $strResetIcon;

        $strName = StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName);
        $arrTemplate["ajaxScript"] = "
	        <script type=\"text/javascript\">
            $(function() {
                var objConfig = new V4skin.defaultAutoComplete();
                objConfig.source = function(request, response) {
                    $.ajax({
                        url: '" . getLinkAdminXml("user", "getUserByFilter") . "',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            filter: request.term,
                            user: " . ($bitUser ? "'true'" : "'false'") . ",
                            group: " . ($bitGroups ? "'true'" : "'false'") . ",
                            block: " . ($bitBlockCurrentUser ? "'current'" : "''") . ",
                            checkid: '" . $strCheckIds . "',
                            groupid: '" . (validateSystemid($strSelectedGroupId) ? $strSelectedGroupId : "") . "'
                        },
                        success: response
                    });
                };
                        ".($bitKeepOpen ? " objConfig.keepUi = true;" : "")."
                 
                $('#" . $strName . "').autocomplete(objConfig).data( 'ui-autocomplete' )._renderItem = function( ul, item ) {
                    return $( '<li></li>' )
                        .data('ui-autocomplete-item', item)
                        .append( '<div class=\'ui-autocomplete-item\' >'+item.icon+item.title+'</div>' )
                        .appendTo( ul );
                } ;
            });
	        </script>
        ";
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_userselector", true);
    }

    /**
     * General form entry which displays an list of objects which can be deleted. It is possible to provide an addlink
     * where entries can be appended to the list. To add an entry you can use the javascript function
     * v4skin.setObjectListItems
     *
     * @param $strName
     * @param string $strTitle
     * @param array $arrObjects
     * @param string $strAddLink
     *
     * @param bool $bitReadOnly
     * @return string
     * @deprecated - see component objectlist
     */
    public function formInputObjectList($strName, $strTitle, array $arrObjects, $strAddLink, $bitReadOnly = false)
    {
        $objectList = new Objectlist($strName, $strTitle, $arrObjects);
        $objectList->setReadOnly($bitReadOnly);
        $objectList->setAddLink($strAddLink);

        return $objectList->renderComponent();
    }

    /**
     * Returns a regular text-input field with a file browser button.
     * Use $strRepositoryId to set a specific filemanager repository id
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param string $strRepositoryId
     * @param string $strClass
     * @param bool $bitLinkAsDownload
     *
     * @return string
     * @since 3.3.4
     */
    public function formInputFileSelector($strName, $strTitle = "", $strValue = "", $strRepositoryId = "", $strClass = "", $bitLinkAsDownload = true)
    {
        $strOpener = getLinkAdminDialog(
            "mediamanager",
            "folderContentFolderviewMode",
            "&form_element=" . $strName . "&systemid=" . $strRepositoryId . ($bitLinkAsDownload ? "&download=1" : ""),
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system"),
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system"),
            "icon_externalBrowser",
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system")
        );

        return $this->formInputText($strName, $strTitle, $strValue, $strClass, $strOpener);
    }

    /**
     * Returns a regular text-input field with a file browser button.
     * The repository is set to the images-repo by default.
     * In addition, a button to edit the image is added by default.
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param string $strClass
     *
     * @return string
     * @since 3.4.0
     */
    public function formInputImageSelector($strName, $strTitle = "", $strValue = "", $strClass = "")
    {
        $strOpener = getLinkAdminDialog(
            "mediamanager",
            "folderContentFolderviewMode",
            "&form_element=" . $strName . "&systemid=" . SystemSetting::getConfigValue("_mediamanager_default_imagesrepoid_"),
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system"),
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system"),
            "icon_externalBrowser",
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system")
        );

        $strOpener .= " " . getLinkAdminDialog(
                "mediamanager",
                "imageDetails",
                "file='+document.getElementById('" . $strName . "').value+'",
                Carrier::getInstance()->getObjLang()->getLang("action_edit_image", "mediamanager"),
                Carrier::getInstance()->getObjLang()->getLang("action_edit_image", "mediamanager"),
                "icon_crop",
                Carrier::getInstance()->getObjLang()->getLang("action_edit_image", "mediamanager"),
                true,
                false,
                " (function() {
         if(document.getElementById('" . $strName . "').value != '') {
             Folderview.dialog.setContentIFrame('" . urldecode(getLinkAdminHref("mediamanager", "imageDetails", "file='+document.getElementById('" . $strName . "').value+'")) . "');
             Folderview.dialog.setTitle('" . $strTitle . "');
             Folderview.dialog.init();
         }
         return false; })(); return false;"
            );

        return $this->formInputText($strName, $strTitle, $strValue, $strClass, $strOpener);
    }

    /**
     * Returns a text-input field as textarea
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param string $strClass = inputTextarea
     * @param bool $bitReadonly
     * @param int $numberOfRows
     *
     * @return string
     */
    public function formInputTextArea($strName, $strTitle = "", $strValue = "", $strClass = "", $bitReadonly = false, $numberOfRows = 4, $strOpener = "", $strPlaceholder = "")
    {

        $cmp = new Inputtextarea($strName, $strTitle);
        $cmp->setValue($strValue);
        $cmp->setClass($strClass);
        $cmp->setReadOnly($bitReadonly);
        $cmp->setNumberOfRows($numberOfRows);
        $cmp->setOpener($strOpener);
        $cmp->setPlaceholder($strPlaceholder);

        return $cmp->renderComponent();
    }

    /**
     * Returns a password text-input field
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param string $strClass
     *
     * @return string
     */
    public function formInputPassword($strName, $strTitle = "", $strValue = "", $strClass = "")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = htmlspecialchars($strValue, ENT_QUOTES, "UTF-8", false);
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_password");
    }

    /**
     * Returns a button to submit a form, by default with a wrapper
     *
     * @param string $strValue
     * @param string $strName
     * @param string $strOnclick
     * @param string $strClass use cancelbutton for cancel-buttons
     * @param bool $bitEnabled
     *
     * @param bool $bitWithWrapper
     *
     * @return string
     */
    public function formInputSubmit($strValue = null, $strName = "Submit", $strOnclick = null, $strClass = "", $bitEnabled = true, $bitWithWrapper = true)
    {
        $cmp = new Submit($strName, $strValue);
        if ($strOnclick !== null) {
            $cmp->setOnClick($strOnclick);
        }
        $cmp->setClass($strClass);
        $cmp->setReadOnly(!$bitEnabled);
        $cmp->setWithWrapper($bitWithWrapper);
        return $cmp->renderComponent();
    }

    /**
     * Renders a wrapper around a single or multiple buttons
     * @param $strButtons
     *
     * @return string
     */
    public function formInputButtonWrapper($strButtons)
    {
        $cmp = new Buttonwrapper($strButtons);
        return $cmp->renderComponent();
    }

    /**
     * Returns a input-file element
     *
     * @param $strName
     * @param string $strTitle
     * @param string $strClass
     * @param string $strFileName
     * @param string $strFileHref
     * @param bool $bitEnabled
     * @return string
     */
    public function formInputUpload($strName, $strTitle = "", $strClass = "", $strFileName = null, $strFileHref = null, $bitEnabled = true)
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["fileName"] = $strFileName;
        $arrTemplate["fileHref"] = $strFileHref;

        if ($bitEnabled) {
            $objText = Carrier::getInstance()->getObjLang();
            $arrTemplate["maxSize"] = $objText->getLang("max_size", "mediamanager") . " " . bytesToString(Config::getInstance()->getPhpMaxUploadSize());
            return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_upload");
        } else {
            return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_upload_disabled");
        }
    }


    /**
     * Returning a complete Dropdown
     *
     * @param string $strName
     * @param mixed $arrKeyValues
     * @param string $strTitle
     * @param string $strKeySelected
     * @param string $strClass
     * @param bool $bitEnabled
     * @param string $strAddons
     * @param string $strDataPlaceholder
     * @param string $strOpener
     * @return string
     * @throws Exception
     * @deprecated
     */
    public function formInputDropdown($strName, array $arrKeyValues, $strTitle = "", $strKeySelected = "", $strClass = "", $bitEnabled = true, $strAddons = "", $strDataPlaceholder = "", $strOpener = "", $strInstantEditor = "")
    {
        $dropdown = new Dropdown($strName, $strTitle, $arrKeyValues, $strKeySelected);
        $dropdown->setClass($strClass);
        $dropdown->setReadOnly(!$bitEnabled);
        $dropdown->setOpener($strOpener);
        $dropdown->setAddons($strAddons);

        if (!empty($strDataPlaceholder)) {
            $dropdown->setData('placeholder', $strDataPlaceholder);
        }

        $dropdown->setData('kajona-instantsave', $strInstantEditor);

        return $dropdown->renderComponent();
    }

    /**
     * Returning a complete dropdown but in multiselect-style
     *
     * @param string $strName
     * @param mixed $arrKeyValues
     * @param string $strTitle
     * @param array $arrKeysSelected
     * @param string $strClass
     * @param bool $bitEnabled
     * @param string $strAddons
     *
     * @return string
     */
    public function formInputMultiselect($strName, array $arrKeyValues, $strTitle = "", $arrKeysSelected = array(), $strClass = "", $bitEnabled = true, $strAddons = "")
    {
        $strOptions = "";
        //Iterating over the array to create the options
        foreach ($arrKeyValues as $strKey => $strValue) {
            $arrTemplate = array();
            $arrTemplate["key"] = $strKey;
            $arrTemplate["value"] = $strValue;
            if (in_array($strKey, $arrKeysSelected)) {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_multiselect_row_selected");
            } else {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_multiselect_row");
            }
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["disabled"] = ($bitEnabled ? "" : "disabled=\"disabled\"");
        $arrTemplate["options"] = $strOptions;
        $arrTemplate["addons"] = $strAddons;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_multiselect", true);
    }

    /**
     * Form entry which displays an input text field where you can add or remove tags
     *
     * @param $strName
     * @param string $strTitle
     * @param array $arrValues
     * @param string|null $strOnChange
     * @param string|null $strDelimiter
     * @return string
     */
    public function formInputTagEditor($strName, $strTitle = "", array $arrValues = array(), $strOnChange = null, $strDelimiter = null)
    {
        // set default delimiter
        // @see https://goodies.pixabay.com/jquery/tag-editor/demo.html
        if (empty($strDelimiter)) {
            $strDelimiter = ',;';
        }

        $strJs = <<<HTML
        function(field, editor, tags){
        var fieldName = $(field).data('name');

        //remove all existing hidden fields
        $('[id="' + fieldName + '-list"]').remove();

        //add all existin hidden fields
        var html = '<div id="' + fieldName + '-list">';
        for (var i = 0; i < tags.length; i++) {
         html += '<input type="hidden" name="' + fieldName + '[]" value="' + tags[i] + '" />';
        }
        html += '</div>';
        $(field).parent().append(html);
        }
HTML;

        // html decode comma value
        $values = array_values($arrValues);
        $values = array_map(function ($value) {
            return FormentryTageditor::decodeValue($value);
        }, $values);

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["values"] = json_encode($values);
        $arrTemplate["delimiter"] = json_encode($strDelimiter);
        $arrTemplate["onChange"] = empty($strOnChange) ? $strJs : (string) $strOnChange;

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_tageditor", true);
    }

    /**
     * Form entry which displays an input text field where you must select entries from an autocomplete
     *
     * @param $strName
     * @param string $strTitle
     * @param $strSource
     * @param array $arrValues
     * @param string|null $strOnChange
     * @param string|null $opener
     * @return string
     * @throws Exception
     */
    public function formInputObjectTags($strName, $strTitle, $strSource, array $arrValues = array(), $strOnChange = null, $opener = null)
    {
        $strData = "";
        $arrResult = array();
        if (!empty($arrValues)) {
            foreach ($arrValues as $objValue) {
                if ($objValue instanceof ModelInterface) {
                    $strData .= '<input type="hidden" name="' . $strName . '_id[]" value="' . $objValue->getStrSystemid() . '" data-title="' . htmlspecialchars($objValue->getStrDisplayName()) . '" />';
                    $arrResult[] = strip_tags($objValue->getStrDisplayName());
                }
            }
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["values"] = json_encode(array_values($arrResult));
        $arrTemplate["onChange"] = empty($strOnChange) ? "function(){}" : (string) $strOnChange;
        $arrTemplate["source"] = $strSource;
        $arrTemplate["data"] = $strData;
        $arrTemplate["opener"] = $opener;

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_objecttags", true);
    }

    /**
     * Returns a toggle button bar which can be used in the same way as an multiselect
     *
     * @param string $strName
     * @param mixed $arrKeyValues
     * @param string $strTitle
     * @param array $arrKeysSelected
     * @param bool $bitEnabled
     * @return string
     * @deprecated
     */
    public function formToggleButtonBar($strName, array $arrKeyValues, $strTitle = "", $arrKeysSelected = array(), $bitEnabled = true, $strType = "checkbox")
    {
        $buttonBar = new Buttonbar($strName, $strTitle, $arrKeyValues, $arrKeysSelected);
        $buttonBar->setReadOnly(!$bitEnabled);
        $buttonBar->setType($strType);

        return $buttonBar->renderComponent();
    }

    /**
     * Creates a list of radio-buttons.
     * In difference to a dropdown a radio-button may not force the user to
     * make a selection / does not generate an implicit selection
     *
     * @param string $strName
     * @param mixed $arrKeyValues
     * @param string $strTitle
     * @param string $strKeySelected
     * @param string $strClass
     * @param bool $bitEnabled
     * @return string
     * @deprecated
     */
    public function formInputRadiogroup($strName, array $arrKeyValues, $strTitle = "", $strKeySelected = "", $strClass = "", $bitEnabled = true)
    {
        $radioGroup = new Radiogroup($strName, $strTitle, $arrKeyValues, $strKeySelected);
        $radioGroup->setClass($strClass);
        $radioGroup->setReadOnly(!$bitEnabled);

        return $radioGroup->renderComponent();
    }

    /**
     * Form entry which is an container for other form elements
     *
     * @param $strName
     * @param string $strTitle
     * @param array $arrFields
     *
     * @return string
     * @throws Exception
     */
    public function formInputContainer($strName, $strTitle = "", array $arrFields = array(), $strOpener = "")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["opener"] = $strOpener;

        $strElements = "";
        foreach ($arrFields as $strField) {
            $strElements .= $this->objTemplate->fillTemplateFile(array("element" => $strField), "/admin/skins/kajona_v4/elements.tpl", "input_container_row", true);
        }

        $arrTemplate["elements"] = $strElements;

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_container", true);
    }

    /**
     * @param $strName
     * @param string $strTitle
     * @param $intType
     * @param array $arrValues
     * @param array $arrSelected
     * @param bool $bitInline
     *
     * @param bool $bitReadonly
     * @param string $strOpener
     *
     * @return string
     * @deprecated
     */
    public function formInputCheckboxArray($strName, $strTitle, $intType, array $arrValues, array $arrSelected, $bitInline = false, $bitReadonly = false, $strOpener = "")
    {
        $cmp = new Checkboxarray($strName, $strTitle, $arrValues, $arrSelected);
        $cmp->setType($intType);
        $cmp->setInline($bitInline);
        $cmp->setReadOnly($bitReadonly);

        return $cmp->renderComponent();
    }

    /**
     * Creates a list of checkboxes based on an object array
     *
     * @param string $strName
     * @param string $strTitle
     * @param array $arrAvailableItems
     * @param array $arrSelectedSystemids
     * @param bool $bitReadonly
     * @param bool $bitShowPath
     *
     * @return string
     */
    public function formInputCheckboxArrayObjectList($strName, $strTitle, array $arrAvailableItems, array $arrSelectedSystemids, $bitReadonly = false, $bitShowPath = true, \Closure $objShowPath = null, $strAddLink = null)
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;

        $strList = $this->listHeader();
        foreach ($arrAvailableItems as $objObject) {
            /** @var $objObject Model */
            $bitSelected = in_array($objObject->getStrSystemid(), $arrSelectedSystemids);

            $strPath = "";
            if ($bitShowPath) {
                if ($objShowPath instanceof \Closure) {
                    $arrPath = $objShowPath($objObject);
                } else {
                    $arrPath = $objObject->getPathArray();
                    // remove module
                    array_shift($arrPath);
                    // remove current systemid
                    array_pop($arrPath);
                    // remove empty entries
                    $arrPath = array_filter($arrPath);

                    $arrPath = array_map(function ($strSystemId) {
                        return Objectfactory::getInstance()->getObject($strSystemId)->getStrDisplayName();
                    }, $arrPath);
                }
                $strPath = implode(" &gt; ", $arrPath);
            }

            $arrSubTemplate = array(
                "icon" => AdminskinHelper::getAdminImage($objObject->getStrIcon()),
                "title" => $objObject->getStrDisplayName(),
                "path" => $strPath,
                "name" => $strName,
                "systemid" => $objObject->getStrSystemId(),
                "checked" => $bitSelected ? "checked=\"checked\"" : "",
                "readonly" => $bitReadonly ? "disabled" : "",
            );

            $strList .= $this->objTemplate->fillTemplateFile($arrSubTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_checkboxarrayobjectlist_row", true);
        }
        $strList .= $this->listFooter();

        $arrTemplate["elements"] = $strList;
        $arrTemplate["addLink"] = $strAddLink;

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_checkboxarrayobjectlist", true);
    }

    /**
     * Creates the header needed to open a form-element
     *
     * @param string $strAction
     * @param string $strName
     * @param string $strEncoding
     * @param string $strOnSubmit
     * @param string $strMethod
     *
     * @param bool $onLeaveChangeDetection
     * @return string
     */
    public function formHeader($strAction, $strName = "", $strEncoding = "", $strOnSubmit = null, $strMethod = "POST", $onLeaveChangeDetection = true)
    {

        $strOnSubmit = $strOnSubmit ?? "Forms.defaultOnSubmit(this);return false;";

        $arrTemplate = array();
        $arrTemplate["name"] = ($strName != "" ? $strName : "form" . generateSystemid());
        $arrTemplate["action"] = $strAction;
        $arrTemplate["method"] = in_array($strMethod, array("GET", "POST")) ? $strMethod : "POST";
        $arrTemplate["enctype"] = $strEncoding;
        $arrTemplate["onsubmit"] = $strOnSubmit;
        $arrTemplate["onchangedetection"] = $onLeaveChangeDetection ? 'true' : 'false';
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "form_start");
    }

    /**
     * Creates a foldable wrapper around optional form fields
     *
     * @param string $strContent
     * @param string $strTitle
     * @param bool $bitVisible
     *
     * @return string
     */
    public function formOptionalElementsWrapper($strContent, $strTitle = "", $bitVisible = false)
    {
        $arrFolder = $this->getLayoutFolderPic($strContent, $strTitle, "icon_folderOpen", "icon_folderClosed", $bitVisible);
        return $this->getFieldset($arrFolder[1], $arrFolder[0]);
    }

    /**
     * Returns a single TextRow in a form
     *
     * @param string $strText
     * @param string $strClass
     *
     * @return string
     */
    public function formTextRow($strText, $strClass = "")
    {
        if ($strText == "") {
            return "";
        }
        $arrTemplate = array();
        $arrTemplate["text"] = $strText;
        $arrTemplate["class"] = $strClass;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "text_row_form", true);
    }

    /**
     * Renders a hint form field
     *
     * @param string $hint
     * @param bool $hideLongText
     * @return string
     */
    public function formTextHint($hint, $hideLongText = false)
    {
        if ($hideLongText) {
            $id = generateSystemid();
            return $this->formTextRow('<div class="form-hint-container" id="' . $id . '" onclick="$(this).toggleClass(\'form-hint-container\')">' . $hint .
                '</div>
            <script type="text/javascript">
            var $el = $("#' . $id . '"); if (!Util.isEllipsisActive($el[0])) { $el.toggleClass(\'form-hint-container\'); }
                </script>');
        } else {
            return $this->formTextRow($hint);
        }
    }

    /**
     * Returns a headline in a form
     *
     * @param string $strText
     * @param string $strClass
     *
     * @param string $strLevel
     * @return string
     * @deprecated
     */
    public function formHeadline($strText, $strClass = "", $strLevel = "h2")
    {
        $cmp = new Headline($strText, $strClass, $strLevel);
        return $cmp->renderComponent();
    }

    /**
     * Returns the tags to close an open form.
     * Includes the hidden fields for a passed pe param and a passed pv param by default.
     *
     * @param bool $bitIncludePeFields
     *
     * @return string
     */
    public function formClose($bitIncludePeFields = true)
    {
        $strPeFields = "";
        if ($bitIncludePeFields) {
            $arrParams = Carrier::getAllParams();
            if (array_key_exists("pe", $arrParams)) {
                $strPeFields .= $this->formInputHidden("pe", $arrParams["pe"]);
            }
            if (array_key_exists("folderview", $arrParams)) {
                $strPeFields .= $this->formInputHidden("folderview", $arrParams["folderview"]);

                if (!array_key_exists("pe", $arrParams)) {
                    $strPeFields .= $this->formInputHidden("pe", "1");
                }
            }
            if (array_key_exists("pv", $arrParams)) {
                $strPeFields .= $this->formInputHidden("pv", $arrParams["pv"]);
            }
        }
        return $strPeFields . $this->objTemplate->fillTemplateFile(array(), "/admin/skins/kajona_v4/elements.tpl", "form_close");
    }

    // --- GRID-Elements ------------------------------------------------------------------------------------

    /**
     * Creates the code to start a sortable grid.
     * By default, a grid is sortable.
     *
     * @param bool $bitSortable
     * @param $intElementsPerPage
     * @param $intCurPage
     *
     * @return string
     */
    public function gridHeader($bitSortable = true, $intElementsPerPage = -1, $intCurPage = -1)
    {
        return $this->objTemplate->fillTemplateFile(
            array("sortable" => ($bitSortable ? "sortable" : ""), "elementsPerPage" => $intElementsPerPage, "curPage" => $intCurPage),
            "/admin/skins/kajona_v4/elements.tpl",
            "grid_header"
        );
    }

    /**
     * Renders a single entry of the current grid.
     *
     * @param AdminGridableInterface|Model|ModelInterface $objEntry
     * @param $strActions
     * @param string $strClickAction
     *
     * @return string
     */
    public function gridEntry(AdminGridableInterface $objEntry, $strActions, $strClickAction = "")
    {
        $strCSSAddon = "";
        if (method_exists($objEntry, "getIntRecordStatus")) {
            $strCSSAddon = $objEntry->getIntRecordStatus() == 0 ? "disabled" : "";
        }

        $arrTemplate = array(
            "title" => $objEntry->getStrDisplayName(),
            "image" => $objEntry->getStrGridIcon(),
            "actions" => $strActions,
            "systemid" => $objEntry->getSystemid(),
            "subtitle" => $objEntry->getStrLongDescription(),
            "info" => $objEntry->getStrAdditionalInfo(),
            "cssaddon" => $strCSSAddon,
            "clickaction" => $strClickAction,
        );

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "grid_entry");
    }

    /**
     * Renders the closing elements of a grid.
     *
     * @return string
     */
    public function gridFooter()
    {
        return $this->objTemplate->fillTemplateFile(array(), "/admin/skins/kajona_v4/elements.tpl", "grid_footer");
    }

    /*"*****************************************************************************************************/

    // --- LIST-Elements ------------------------------------------------------------------------------------

    /**
     * Returns the htmlcode needed to start a proper list
     *
     * @return string
     */
    public function listHeader()
    {
        return $this->objTemplate->fillTemplateFile(array(), "/admin/skins/kajona_v4/elements.tpl", "list_header");
    }

    /**
     * Returns the htmlcode needed to start a proper list, supporting drag n drop to
     * reorder list-items
     *
     * @param string $strListId
     * @param bool $bitOnlySameTable dropping only allowed within the same table or also in other tables
     * @param bool $bitAllowDropOnTree
     * @param int $intElementsPerPage
     * @param int $intCurPage
     *
     * @return string
     */
    public function dragableListHeader($strListId, $bitOnlySameTable = false, $bitAllowDropOnTree = false, $intElementsPerPage = -1, $intCurPage = -1)
    {
        return $this->objTemplate->fillTemplateFile(
            array(
                "listid" => $strListId,
                "sameTable" => $bitOnlySameTable ? "true" : "false",
                "bitMoveToTree" => ($bitAllowDropOnTree ? "true" : "false"),
                "elementsPerPage" => $intElementsPerPage,
                "curPage" => $intCurPage,
            ),
            "/admin/skins/kajona_v4/elements.tpl",
            "dragable_list_header"
        );
    }

    /**
     * Returns the code to finish the opened list
     *
     * @return string
     */
    public function listFooter()
    {
        return $this->objTemplate->fillTemplateFile(array("clickable" => SystemSetting::getConfigValue("_system_lists_clickable_") === "true" ? 'true' : 'false'), "/admin/skins/kajona_v4/elements.tpl", "list_footer");
    }

    /**
     * Returns the code to finish the opened list
     *
     * @param string $strListId
     *
     * @return string
     */
    public function dragableListFooter($strListId)
    {
        return $this->objTemplate->fillTemplateFile(array("listid" => $strListId, "clickable" => (SystemSetting::getConfigValue("_system_lists_clickable_") === "true" ? 'true' : 'false')), "/admin/skins/kajona_v4/elements.tpl", "dragable_list_footer");
    }

    /**
     * Renders a simple admin-object, implementing ModelInterface
     *
     * @param AdminListableInterface|ModelInterface|Model $objEntry
     * @param string $strActions
     * @param bool $bitCheckbox
     * @param string $strCssAddon
     * @return string
     */
    public function simpleAdminList(AdminListableInterface $objEntry, $strActions, $bitCheckbox = false , $strCssAddon ="")
    {
        $strImage = $objEntry->getStrIcon();
        if (is_array($strImage)) {
            $strImage = AdminskinHelper::getAdminImage($strImage[0], $strImage[1]);
        } else {
            $strImage = AdminskinHelper::getAdminImage($strImage);
        }
        $strCSSAddon = "";
        if($strCssAddon!=""){
            $strCSSAddon = $strCssAddon ;
        }



        $comp = new Listbody($objEntry->getSystemid(), $objEntry->getStrDisplayName(), $strImage, $strActions);
        $comp->setAdditionalInfo($objEntry->getStrAdditionalInfo())
            ->setDescription($objEntry->getStrLongDescription())
            ->setCheckbox($bitCheckbox)
            ->setCssAddon($strCSSAddon)
            ->setDeleted($objEntry->getIntRecordDeleted() != 1 ? "" : "1");

        if ($objEntry instanceof HierarchicalListableInterface) {
            $comp->setPath($objEntry->getHierarchicalPath());
        }

        return $comp->renderComponent();
    }

    /**
     * Renders a single admin-row, takes care of selecting the matching template-sections.
     *
     * @param string $strId
     * @param string $strName
     * @param string $strIcon
     * @param string $strActions
     * @param string $strAdditionalInfo
     * @param string $strDescription
     * @param bool $bitCheckbox
     * @param string $strCssAddon
     *
     * @return string
     */
    public function genericAdminList($strId, $strName, $strIcon, $strActions, $strAdditionalInfo = "", $strDescription = "", $bitCheckbox = false, $strCssAddon = "", $strDeleted = "")
    {
        $comp = new Listbody($strId, $strName ?? "", $strIcon, $strActions ?? "");
        $comp->setAdditionalInfo($strAdditionalInfo ?? "")->setDescription($strDescription ?? "")->setCheckbox($bitCheckbox)->setCssAddon($strCssAddon)->setDeleted($strDeleted);
        return $comp->renderComponent();
    }

    /**
     *
     * @param AdminBatchaction[] $arrActions
     *
     * @return string
     */
    public function renderBatchActionHandlers(array $arrActions)
    {
        $strEntries = "";

        foreach ($arrActions as $objOneAction) {
            $strEntries .= $this->listButton($this->objTemplate->fillTemplateFile(
                array(
                    "title" => $objOneAction->getStrTitle(),
                    "icon" => $objOneAction->getStrIcon(),
                    "targeturl" => $objOneAction->getStrTargetUrl(),
                    "renderinfo" => $objOneAction->getBitRenderInfo() ? "1" : "0",
                    "onclick" => $objOneAction->getStrOnClickHandler(),
                ),
                "/admin/skins/kajona_v4/elements.tpl",
                "batchactions_entry"
            ));
        }

        return $this->objTemplate->fillTemplateFile(array("entries" => $strEntries), "/admin/skins/kajona_v4/elements.tpl", "batchactions_wrapper");
    }

    /**
     * Returns a table filled with infos.
     * The header may be build using cssclass -> value or index -> value arrays
     * Values may be build using cssclass -> value or index -> value arrays, too (per row)
     * For header, the passing of the fake-classes colspan-2 and colspan-3 are allowed in order to combine cells
     *
     * @param mixed $arrHeader the first row to name the columns
     * @param mixed $arrValues every entry is one row
     * @param string $strTableCssAddon an optional css-class added to the table tag
     * @param boolean $bitWithTbody whether to render the table with a tbody element
     *
     * @return string
     * @deprecated Deprecated, use 'DTableComponent" with "DTable" class.
     */
    public function dataTable(array $arrHeader, array $arrValues, $strTableCssAddon = "", $bitWithTbody = false)
    {
        $objTable = new Datatable($arrHeader, $arrValues);
        $objTable->setStrTableCssAddon($strTableCssAddon);
        $objTable->setBitWithTbody($bitWithTbody);
        return $objTable->renderComponent();

    }

    // --- Action-Elements ----------------------------------------------------------------------------------

    /**
     * Creates a action-Entry in a list
     *
     * @param string $strContent
     *
     * @return string
     *
     * @deprecated use new ListButton($content) instead
     */
    public function listButton($content)
    {
        $listAddButton = new ListButton($content);

        return $listAddButton->renderComponent();
    }

    /**
     * Generates a delete-button. The passed element name and question is shown as a modal dialog
     * when the icon was clicked. So set the link-href-param for the final deletion, otherwise the
     * user has no more chance to delete the record!
     *
     * @param string $strElementName
     * @param string $strQuestion
     * @param string $strLinkHref
     *
     * @return string
     */
    public function listDeleteButton($strElementName, $strQuestion, $strLinkHref)
    {
        $strElementName = StringUtil::replace(array('\''), array('\\\''), $strElementName);
        $strQuestion = StringUtil::replace("%%element_name%%", StringUtil::jsSafeString(html_entity_decode($strElementName)), $strQuestion);
        return $this->listConfirmationButton($strQuestion, $strLinkHref, "icon_delete", Carrier::getInstance()->getObjLang()->getLang("commons_delete", "system"), Carrier::getInstance()->getObjLang()->getLang("dialog_deleteHeader", "system"), Carrier::getInstance()->getObjLang()->getLang("dialog_deleteButton", "system"));
    }

    /**
     * Renders a button triggering a confirmation dialog. Useful if the loading of the linked pages
     * should be confirmed by the user
     *
     * @param $strDialogContent
     * @param $strConfirmationLinkHref
     * @param $strButton
     * @param $strButtonTooltip
     * @param string $strHeader
     * @param string $strConfirmationButtonLabel
     *
     * @return string
     *
     */
    public function listConfirmationButton($strDialogContent, $strConfirmationLinkHref, $strButton, $strButtonTooltip, $strHeader = "", $strConfirmationButtonLabel = "")
    {
        $strDialogContent = StringUtil::jsSafeString($strDialogContent);

        //get the reload-url
        if (StringUtil::indexOf($strConfirmationLinkHref, "javascript:") === false) {
            $strParam = (StringUtil::indexOf($strConfirmationLinkHref, "?") ? "&" : "?") . "reloadUrl='+encodeURIComponent(document.location.hash.substr(1))+'";

            $strConfirmationLink = "'" . $strConfirmationLinkHref . $strParam . "'";
        } elseif (StringUtil::indexOf($strConfirmationLinkHref, "javascript:") === 0) {
            // if starts with javascript: we use a closure because on some browser the user gets redirected to a blank
            // page which displays the output of the javascript expression. But in our case we want that the user stays
            // on the same site. Because of this we pass a function to the dialog setContent method which gets executed
            // on click
            $strConfirmationLinkHref = substr($strConfirmationLinkHref, 11);
            $strConfirmationLinkHref = stripslashes($strConfirmationLinkHref);

            $strConfirmationLink = "function() { {$strConfirmationLinkHref}; return false; }";
        } else {
            $strConfirmationLink = "'" . $strConfirmationLinkHref . "'";
        }

        if ($strConfirmationButtonLabel == "") {
            $strConfirmationButtonLabel = Carrier::getInstance()->getObjLang()->getLang("commons_ok", "system");
        }

        //create the list-button and the js code to show the dialog
        $strButton = Link::getLinkAdminManual(
            "href=\"#\" onclick=\"javascript:jsDialog_1.setTitle('{$strHeader}'); jsDialog_1.setContent('{$strDialogContent}', '{$strConfirmationButtonLabel}', {$strConfirmationLink}); jsDialog_1.init(); return false;\"",
            "",
            $strButtonTooltip,
            $strButton
        );

        return $this->listButton($strButton);
    }

    /**
     * Renders a button triggering a confirmation dialog. Useful if the loading of the linked pages
     * should be confirmed by the user
     *
     * @param $strDialogContent
     * @param $strConfirmationLinkHref
     * @param $strLinkText
     * @param string $strHeader
     * @param string $strConfirmationButtonLabel
     * @return string
     * @internal param $strButton
     */
    public function confirmationLink($strDialogContent, $strConfirmationLinkHref, $strLinkText, $strHeader = "", $strConfirmationButtonLabel = "")
    {
        //get the reload-url
        $objHistory = new History();
        $strParam = "";
        if (StringUtil::indexOf($strConfirmationLinkHref, "javascript:") === false) {
            $strParam .= StringUtil::indexOf($strConfirmationLinkHref, "?") === false ? '?' : '&';
            $strParam .= "reloadUrl='+encodeURIComponent(document.location.hash.substr(1))+'";
        }

        if ($strConfirmationButtonLabel == "") {
            $strConfirmationButtonLabel = Carrier::getInstance()->getObjLang()->getLang("commons_ok", "system");
        }

        //create the list-button and the js code to show the dialog
        return Link::getLinkAdminManual(
            "href=\"#\" onclick=\"javascript:jsDialog_1.setTitle('{$strHeader}'); jsDialog_1.setContent('{$strDialogContent}', '{$strConfirmationButtonLabel}',  '" . $strConfirmationLinkHref . $strParam . "'); jsDialog_1.init(); return false;\"",
            $strLinkText
        );
    }

    /**
     * Generates a button allowing to change the status of the record passed.
     * Therefore an ajax-method is called.
     *
     * @param Model|string $objInstance or a systemid
     * @param bool $bitReload triggers a page-reload afterwards
     * @param string $strAltActive tooltip text for the icon if record is active
     * @param string $strAltInactive tooltip text for the icon if record is inactive
     *
     * @throws Exception
     * @return string
     */
    public function listStatusButton($objInstance, $bitReload = false, $strAltActive = "", $strAltInactive = "")
    {
        $strAltActive = $strAltActive != "" ? $strAltActive : Carrier::getInstance()->getObjLang()->getLang("status_active", "system");
        $strAltInactive = $strAltInactive != "" ? $strAltInactive : Carrier::getInstance()->getObjLang()->getLang("status_inactive", "system");

        if (is_object($objInstance) && $objInstance instanceof Model) {
            $objRecord = $objInstance;
        } elseif (validateSystemid($objInstance) && Objectfactory::getInstance()->getObject($objInstance) !== null) {
            $objRecord = Objectfactory::getInstance()->getObject($objInstance);
        } else {
            throw new Exception("failed loading instance for " . (is_object($objInstance) ? " @ " . get_class($objInstance) : $objInstance), Exception::$level_ERROR);
        }

        if ($objRecord->getIntRecordStatus() == 1) {
            $strLinkContent = AdminskinHelper::getAdminImage("icon_enabled", $strAltActive);
        } else {
            $strLinkContent = AdminskinHelper::getAdminImage("icon_disabled", $strAltInactive);
        }

        $strJavascript = "";

        //output texts and image paths only once
        if (Carrier::getInstance()->getObjSession()->getSession("statusButton", Session::$intScopeRequest) === false) {
            $strJavascript .= "<script type=\"text/javascript\">
            Ajax.setSystemStatusMessages.strActiveIcon = '" . addslashes(AdminskinHelper::getAdminImage("icon_enabled", $strAltActive)) . "';
            Ajax.setSystemStatusMessages.strInActiveIcon = '" . addslashes(AdminskinHelper::getAdminImage("icon_disabled", $strAltInactive)) . "';
</script>";
            Carrier::getInstance()->getObjSession()->setSession("statusButton", "true", Session::$intScopeRequest);
        }

        $strButton = getLinkAdminManual(
            "href=\"javascript:Ajax.setSystemStatus('" . $objRecord->getSystemid() . "', " . ($bitReload ? "true" : "false") . ");\"",
            $strLinkContent,
            "",
            "",
            "",
            "statusLink_" . $objRecord->getSystemid(),
            false
        );

        return $this->listButton($strButton) . $strJavascript;
    }

    // --- Misc-Elements ------------------------------------------------------------------------------------

    /**
     * Returns a warning box, e.g. shown before deleting a record
     *
     * @param string $strContent
     * @param string $strClass
     * @param bool $dismissible
     * @return string
     * @throws \Twig\Error\Error
     */
    public function warningBox($strContent, $strClass = Warningbox::CSS_CLASS_WARNING, $dismissible = true): string
    {
        $warningBox = new Warningbox($strContent, $strClass);
        $warningBox->setDismissible($dismissible);

        return $warningBox->renderComponent();
    }

    /**
     * Returns a single TextRow
     *
     * @param string $strText
     * @param string $strClass
     *
     * @return string
     */
    public function getTextRow($strText, $strClass = "text")
    {
        $cmp = new TextRow($strText, $strClass);
        return $cmp->renderComponent();
    }

    /**
     * Creates the mechanism to fold parts of the site / make them visible or invisible
     *
     * @param string $strContent
     * @param string $strLinkText The text / content,
     * @param bool $bitVisible
     * @param string $strCallbackVisible JS function
     * @param string $strCallbackInvisible JS function
     *
     * @return mixed 0: The html-layout code
     *               1: The link to fold / unfold
     */
    public function getLayoutFolder($strContent, $strLinkText, $bitVisible = false, $strCallbackVisible = "", $strCallbackInvisible = "")
    {
        $arrReturn = array();
        $strID = generateSystemid();
        $arrTemplate = array();
        $arrTemplate["id"] = $strID;
        $arrTemplate["content"] = $strContent;
        $arrTemplate["display"] = ($bitVisible ? "folderVisible" : "folderHidden");
        $arrReturn[0] = $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "layout_folder");
        $arrReturn[1] = "<a href=\"javascript:Util.fold('" . $strID . "', " . ($strCallbackVisible != "" ? $strCallbackVisible : "null") . ", " . ($strCallbackInvisible != "" ? $strCallbackInvisible : "null") . ");\">" . $strLinkText . "</a>";
        return $arrReturn;
    }

    /**
     * Creates the mechanism to fold parts of the site / make them vivsible or invisible.
     * The image is prepended to the passed link-text.
     *
     * @param string $strContent
     * @param string $strLinkText Mouseovertext
     * @param string $strImageVisible clickable
     * @param string $strImageInvisible clickable
     * @param bool $bitVisible
     *
     * @return string
     *
     */
    public function getLayoutFolderPic($strContent, $strLinkText = "", $strImageVisible = "icon_folderOpen", $strImageInvisible = "icon_folderClosed", $bitVisible = true)
    {

        $strImageVisible = AdminskinHelper::getAdminImage($strImageVisible);
        $strImageInvisible = AdminskinHelper::getAdminImage($strImageInvisible);

        $strID = generateSystemid();
        $strLinkText = "<span id='{$strID}'>" . ($bitVisible ? $strImageVisible : $strImageInvisible) . "</span> " . $strLinkText;

        $strImageVisible = addslashes(htmlentities($strImageVisible));
        $strImageInvisible = addslashes(htmlentities($strImageInvisible));

        $strVisibleCallback = <<<JS
            function() {  $('#{$strID}').html('{$strImageVisible}'); }
JS;

        $strInvisibleCallback = <<<JS
            function() {  $('#{$strID}').html('{$strImageInvisible}'); }
JS;

        return $this->getLayoutFolder($strContent, $strLinkText, $bitVisible, trim($strVisibleCallback), trim($strInvisibleCallback));
    }

    /**
     * Creates a fieldset to structure elements
     *
     * @param string $strTitle
     * @param string $strContent
     * @param string $strClass
     *
     * @return string
     */
    public function getFieldset($strTitle, $strContent, $strClass = "fieldset", $strSystemid = "")
    {
        //remove old placeholder from content
        $this->objTemplate->setTemplate($strContent);
        $this->objTemplate->deletePlaceholder();
        $strContent = $this->objTemplate->getTemplate();
        $arrContent = array();
        $arrContent["title"] = $strTitle;
        $arrContent["content"] = $strContent;
        $arrContent["class"] = $strClass;
        $arrContent["systemid"] = $strSystemid;
        return $this->objTemplate->fillTemplateFile($arrContent, "/admin/skins/kajona_v4/elements.tpl", "misc_fieldset");
    }

    /**
     * Creates a tab-list out of the passed tabs.
     * The params is expected as
     * arraykey => tabname
     * arrayvalue => tabcontent
     *
     * If tabcontent is an url the content is loaded per ajax from this url. Url means the content string starts with
     * http:// or https://
     *
     * @param $arrTabs array(key => content)
     * @param bool $bitFullHeight whether the tab content should use full height
     *
     * @deprecated use direct the Tabbedcontent component
     *
     * @return string
     */
    public function getTabbedContent(array $arrTabs, $bitFullHeight = false)
    {
        $objTabbedContent = new Tabbedcontent($arrTabs, $bitFullHeight);
        return $objTabbedContent->renderComponent();
    }

    /**
     * Container for graphs, e.g. used by stats.
     *
     * @param string $strImgSrc
     *
     * @return string
     */
    public function getGraphContainer($strImgSrc)
    {
        $arrContent = array();
        $arrContent["imgsrc"] = $strImgSrc;
        return $this->objTemplate->fillTemplateFile($arrContent, "/admin/skins/kajona_v4/elements.tpl", "graph_container");
    }

    /**
     * Includes an IFrame with the given URL
     *
     * @param string $strIFrameSrc
     * @param string $strIframeId
     *
     * @return string
     * @deprecated
     */
    public function getIFrame($strIFrameSrc, $strIframeId = "")
    {
        $arrContent = array();
        $arrContent["iframesrc"] = $strIFrameSrc;
        $arrContent["iframeid"] = $strIframeId !== "" ? $strIframeId : generateSystemid();
        return $this->objTemplate->fillTemplateFile($arrContent, "/admin/skins/kajona_v4/elements.tpl", "iframe_container");
    }

    /**
     * Renders the login-status and corresponding links
     *
     * @param array $arrElements
     *
     * @return string
     * @since 3.4.0
     */
    public function getLoginStatus(array $arrElements)
    {
        //Loading a small login-form
        $arrElements["renderTags"] = SystemModule::getModuleByName("tags") != null && SystemModule::getModuleByName("tags")->rightView() ? "true" : "false";
        $arrElements["renderMessages"] = SystemModule::getModuleByName("messaging") != null && SystemModule::getModuleByName("messaging")->rightView() ? "true" : "false";
        $strReturn = $this->objTemplate->fillTemplateFile($arrElements, "/admin/skins/kajona_v4/elements.tpl", "logout_form");
        return $strReturn;
    }

    // --- Navigation-Elements ------------------------------------------------------------------------------

    /**
     * The v4 way of generating a backend-navigation.
     *
     * @return string
     */
    public function getAdminSitemap()
    {
        $strAllModules = "";

        $arrToggleEntries = [];
        foreach (SystemAspect::getActiveObjectList() as $objOneAspect) {
            if (!$objOneAspect->rightView()) {
                continue;
            }

            $arrToggleEntries[] = ["name" => $objOneAspect->getStrDisplayName(), "onclick" => "ModuleNavigation.switchAspect('{$objOneAspect->getSystemid()}'); return false;"];

            $arrModules = SystemModule::getModulesInNaviAsArray($objOneAspect->getSystemid());

            /** @var $arrNaviInstances SystemModule[] */
            $arrNaviInstances = [];
            foreach ($arrModules as $arrModule) {
                $objModule = SystemModule::getModuleBySystemid($arrModule["module_id"]);
                if ($objModule->rightView()) {
                    $arrNaviInstances[] = $objModule;
                }
            }

            $strCombinedHeader = "";
            $strCombinedBody = "";

            $arrCombined = [
                "messaging" => "fa-envelope",
                "dashboard" => "fa-home",
                "tags" => "fa-tags",
            ];

            $strModules = "";
            foreach ($arrNaviInstances as $objOneInstance) {
                $arrActions = AdminHelper::getModuleActionNaviHelper($objOneInstance);

                $strActions = "";
                foreach ($arrActions as $strOneAction) {
                    if (trim($strOneAction) != "") {
                        $arrActionEntries = [
                            "action" => $strOneAction,
                        ];
                        $strActions .= $this->objTemplate->fillTemplateFile($arrActionEntries, "/admin/skins/kajona_v4/elements.tpl", "sitemap_action_entry");
                    } else {
                        $strActions .= $this->objTemplate->fillTemplateFile([], "/admin/skins/kajona_v4/elements.tpl", "sitemap_divider_entry");
                    }
                }

                $arrModuleLevel = [
                    "module" => Link::getLinkAdmin($objOneInstance->getStrName(), "", "", Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName())),
                    "actions" => $strActions,
                    "systemid" => $objOneInstance->getSystemid(),
                    "moduleTitle" => $objOneInstance->getStrName(),
                    "moduleName" => Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName()),
                    "moduleHref" => Link::getLinkAdminHref($objOneInstance->getStrName(), ""),
                    "aspectId" => $objOneAspect->getSystemid(),
                ];

                if (array_key_exists($objOneInstance->getStrName(), $arrCombined)) {
                    $arrModuleLevel["faicon"] = $arrCombined[$objOneInstance->getStrName()];
                    $strCombinedHeader .= $this->objTemplate->fillTemplateFile($arrModuleLevel, "/admin/skins/kajona_v4/elements.tpl", "sitemap_combined_entry_header");
                    $strCombinedBody .= $this->objTemplate->fillTemplateFile($arrModuleLevel, "/admin/skins/kajona_v4/elements.tpl", "sitemap_combined_entry_body");
                } else {
                    $strModules .= $this->objTemplate->fillTemplateFile($arrModuleLevel, "/admin/skins/kajona_v4/elements.tpl", "sitemap_module_wrapper");
                }
            }

            if ($strCombinedHeader != "") {
                $strModules = $this->objTemplate->fillTemplateFile(
                        ["combined_header" => $strCombinedHeader, "combined_body" => $strCombinedBody],
                        "/admin/skins/kajona_v4/elements.tpl",
                        "sitemap_combined_entry_wrapper"
                    ) . $strModules;
            }

            $strAllModules .= $this->objTemplate->fillTemplateFile(
                array("aspectContent" => $strModules, "aspectId" => $objOneAspect->getSystemid(), "class" => ($strAllModules == "" ? "" : "hidden")),
                "/admin/skins/kajona_v4/elements.tpl",
                "sitemap_aspect_wrapper"
            );

        }

        $strToggleDD = "";
        if (!empty($arrToggleEntries)) {
            $strToggle = $this->registerMenu("mainNav", $arrToggleEntries);
            $strToggleDD =
                "<span class='dropdown pull-left'><a href='#' data-toggle='dropdown' role='button'>" . AdminskinHelper::getAdminImage("icon_submenu") . "</a>{$strToggle}</span>"
            ;
        }

        return $this->objTemplate->fillTemplateFile(array("level" => $strAllModules, "aspectToggle" => $strToggleDD), "/admin/skins/kajona_v4/elements.tpl", "sitemap_wrapper");
    }

    // --- Path Navigation ----------------------------------------------------------------------------------

    /**
     * Generates the layout for a small navigation
     *
     * @param mixed $arrEntries
     *
     * @return string
     */
    public function getPathNavigation(array $arrEntries)
    {
        $strRows = "<script type=\"text/javascript\"> Breadcrumb.resetBar();</script>"; //TODO: das muss hier raus, falsche stelle?
        foreach ($arrEntries as $strOneEntry) {
            $strRows .= $this->objTemplate->fillTemplateFile(array("pathlink" => json_encode($strOneEntry)), "/admin/skins/kajona_v4/elements.tpl", "path_entry");
        }
        return $strRows;
    }

    // --- Content Toolbar ----------------------------------------------------------------------------------

    /**
     * A content toolbar can be used to group a subset of actions linking different views
     *
     * @param mixed $arrEntries
     * @param int $intActiveEntry Array-counting, so first element is 0, last is array-length - 1
     *
     * @return string
     * @deprecated use addToContentToolbar instead
     */
    public function getContentToolbar(array $arrEntries, $intActiveEntry = -1)
    {
        $strRows = "";
        foreach ($arrEntries as $intI => $strOneEntry) {
            $strRows .= $this->addToContentToolbar($strOneEntry, "", $intI == $intActiveEntry);
        }
        return $strRows;
    }

    /**
     * Adds a new entry to the current toolbar
     *
     * @param $strButton
     * @param $strIdentifier
     * @return string
     */
    public function addToContentToolbar($strButton, $strIdentifier = '', $bitActive = false)
    {
        $strEntry = $this->objTemplate->fillTemplateFile(array("entry" => StringUtil::jsSafeString($strButton), "identifier" => $strIdentifier, "active" => $bitActive ? 'true' : 'false'), "/admin/skins/kajona_v4/elements.tpl", "contentToolbar_entry");
        return $this->objTemplate->fillTemplateFile(array("entries" => $strEntry), "/admin/skins/kajona_v4/elements.tpl", "contentToolbar_wrapper");
    }

    /**
     * A list of action icons for the current record. In most cases the same icons as when rendering the list.
     *
     * @param $strContent
     *
     * @return string
     */
    public function getContentActionToolbar($strContent)
    {
        if (empty($strContent)) {
            return "";
        }
        return $this->objTemplate->fillTemplateFile(array("content" => $strContent), "/admin/skins/kajona_v4/elements.tpl", "contentActionToolbar_wrapper");
    }

    // --- Validation Errors --------------------------------------------------------------------------------

    /**
     * Generates a list of errors found by the form-validation
     *
     * @param AdminController|AdminFormgenerator $objCalling
     * @param string $strTargetAction
     *
     * @return string
     */
    public function getValidationErrors($objCalling, $bitErrorsAsWarning = false)
    {
        $strRendercode = "";
        //render mandatory fields?
        if (method_exists($objCalling, "getRequiredFields") && is_callable(array($objCalling, "getRequiredFields"))) {
            if ($objCalling instanceof AdminFormgenerator) {
                $arrFields = $objCalling->getRequiredFields();
            } else {
                $arrFields = $objCalling->getRequiredFields();
            }

            if (count($arrFields) > 0) {
                $arrRequiredFields = array();
                foreach ($arrFields as $strName => $strType) {
                    $arrRequiredFields[] = array($strName, $strType);
                }
                $strRequiredFields = json_encode($arrRequiredFields);

                $strRendercode .= "<script type=\"text/javascript\">
                            $(document).ready(function(){
                                Forms.renderMandatoryFields($strRequiredFields);
                            })
                </script>";
            }
        }

        $arrErrors = method_exists($objCalling, "getValidationErrors") ? $objCalling->getValidationErrors() : array();
        if (count($arrErrors) == 0) {
            return $strRendercode;
        }

        $strRows = "";
        $strRendercode .= "<script type=\"text/javascript\">
            $(document).ready(function(){
                Forms.renderMissingMandatoryFields([";

        foreach ($arrErrors as $strKey => $arrOneErrors) {
            foreach ($arrOneErrors as $strOneError) {
                if ($strOneError != "") {
                    $strRows .= $this->objTemplate->fillTemplateFile(array("field_errortext" => $strOneError), "/admin/skins/kajona_v4/elements.tpl", "error_row");
                }
                $strRendercode .= "[ '" . $strKey . "' ], ";
            }
        }
        $strRendercode .= " [] ]);
            })
        </script>";
        $arrTemplate = array();
        $arrTemplate["errorrows"] = $strRows;
        $arrTemplate["errorintro"] = Lang::getInstance()->getLang("errorintro", "system");
        $arrTemplate["errorclass"] = $bitErrorsAsWarning ? "alert-warning" : "alert-danger";
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "error_container") . $strRendercode;
    }

    // --- Pre-formatted ------------------------------------------------------------------------------------

    /**
     * Returns a simple <pre>-Element to display pre-formatted text such as logfiles
     *
     * @param array $arrLines
     * @param int $nrRows number of rows to display
     *
     * @return string
     */
    public function getPreformatted($arrLines, $nrRows = 0, $bitHighlightKeywords = true)
    {
        $strRows = "";
        $intI = 0;
        foreach ($arrLines as $strOneLine) {
            if ($nrRows != 0 && $intI++ > $nrRows) {
                break;
            }
            $strOneLine = str_replace(array("<pre>", "</pre>", "\n"), array(" ", " ", "\r\n"), $strOneLine);

            $strOneLine = htmlToString($strOneLine, true);
            $strOneLine = StringUtil::replace(
                array("INFO", "ERROR", "WARNING"),
                array(
                    "<span style=\"color: green\">INFO</span>",
                    "<span style=\"color: red\">ERROR</span>",
                    "<span style=\"color: orange\">WARNING</span>",
                ),
                $strOneLine
            );
            $strRows .= $strOneLine;
        }

        return $this->objTemplate->fillTemplateFile(array("pretext" => $strRows), "/admin/skins/kajona_v4/elements.tpl", "preformatted");
    }

    // --- Pageview mechanism ------------------------------------------------------------------------------

    /**
     * Creates a pageview
     *
     * @param ArraySectionIterator $objArraySectionIterator
     * @param string $strModule
     * @param string $strAction
     * @param string|array $strLinkAdd
     *
     * @return string the pageview code
     * @since 4.6
     */
    public function getPageview($objArraySectionIterator, $strModule, $strAction, $strLinkAdd = "")
    {

        $intCurrentpage = $objArraySectionIterator->getPageNumber();
        $intNrOfPages = $objArraySectionIterator->getNrOfPages();
        $intNrOfElements = $objArraySectionIterator->getNumberOfElements();

        //build layout
        $arrTemplate = array();

        $strListItems = "";

        if (is_string($strLinkAdd)) {
            $arrParams = [];
            parse_str($strLinkAdd, $arrParams);
            $strLinkAdd = $arrParams;
        }

        //just load the current +-4 pages and the first/last +-2
        $intCounter2 = 1;
        for ($intI = 1; $intI <= $intNrOfPages; $intI++) {
            $bitDisplay = false;
            if ($intCounter2 <= 2) {
                $bitDisplay = true;
            } elseif ($intCounter2 >= ($intNrOfPages - 1)) {
                $bitDisplay = true;
            } elseif ($intCounter2 >= ($intCurrentpage - 2) && $intCounter2 <= ($intCurrentpage + 2)) {
                $bitDisplay = true;
            }

            if ($bitDisplay) {
                $arrLinkTemplate = array();
                $arrLinkTemplate["href"] = Link::getLinkAdminHref($strModule, $strAction, $strLinkAdd + ["pv" => $intI], true, true);
                $arrLinkTemplate["pageNr"] = $intI;

                if ($intI == $intCurrentpage) {
                    $strListItems .= $this->objTemplate->fillTemplateFile($arrLinkTemplate, "/admin/skins/kajona_v4/elements.tpl", "pageview_list_item_active");
                } else {
                    $strListItems .= $this->objTemplate->fillTemplateFile($arrLinkTemplate, "/admin/skins/kajona_v4/elements.tpl", "pageview_list_item");
                }
            }
            $intCounter2++;
        }
        $arrTemplate["pageList"] = $this->objTemplate->fillTemplateFile(array("pageListItems" => $strListItems), "/admin/skins/kajona_v4/elements.tpl", "pageview_page_list");
        $arrTemplate["nrOfElementsText"] = Carrier::getInstance()->getObjLang()->getLang("pageview_total", "system");
        $arrTemplate["nrOfElements"] = $intNrOfElements;
        if ($intCurrentpage < $intNrOfPages) {
            $arrTemplate["linkForward"] = $this->objTemplate->fillTemplateFile(
                array(
                    "linkText" => Carrier::getInstance()->getObjLang()->getLang("pageview_forward", "system"),
                    "href" => Link::getLinkAdminHref($strModule, $strAction, $strLinkAdd + ["pv" => ($intCurrentpage + 1)], true, true),
                ),
                "/admin/skins/kajona_v4/elements.tpl",
                "pageview_link_forward"
            );
        }
        if ($intCurrentpage > 1) {
            $arrTemplate["linkBackward"] = $this->objTemplate->fillTemplateFile(
                array(
                    "linkText" => Carrier::getInstance()->getObjLang()->getLang("commons_back", "commons"),
                    "href" => Link::getLinkAdminHref($strModule, $strAction, $strLinkAdd + ["pv" => ($intCurrentpage - 1)], true, true),
                ),
                "/admin/skins/kajona_v4/elements.tpl",
                "pageview_link_backward"
            );
        }

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "pageview_body");
    }

    //--- misc ----------------------------------------------------------------------------------------------

    /**
     * Sets the users browser focus to the element with the given id
     *
     * @param string $strElementId
     *
     * @return string
     */
    public function setBrowserFocus($strElementId)
    {
        $strReturn = "
            <script type=\"text/javascript\">
            Util.setBrowserFocus(\"" . $strElementId . "\");
            </script>";
        return $strReturn;
    }

    /**
     * Create a tree-view UI-element.
     * The nodes are loaded via AJAX by calling the url passed as the first arg.
     * The optional third param is an ordered list of systemid identifying the nodes to expand initially.
     * The tree may be wrapped into a two-column view.
     *
     * @param SystemJSTreeConfig $objTreeConfig
     * @param string $strSideContent
     *
     * @return string
     */
    public function getTreeview(SystemJSTreeConfig $objTreeConfig, $strSideContent = "")
    {
        $arrTemplate = array();
        $arrTemplate["sideContent"] = $strSideContent;
        $arrTemplate["treeContent"] = $this->getTree($objTreeConfig);
        $arrTemplate["treeId"] = "tree_" . $objTreeConfig->getStrRootNodeId();
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "treeview");
    }

    /**
     * Create a tree-view UI-element.
     * The nodes are loaded via AJAX by calling the url passed as the first arg.
     * The optional third param is an ordered list of systemid identifying the nodes to expand initially.
     * Renders only the tree, so no other content
     *
     * @param SystemJSTreeConfig $objTreeConfig
     *
     * @return string
     */
    public function getTree(SystemJSTreeConfig $objTreeConfig)
    {
        $arrTemplate = array();
        $arrTemplate["rootNodeSystemid"] = $objTreeConfig->getStrRootNodeId();
        $arrTemplate["loadNodeDataUrl"] = $objTreeConfig->getStrNodeEndpoint();
        $arrTemplate["treeId"] = "tree_" . $objTreeConfig->getStrRootNodeId();
        $arrTemplate["treeConfig"] = $objTreeConfig->toJson();
        $arrTemplate["treeviewExpanders"] = is_array($objTreeConfig->getArrNodesToExpand()) ?
            json_encode(array_values($objTreeConfig->getArrNodesToExpand())) : "[]"; //using array_values just in case an associative array is being returned
        $arrTemplate["initiallySelectedNodes"] = is_array($objTreeConfig->getArrInitiallySelectedNodes()) ?
            json_encode(array_values($objTreeConfig->getArrInitiallySelectedNodes())) : "[]"; //using array_values just in case an associative array is being returned

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "tree");
    }

    /**
     * Renderes the quickhelp-button and the quickhelp-text passed
     *
     * @param string $strText
     *
     * @return string
     */
    public function getQuickhelp($strText)
    {
        $strReturn = "";
        $arrTemplate = array();
        $arrTemplate["title"] = Carrier::getInstance()->getObjLang()->getLang("quickhelp_title", "system");
        $arrTemplate["text"] = StringUtil::replace(array("\r", "\n"), "", addslashes($strText));
        $strReturn .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "quickhelp");

        //and the button
        $arrTemplate = array();
        $arrTemplate["text"] = Carrier::getInstance()->getObjLang()->getLang("quickhelp_title", "system");
        $strReturn .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "quickhelp_button");

        return $strReturn;
    }

    /**
     * Generates the wrapper required to render the list of tags.
     *
     * @param string $strWrapperid
     * @param string $strTargetsystemid
     * @param string $strAttribute
     *
     * @return string
     */
    public function getTaglistWrapper($strWrapperid, $strTargetsystemid, $strAttribute)
    {
        $arrTemplate = array();
        $arrTemplate["wrapperId"] = $strWrapperid;
        $arrTemplate["targetSystemid"] = $strTargetsystemid;
        $arrTemplate["attribute"] = $strAttribute;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "tags_wrapper");
    }

    /**
     * Renders a single tag (including the options to remove the tag again)
     *
     * @param TagsTag $objTag
     * @param string $strTargetid
     * @param string $strAttribute
     *
     * @return string
     */
    public function getTagEntry(TagsTag $objTag, $strTargetid, $strAttribute)
    {
        $strFavorite = "";
        if ($objTag->rightRight1()) {
            $strJs = "<script type='text/javascript'>
            Tags.createFavoriteEnabledIcon = '" . addslashes(AdminskinHelper::getAdminImage("icon_favorite", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_remove", "tags"))) . "';
            Tags.createFavoriteDisabledIcon = '" . addslashes(AdminskinHelper::getAdminImage("icon_favoriteDisabled", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_add", "tags"))) . "';

            </script>";

            $strImage = TagsFavorite::getAllFavoritesForUserAndTag(Carrier::getInstance()->getObjSession()->getUserID(), $objTag->getSystemid()) != null ?
                AdminskinHelper::getAdminImage("icon_favorite", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_remove", "tags")) :
                AdminskinHelper::getAdminImage("icon_favoriteDisabled", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_add", "tags"));

            $strFavorite = $strJs . "<a href=\"#\" onclick=\"Tags.createFavorite('" . $objTag->getSystemid() . "', this); return false;\">" . $strImage . "</a>";
        }

        $arrTemplate = array();
        $arrTemplate["tagname"] = $objTag->getStrDisplayName();
        $arrTemplate["strTagId"] = $objTag->getSystemid();
        $arrTemplate["strTargetSystemid"] = $strTargetid;
        $arrTemplate["strAttribute"] = $strAttribute;
        $arrTemplate["strFavorite"] = $strFavorite;
        $arrTemplate["strDelete"] = AdminskinHelper::getAdminImage("icon_delete", Carrier::getInstance()->getObjLang()->getLang("commons_delete", "tags"));
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", Carrier::getInstance()->getParam("delete") != "false" ? "tags_tag_delete" : "tags_tag");
    }

    /**
     * Returns a regular text-input field
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strClass
     *
     * @return string
     */
    public function formInputTagSelector($strName, $strTitle = "", $strClass = "inputText")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;

        $arrTemplate["ajaxScript"] = "
	        <script type=\"text/javascript\">
            $(function() {
                function split( val ) {
                    return val.split( /,\s*/ );
                }

                function extractLast( term ) {
                    return split( term ).pop();
                }

                var objConfig = new V4skin.defaultAutoComplete();
                objConfig.source = function(request, response) {
                    $.ajax({
                        url: '" . getLinkAdminXml("tags", "getTagsByFilter") . "',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            filter:  extractLast( request.term )
                        },
                        success: response
                    });
                };

                objConfig.select = function( event, ui ) {
                    var terms = split( this.value );
                    terms.pop();
                    terms.push( ui.item.value );
                    terms.push( '' );
                    this.value = terms.join( ', ' );
                    return false;
                };

                $('#" . StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName) . "').autocomplete(objConfig);
            });
	        </script>
        ";

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_tagselector", true);
    }

    /**
     * Creates a tooltip shown on hovering the passed text.
     * If both are the same, text and tooltip, only the plain text is returned.
     *
     * @param string $strText
     * @param string $strTooltip
     *
     * @return string
     * @since 3.4.0
     */
    public function getTooltipText($strText, $strTooltip)
    {
        if ($strText == $strTooltip) {
            return $strText;
        }

        return $this->objTemplate->fillTemplateFile(array("text" => $strText, "tooltip" => $strTooltip), "/admin/skins/kajona_v4/elements.tpl", "tooltip_text");
    }

    /**
     * Generates a bootstrap popover
     * @param $strText
     * @param $strPopoverTitle
     * @param $strPopoverContent
     * @param string $strTrigger one of click | hover | focus | manual
     * @return string
     * @since 6.5
     * @deprecated
     */
    public function getPopoverText($strText, $strPopoverTitle, $strPopoverContent, $strTrigger = "hover")
    {
        $popover = new Popover();
        $popover->setTitle($strPopoverTitle)->setContent($strPopoverContent)->setTrigger($strTrigger)->setLink($strText);
        return $popover->renderComponent();
    }

    /**
     * Renders a button with onClick callback.
     *
     * @param string $icon
     * @param string $label
     * @param string $callback
     * @return string
     * @since 7.1
     */
    public function getJsActionButton($icon, $label, $callback)
    {
        $icon = AdminskinHelper::getAdminImage($icon);

        return $this->objTemplate->fillTemplateFile(["icon" => $icon, "label" => $label, "callback" => $callback], "/admin/skins/kajona_v4/elements.tpl", "js_action_button");

    }

    //---contect menues ---------------------------------------------------------------------------------

    /**
     * Creates the markup to render a js-based contex-menu.
     * Each entry is an array with the keys
     *   array("name" => "xx", "link" => "xx", "submenu" => array());
     * The support of submenus depends on the current implementation, so may not be present everywhere!
     *
     * @since 3.4.1
     *
     * @param string $strIdentifier
     * @param string[] $arrEntries
     *
     * @param bool $bitOpenToLeft
     *
     * @return string
     * @deprecated - please use the Menu component
     */
    public function registerMenu($strIdentifier, array $arrEntries, $bitOpenToLeft = false)
    {
        $strEntries = "";
        foreach ($arrEntries as $arrOneEntry) {
            if (!isset($arrOneEntry["link"])) {
                $arrOneEntry["link"] = "";
            }
            if (!isset($arrOneEntry["name"])) {
                $arrOneEntry["name"] = "";
            }
            if (!isset($arrOneEntry["onclick"])) {
                $arrOneEntry["onclick"] = "";
            }
            if (!isset($arrOneEntry["fullentry"])) {
                $arrOneEntry["fullentry"] = "";
            }

            $arrTemplate = array(
                "elementName" => $arrOneEntry["name"],
                "elementAction" => $arrOneEntry["onclick"],
                "elementLink" => $arrOneEntry["link"],
                "elementActionEscaped" => StringUtil::replace("'", "\'", $arrOneEntry["onclick"]),
                "elementFullEntry" => $arrOneEntry["fullentry"],
            );

            if ($arrTemplate["elementFullEntry"] != "") {
                $strCurTemplate = "contextmenu_entry_full";
            } else {
                $strCurTemplate = "contextmenu_entry";
            }

            if (isset($arrOneEntry["submenu"]) && count($arrOneEntry["submenu"]) > 0) {
                $strSubmenu = "";
                foreach ($arrOneEntry["submenu"] as $arrOneSubmenu) {
                    $strCurSubTemplate = "contextmenu_entry";

                    if (!isset($arrOneEntry["link"])) {
                        $arrOneEntry["link"] = "";
                    }
                    if (!isset($arrOneEntry["name"])) {
                        $arrOneEntry["name"] = "";
                    }
                    if (!isset($arrOneEntry["onclick"])) {
                        $arrOneEntry["onclick"] = "";
                    }
                    if (!isset($arrOneEntry["fullentry"])) {
                        $arrOneEntry["fullentry"] = "";
                    }

                    if ($arrOneSubmenu["name"] == "") {
                        $arrSubTemplate = array();
                        $strCurSubTemplate = "contextmenu_divider_entry";
                    } else {
                        $arrSubTemplate = array(
                            "elementName" => $arrOneSubmenu["name"],
                            "elementAction" => $arrOneSubmenu["onclick"],
                            "elementLink" => $arrOneSubmenu["link"],
                            "elementActionEscaped" => StringUtil::replace("'", "\'", $arrOneSubmenu["onclick"]),
                            "elementFullEntry" => $arrOneEntry["fullentry"],
                        );

                        if ($arrSubTemplate["elementFullEntry"] != "") {
                            $strCurSubTemplate = "contextmenu_entry_full";
                        }

                    }

                    $strSubmenu .= $this->objTemplate->fillTemplateFile($arrSubTemplate, "/admin/skins/kajona_v4/elements.tpl", $strCurSubTemplate);
                }
                $arrTemplate["entries"] = $strSubmenu;

                if ($arrTemplate["elementFullEntry"] != "") {
                    $strCurTemplate = "contextmenu_submenucontainer_entry_full";
                } else {
                    $strCurTemplate = "contextmenu_submenucontainer_entry";
                }
            }

            $strEntries .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", $strCurTemplate);
        }

        $arrTemplate = array();
        $arrTemplate["id"] = $strIdentifier;
        $arrTemplate["entries"] = StringUtil::substring($strEntries, 0, -1);
        if ($bitOpenToLeft) {
            $arrTemplate["ddclass"] = "dropdown-menu-right";

        }
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "contextmenu_wrapper");
    }
}
