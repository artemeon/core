<?php
/*"******************************************************************************************************
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 ********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Class to handle all link-generations, backend and portal.
 * Moved from functions.php to a central class in order to avoid duplicated code.
 * As a side-effect, the class may be overridden using the /project schema in order
 * to modify the link generation (if required).
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.3
 */
class Link
{

    /**
     * Generates a link using the content passed. The content is either a string or an associative array.
     * If its an array the values are escaped. Returns a link in the format: <a [name]=[value]>[text]</a>
     *
     * @param string|array $strLinkContent
     * @param string $strText
     * @param string $strAlt
     * @param string $strImage
     * @param string $strImageId
     * @param string $strLinkId
     * @param bool $bitTooltip
     * @param string $strCss
     *
     * @return string
     */
    public static function getLinkAdminManual($strLinkContent, $strText, $strAlt = "", $strImage = "", $strImageId = "", $strLinkId = "", $bitTooltip = true, $strCss = "")
    {
        $arrAttr = [];

        if (!empty($strImage)) {
            $strText = AdminskinHelper::getAdminImage($strImage, $strAlt, true, $strImageId);
        } elseif (!empty($strText)) {
            if ($bitTooltip && (trim($strAlt) == "" || $strAlt == $strText)) {
                $bitTooltip = false;
                $strAlt = empty($strAlt) ? strip_tags($strText) : $strAlt;
            }
        }

        if (!empty($strAlt)) {
            $arrAttr["title"] = $strAlt;
        }

        if (!empty($strLinkId)) {
            $arrAttr["id"] = $strLinkId;
        }

        if ($bitTooltip) {
            $arrAttr["rel"] = "tooltip";
        }

        if (!empty($strCss)) {
            $arrAttr["class"] = $strCss;
        }

        if (is_array($strLinkContent)) {
            $arrAttr = array_merge($arrAttr, $strLinkContent);
        }

        $arrParts = [];
        foreach ($arrAttr as $strAttrName => $strAttrValue) {
            if (!empty($strAttrValue)) {
                if (is_scalar($strAttrValue)) {
                    $arrParts[] = $strAttrName . "=\"" . htmlspecialchars($strAttrValue, ENT_COMPAT | ENT_HTML401, "UTF-8", false) . "\"";
                } else {
                    throw new \InvalidArgumentException("Array must contain only scalar values");
                }
            }
        }

        if (is_string($strLinkContent)) {
            array_unshift($arrParts, $strLinkContent);
        }

        return "<a " . implode(" ", $arrParts) . ">" . $strText . "</a>";
    }

    /**
     * Generates a link for the admin-area
     *
     * @param string $strModule
     * @param string $strAction
     * @param string|array $strParams - may be a string of params or an array
     * @param string $strText
     * @param string $strAlt
     * @param string $strImage
     * @param bool $bitTooltip
     * @param string $strCss
     * @param string $strOnClick
     *
     * @return string
     */
    public static function getLinkAdmin($strModule, $strAction, $strParams = "", $strText = "", $strAlt = "", $strImage = "", $bitTooltip = true, $strCss = "", $strOnClick = "")
    {
        $strHref = "href=\"" . Link::getLinkAdminHref($strModule, $strAction, $strParams, true, true) . "\"";
        if (!empty($strOnClick)) {
            $strHref .= ' onclick="' . htmlspecialchars($strOnClick) . '"';
        }
        return self::getLinkAdminManual($strHref, $strText, $strAlt, $strImage, "", "", $bitTooltip, $strCss);
    }

