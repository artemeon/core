<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                           *
********************************************************************************************************/

namespace Kajona\V4skin\Admin\Skins\Kajona_V4;

use Kajona\System\Admin\AdminskinImageresolverInterface;
use Kajona\System\System\StringUtil;

/**
 * @author sidler@mulchprod.de
 * @since 4.2
 * @package module_v4skin
 */
class AdminskinImageresolver implements AdminskinImageresolverInterface
{



    /**
     * Converts the passed image-name into a real, resolvable code-fragment (such as an image-tag or an
     * i-tag with css-code).
     *
     * @param string $strName
     * @param string $strAlt
     * @param bool $bitBlockTooltip
     * @param string $strEntryId
     *
     * @return string
     */
    public function getImage($strName, $strAlt = "", $bitBlockTooltip = false, $strEntryId = "")
    {


        $strFaImage = $this->getFASomeImage($strName);

        if ($strFaImage !== null) {
            if ($bitBlockTooltip || $strAlt == "") {
                return $strFaImage;
            }

            return "<span rel=\"tooltip\" title=\"" . $strAlt . "\" data-kajona-icon='" . $strName . "' >" . $strFaImage . "</span>";
        }

        if ($strName == "loadingSmall") {
            return "<span rel=\"tooltip\" title=\"\" data-kajona-icon='" . $strName . "' ><i class='fa fa-spinner fa-spin'></i></span>";
        }


        return "<img src=\""._webpath_.$strName."\"  alt=\"".$strAlt."\"  ".(!$bitBlockTooltip ? "rel=\"tooltip\" title=\"".$strAlt."\" " : "")." ".($strEntryId != "" ? " id=\"".$strEntryId."\" " : "")." data-kajona-icon='".$strName."' />";
    }


    /**
     * @param string $strImage
     * @param string $strTooltip
     *
     * @return null|string
     */
    private function getFASomeImage($strImage)
    {

        $strImage = StringUtil::replace("_small", "", $strImage);

        $strColor = null;
        if (StringUtil::startsWith($strImage, "icon_flag_hex")) {
            $intLastUnderscore = StringUtil::lastIndexOf($strImage, "_");
            $strColor = StringUtil::substring($strImage, $intLastUnderscore +1);
            $strImage = StringUtil::substring($strImage, 0, $intLastUnderscore);
        }

        if (isset(self::$arrFAImages[$strImage])) {
            $strImage = self::$arrFAImages[$strImage];

            if ($strColor !== null) {
                $strImage = StringUtil::replace("{color}", $strColor, $strImage);
            }

            return $strImage;
        }
        return null;
    }


