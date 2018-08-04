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
use Kajona\System\Admin\Formentries\FormentryCheckboxarray;
use Kajona\System\Admin\Formentries\FormentryTageditor;
use Kajona\System\System\AdminGridableInterface;
use Kajona\System\System\AdminListableInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\Date;
use Kajona\System\System\Exception;
use Kajona\System\System\History;
use Kajona\System\System\Lang;
use Kajona\System\System\Link;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemJSTreeConfig;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\Toolkit;
use Kajona\System\View\Components\Datatable\Datatable;
use Kajona\System\View\Components\Formentry\Objectlist\Objectlist;
use Kajona\System\View\Components\Popover\Popover;
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
     */
    public function formDateSingle($strName, $strTitle, $objDateToShow, $strClass = "", $bitWithTime = false, $bitReadOnly = false)
    {
        //check passed param
        if ($objDateToShow != null && !$objDateToShow instanceof Date) {
            throw new Exception("param passed to ToolkitAdmin::formDateSingle is not an instance of Date", Exception::$level_ERROR);
        }

        $arrTemplate = array();
        $arrTemplate["class"] = $strClass;
        $arrTemplate["titleDay"] = $strName."_day";
        $arrTemplate["titleMonth"] = $strName."_month";
        $arrTemplate["titleYear"] = $strName."_year";
        $arrTemplate["titleHour"] = $strName."_hour";
        $arrTemplate["titleMin"] = $strName."_minute";
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["valueDay"] = $objDateToShow != null ? $objDateToShow->getIntDay() : "";
        $arrTemplate["valueMonth"] = $objDateToShow != null ? $objDateToShow->getIntMonth() : "";
        $arrTemplate["valueYear"] = $objDateToShow != null ? $objDateToShow->getIntYear() : "";
        $arrTemplate["valueHour"] = $objDateToShow != null ? $objDateToShow->getIntHour() : "";
        $arrTemplate["valueMin"] = $objDateToShow != null ? $objDateToShow->getIntMin() : "";
        $arrTemplate["valuePlain"] = dateToString($objDateToShow, false);
        $arrTemplate["dateFormat"] = Carrier::getInstance()->getObjLang()->getLang("dateStyleShort", "system");
        $arrTemplate["calendarLang"] = empty(Carrier::getInstance()->getObjSession()->getAdminLanguage()) ? 'en' : Carrier::getInstance()->getObjSession()->getAdminLanguage();

        $arrTemplate["titleTime"] = Carrier::getInstance()->getObjLang()->getLang("titleTime", "system");

        //set up the container div
        $arrTemplate["calendarId"] = $strName;
        $strContainerId = $strName."_calendarContainer";
        $arrTemplate["calendarContainerId"] = $strContainerId;
        $arrTemplate["calendarLang_weekday"] = " [".Carrier::getInstance()->getObjLang()->getLang("toolsetCalendarWeekday", "system")."]\n";
        $arrTemplate["calendarLang_month"] = " [".Carrier::getInstance()->getObjLang()->getLang("toolsetCalendarMonth", "system")."]\n";

        $arrTemplate["readonly"] = ($bitReadOnly ? "disabled=\"disabled\"" : "");

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", $bitWithTime ? "input_datetime_simple" : "input_date_simple");
    }


    /**
     * Returns a text-field using the cool WYSIWYG editor
     * You can use the different toolbar sets defined in /scripts/ckeditor/config.js
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strContent
     * @param string $strToolbarset
     * @param bool $bitReadonly
     * @return string
     */
    public function formWysiwygEditor($strName = "inhalt", $strTitle = "", $strContent = "", $strToolbarset = "standard", $bitReadonly = false, $strOpener = "")
    {
        $strReturn = "";

        //create the html-input element
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["opener"] = $strOpener;
        $arrTemplate["editorid"] = generateSystemid();
        $arrTemplate["readonly"] = ($bitReadonly ? " readonly=\"readonly\" " : "");
        $arrTemplate["content"] = htmlentities($strContent, ENT_COMPAT, "UTF-8");
        $strReturn .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "wysiwyg_ckeditor");
        //for the popups, we need the skinwebpath
        $strReturn .= $this->formInputHidden("skinwebpath", _skinwebpath_);

        //set the language the user defined for the admin
        $strLanguage = Session::getInstance()->getAdminLanguage();
        if ($strLanguage == "") {
            $strLanguage = "en";
        }

        //include the settings made by admin skin
        $strTemplateInit = $this->objTemplate->fillTemplateFile(array(), "/admin/skins/kajona_v4/elements.tpl", "wysiwyg_ckeditor_inits");

        //check if a customized editor-config is available
        $strConfigFile = "'config_kajona_standard.js'";
        //BC
        if (is_file(_realpath_."project/module_system/scripts/admin/ckeditor/config_kajona_standard.js")) {
            $strConfigFile = "KAJONA_WEBPATH+'/project/module_system/admin/scripts/ckeditor/config_kajona_standard.js'";
        }

        if (is_file(_realpath_."project/module_system/scripts/ckeditor/config_kajona_standard.js")) {
            $strConfigFile = "KAJONA_WEBPATH+'/project/module_system/scripts/ckeditor/config_kajona_standard.js'";
        }

        //to add role-based editors, you could load a different toolbar or also a different CKEditor config file
        //the editor code
        $strReturn .= " <script type=\"text/javascript\" src=\""._webpath_.Resourceloader::getInstance()->getWebPathForModule("module_system")."/scripts/ckeditor/ckeditor.js\"></script>\n";
        $strReturn .= " <script type=\"text/javascript\">\n";
        $strReturn .= "
            var ckeditorConfig = {
                customConfig : ".$strConfigFile.",
                toolbar : '".$strToolbarset."',
                ".$strTemplateInit."
                language : '".$strLanguage."',
                filebrowserBrowseUrl : '".StringUtil::replace("&amp;", "&", getLinkAdminHref("folderview", "browserChooser", "&form_element=ckeditor&download=1"))."',
                filebrowserImageBrowseUrl : '".StringUtil::replace("&amp;", "&", getLinkAdminHref("mediamanager", "folderContentFolderviewMode", "systemid=".SystemSetting::getConfigValue("_mediamanager_default_imagesrepoid_")."&form_element=ckeditor&bit_link=1"))."'
	        };
            var curEditor = CKEDITOR.replace($(\"textarea[name='".$strName."'][data-kajona-editorid='".$arrTemplate["editorid"]."']\")[0], ckeditorConfig);
            curEditor.on('change', function(event) {
                $(\"textarea[name='".$strName."'][data-kajona-editorid='".$arrTemplate["editorid"]."']\").val(event.editor.getData());
            });
            curEditor.on('instanceReady', function(event) {
                if($(\"textarea[name='".$strName."'][data-kajona-editorid='".$arrTemplate["editorid"]."']\").hasClass('mandatoryFormElement')) {
                        event.editor.container.addClass('mandatoryFormElement')
                }
            });
            
        ";
        $strReturn .= "</script>\n";

        return $strReturn;
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
     *
     * @return string
     */
    public function formInputCheckbox($strName, $strTitle, $bitChecked = false, $strClass = "", $bitReadOnly = false)
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["checked"] = ($bitChecked ? "checked=\"checked\"" : "");
        $arrTemplate["readonly"] = ($bitReadOnly ? "disabled=\"disabled\"" : "");
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_checkbox");
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
     *
     * @return string
     */
    public function formInputOnOff($strName, $strTitle, $bitChecked = false, $bitReadOnly = false, $strOnSwitchJSCallback = "", $strClass = "")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["checked"] = ($bitChecked ? "checked=\"checked\"" : "");
        $arrTemplate["readonly"] = ($bitReadOnly ? "disabled=\"disabled\"" : "");
        $arrTemplate["onSwitchJSCallback"] = $strOnSwitchJSCallback;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_on_off_switch");
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
     *
     * @param string $strInstantEditor
     * @return string
     */
    public function formInputText($strName, $strTitle = "", $strValue = "", $strClass = "", $strOpener = "", $bitReadonly = false, $strInstantEditor = "")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = htmlspecialchars($strValue, ENT_QUOTES, "UTF-8", false);
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["opener"] = $strOpener;
        $arrTemplate["instantEditor"] = $strInstantEditor;
        $arrTemplate["readonly"] = ($bitReadonly ? "readonly=\"readonly\"" : "");

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_text");
    }


    /**
     * Returns a field to enter hex-color values using a color picker
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strValue
     * @param bool $bitReadonly
     *
     * @return string
     */
    public function formInputColorPicker($strName, $strTitle = "", $strValue = "", $bitReadonly = false, $strInstantEditor = "")
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = htmlspecialchars($strValue, ENT_QUOTES, "UTF-8", false);
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["instantEditor"] = $strInstantEditor;
        $arrTemplate["readonly"] = ($bitReadonly ? "readonly=\"readonly\"" : "");

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_colorpicker");
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
                "&pages=1&form_element=".StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName).(!$bitElements ? "&elements=false" : ""),
                Carrier::getInstance()->getObjLang()->getLang("select_page", "pages"),
                Carrier::getInstance()->getObjLang()->getLang("select_page", "pages"),
                "icon_externalBrowser",
                Carrier::getInstance()->getObjLang()->getLang("select_page", "pages")
            );
        }

        $arrTemplate["opener"] .= $strAddonAction;

        $arrTemplate["ajaxScript"] = "
	        <script type=\"text/javascript\">
	            require(['jquery', 'v4skin'], function($, v4skin){
                    $(function() {
                        var objConfig = new v4skin.defaultAutoComplete();
                        objConfig.source = function(request, response) {
                            $.ajax({
                                url: '".getLinkAdminXml("pages", "getPagesByFilter")."',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    filter: request.term
                                },
                                success: response
                            });
                        };

                        $('#".StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName)."').autocomplete(objConfig);
                    });
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
     * @return string
     */
    public function formInputUserSelector($strName, $strTitle = "", $strValue = "", $strClass = "", $bitUser = true, $bitGroups = false, $bitBlockCurrentUser = false, array $arrValidateSystemid = null, $strSelectedGroupId = null)
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
            "href=\"#\" onclick=\"document.getElementById('".$strName."').value='';document.getElementById('".$strName."_id').value='';return false;\"",
            "",
            Carrier::getInstance()->getObjLang()->getLang("user_browser_reset", "user"),
            "icon_delete"
        ));

        $arrTemplate["opener"] .= $strResetIcon;

        $strName = StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName);
        $arrTemplate["ajaxScript"] = "
	        <script type=\"text/javascript\">
	            require(['jquery', 'v4skin'], function($, v4skin){
                    $(function() {
                        var objConfig = new v4skin.defaultAutoComplete();
                        objConfig.source = function(request, response) {
                            $.ajax({
                                url: '".getLinkAdminXml("user", "getUserByFilter")."',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    filter: request.term,
                                    user: ".($bitUser ? "'true'" : "'false'").",
                                    group: ".($bitGroups ? "'true'" : "'false'").",
                                    block: ".($bitBlockCurrentUser ? "'current'" : "''").",
                                    checkid: '".$strCheckIds."',
                                    groupid: '".(validateSystemid($strSelectedGroupId) ? $strSelectedGroupId : "")."'
                                },
                                success: response
                            });
                        };


                        $('#".$strName."').autocomplete(objConfig).data( 'ui-autocomplete' )._renderItem = function( ul, item ) {
                            return $( '<li></li>' )
                                .data('ui-autocomplete-item', item)
                                .append( '<div class=\'ui-autocomplete-item\' >'+item.icon+item.title+'</div>' )
                                .appendTo( ul );
                        } ;
                    });
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
            "&form_element=".$strName."&systemid=".$strRepositoryId.($bitLinkAsDownload ? "&download=1" : ""),
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
            "&form_element=".$strName."&systemid=".SystemSetting::getConfigValue("_mediamanager_default_imagesrepoid_"),
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system"),
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system"),
            "icon_externalBrowser",
            Carrier::getInstance()->getObjLang()->getLang("filebrowser", "system")
        );

        $strOpener .= " " . getLinkAdminDialog(
            "mediamanager",
            "imageDetails",
            "file='+document.getElementById('" . $strName . "').value+'",
            Carrier ::getInstance() -> getObjLang() -> getLang("action_edit_image", "mediamanager"),
            Carrier ::getInstance() -> getObjLang() -> getLang("action_edit_image", "mediamanager"),
            "icon_crop",
            Carrier ::getInstance() -> getObjLang() -> getLang("action_edit_image", "mediamanager"),
            true,
            false,
            " (function() {
         if(document.getElementById('" . $strName . "').value != '') {
             require('folderview').dialog.setContentIFrame('" . urldecode(getLinkAdminHref("mediamanager", "imageDetails", "file='+document.getElementById('" . $strName . "').value+'")) . "');
             require('folderview').dialog.setTitle('" . $strTitle . "');
             require('folderview').dialog.init();
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
    public function formInputTextArea($strName, $strTitle = "", $strValue = "", $strClass = "", $bitReadonly = false, $numberOfRows = 4, $strOpener = "", $strPlaceholder = null)
    {
        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = htmlspecialchars($strValue, ENT_QUOTES, "UTF-8", false);
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["readonly"] = ($bitReadonly ? " readonly=\"readonly\" " : "");
        $arrTemplate["numberOfRows"] = $numberOfRows;
        $arrTemplate["opener"] = $strOpener;
        $arrTemplate["placeholder"] = !empty($strPlaceholder) ? htmlspecialchars($strPlaceholder) : "";
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_textarea");
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
     * @param string $strEventhandler
     * @param string $strClass use cancelbutton for cancel-buttons
     * @param bool $bitEnabled
     *
     * @param bool $bitWithWrapper
     *
     * @return string
     */
    public function formInputSubmit($strValue = null, $strName = "Submit", $strEventhandler = "", $strClass = "", $bitEnabled = true, $bitWithWrapper = true)
    {
        if ($strValue === null) {
            $strValue = Carrier::getInstance()->getObjLang()->getLang("commons_save", "system");
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = $strValue;
        $arrTemplate["eventhandler"] = $strEventhandler;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["disabled"] = $bitEnabled ? "" : "disabled=\"disabled\"";

        $strButton = $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_submit");

        if ($bitWithWrapper) {
            $strButton = $this->objTemplate->fillTemplateFile(array("button" => $strButton), "/admin/skins/kajona_v4/elements.tpl", "input_submit_wrapper");
        }
        return $strButton;
    }

    /**
     * Renders a wrapper around a single or multiple buttons
     * @param $strButtons
     *
     * @return string
     */
    public function formInputButtonWrapper($strButtons)
    {
        return $this->objTemplate->fillTemplateFile(array("button" => $strButtons), "/admin/skins/kajona_v4/elements.tpl", "input_submit_wrapper");
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
            $arrTemplate["maxSize"] = $objText->getLang("max_size", "mediamanager")." ".bytesToString(Config::getInstance()->getPhpMaxUploadSize());
            return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_upload");
        } else {
            return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_upload_disabled");
        }
    }

    /**
     * Returns a input-file element for uploading multiple files with progress bar. Only functional in combination with
     * the mediamanager module
     *
     * @param string $strName
     * @param string $strAllowedFileTypes
     * @param string $strMediamangerRepoSystemId
     *
     * @return string
     */
    public function formInputUploadMultiple($strName, $strAllowedFileTypes, $strMediamangerRepoSystemId)
    {

        if (SystemModule::getModuleByName("mediamanager") === null) {
            return ($this->warningBox("Module mediamanager is required for multiple uploads"));
        }

        $objConfig = Carrier::getInstance()->getObjConfig();
        $objText = Carrier::getInstance()->getObjLang();

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["mediamanagerRepoId"] = $strMediamangerRepoSystemId;

        $strAllowedFileRegex = StringUtil::replace(array(".", ","), array("", "|"), $strAllowedFileTypes);
        $strAllowedFileTypes = StringUtil::replace(array(".", ","), array("", "', '"), $strAllowedFileTypes);

        $arrTemplate["allowedExtensions"] = $strAllowedFileTypes != "" ? $objText->getLang("upload_allowed_extensions", "mediamanager").": '".$strAllowedFileTypes."'" : $strAllowedFileTypes;
        $arrTemplate["maxFileSize"] = $objConfig->getPhpMaxUploadSize();
        $arrTemplate["acceptFileTypes"] = $strAllowedFileRegex != "" ? "/(\.|\/)(".$strAllowedFileRegex.")$/i" : "''";

        $arrTemplate["upload_multiple_errorFilesize"] = $objText->getLang("upload_multiple_errorFilesize", "mediamanager")." ".bytesToString($objConfig->getPhpMaxUploadSize());

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_upload_multiple");
    }

    /**
     * Creates a multi-upload field inline, so directly within a form.
     * Only to be used in combination with FormentryMultiUpload.
     * The dir-param is the directory in the filesystem. The Mediamanager-Backend takes
     * care of validating permissions and creating the relevant MediamanagerFile entries on the fly.
     *
     * @param string $strName
     * @param $strTitle
     * @param MediamanagerRepo $objRepo
     * @param $strTargetDir
     * @param bool $bitReadonly
     * @param bool $bitVersioning
     * @return string
     * @see FormentryMultiUpload
     */
    public function formInputUploadInline($strName, $strTitle, MediamanagerRepo $objRepo, $strTargetDir, $bitReadonly = false, $bitVersioning = true, $bitMultiUpload = true)
    {

        if (SystemModule::getModuleByName("mediamanager") === null) {
            return ($this->warningBox("Module mediamanager is required for multiple uploads"));
        }

        $objConfig = Carrier::getInstance()->getObjConfig();
        $objText = Carrier::getInstance()->getObjLang();

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["mediamanagerRepoId"] = $objRepo->getSystemid();
        $arrTemplate["folder"] = $strTargetDir;
        $arrTemplate["readOnly"] = $bitReadonly ? 'true' : 'false';
        $arrTemplate["multiUpload"] = $bitMultiUpload ? 'true' : 'false';
        $arrTemplate["addButton"] = $bitReadonly ? "" : $this->listButton("<i class='kj-icon fa fa-plus-circle'></i>");//AdminskinHelper::getAdminImage("icon_new", $objText->getLang("mediamanager_upload", "mediamanager"));
        $arrTemplate["moveButton"] = $bitReadonly || !$bitVersioning ? "" : "<a id='version_{$strName}'>".$this->listButton(AdminskinHelper::getAdminImage("icon_archive", $objText->getLang("version_files", "mediamanager")))."</a>";

        $strAllowedFileRegex = StringUtil::replace(array(".", ","), array("", "|"), $objRepo->getStrUploadFilter());
        $strAllowedFileTypes = StringUtil::replace(array(".", ","), array("", "', '"), $objRepo->getStrUploadFilter());

        $arrTemplate["allowedExtensions"] = $strAllowedFileTypes != "" ? $objText->getLang("upload_allowed_extensions", "mediamanager").": '".$strAllowedFileTypes."'" : $strAllowedFileTypes;
        $arrTemplate["maxFileSize"] = $objConfig->getPhpMaxUploadSize();
        $arrTemplate["acceptFileTypes"] = $strAllowedFileRegex != "" ? "/(\.|\/)(".$strAllowedFileRegex.")$/i" : "''";
        $arrTemplate["upload_multiple_errorFilesize"] = $objText->getLang("upload_multiple_errorFilesize", "mediamanager")." ".bytesToString($objConfig->getPhpMaxUploadSize());

        $arrTemplate["helpButton"] = $bitReadonly ? "" : $this->listButton(
            "<a>".$this->getPopoverText(
                AdminskinHelper::getAdminImage("icon_question", "", true),
                $objText->getLang("mediamanager_upload", "mediamanager"),
                $objText->getLang("upload_dropArea_extended", "mediamanager", [$strAllowedFileTypes, bytesToString($objConfig->getPhpMaxUploadSize())])
            )."</a>"
        );

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_upload_inline");
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
     *
     * @return string
     * @throws Exception
     */
    public function formInputDropdown($strName, array $arrKeyValues, $strTitle = "", $strKeySelected = "", $strClass = "", $bitEnabled = true, $strAddons = "", $strDataPlaceholder = "", $strOpener = "", $strInstantEditor = "")
    {
        $strOptions = "";
        foreach (array("", 0, "\"\"") as $strOneKeyToCheck) {
            if (array_key_exists($strOneKeyToCheck, $arrKeyValues) && trim($arrKeyValues[$strOneKeyToCheck]) == "") {
                unset($arrKeyValues[$strOneKeyToCheck]);
            }
        }

        //see if the selected value is valid
        if (!in_array($strKeySelected, array_keys($arrKeyValues))) {
            $strKeySelected = "";
        }

        if (!isset($arrKeyValues[""])) {
            $strPlaceholder = $strDataPlaceholder != "" ? $strDataPlaceholder : Carrier::getInstance()->getObjLang()->getLang("commons_dropdown_dataplaceholder", "system");
            $strOptions .= "<option value='' disabled ".($strKeySelected == "" ? " selected " : "").">".$strPlaceholder."</option>";
        }

        //Iterating over the array to create the options
        foreach ($arrKeyValues as $strKey => $strValue) {
            $arrTemplate = array();
            $arrTemplate["key"] = $strKey;
            $arrTemplate["value"] = $strValue;
            if ((string)$strKey == (string)$strKeySelected) {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_dropdown_row_selected");
            } else {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_dropdown_row");
            }
        }


        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["disabled"] = ($bitEnabled ? "" : "disabled=\"disabled\"");
        $arrTemplate["options"] = $strOptions;
        $arrTemplate["addons"] = $strAddons;
        $arrTemplate["opener"] = $strOpener;
        $arrTemplate["instantEditor"] = $strInstantEditor;
        $arrTemplate["dataplaceholder"] = $strDataPlaceholder != "" ? $strDataPlaceholder : Carrier::getInstance()->getObjLang()->getLang("commons_dropdown_dataplaceholder", "system"); //TODO noch benötigt?


        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_dropdown", true);
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
     *
     * @param string $strInstantEditor
     * @return string
     */
    public function formInputInterval($strName, $strTitle = "", \DateInterval $objValue = null, $strClass = "", $bitReadonly = false)
    {
        $objLang = Lang::getInstance();
        $arrKeyValues = [
            "D" => $objLang->getLang("commons_interval_day", "system"),
            "W" => $objLang->getLang("commons_interval_week", "system"),
            "M" => $objLang->getLang("commons_interval_month", "system"),
            "Y" => $objLang->getLang("commons_interval_year", "system"),
        ];

        $strKeySelected = "";
        $strValue = "";
        if ($objValue !== null) {
            if ($objValue->d > 0) {
                if ($objValue->d % 7 == 0) {
                    $strKeySelected = "W";
                    $strValue = $objValue->d / 7;
                } else {
                    $strKeySelected = "D";
                    $strValue = $objValue->d;
                }
            } elseif ($objValue->m > 0) {
                $strKeySelected = "M";
                $strValue = $objValue->m;
            } elseif ($objValue->y > 0) {
                $strKeySelected = "Y";
                $strValue = $objValue->y;
            }
        }
        if (empty($strKeySelected)) {
            $strKeySelected = "D";
        }

        $strOptions = "";
        foreach ($arrKeyValues as $strKey => $strVal) {
            $arrTemplate = array();
            $arrTemplate["key"] = $strKey;
            $arrTemplate["value"] = $strVal;
            if ((string)$strKey == (string)$strKeySelected) {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_dropdown_row_selected");
            } else {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_dropdown_row");
            }
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["value"] = $strValue;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["units"] = $strOptions;
        $arrTemplate["readonly"] = ($bitReadonly ? "readonly=\"readonly\"" : "");

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_date_interval");
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
                require(["jquery"], function($){
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
                });
            }
HTML;

        // html decode comma value
        $values = array_values($arrValues);
        $values = array_map(function($value){
            return FormentryTageditor::decodeValue($value);
        }, $values);

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["values"] = json_encode($values);
        $arrTemplate["delimiter"] = json_encode($strDelimiter);
        $arrTemplate["onChange"] = empty($strOnChange) ? $strJs : (string)$strOnChange;

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_tageditor", true);
    }

    /**
     * Form entry which displays an input text field where you must select entries from an autocomplete
     *
     * @param $strName
     * @param string $strTitle
     * @param $strSource
     * @param array $arrValues
     * @param null $strOnChange
     * @return string
     * @throws Exception
     */
    public function formInputObjectTags($strName, $strTitle, $strSource, array $arrValues = array(), $strOnChange = null)
    {
        $strData = "";
        $arrResult = array();
        if (!empty($arrValues)) {
            foreach ($arrValues as $objValue) {
                if ($objValue instanceof ModelInterface) {
                    $strData.= '<input type="hidden" name="' . $strName . '_id[]" value="' . $objValue->getStrSystemid() . '" data-title="' . htmlspecialchars($objValue->getStrDisplayName()) . '" />';
                    $arrResult[] = strip_tags($objValue->getStrDisplayName());
                }
            }
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["values"] = json_encode(array_values($arrResult));
        $arrTemplate["onChange"] = empty($strOnChange) ? "function(){}" : (string)$strOnChange;
        $arrTemplate["source"] = $strSource;
        $arrTemplate["data"] = $strData;

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
     *
     * @return string
     */
    public function formToggleButtonBar($strName, array $arrKeyValues, $strTitle = "", $arrKeysSelected = array(), $bitEnabled = true, $strType = "checkbox")
    {
        $strOptions = "";
        //Iterating over the array to create the options
        foreach ($arrKeyValues as $strKey => $strValue) {
            $arrTemplate = array();
            $arrTemplate["name"] = $strName;
            $arrTemplate["type"] = $strType;
            $arrTemplate["key"] = $strKey;
            $arrTemplate["value"] = $strValue;
            $arrTemplate["disabled"] = ($bitEnabled ? "" : "disabled=\"disabled\"");
            $arrTemplate["btnclass"] = ($bitEnabled ? "" : "disabled");
            if (in_array($strKey, $arrKeysSelected)) {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_toggle_buttonbar_button_selected");
            } else {
                $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_toggle_buttonbar_button");
            }
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["options"] = $strOptions;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_toggle_buttonbar", true);
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
     *
     * @return string
     */
    public function formInputRadiogroup($strName, array $arrKeyValues, $strTitle = "", $strKeySelected = "", $strClass = "", $bitEnabled = true)
    {
        $strOptions = "";
        //Iterating over the array to create the options
        foreach ($arrKeyValues as $strKey => $strValue) {
            $arrTemplate = array();
            $arrTemplate["key"] = $strKey;
            $arrTemplate["value"] = $strValue;
            $arrTemplate["name"] = $strName;
            $arrTemplate["class"] = $strClass;
            $arrTemplate["disabled"] = ($bitEnabled ? "" : "disabled=\"disabled\"");
            $arrTemplate["checked"] = ((string)$strKey == (string)$strKeySelected ? " checked " : "");
            $strOptions .= $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_radiogroup_row");
        }

        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["radios"] = $strOptions;
        $arrTemplate["class"] = $strClass;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "input_radiogroup", true);
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
     */
    public function formInputCheckboxArray($strName, $strTitle, $intType, array $arrValues, array $arrSelected, $bitInline = false, $bitReadonly = false, $strOpener = "")
    {
        $strElement = "input_checkboxarray";
        $strElementRow = "input_checkboxarray_checkbox";
        if ($intType == FormentryCheckboxarray::TYPE_RADIO) {
            $strElement = "input_radioarray";
            $strElementRow = "input_radioarray_radio";
        }

        $arrTemplate = array();
        $arrTemplate["name"] = $strName;
        $arrTemplate["title"] = $strTitle;
        $arrTemplate["opener"] = $strOpener;

        $strElements = '';
        foreach ($arrValues as $strKey => $strValue) {
            $arrTemplateRow = array(
                'key'      => $strKey,
                'title'    => $strValue,
                'checked'  => in_array($strKey, $arrSelected) ? 'checked' : '',
                'inline'   => $bitInline ? '-inline' : '',
                'readonly' => $bitReadonly ? 'disabled' : '',
                'css'      => "",
            );

            switch ($intType) {
                case FormentryCheckboxarray::TYPE_RADIO:
                    $arrTemplateRow['type'] = 'radio';
                    $arrTemplateRow['name'] = $strName;
                    $arrTemplateRow['value'] = $strKey;
                    break;
                case FormentryCheckboxarray::TYPE_CHECKBOX:
                    $arrTemplateRow['type'] = 'checkbox';
                    $arrTemplateRow['name'] = $strName.'['.$strKey.']';
                    $arrTemplateRow['value'] = 'checked';
                    break;
            }

            $bitHeadline = substr($strValue, 0, 1) == "#";
            if ($bitHeadline) {
                $strTitle = trim(substr($strValue, 1));
                $strElements .= "<b>{$strTitle}</b><br>";
            } else {
                $bitIndent = substr($strValue, 0, 1) == "-";
                if ($bitIndent) {
                    $arrTemplateRow["title"] = substr($strValue, 1);
                    $arrTemplateRow["css"] = "style='margin-left:20px;'";
                }
                $strElements .= $this->objTemplate->fillTemplateFile($arrTemplateRow, "/admin/skins/kajona_v4/elements.tpl", $strElementRow, true);
            }
        }

        $arrTemplate["elements"] = $strElements;

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", $strElement, true);
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
     * @return string
     */
    public function formHeader($strAction, $strName = "", $strEncoding = "", $strOnSubmit = null, $strMethod = "POST")
    {

        $strOnSubmit = $strOnSubmit ?? "require('forms').defaultOnSubmit(this);return false;";

        $arrTemplate = array();
        $arrTemplate["name"] = ($strName != "" ? $strName : "form".generateSystemid());
        $arrTemplate["action"] = $strAction;
        $arrTemplate["method"] = in_array($strMethod, array("GET", "POST")) ? $strMethod : "POST";
        $arrTemplate["enctype"] = $strEncoding;
        $arrTemplate["onsubmit"] = $strOnSubmit;
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
     * Returns a headline in a form
     *
     * @param string $strText
     * @param string $strClass
     *
     * @param string $strLevel
     * @return string
     */
    public function formHeadline($strText, $strClass = "", $strLevel = "h2")
    {
        $arrTemplate = array();
        $arrTemplate["text"] = $strText;
        $arrTemplate["class"] = $strClass;
        $arrTemplate["level"] = $strLevel;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "headline_form", true);
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
        return $strPeFields.$this->objTemplate->fillTemplateFile(array(), "/admin/skins/kajona_v4/elements.tpl", "form_close");
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
            "title"       => $objEntry->getStrDisplayName(),
            "image"       => $objEntry->getStrGridIcon(),
            "actions"     => $strActions,
            "systemid"    => $objEntry->getSystemid(),
            "subtitle"    => $objEntry->getStrLongDescription(),
            "info"        => $objEntry->getStrAdditionalInfo(),
            "cssaddon"    => $strCSSAddon,
            "clickaction" => $strClickAction
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
                "listid"          => $strListId,
                "sameTable"       => $bitOnlySameTable ? "true" : "false",
                "bitMoveToTree"   => ($bitAllowDropOnTree ? "true" : "false"),
                "elementsPerPage" => $intElementsPerPage,
                "curPage"         => $intCurPage
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
        return $this->objTemplate->fillTemplateFile(array(), "/admin/skins/kajona_v4/elements.tpl", "list_footer");
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
        return $this->objTemplate->fillTemplateFile(array("listid" => $strListId), "/admin/skins/kajona_v4/elements.tpl", "dragable_list_footer");
    }

    /**
     * Renders a simple admin-object, implementing ModelInterface
     *
     * @param AdminListableInterface|ModelInterface|Model $objEntry
     * @param string $strActions
     * @param bool $bitCheckbox
     *
     * @return string
     */
    public function simpleAdminList(AdminListableInterface $objEntry, $strActions, $bitCheckbox = false)
    {
        $strImage = $objEntry->getStrIcon();
        if (is_array($strImage)) {
            $strImage = AdminskinHelper::getAdminImage($strImage[0], $strImage[1]);
        } else {
            $strImage = AdminskinHelper::getAdminImage($strImage);
        }

        $strCSSAddon = "";
        if (method_exists($objEntry, "getIntRecordStatus")) {
            $strCSSAddon = $objEntry->getIntRecordStatus() == 0 ? "disabled" : "";
        }

        return $this->genericAdminList(
            $objEntry->getSystemid(),
            $objEntry->getStrDisplayName(),
            $strImage,
            $strActions,
            $objEntry->getStrAdditionalInfo(),
            $objEntry->getStrLongDescription(),
            $bitCheckbox,
            $strCSSAddon,
            $objEntry->getIntRecordDeleted() != 1 ? "" : "1"
        );
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
        $arrTemplate = array();
        $arrTemplate["listitemid"] = $strId;
        $arrTemplate["image"] = $strIcon;
        $arrTemplate["title"] = $strName;
        $arrTemplate["center"] = $strAdditionalInfo;
        $arrTemplate["actions"] = $strActions;
        $arrTemplate["description"] = $strDescription;
        $arrTemplate["cssaddon"] = $strCssAddon;
        $arrTemplate["deleted"] = $strDeleted;

        if ($bitCheckbox) {
            $arrTemplate["checkbox"] = $this->objTemplate->fillTemplateFile(array("systemid" => $strId), "/admin/skins/kajona_v4/elements.tpl", "generallist_checkbox");
        }


        if ($strDescription != "") {
            if ($this->objTemplate->providesSection("/admin/skins/kajona_v4/elements.tpl", "generallist_desc")) {
                $strSection = "generallist_desc";
            } else {
                $strSection = "generallist_desc_1";
            }
        } else {
            if ($this->objTemplate->providesSection("/admin/skins/kajona_v4/elements.tpl", "generallist")) {
                $strSection = "generallist";
            } else {
                $strSection = "generallist_1";
            }
        }

        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", $strSection);
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
                    "title"      => $objOneAction->getStrTitle(),
                    "icon"       => $objOneAction->getStrIcon(),
                    "targeturl"  => $objOneAction->getStrTargetUrl(),
                    "renderinfo" => $objOneAction->getBitRenderInfo() ? "1" : "0",
                    "onclick"    => $objOneAction->getStrOnClickHandler()
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
     */
    public function listButton($strContent)
    {
        $arrTemplate = array();
        $arrTemplate["content"] = $strContent;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "list_button");
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
        $strQuestion = StringUtil::replace("%%element_name%%", htmlToString($strElementName, true), $strQuestion);


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
        //get the reload-url
        $strParam = "";
        if (StringUtil::indexOf($strConfirmationLinkHref, "javascript:") === false) {
            $strParam = (StringUtil::indexOf($strConfirmationLinkHref, "?") ? "&" : "?") ."reloadUrl='+encodeURIComponent(document.location.hash.substr(1))+'";
        }

        if ($strConfirmationButtonLabel == "") {
            $strConfirmationButtonLabel = Carrier::getInstance()->getObjLang()->getLang("commons_ok", "system");
        }

        //create the list-button and the js code to show the dialog
        $strButton = Link::getLinkAdminManual(
            "href=\"#\" onclick=\"javascript:jsDialog_1.setTitle('{$strHeader}'); jsDialog_1.setContent('{$strDialogContent}', '{$strConfirmationButtonLabel}',  '".$strConfirmationLinkHref.$strParam."'); jsDialog_1.init(); return false;\"",
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
            $strParam = "?reloadUrl='+encodeURIComponent(document.location.hash.substr(1))+'";
        }

        if ($strConfirmationButtonLabel == "") {
            $strConfirmationButtonLabel = Carrier::getInstance()->getObjLang()->getLang("commons_ok", "system");
        }

        //create the list-button and the js code to show the dialog
        return Link::getLinkAdminManual(
            "href=\"#\" onclick=\"javascript:jsDialog_1.setTitle('{$strHeader}'); jsDialog_1.setContent('{$strDialogContent}', '{$strConfirmationButtonLabel}',  '".$strConfirmationLinkHref.$strParam."'); jsDialog_1.init(); return false;\"",
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
            throw new Exception("failed loading instance for ".(is_object($objInstance) ? " @ ".get_class($objInstance) : $objInstance), Exception::$level_ERROR);
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
require(['ajax'], function(ajax){
    ajax.setSystemStatusMessages.strActiveIcon = '".addslashes(AdminskinHelper::getAdminImage("icon_enabled", $strAltActive))."';
    ajax.setSystemStatusMessages.strInActiveIcon = '".addslashes(AdminskinHelper::getAdminImage("icon_disabled", $strAltInactive))."';
});
</script>";
            Carrier::getInstance()->getObjSession()->setSession("statusButton", "true", Session::$intScopeRequest);
        }

        $strButton = getLinkAdminManual(
            "href=\"javascript:require('ajax').setSystemStatus('".$objRecord->getSystemid()."', ".($bitReload ? "true" : "false").");\"",
            $strLinkContent,
            "",
            "",
            "",
            "statusLink_".$objRecord->getSystemid(),
            false
        );

        return $this->listButton($strButton).$strJavascript;
    }

    // --- Misc-Elements ------------------------------------------------------------------------------------

    /**
     * Returns a warning box, e.g. shown before deleting a record
     *
     * @param string $strContent
     * @param string $strClass
     *
     * @return string
     */
    public function warningBox($strContent, $strClass = "alert-warning")
    {
        $arrTemplate = array();
        $arrTemplate["content"] = $strContent;
        $arrTemplate["class"] = $strClass;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "warning_box");
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
        $arrTemplate = array();
        $arrTemplate["text"] = $strText;
        $arrTemplate["class"] = $strClass;
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "text_row");
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
        $arrReturn[1] = "<a href=\"javascript:require('util').fold('".$strID."', ".($strCallbackVisible != "" ? $strCallbackVisible : "null").", ".($strCallbackInvisible != "" ? $strCallbackInvisible : "null").");\">".$strLinkText."</a>";
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
        $strLinkText = "<span id='{$strID}'>".($bitVisible ? $strImageVisible : $strImageInvisible)."</span> ".$strLinkText;

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
     * @return string
     */
    public function getTabbedContent(array $arrTabs, $bitFullHeight = false)
    {

        $strMainTabId = generateSystemid();
        $bitRemoteContent = false;

        $strTabs = "";
        $strTabContent = "";
        $strClassaddon = "active in ";
        foreach ($arrTabs as $strTitle => $strContent) {
            $strTabId = generateSystemid();
            // if content is an url enable ajax loading
            if (substr($strContent, 0, 7) == 'http://' || substr($strContent, 0, 8) == 'https://') {
                $strTabs .= $this->objTemplate->fillTemplateFile(array("tabid" => $strTabId, "tabtitle" => $strTitle, "href" => $strContent, "classaddon" => $strClassaddon), "/admin/skins/kajona_v4/elements.tpl", "tabbed_content_tabheader");
                $strTabContent .= $this->objTemplate->fillTemplateFile(array("tabid" => $strTabId, "tabcontent" => "", "classaddon" => $strClassaddon . "contentLoading"), "/admin/skins/kajona_v4/elements.tpl", "tabbed_content_tabcontent");
                $bitRemoteContent = true;
            } else {
                $strTabs .= $this->objTemplate->fillTemplateFile(array("tabid" => $strTabId, "tabtitle" => $strTitle, "href" => "", "classaddon" => $strClassaddon), "/admin/skins/kajona_v4/elements.tpl", "tabbed_content_tabheader");
                $strTabContent .= $this->objTemplate->fillTemplateFile(array("tabid" => $strTabId, "tabcontent" => $strContent, "classaddon" => $strClassaddon), "/admin/skins/kajona_v4/elements.tpl", "tabbed_content_tabcontent");
            }
            $strClassaddon = "";
        }

        $strHtml = $this->objTemplate->fillTemplateFile(array("id" => $strMainTabId, "tabheader" => $strTabs, "tabcontent" => $strTabContent, "classaddon" => ($bitFullHeight === true ? 'fullHeight' : '')), "/admin/skins/kajona_v4/elements.tpl", "tabbed_content_wrapper");

        // add ajax loader if we have content which we need to fetch per ajax
        if ($bitRemoteContent) {
            $strHtml.= <<<HTML
<script type="text/javascript">
require(['jquery', 'forms'], function($, forms){
    $('#{$strMainTabId} > li > a[data-href!=""]').on('click', function(e){
        if(!$(e.target).data('loaded')) {
            forms.loadTab($(e.target).data('target').substr(1), $(e.target).data('href'));
            $(e.target).data('loaded', true);
        }
    });
    
    $(document).ready(function(){
        var el = $('#{$strMainTabId} > li.active > a[data-href!=""]');
        if (el.length > 0) {
            if(!el.data('loaded')) {
                forms.loadTab(el.data('target').substr(1), el.data('href'));
                el.data('loaded', true);
            }
        }
    });
});
</script>
HTML;
        }

        return $strHtml;
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

            $arrToggleEntries[] = ["name" => $objOneAspect->getStrDisplayName(), "onclick" => "require('moduleNavigation').switchAspect('{$objOneAspect->getSystemid()}'); return false;"];

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
                "tags"      => "fa-tags",
                "search"    => "fa-search"
            ];


            $strModules = "";
            foreach ($arrNaviInstances as $objOneInstance) {
                $arrActions = AdminHelper::getModuleActionNaviHelper($objOneInstance);

                $strActions = "";
                foreach ($arrActions as $strOneAction) {
                    if (trim($strOneAction) != "") {
                        $arrActionEntries = [
                            "action" => $strOneAction
                        ];
                        $strActions .= $this->objTemplate->fillTemplateFile($arrActionEntries, "/admin/skins/kajona_v4/elements.tpl", "sitemap_action_entry");
                    } else {
                        $strActions .= $this->objTemplate->fillTemplateFile([], "/admin/skins/kajona_v4/elements.tpl", "sitemap_divider_entry");
                    }
                }

                $arrModuleLevel = [
                    "module"      => Link::getLinkAdmin($objOneInstance->getStrName(), "", "", Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName())),
                    "actions"     => $strActions,
                    "systemid"    => $objOneInstance->getSystemid(),
                    "moduleTitle" => $objOneInstance->getStrName(),
                    "moduleName"  => Carrier::getInstance()->getObjLang()->getLang("modul_titel", $objOneInstance->getStrName()),
                    "moduleHref"  => Link::getLinkAdminHref($objOneInstance->getStrName(), ""),
                    "aspectId"    => $objOneAspect->getSystemid()
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
                ).$strModules;
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
                "<span class='dropdown pull-left'><a href='#' data-toggle='dropdown' role='button'>".AdminskinHelper::getAdminImage("icon_submenu")."</a>{$strToggle}</span>"
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
        $strRows = "<script type=\"text/javascript\"> require(['breadcrumb'], function(breadcrumb) { breadcrumb.resetBar()}); </script>"; //TODO: das muss hier raus, falsche stelle?
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
     */
    public function getContentToolbar(array $arrEntries, $intActiveEntry = -1)
    {
        $strRows = "";
        foreach ($arrEntries as $intI => $strOneEntry) {
            if ($intI == $intActiveEntry) {
                $strRows .= $this->objTemplate->fillTemplateFile(array("entry" => addslashes($strOneEntry), "active" => 'true'), "/admin/skins/kajona_v4/elements.tpl", "contentToolbar_entry");
            } else {
                $strRows .= $this->objTemplate->fillTemplateFile(array("entry" => addslashes($strOneEntry), "active" => 'false'), "/admin/skins/kajona_v4/elements.tpl", "contentToolbar_entry");
            }
        }
        return $this->objTemplate->fillTemplateFile(array("entries" => $strRows), "/admin/skins/kajona_v4/elements.tpl", "contentToolbar_wrapper");
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
        $strEntry = $this->objTemplate->fillTemplateFile(array("entry" => addslashes($strButton), "identifier" => $strIdentifier, "active" => $bitActive ? 'true' : 'false'), "/admin/skins/kajona_v4/elements.tpl", "contentToolbar_entry");
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
                    require(['forms', 'domReady'], function(forms, domReady){
                        domReady(function(){
                            forms.renderMandatoryFields($strRequiredFields);
                        });
                    });
                </script>";
            }
        }

        $arrErrors = method_exists($objCalling, "getValidationErrors") ? $objCalling->getValidationErrors() : array();
        if (count($arrErrors) == 0) {
            return $strRendercode;
        }

        $strRows = "";
        $strRendercode .= "<script type=\"text/javascript\">

         require(['forms', 'domReady'], function(forms, domReady) {
            domReady(function(){
                forms.renderMissingMandatoryFields([";

        foreach ($arrErrors as $strKey => $arrOneErrors) {
            foreach ($arrOneErrors as $strOneError) {
                if ($strOneError != "") {
                    $strRows .= $this->objTemplate->fillTemplateFile(array("field_errortext" => $strOneError), "/admin/skins/kajona_v4/elements.tpl", "error_row");
                }
                $strRendercode .= "[ '".$strKey."' ], ";
            }
        }
        $strRendercode .= " [] ]); }); });</script>";
        $arrTemplate = array();
        $arrTemplate["errorrows"] = $strRows;
        $arrTemplate["errorintro"] = Lang::getInstance()->getLang("errorintro", "system");
        $arrTemplate["errorclass"] = $bitErrorsAsWarning ? "alert-warning" : "alert-danger";
        return $this->objTemplate->fillTemplateFile($arrTemplate, "/admin/skins/kajona_v4/elements.tpl", "error_container").$strRendercode;
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
                    "<span style=\"color: orange\">WARNING</span>"
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
                    "href"     => Link::getLinkAdminHref($strModule, $strAction, $strLinkAdd + ["pv" => ($intCurrentpage + 1)], true, true)
                ),
                "/admin/skins/kajona_v4/elements.tpl",
                "pageview_link_forward"
            );
        }
        if ($intCurrentpage > 1) {
            $arrTemplate["linkBackward"] = $this->objTemplate->fillTemplateFile(
                array(
                    "linkText" => Carrier::getInstance()->getObjLang()->getLang("commons_back", "commons"),
                    "href"     => Link::getLinkAdminHref($strModule, $strAction, $strLinkAdd + ["pv" => ($intCurrentpage - 1)], true, true)
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
                require([\"util\"], function(util){
                    util.setBrowserFocus(\"".$strElementId."\");
                });
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
        $arrTemplate["treeId"] = "tree_".$objTreeConfig->getStrRootNodeId();
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
        $arrTemplate["treeId"] = "tree_".$objTreeConfig->getStrRootNodeId();
        $arrTemplate["treeConfig"] = $objTreeConfig->toJson();
        $arrTemplate["treeviewExpanders"] = is_array($objTreeConfig->getArrNodesToExpand()) ?
            json_encode(array_values($objTreeConfig->getArrNodesToExpand())) : "[]" ;//using array_values just in case an associative array is being returned
        $arrTemplate["initiallySelectedNodes"] = is_array($objTreeConfig->getArrInitiallySelectedNodes()) ?
            json_encode(array_values($objTreeConfig->getArrInitiallySelectedNodes())) : "[]" ;//using array_values just in case an associative array is being returned

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
            require(['tags'], function(tags){
                tags.createFavoriteEnabledIcon = '".addslashes(AdminskinHelper::getAdminImage("icon_favorite", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_remove", "tags")))."';
                tags.createFavoriteDisabledIcon = '".addslashes(AdminskinHelper::getAdminImage("icon_favoriteDisabled", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_add", "tags")))."';
            });
            </script>";

            $strImage = TagsFavorite::getAllFavoritesForUserAndTag(Carrier::getInstance()->getObjSession()->getUserID(), $objTag->getSystemid()) != null ?
                AdminskinHelper::getAdminImage("icon_favorite", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_remove", "tags")) :
                AdminskinHelper::getAdminImage("icon_favoriteDisabled", Carrier::getInstance()->getObjLang()->getLang("tag_favorite_add", "tags"));

            $strFavorite = $strJs."<a href=\"#\" onclick=\"require('tags').createFavorite('".$objTag->getSystemid()."', this); return false;\">".$strImage."</a>";
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
            require(['jquery', 'v4skin'], function($, v4skin){
                $(function() {
                    function split( val ) {
                        return val.split( /,\s*/ );
                    }

                    function extractLast( term ) {
                        return split( term ).pop();
                    }

                    var objConfig = new v4skin.defaultAutoComplete();
                    objConfig.source = function(request, response) {
                        $.ajax({
                            url: '".getLinkAdminXml("tags", "getTagsByFilter")."',
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

                    $('#".StringUtil::replace(array("[", "]"), array("\\\[", "\\\]"), $strName)."').autocomplete(objConfig);
                });
            
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
                "elementName"          => $arrOneEntry["name"],
                "elementAction"        => $arrOneEntry["onclick"],
                "elementLink"          => $arrOneEntry["link"],
                "elementActionEscaped" => StringUtil::replace("'", "\'", $arrOneEntry["onclick"]),
                "elementFullEntry"     => $arrOneEntry["fullentry"]
            );

            if ($arrTemplate["elementFullEntry"] != "") {
                $strCurTemplate = "contextmenu_entry_full";
            }
            else {
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
                    }
                    else {
                        $arrSubTemplate = array(
                            "elementName"          => $arrOneSubmenu["name"],
                            "elementAction"        => $arrOneSubmenu["onclick"],
                            "elementLink"          => $arrOneSubmenu["link"],
                            "elementActionEscaped" => StringUtil::replace("'", "\'", $arrOneSubmenu["onclick"]),
                            "elementFullEntry"     => $arrOneEntry["fullentry"]
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
                }
                else {
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