    /**
     * Generates a link for the admin-area
     *
     * @param string $strModule
     * @param string $strAction
     * @param string|array $strParams - may be a string of params or an array
     * @param bool $bitEncodedAmpersand
     * @param bool $bitHashUrl
     * @param bool $bitPath - used to generate a uri without scheme i.e. /module/action/systemid?foo=bar
     * @return string
     */
    public static function getLinkAdminHref($strModule, $strAction = "", $strParams = "", $bitEncodedAmpersand = true, $bitHashUrl = true, $bitPath = false)
    {
        //systemid in params?
        $strSystemid = "";
        $strParams = self::sanitizeUrlParams($strParams, $strSystemid);
        $arrParams = array();
        if ($strParams !== "") {
            $arrParams = explode("&", $strParams);
        }

        //urlencoding
        $strModule = urlencode($strModule);
        $strAction = urlencode($strAction);

        if ($bitHashUrl || $bitPath) {

            //scheme: /admin/module.action.systemid
            $strLink = "";
            if (!$bitPath) {
                $strLink = "#";
            }

            if ($strModule != "" && $strAction == "" && $strSystemid == "") {
                $strLink .= "/" . $strModule . "";
            } elseif ($strModule != "" && $strAction != "" && $strSystemid == "") {
                $strLink .= "/" . $strModule . "/" . $strAction . "";
            } else {
                $strLink .= "/" . $strModule . "/" . $strAction . "/" . $strSystemid . "";
            }

            if (count($arrParams) > 0) {
                $strLink .= "?" . implode("&", $arrParams);
            }

            if (!$bitEncodedAmpersand) {
                $strLink = StringUtil::replace("&amp;", "&", $strLink);
            }

            if (!$bitPath) {
                return _webpath_ . "/" . $strLink;
            } else {
                return $strLink;
            }
        }

        //rewriting enabled?
        if (SystemSetting::getConfigValue("_system_mod_rewrite_") == "true") {
            $strPrefix = "/admin";
            if (SystemSetting::getConfigValue("_system_mod_rewrite_admin_only_") == "true") {
                $strPrefix = "";
            }

            //scheme: /admin/module.action.systemid
            if ($strModule != "" && $strAction == "" && $strSystemid == "") {
                $strLink = _webpath_ . $strPrefix . "/" . $strModule . ".html";
            } elseif ($strModule != "" && $strAction != "" && $strSystemid == "") {
                $strLink = _webpath_ . $strPrefix . "/" . $strModule . "/" . $strAction . ".html";
            } else {
                $strLink = _webpath_ . $strPrefix . "/" . $strModule . "/" . $strAction . "/" . $strSystemid . ".html";
            }

            if (count($arrParams) > 0) {
                $strLink .= "?" . implode("&amp;", $arrParams);
            }

        } else {
            $strLink = "" . _indexpath_ . "?module=" . $strModule .
                ($strAction != "" ? "&amp;action=" . $strAction : "") .
                ($strSystemid != "" ? "&amp;systemid=" . $strSystemid : "");

            if (count($arrParams) > 0) {
                $strLink .= "&amp;" . (implode("&amp;", $arrParams));
            }
        }

        if (!$bitEncodedAmpersand) {
            $strLink = StringUtil::replace("&amp;", "&", $strLink);
        }

        return $strLink;
    }

    /**
     * Generates an admin-url to trigger xml-requests. Takes care of url-rewriting
     *
     * @param string $strModule
     * @param string $strAction
     * @param string|array $strParams - may be a string of params or an array
     * @param bool $bitEncodedAmpersand
     *
     * @return mixed|string
     */
    public static function getLinkAdminXml($strModule, $strAction = "", $strParams = "", $bitEncodedAmpersand = false)
    {
        //systemid in params?
        $strSystemid = "";
        $strParams = self::sanitizeUrlParams($strParams, $strSystemid);
        $arrParams = array();
        if ($strParams !== "") {
            $arrParams = explode("&", $strParams);
        }

        //urlencoding
        $strModule = urlencode($strModule);
        $strAction = urlencode($strAction);

        //rewriting enabled?
        if (false && SystemSetting::getConfigValue("_system_mod_rewrite_") == "true") {
            $strPrefix = "/admin";
            if (SystemSetting::getConfigValue("_system_mod_rewrite_admin_only_") == "true") {
                $strPrefix = "";
            }

            //scheme: /admin/module.action.systemid
            if ($strModule != "" && $strAction == "" && $strSystemid == "") {
                $strLink = _webpath_ . "/xml" . $strPrefix . "/" . $strModule;
            } elseif ($strModule != "" && $strAction != "" && $strSystemid == "") {
                $strLink = _webpath_ . "/xml" . $strPrefix . "/" . $strModule . "/" . $strAction;
            } else {
                $strLink = _webpath_ . "/xml" . $strPrefix . "/" . $strModule . "/" . $strAction . "/" . $strSystemid;
            }

            if (count($arrParams) > 0) {
                $strLink .= "?" . implode("&amp;", $arrParams);
            }

        } else {
            $strLink = "" . _webpath_ . "/xml.php?module=" . $strModule .
                ($strAction != "" ? "&amp;action=" . $strAction : "") .
                ($strSystemid != "" ? "&amp;systemid=" . $strSystemid : "");

            if (count($arrParams) > 0) {
                $strLink .= "&amp;" . (implode("&amp;", $arrParams));
            }
        }

        if (!$bitEncodedAmpersand) {
            $strLink = StringUtil::replace("&amp;", "&", $strLink);
        }

        return $strLink;
    }