    public static $arrFAImages = [

        "icon_accept"                 => "<i class='kj-icon fa fa-check'></i>",
        "icon_acceptGreen"            => "<i class='kj-icon fa fa-check' style='color: green'></i>",
        "icon_acceptDisabled"         => "<span class='kj-icon fa-stack'><i class='fa fa-check'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_arrowDown"              => "<i class='kj-icon fa fa-arrow-circle-down'></i>",
        "icon_arrowUp"                => "<i class='kj-icon fa fa-arrow-circle-up'></i>",
        "icon_aspect"                 => "<i class='kj-icon fa fa-columns'></i>",
        "icon_balance"                => "<i class='kj-icon fa fa-balance-scale'></i>",
        "icon_binary"                 => "<i class='kj-icon fa fa-file'></i>",
        "icon_blank"                  => "<i class='kj-icon fa fa-file-o'></i>",
        "icon_book"                   => "<i class='kj-icon fa fa-book'></i>",
        "icon_bookLens"               => "<span class='kj-icon fa-stack'><i class='fa fa-book'></i><i class='fa fa-search fa-stack-1x kj-stack' ></i></span>",
        "icon_calendar"               => "<i class='kj-icon fa fa-calendar'></i>",
        "icon_clone"                  => "<i class='kj-icon fa fa-clone'></i>",
        "icon_comment"                => "<i class='kj-icon fa fa-comment'></i>",
        "icon_copy"                   => "<i class='kj-icon fa fa-files-o'></i>",
        "icon_crop"                   => "<i class='kj-icon fa fa-crop'></i>",
        "icon_crop_accept"            => "<span class='kj-icon fa-stack'><i class='fa fa-crop'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green'></i></span>",
        "icon_crop_acceptDisabled"    => "<span class='kj-icon fa-stack'><i class='fa fa-crop'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_dashboard"              => "<i class='kj-icon fa fa-tachometer'></i>",
        "icon_delete"                 => "<i class='kj-icon fa fa-trash-o'></i>",
        "icon_deleteDisabled"         => "<span class='kj-icon fa-stack'><i class='fa fa-trash-o'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_deleteLocked"           => "<span class='kj-icon fa-stack'><i class='fa fa-trash-o'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_disabled"               => "<i class='kj-icon fa fa-flag' style='color: #FF0000;'></i>",
        "icon_disabledLocked"         => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #FF0000;'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_dot"                    => "<i class='kj-icon fa fa-star'></i>",
        "icon_downloads"              => "<i class='kj-icon fa fa-download'></i>",
        "icon_earth"                  => "<i class='kj-icon fa fa-globe '></i>",
        "icon_earthDisabled"          => "<span class='kj-icon fa-stack'><i class='fa fa-globe'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_edit"                   => "<i class='kj-icon fa fa-pencil'></i>",
        "icon_editDisabled"           => "<span class='kj-icon fa-stack'><i class='fa fa-pencil'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_editLocked"             => "<span class='kj-icon fa-stack'><i class='fa fa-pencil'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_enabled"                => "<i class='kj-icon fa fa-flag' style='color: #00893d;'></i>",
        "icon_enabledLocked"          => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #00893d;'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_event"                  => "<i class='kj-icon fa fa-calendar-o'></i>",
        "icon_eventLocked"            => "<span class='kj-icon fa-stack'><i class='fa fa-calendar-o'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_excel"                  => "<i class='kj-icon fa fa-file-excel-o'></i>",
        "icon_externalBrowser"        => "<i class='kj-icon fa fa-search'></i>",
        "icon_favorite"               => "<i class='kj-icon fa fa-bookmark'></i>",
        "icon_favoriteDisabled"       => "<i class='kj-icon fa fa-bookmark-o'></i>",
        "icon_filter"                 => "<i class='kj-icon fa fa-filter'></i>",
        "icon_flag_hex"               => "<i class='kj-icon fa fa-flag-o' style='color: {color}'></i>",
        "icon_flag_hex_filled"        => "<i class='kj-icon fa fa-flag' style='color: {color}'></i>",
        "icon_flag_hex_disabled"      => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: {color}'></i></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_black"             => "<i class='kj-icon fa fa-flag-o' style='color: #000000'></i>",
        "icon_flag_black_filled"      => "<i class='kj-icon fa fa-flag' style='color: #000000'></i>",
        "icon_flag_black_disabled"    => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #000000'></i></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_blue"              => "<i class='kj-icon fa fa-flag-o' style='color: #0040b3'></i>",
        "icon_flag_blue_filled"       => "<i class='kj-icon fa fa-flag' style='color: #0040b3'></i>",
        "icon_flag_blue_disabled"     => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #0040b3'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_brown"             => "<i class='kj-icon fa fa-flag-o' style='color: #d47a0b'></i>",
        "icon_flag_brown_filled"      => "<i class='kj-icon fa fa-flag' style='color: #d47a0b'></i>",
        "icon_flag_brown_disabled"    => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #d47a0b'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_green"             => "<i class='kj-icon fa fa-flag-o' style='color: #0e8500'></i>",
        "icon_flag_green_filled"      => "<i class='kj-icon fa fa-flag' style='color: #0e8500'></i>",
        "icon_flag_green_disabled"    => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #0e8500'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_grey"              => "<i class='kj-icon fa fa-flag-o' style='color: #aeaeae'></i>",
        "icon_flag_grey_filled"       => "<i class='kj-icon fa fa-flag' style='color: #aeaeae'></i>",
        "icon_flag_grey_disabled"     => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #aeaeae'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_orange"            => "<i class='kj-icon fa fa-flag-o' style='color: #ff5600'></i>",
        "icon_flag_orange_filled"     => "<i class='kj-icon fa fa-flag' style='color: #ff5600'></i>",
        "icon_flag_orange_disabled"   => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #ff5600'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_purple"            => "<i class='kj-icon fa fa-flag-o' style='color: #e23bff'></i>",
        "icon_flag_purple_filled"     => "<i class='kj-icon fa fa-flag' style='color: #e23bff'></i>",
        "icon_flag_purple_disabled"   => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #e23bff'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_red"               => "<i class='kj-icon fa fa-flag-o' style='color: #d42f00'></i>",
        "icon_flag_red_filled"        => "<i class='kj-icon fa fa-flag' style='color: #d42f00'></i>",
        "icon_flag_red_disabled"      => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #d42f00'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_flag_yellow"            => "<i class='kj-icon fa fa-flag-o' style='color: #ffe211'></i>",
        "icon_flag_yellow_filled"     => "<i class='kj-icon fa fa-flag' style='color: #ffe211'></i>",
        "icon_flag_yellow_disabled"   => "<span class='kj-icon fa-stack'><i class='fa fa-flag' style='color: #ffe211'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_folderActionLevelup"    => "<span class='kj-icon fa-stack'><i class='fa fa-folder-open-o'></i><i class='fa fa-arrow-circle-up fa-stack-1x kj-stack' ></i></span>",
        "icon_folderActionOpen"       => "<span class='kj-icon fa-stack'><i class='fa fa-folder-open-o'></i><i class='fa fa-search fa-stack-1x kj-stack' ></i></span>",
        "icon_folderClosed"           => "<i class='kj-icon fa fa-folder-o'></i>",
        "icon_folderOpen"             => "<i class='kj-icon fa fa-folder-open-o'></i>",
        "icon_gallery"                => "<i class='kj-icon fa fa-picture-o'></i>",
        "icon_group"                  => "<i class='kj-icon fa fa-users'></i>",
        "icon_archive"                => "<i class='kj-icon fa fa-archive'></i>",
        "icon_history"                => "<i class='kj-icon fa fa-clock-o'></i>",
        "icon_wait"                   => "<i class='kj-icon fa fa-hourglass'></i>",
        "icon_image"                  => "<i class='kj-icon fa fa-picture-o'></i>",
        "icon_install"                => "<i class='kj-icon fa fa-download'></i>",
        "icon_installDisabled"        => "<span class='kj-icon fa-stack'><i class='fa fa-download'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_key"                    => "<i class='kj-icon fa fa-key'></i>",
        "icon_language"               => "<i class='kj-icon fa fa-microphone'></i>",
        "icon_lens"                   => "<i class='kj-icon fa fa-search'></i>",
        "icon_lockerOpen"             => "<i class='kj-icon fa fa-unlock'></i>",
        "icon_lockerClosed"           => "<i class='kj-icon fa fa-lock'></i>",
        "icon_mail"                   => "<i class='kj-icon fa fa-envelope-o'></i>",
        "icon_mailDisabled"           => "<span class='kj-icon fa-stack'><i class='fa fa-envelope-o'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_mailNew"                => "<i class='kj-icon fa fa-envelope'></i>",
        "icon_missing"                => "<i class='kj-icon fa fa-times'></i>",
        "icon_missingRed"             => "<i class='kj-icon fa fa-times' style='color: red'></i>",
        "icon_module"                 => "<i class='kj-icon fa fa-hdd-o'></i>",
        "icon_move"                   => "<i class='kj-icon fa fa-arrows'></i>",
        "icon_movie"                  => "<i class='kj-icon fa fa-film'></i>",
        "icon_new"                    => "<i class='kj-icon fa fa-plus-circle'></i>",
        "icon_new_alias"              => "<span class='kj-icon fa-stack'><i class='fa fa-plus-circle'></i><i class='fa fa-link fa-stack-1x kj-stack'></i></span>",
        "icon_new_multi"              => "<span class='kj-icon fa-stack'><i class='fa fa-plus-circle'></i><i class='fa fa-chevron-down fa-stack-1x kj-stack'></i></span>",
        "icon_news"                   => "<i class='kj-icon fa fa-quote-left'></i>",
        "icon_page"                   => "<i class='kj-icon fa fa-file-o'></i>",
        "icon_pageLocked"             => "<span class='kj-icon fa-stack'><i class='fa fa-file-o'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_page_alias"             => "<span class='kj-icon fa-stack'><i class='fa fa-file-o'></i><i class='fa fa-chevron-right fa-stack-1x kj-stack'></i></span>",
        "icon_permissions"            => "<span class='kj-icon fa-stack'><i class='fa fa-users'></i><i class='fa fa-star fa-stack-1x kj-stack' style='color: #ffa500;'></i></span>",
        "icon_permissions_inherited"  => "<i class='kj-icon fa fa-users'></i>",
        "icon_phar"                   => "<i class='kj-icon fa fa-file-archive-o'></i>",
        "icon_powerpoint"             => "<i class='kj-icon fa fa-file-powerpoint-o'></i>",
        "icon_progressbar"            => "<i class='kj-icon fa fa-spinner icon-spin'></i>",
        "icon_question"               => "<i class='kj-icon fa fa-question-circle'></i>",
        "icon_reply"                  => "<i class='kj-icon fa fa-reply'></i>",
        "icon_rotate_left"            => "<i class='kj-icon fa fa-undo'></i>",
        "icon_rotate_right"           => "<i class='kj-icon fa fa-repeat'></i>",
        "icon_rss"                    => "<i class='kj-icon fa fa-rss'></i>",
        "icon_sitemap"                => "<i class='kj-icon fa fa-sitemap'></i>",
        "icon_sound"                  => "<i class='kj-icon fa fa-music'></i>",
        "icon_sort_numeric"           => "<i class='kj-icon fa fa-sort-numeric-asc'></i>",
        "icon_submenu"                => "<i class='kj-icon fa fa-chevron-down'></i>",
        "icon_sync"                   => "<i class='kj-icon fa fa-refresh'></i>",
        "icon_systemtask"             => "<i class='kj-icon fa fa-tasks'></i>",
        "icon_tag"                    => "<i class='kj-icon fa fa-tag'></i>",
        "icon_text"                   => "<i class='kj-icon fa fa-file-text-o'></i>",
        "icon_textDisabled"           => "<span class='kj-icon fa-stack'><i class='fa fa-file-text-o'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_treeBranchOpen"         => "<span class='kj-icon fa-stack'><i class='fa fa-sitemap'></i><i class='fa fa-chevron-right fa-stack-1x kj-stack'></i></span>",
        "icon_treeBranchOpenDisabled" => "<span class='kj-icon fa-stack'><i class='fa fa-sitemap'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_treeLeaf"               => "<i class='kj-icon fa fa-sitemap'></i>",
        "icon_treeLeaf_link"          => "<span class='kj-icon fa-stack'><i class='fa fa-sitemap'></i><i class='fa fa-link fa-stack-1x kj-stack'></i></span>",
        "icon_treeLink"               => "<i class='kj-icon fa fa-link'></i>",
        "icon_treeLevelUp"            => "<span class='kj-icon fa-stack'><i class='fa fa-sitemap'></i><i class='fa fa-chevron-up fa-stack-1x kj-stack'></i></span>",
        "icon_treeRoot"               => "<i class='kj-icon fa fa-sitemap'></i>",
        "icon_undo"                   => "<i class='kj-icon fa fa-undo'></i>",
        "icon_undoDisabled"           => "<span class='kj-icon fa-stack'><i class='fa fa-undo'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_update"                 => "<i class='kj-icon fa fa-cloud-download'></i>",
        "icon_updateDisabled"         => "<span class='kj-icon fa-stack'><i class='fa fa-cloud-download'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green'></i></span>",
        "icon_updateError"            => "<span class='kj-icon fa-stack'><i class='fa fa-cloud-download'></i><i class='fa fa-exclamation-triangle fa-stack-1x kj-stack'></i></span>",
        "icon_upload"                 => "<i class='kj-icon fa fa-upload'></i>",
        "icon_user"                   => "<i class='kj-icon fa fa-user'></i>",
        "icon_userPending"            => "<span class='kj-icon fa-stack'><i class='fa fa-user'></i><i class='fa fa-pause fa-stack-1x kj-stack' style='color: orange;'></i></span>",
        "icon_userDone"               => "<span class='kj-icon fa-stack'><i class='fa fa-user'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green;'></i></span>",
        "icon_userError"              => "<span class='kj-icon fa-stack'><i class='fa fa-user'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red;'></i></span>",
        "icon_userswitch"             => "<span class='kj-icon fa-stack'><i class='fa fa-user'></i><i class='fa fa-play fa-stack-1x kj-stack'></i></span>",
        "icon_warning"                => "<i class='kj-icon fa fa-exclamation-triangle'></i>",
        "icon_warning_red"            => "<i class='kj-icon fa fa-exclamation-triangle' style='color: red;'></i>",
        "icon_warning_orange"         => "<i class='kj-icon fa fa-exclamation-triangle' style='color: orange;'></i>",
        "icon_word"                   => "<i class='kj-icon fa fa-ms-word'></i>",
        "icon_workflow"               => "<i class='kj-icon fa fa-cog'></i>",
        "icon_workflowExecuted"       => "<span class='kj-icon fa-stack'><i class='fa fa-cog'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green'></i></span>",
        "icon_workflowNew"            => "<span class='kj-icon fa-stack'><i class='fa fa-cog'></i><i class='fa fa-star fa-stack-1x kj-stack' style='color: orange'></i></span>",
        "icon_workflowScheduled"      => "<span class='kj-icon fa-stack'><i class='fa fa-cog'></i><i class='fa fa-pause fa-stack-1x kj-stack'  '></i></span>",
        "icon_workflowTrigger"        => "<span class='kj-icon fa-stack'><i class='fa fa-cog'></i><i class='fa fa-play fa-stack-1x kj-stack' ></i></span>",
        "icon_workflow_ui"            => "<i class='kj-icon fa fa-list-alt'></i>",
        "icon_zoom_in"                => "<i class='kj-icon fa fa-search-plus'></i>",
        "icon_zoom_out"               => "<i class='kj-icon fa fa-search-minus'></i>",
        "loadingSmall"                => "<i class='kj-icon fa fa-spinner fa-spin'></i>",


        "icon_attachement" => "<i class='kj-icon fa fa-paperclip'></i>",
        "icon_entity" => "<i class='kj-icon fa fa-building'></i>",
        "icon_subentity" => "<i class='kj-icon fa fa-building-o'></i>",
        "icon_chart" => "<i class='kj-icon fa fa-line-chart'></i>",
        "icon_checkbox" => "<i class='kj-icon fa fa-check-square'></i>",
        "icon_column" => "<i class='kj-icon fa fa-columns'></i>",
        "icon_dimension" => "<i class='kj-icon fa fa-bar-chart'></i>",
        "icon_document" => "<i class='kj-icon fa fa-file-archive-o'></i>",
        "icon_dropdown" => "<i class='kj-icon fa fa-caret-square-o-down'></i>",
        "icon_ereignis" => "<span class='fa-stack kj-icon' style='text-align: center'><i class='fa fa-square' style='color:orange;'></i><i class='fa fa-stack-1x fa-inverse kj-text-icon' style='border-bottom:0; font-weight: normal;'>E</i></span>",
        "icon_kriterium" => "<i class='kj-icon fa fa fa-check-square-o'></i>",
        "icon_kriteriumLocked" => "<span class='kj-icon fa-stack'><i class='fa fa-check-square-o'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_kriterium_ampel" => "<i class='kj-icon fa fa fa-check-circle-o'></i>",
        "icon_incidents" => "<span class='fa-stack kj-icon'><i class='fa kj-text-icon'>I</i></span>",
        "icon_list" => "<i class='kj-icon fa fa-list'></i>",
        "icon_listPending" => "<span class='kj-icon fa-stack'><i class='fa fa-list'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: #ffa500;'></i></span>",
        "icon_listDone" => "<span class='kj-icon fa-stack'><i class='fa fa-list'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green;'></i></span>",
        "icon_ls" => "<i class='kj-icon fa fa-file-text'></i>",
        "icon_ls_multi" => "<span class='kj-icon fa-stack'><i class='fa fa-file-text'></i><i class='fa fa-file-text fa-stack-1x kj-stack' ></i></span>",
        "icon_ls_norm" => "<i class='kj-icon fa fa-columns'></i>",
        "icon_monita" => "<span class='fa-stack kj-icon'><i class='fa kj-text-icon'>M</i></span>",
        "icon_oprisk" => "<span class='fa-stack kj-icon' style='text-align: center'><i class='fa fa-square' style='color:#DA0000'></i><i class='fa fa-stack-1x fa-inverse kj-text-icon' style='border-bottom:0; font-weight: normal;'>R</i></span>",
        "icon_oprisk_uebergreif" => "<span class='fa-stack kj-icon' style='text-align: center'><i class='fa fa-square' style='color:#DA0000'></i><i class='fa fa-stack-1x fa-inverse kj-text-icon' style='font-size: 0.5em; left: -0.1em; border-bottom:0; font-weight: normal;'>ÜR</i></span>",
        "icon_pdf" => "<i class='kj-icon fa fa-file-pdf-o'></i>",
        "icon_play" => "<i class='kj-icon fa fa-play-circle-o'></i>",
        "icon_playNew" => "<span class='kj-icon fa-stack'><i class='kj-icon fa fa-play-circle-o'></i><i class='fa fa-star fa-stack-1x kj-stack' style='color: orange'></i></span>",
        "icon_preconfirmed" => "<i class='kj-icon fa fa-flag' style='color: orange;'></i>",
        "icon_preconfirmedLocked" => "<span class='kj-icon fa-stack'><i class='fa fa-flag'></i><i class='fa fa-lock fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_project" => "<i class='kj-icon fa fa-tasks'></i>",
        "icon_project_issue" => "<i class='kj-icon fa fa-exclamation-triangle'></i>",
        "icon_report" => "<i class='fa fa-external-link'></i>",
        "icon_risk" => "<i class='kj-icon fa fa-registered'></i>",
        "icon_scale" => "<i class='kj-icon fa fa-arrows-h'></i>",
        "icon_sidebarClose" => "<i class='fa fa-expand'></i>",
        "icon_summary" => "<i class='kj-icon fa fa-file-text-o'></i>",
        "icon_servicer" => "<i class='kj-icon fa fa-building-o'></i>",
        "icon_servicerDisabled" => "<span class='kj-icon fa-stack'><i class='fa fa-building-o'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_servicerEnabled" => "<span class='kj-icon fa-stack'><i class='fa fa-building-o'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green;'></i></span>",
        "icon_outsourcing" => "<i class='kj-icon fa fa-sign-out'></i>",
        "icon_insourcing" => "<i class='kj-icon fa fa-sign-in'></i>",
        "icon_subservicer" => "<i class='kj-icon fa fa-building'></i>",
        "icon_szenario" => "<span class='fa-stack kj-icon' style='text-align: center'><i class='fa fa-square' style='color:#DA0000'></i><i class='fa fa-stack-1x fa-inverse kj-text-icon' style='border-bottom:0; font-weight: normal;'>S</i></span>",
        "icon_table" => "<i class='kj-icon fa fa-table'></i>",
        "icon_textfield" => "<i class='kj-icon fa fa-file-text-o'></i>",
        "icon_treeLeistungsschein" => "<span class='kj-icon fa-stack'><i class='fa fa-sitemap'></i><i class='fa fa-check fa-stack-1x kj-stack'></i></span>",
        "icon_training" => "<i class='kj-icon fa fa-calendar-check-o'></i>",
        "icon_vector" => "<i class='kj-icon fa fa-list'></i>",
        "icon_verursacher" => "<span class='fa-stack kj-icon' style='text-align: center'><i class='fa fa-square' style='color:orange;'></i><i class='fa fa-stack-1x fa-inverse kj-text-icon' style='border-bottom:0; font-weight: normal;'>V</i></span>",
        "icon_policy" => "<i class='kj-icon fa fa-university'></i>",
        "icon_policyDirty" => "<span class='kj-icon fa-stack'><i class='fa fa-university'></i><i class='fa fa-star fa-stack-1x kj-stack' style='color: #ffa500;'></i></span>",
        "icon_compliance" => "<i class='kj-icon fa fa-gavel'></i>",
        "icon_complianceDisabled" => "<span class='kj-icon fa-stack'><i class='fa fa-gavel'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red;'></i></span>",
        "icon_complianceDone" => "<span class='kj-icon fa-stack'><i class='fa fa-gavel'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green;'></i></span>",
        "icon_index" => "<i class='kj-icon fa fa-bolt'></i>",
        "icon_inventar" => "<i class='kj-icon fa fa-archive'></i>",
        "icon_inventarDisabled" => "<span class='kj-icon fa-stack'><i class='fa fa-archive'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red;'></i></span>",
        "icon_inventarReview" => "<span class='kj-icon fa-stack'><i class='fa fa-archive'></i><i class='fa fa-pause fa-stack-1x kj-stack' style='color: orange;'></i></span>",
        "icon_inventarDone" => "<span class='kj-icon fa-stack'><i class='fa fa-archive'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green;'></i></span>",
        "icon_radar" => "<i class='kj-icon fa fa-eye'></i>",
        "icon_radarDisabled" => "<span class='kj-icon fa-stack'><i class='fa fa-eye'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red;'></i></span>",
        "icon_radarReview" => "<span class='kj-icon fa-stack'><i class='fa fa-eye'></i><i class='fa fa-pause fa-stack-1x kj-stack' style='color: orange;'></i></span>",
        "icon_radarDone" => "<span class='kj-icon fa-stack'><i class='fa fa-eye'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green;'></i></span>",
        "icon_control" => "<i class='kj-icon fa fa-circle-o'></i>",
        "icon_controlRateable" => "<i class='kj-icon fa fa-check-circle-o'></i>",
        "icon_business_process" => "<i class='kj-icon fa fa-industry'></i>",
        "icon_confirmation" =>  "<i class='kj-icon fa fa-envelope'></i>",
        "icon_commodity_group" =>  "<i class='kj-icon fa fa-cubes'></i>",
        "icon_product_group" =>  "<i class='kj-icon fa fa-cube'></i>",
        "icon_shopping_cart" =>  "<i class='kj-icon fa fa-shopping-cart'></i>",
        "icon_shopping_cartReady" =>  "<span class='kj-icon fa-stack'><i class='fa fa-shopping-cart'></i><i class='fa fa-check fa-stack-1x kj-stack' style='color: green'></i></span>",
        "icon_shopping_cartError" =>  "<span class='kj-icon fa-stack'><i class='fa fa-shopping-cart'></i><i class='fa fa-ban fa-stack-1x kj-stack' style='color: red'></i></span>",
        "icon_it_sytems" => "<i class='kj-icon fa fa-cogs'></i>",
        "icon_link" => "<i class='kj-icon fa fa-link'></i>",
        "icon_ra_container"       => "<i class='kj-icon fa fa-folder-o'></i>",
        "icon_ra_bait_container"  => "<span class='kj-icon fa-stack'><i class='fa fa-folder-o'></i><i class='fa fa-cogs fa-stack-1x kj-stack'></i></span>",

        //note: a copy of this markup is placed in agp_util.js and elements_artemeon.tpl::input_dropdown_ampel
        "icon_ampel_gelb" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_gelb'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle orange'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_rot" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_rot'><i class='kj-icon fa fa-circle red' ></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_gruen" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_gruen'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle green'></i></span>",
        "icon_ampel_grau" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_grau'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_schwarz" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_schwarz'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle black'></i><i class='kj-icon fa fa-circle-thin'></i></span>",


        "icon_ampel_gelb_4" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_gelb'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle orange'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_rot_4" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_rot'><i class='kj-icon fa fa-circle red' ></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_gruen_4" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_gruen'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle green'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_grau_4" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_grau'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_schwarz_4" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_schwarz'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle black'></i></span>",

        "icon_ampel_rot_2" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_rot'><i class='kj-icon fa fa-circle red' ></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_rot_gelb_2" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_rot_gelb'><i class='kj-icon fa fa-circle red' ></i><i class='kj-icon fa fa-circle orange'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_gelb_2" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_gelb'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle orange'></i><i class='kj-icon fa fa-circle-thin'></i></span>",
        "icon_ampel_gruen_gelb_2" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_gruen_gelb'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle orange'></i><i class='kj-icon fa fa-circle green'></i></span>",
        "icon_ampel_gruen_2" => "<span class='traffic-icon' data-kajona-icon='icon_ampel_gruen'><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle-thin'></i><i class='kj-icon fa fa-circle green'></i></span>",

    ];


}