    /**
     * Generates a link opening in a popup in admin-area
     *
     * @param string $strModule
     * @param string $strAction
     * @param string|array $strParams - may be a string of params or an array
     * @param string $strText
     * @param string $strAlt
     * @param string $strImage
     * @param int|string $intWidth
     * @param int|string $intHeight
     * @param string $strTitle
     * @param bool $bitTooltip
     * @param bool $bitPortalEditor
     *
     * @return string
     */
    public static function getLinkAdminPopup($strModule, $strAction, $strParams = "", $strText = "", $strAlt = "", $strImage = "", $intWidth = "500", $intHeight = "500", $strTitle = "", $bitTooltip = true, $bitPortalEditor = false)
    {
        $strLink = "";
        //if($strParams != "")
        //    $strParams = str_replace("&", "&amp;", $strParams);
        $strTitle = addslashes(StringUtil::replace(array("\n", "\r"), array(), strip_tags(nl2br($strTitle))));

        if ($bitPortalEditor && $intHeight == "500") {
            $intHeight = 690;
        }

        //urlencoding
        $strModule = urlencode($strModule);
        $strAction = urlencode($strAction);

        if ($bitPortalEditor) {
            if (is_string($strParams)) {
                $strParams .= "&pe=1";
            } elseif (is_array($strParams)) {
                $strParams["pe"] = "1";
            }
        }

        if ($strImage != "") {
            if ($strAlt == "") {
                $strAlt = $strAction;
            }

            if (!$bitTooltip) {
                $strLink = "<a href=\"#\" onclick=\"window.open('" . Link::getLinkAdminHref($strModule, $strAction, $strParams) . "','" . $strTitle . "','scrollbars=yes,resizable=yes,width=" . $intWidth . ",height=" . $intHeight . "'); return false;\" " .
                    "title=\"" . strip_tags($strAlt) . "\">" . AdminskinHelper::getAdminImage($strImage, $strAlt, true) . "</a>";
            } else {
                $strLink = "<a href=\"#\" onclick=\"window.open('" . Link::getLinkAdminHref($strModule, $strAction, $strParams) . "','" . $strTitle . "','scrollbars=yes,resizable=yes,width=" . $intWidth . ",height=" . $intHeight . "'); return false;\" " .
                    "title=\"" . strip_tags($strAlt) . "\" rel=\"tooltip\">" . AdminskinHelper::getAdminImage($strImage, $strAlt, true) . "</a>";
            }
        }

        if ($strImage == "" && $strText != "") {
            $bitTooltip = $bitTooltip && $strAlt != "";

            $strLink = "<a href=\"#\" " . ($bitPortalEditor ? "class=\"pe_link\"" : "") . " " . ($bitTooltip ? "title=\"" . strip_tags($strAlt) . "\" rel=\"tooltip\" " : "") . " " .
                "onclick=\"window.open('" . Link::getLinkAdminHref($strModule, $strAction, $strParams) . "','" . $strTitle . "','scrollbars=yes,resizable=yes,width=" . $intWidth . ",height=" . $intHeight . "'); return false;\">" . $strText . "</a>";
        }
        return $strLink;
    }

    /**
     * Generates a link opening in a dialog in admin-area
     *
     * @param string $strModule
     * @param string $strAction
     * @param string|array $strParams - may be a string of params or an array
     * @param string $strText
     * @param string $strAlt
     * @param string $strImage
     * @param string $strTitle
     * @param bool $bitTooltip
     * @param bool $bitPortalEditor
     * @param bool|string $strOnClick
     *
     * @return string
     */
    public static function getLinkAdminDialog($strModule, $strAction, $strParams = "", $strText = "", $strAlt = "", $strImage = "", $strTitle = "", $bitTooltip = true, $bitPortalEditor = false, $strOnClick = "")
    {
        $strTitle = StringUtil::jsSafeString(StringUtil::replace(array("\n", "\r"), array(), strip_tags(nl2br(html_entity_decode($strTitle)))));

        if (is_string($strParams)) {
            if ($bitPortalEditor) {
                $strParams .= "&pe=1";
            }
            $strParams .= "&folderview=1";
        } elseif (is_array($strParams)) {
            if ($bitPortalEditor) {
                $strParams["pe"] = "1";
            }
            $strParams["folderview"] = "1";
        }

        //urlencoding
        $strModule = urlencode($strModule);
        $strAction = urlencode($strAction);

        if ($strOnClick == "") {
            $strLink = Link::getLinkAdminHref($strModule, $strAction, $strParams);
            $strOnClick = "DialogHelper.showIframeDialog('{$strLink}', '{$strTitle}'); return false;";
        }

        $strLinkContent = $strText;
        if ($strAlt == "") {
            $strAlt = $strText;
        }

        if ($strImage !== "") {
            $strLinkContent = AdminskinHelper::getAdminImage($strImage, $strAlt, true);
        }

        return "<a href=\"#\" onclick=\"" . $strOnClick . "\" title=\"" . strip_tags($strAlt) . "\" " . ($bitTooltip ? " rel=\"tooltip\"" : "") . " >" . $strLinkContent . "</a>";
    }

    /**
     * @param string $strModule
     * @param string $strAction
     * @param array $strParams
     * @return string
     */
    public static function clientRedirectHref($strModule, $strAction = "", array $strParams = [], $bitEncodedAmpersand = true, $bitHashUrl = true)
    {
        return self::clientRedirectManual(self::getLinkAdminHref($strModule, $strAction, $strParams, $bitEncodedAmpersand, $bitHashUrl));
    }

    /**
     * @param string $strLink
     * @return string
     */
    public static function clientRedirectManual($strLink)
    {
        $strLink = json_encode($strLink);
        return "<script type='text/javascript'>location.href={$strLink};</script>";
    }

    public static function plainUrlToHashUrl($strUrl)
    {
        $strUrl = StringUtil::replace(array(_indexpath_, _webpath_, "?"), "", $strUrl);
        $strUrl = StringUtil::replace("&amp;", "&", $strUrl);
        $arrFragments = explode("&", $strUrl);

        $strRedirectModule = "";
        $strRedirectAction = "";

        foreach ($arrFragments as $intPartKey => $strOnePart) {
            if ($strOnePart == "admin=1") { //TODO remove, only for backwards compat
                unset($arrFragments[$intPartKey]);
                continue;
            }

            $arrKeyValue = explode("=", $strOnePart);

            if ($arrKeyValue[0] == "module") {
                $strRedirectModule = $arrKeyValue[1];
                unset($arrFragments[$intPartKey]);
                continue;
            }

            if ($arrKeyValue[0] == "action") {
                $strRedirectAction = $arrKeyValue[1];
                unset($arrFragments[$intPartKey]);
                continue;
            }
        }

        return Link::getLinkAdminHref($strRedirectModule, $strRedirectAction, implode("&", $arrFragments), true, true);
    }

    /**
     * Converts the given array to an urlencoded array.
     *
     * Extracts the systemid out of the string|array and updates the passed reference with the
     * systemid.
     *
     * If $arrParams is null, an empty array is being returned.
     *
     * @param array|string $arrParams
     * @param string &$strSystemid
     *
     * @return array
     */
    public static function sanitizeUrlParams($arrParams, &$strSystemid = "")
    {
        if ($arrParams === null) {
            $arrParams = array();
        }

        /*In case it is a string -> build associative array*/
        if (is_string($arrParams)) {
            $strParams = StringUtil::replace("&amp;", "&", $arrParams);

            //if given, remove first ampersand from params
            if (substr($strParams, 0, 1) == "&") {
                $strParams = substr($strParams, 1);
            }

            $arrParams = [];
            foreach (explode("&", $strParams) as $strOneSet) {
                $arrEntry = explode("=", $strOneSet);
                if (count($arrEntry) == 2) {
                    $arrParams[$arrEntry[0]] = urldecode($arrEntry[1]);
                }
            }
        }

        /* Create string params*/
        foreach ($arrParams as $strParamKey => $strValue) {
            //First convert boolean values to string representation "true", "false", then use http_build_query
            //This is done because http_build_query converts booleans to "1"(true) or "0"(false) and not to "true", "false"
            if (is_bool($strValue)) {
                $arrParams[$strParamKey] = $strValue === true ? "true" : "false";
            }

            //Handle systemid param -> removes system from the array and sets reference variable $strSystemid
            if ($strParamKey === "systemid") {
                unset($arrParams[$strParamKey]);
                $strSystemid = $strValue;

                if (!validateSystemid($strValue) && $strValue != "%systemid%") {
                    $strSystemid = "";
                }
            }
        }
        $strParams = http_build_query($arrParams, null, "&");
        return $strParams;
    }

    /** creates a link containing #/vm/ at the beginning in order to enter vue context (similar to router-link of vue-router)
     *
     * @param $strVueLink
     * @param string $strText
     * @param string $strAlt
     * @param string $strImage
     * @param bool $bitTooltip
     * @param string $strCss
     * @param string $strOnClick
     * @return string
     */
    public static function getVueLink($strVueLink, $strText = "", $strAlt = "", $strImage = "", $bitTooltip = true, $strCss = "", $strOnClick = "")
    {
        $strHref = "href=\"#/vm/" . $strVueLink . "\"";
        if (!empty($strOnClick)) {
            $strHref .= ' onclick="' . htmlspecialchars($strOnClick) . '"';
        }
        return self::getLinkAdminManual($strHref, $strText, $strAlt, $strImage, "", "", $bitTooltip, $strCss);
    }

}
