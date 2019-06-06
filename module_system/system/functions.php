<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                                *
********************************************************************************************************/

require_once (__DIR__."/StringUtil.php");

use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Link;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Validators\EmailValidator;
use Kajona\System\System\Validators\NumericValidator;
use Kajona\System\System\Validators\TextValidator;

/**
 * @package module_system
 */

//For the sake of different loaders - check again :(
//Mbstring loaded? If yes, we could use unicode-safe string-functions
if (!defined("_mbstringloaded_")) {
    if (extension_loaded("mbstring")) {
        define("_mbstringloaded_", true);
        mb_internal_encoding("UTF-8");
    }
    else {
        define("_mbstringloaded_", false);
    }
}


/**
 * Returns a value from the GET-array
 *
 * @param string $strKey
 *
 * @return string
 * @deprecated use @link{Carrier::getInstance()->getParam("")} instead
 */
function getGet($strKey)
{
    if (issetGet($strKey)) {
        return $_GET[$strKey];
    }
    else {
        return "";
    }
}

/**
 * Returns the complete GET-Array
 *
 * @return mixed
 */
function getArrayGet()
{
    return $_GET;
}

/**
 * Returns the complete FILE-Array
 *
 * @return mixed
 */
function getArrayFiles()
{
    return $_FILES;
}


/**
 * Checks whether a kay exists in GET-array, or not
 *
 * @param string $strKey
 *
 * @return bool
 * @deprecated use Carrier::issetParam
 */
function issetGet($strKey)
{
    if (isset($_GET[$strKey])) {
        return true;
    }
    else {
        return false;
    }
}

/**
 * Returns a value from the Post-array
 *
 * @param string $strKey
 *
 * @return string
 * @deprecated use @link{Carrier::getInstance()->getParam("")} instead
 */
function getPost($strKey)
{
    if (issetPost($strKey)) {
        return $_POST[$strKey];
    }
    else {
        return "";
    }
}

/**
 * Returns the complete POST-array
 *
 * @return mixed
 */
function getArrayPost()
{
    return $_POST;
}

/**
 * Looks, if a key is in POST-array or not
 *
 * @param string $strKey
 *
 * @return bool
 * @deprecated use Carrier::issetParam
 */
function issetPost($strKey)
{
    if (isset($_POST[$strKey])) {
        return true;
    }
    else {
        return false;
    }
}

/**
 * Returns the complete http-post-body as raw-data.
 * Please indicate whether the source is encoded in "multipart/form-data", in this case
 * the data is read another way internally.
 *
 * @param bool $bitMultipart
 *
 * @return string
 * @since 3.4.0
 */
function getPostRawData($bitMultipart = false)
{
    /*
      sidler, 06/2014: removed, since no longer supported and deprecated up from php 5.6

    if($bitMultipart)
        return $HTTP_RAW_POST_DATA;
    else
    */
    return file_get_contents("php://input");
}

/**
 * Returns a value from the SERVER-Array
 *
 * @param mixed $strKey
 *
 * @return string
 */
function getServer($strKey)
{
    if (issetServer($strKey)) {
        return $_SERVER[$strKey];
    }
    else {
        return "";
    }
}

/**
 * Returns all params passed during startup by get, post or files
 *
 * @return array
 * @deprecated use Carrier::getAllParams() instead
 * @see Carrier::getAllParams()
 * @todo remove
 */
function getAllPassedParams()
{
    return Carrier::getAllParams();
}

/**
 * Key in SERVER-Array?
 *
 * @param string $strKey
 *
 * @return bool
 * @deprecated use Carrier::issetParam
 */
function issetServer($strKey)
{
    if (isset($_SERVER[$strKey])) {
        return true;
    }
    else {
        return false;
    }
}

/**
 * Tests, if the requested cookie exists
 *
 * @param string $strKey
 *
 * @return bool
 * @deprecated
 */
function issetCookie($strKey)
{
    return isset($_COOKIE[$strKey]);
}

/**
 * Provides access to the $_COOKIE Array.
 * NOTE: Use the cookie-class to get data from cookies!
 *
 * @param string $strKey
 *
 * @return mixed
 * @deprecated
 */
function getCookie($strKey)
{
    if (issetCookie($strKey)) {
        return $_COOKIE[$strKey];
    }
    else {
        return "";
    }
}

/**
 * Generates a link using the content passed.
 * The param $strLinkContent should contain all contents of the a-tag.
 * The system renders <a $strLinkContent title... class...>($strText|$strImage)</a>
 *
 * @deprecated
 *
 * @param string $strLinkContent
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
function getLinkAdminManual($strLinkContent, $strText, $strAlt = "", $strImage = "", $strImageId = "", $strLinkId = "", $bitTooltip = true, $strCss = "")
{
    return Link::getLinkAdminManual($strLinkContent, $strText, $strAlt, $strImage, $strImageId, $strLinkId, $bitTooltip, $strCss);
}

/**
 * Generates a link for the admin-area
 *
 * @deprecated
 *
 * @param string $strModule
 * @param string $strAction
 * @param string $strParams
 * @param string $strText
 * @param string $strAlt
 * @param string $strImage
 * @param bool $bitTooltip
 * @param string $strCss
 *
 * @return string
 */
function getLinkAdmin($strModule, $strAction, $strParams = "", $strText = "", $strAlt = "", $strImage = "", $bitTooltip = true, $strCss = "")
{
    return Link::getLinkAdmin($strModule, $strAction, $strParams, $strText, $strAlt, $strImage, $bitTooltip, $strCss);
}

/**
 * Generates a link for the admin-area
 *
 * @deprecated
 *
 * @param string $strModule
 * @param string $strAction
 * @param string $strParams
 * @param bool $bitEncodedAmpersand
 *
 * @return string
 */
function getLinkAdminHref($strModule, $strAction = "", $strParams = "", $bitEncodedAmpersand = true)
{
    return Link::getLinkAdminHref($strModule, $strAction, $strParams, $bitEncodedAmpersand);
}

/**
 * Generates an admin-url to trigger xml-requests. Takes care of url-rewriting
 *
 * @deprecated
 *
 * @param $strModule
 * @param string $strAction
 * @param string $strParams
 * @param bool $bitEncodedAmpersand
 *
 * @return mixed|string
 */
function getLinkAdminXml($strModule, $strAction = "", $strParams = "", $bitEncodedAmpersand = false)
{
    return Link::getLinkAdminXml($strModule, $strAction, $strParams, $bitEncodedAmpersand);
}


/**
 * Generates a link opening in a popup in admin-area
 *
 * @deprecated
 *
 * @param string $strModule
 * @param string $strAction
 * @param string $strParams
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
function getLinkAdminPopup($strModule, $strAction, $strParams = "", $strText = "", $strAlt = "", $strImage = "", $intWidth = "500", $intHeight = "500", $strTitle = "", $bitTooltip = true, $bitPortalEditor = false)
{
    return Link::getLinkAdminPopup($strModule, $strAction, $strParams, $strText, $strAlt, $strImage, $intWidth, $intHeight, $strTitle, $bitTooltip, $bitPortalEditor);
}

/**
 * Generates a link opening in a dialog in admin-area
 *
 * @deprecated
 *
 * @param string $strModule
 * @param string $strAction
 * @param string $strParams
 * @param string $strText
 * @param string $strAlt
 * @param string $strImage
 * @param string $strTitle
 * @param bool $bitTooltip
 * @param bool $bitPortalEditor
 * @param bool|string $strOnClick
 * @param null|int $intWidth
 * @param null|int $intHeight
 *
 * @return string
 */
function getLinkAdminDialog($strModule, $strAction, $strParams = "", $strText = "", $strAlt = "", $strImage = "", $strTitle = "", $bitTooltip = true, $bitPortalEditor = false, $strOnClick = "", $intWidth = null, $intHeight = null)
{
    return Link::getLinkAdminDialog($strModule, $strAction, $strParams, $strText, $strAlt, $strImage, $strTitle, $bitTooltip, $bitPortalEditor, $strOnClick);
}

/**
 * Returns an image-tag with surrounding tooltip
 *
 * @param string $strImage
 * @param string $strAlt
 * @param bool $bitNoAlt
 *
 * @return string
 * @deprecated replaced by AdminskinHelper::getAdminImage()
 * @see AdminskinHelper::getAdminImage()
 */
function getImageAdmin($strImage, $strAlt = "", $bitNoAlt = false)
{
    return AdminskinHelper::getAdminImage($strImage, $strAlt, $bitNoAlt);
}

/**
 * Determines the rights-filename of a system-record. Looks up if the record
 * uses its' own rights or inherits the rights from another record.
 *
 * @param string $strSystemid
 *
 * @return string
 * @todo move to toolkit
 */
function getRightsImageAdminName($strSystemid)
{
    if (Carrier::getInstance()->getObjRights()->isInherited($strSystemid)) {
        return "icon_permissions_inherited";
    }
    else {
        return "icon_permissions";
    }
}


/**
 * Converts a php size string (e.g. "4M") into bytes
 *
 * @param int $strBytes
 *
 * @return int
 */
function phpSizeToBytes($strBytes)
{
    $intReturn = 0;

    $strBytes = StringUtil::toLowerCase($strBytes);

    if (strpos($strBytes, "m") !== false) {
        $intReturn = str_replace("m", "", $strBytes);
        $intReturn = $intReturn * 1024 * 1024;
    }
    elseif (strpos($strBytes, "k") !== false) {
        $intReturn = str_replace("m", "", $strBytes);
        $intReturn = $intReturn * 1024;
    }
    elseif (strpos($strBytes, "g") !== false) {
        $intReturn = str_replace("m", "", $strBytes);
        $intReturn = $intReturn * 1024 * 1024 * 1024;
    }

    return $intReturn;
}

/**
 * Makes out of a byte number a human readable string
 *
 * @param int $intBytes
 * @param bool $bitPhpIni (Value ends with M/K/B)
 *
 * @param bool $renderUnit
 * @return string
 */
function bytesToString($intBytes, $bitPhpIni = false, $renderUnit = true)
{
    $strReturn = "";
    if ($intBytes >= 0) {
        $arrFormats = array("B", "KB", "MB", "GB", "TB");

        if ($bitPhpIni) {
            $intBytes = phpSizeToBytes($intBytes);
        }

        $intTemp = $intBytes;
        $intCounter = 0;

        while ($intTemp > 1024) {
            $intTemp = $intTemp / 1024;
            $intCounter++;
        }

        $strReturn = number_format($intTemp, 2).($renderUnit ? " ".$arrFormats[$intCounter] : "");
        return $strReturn;
    }
    return $strReturn;
}

/**
 * Changes a timestamp to a readable string
 *
 * @param int $intTime
 * @param bool $bitLong
 *
 * @return string
 * @deprecated
 */
function timeToString($intTime, $bitLong = true)
{
    $strReturn = "";
    if ($intTime > 0) {
        if ($bitLong) {
            $strReturn = date(Carrier::getInstance()->getObjLang()->getLang("dateStyleLong", "system"), $intTime);
        }
        else {
            $strReturn = date(Carrier::getInstance()->getObjLang()->getLang("dateStyleShort", "system"), $intTime);
        }
    }
    return $strReturn;
}

/**
 * Converts a dateobject to a readable string
 *
 * @param Date $objDate
 * @param bool $bitLong
 * @param string $strFormat if given, the passed format will be used, otherwise the format defined in the i18n files
 *                          usable placeholders are: d, m, y, h, i, s
 *
 * @return string
 */
function dateToString($objDate, $bitLong = true, $strFormat = "")
{
    $strReturn = "";

    //if the $objDate is a string, convert it to date object
    if ($objDate != null && !$objDate instanceof Date && StringUtil::matches($objDate, "([0-9]){14}")) {
        $objDate = new Date($objDate);
    }

    if ($objDate instanceof Date) {

        //convert to a current date
        if ($strFormat == "") {
            if ($bitLong) {
                $strReturn = StringUtil::toLowerCase(Carrier::getInstance()->getObjLang()->getLang("dateStyleLong", "system"));
            }
            else {
                $strReturn = StringUtil::toLowerCase(Carrier::getInstance()->getObjLang()->getLang("dateStyleShort", "system"));
            }
        }
        else {
            $strReturn = $strFormat;
        }

        //"d.m.Y H:i:s";
        $strReturn = StringUtil::replace("d", $objDate->getIntDay(), $strReturn);
        $strReturn = StringUtil::replace("m", $objDate->getIntMonth(), $strReturn);
        $strReturn = StringUtil::replace("y", $objDate->getIntYear(), $strReturn);
        $strReturn = StringUtil::replace("h", $objDate->getIntHour(), $strReturn);
        $strReturn = StringUtil::replace("i", $objDate->getIntMin(), $strReturn);
        $strReturn = StringUtil::replace("s", $objDate->getIntSec(), $strReturn);

    }
    return $strReturn;
}

/**
 * Formats a number according to the localized separators.
 * Those are defined in the lang-files, different entries for
 * decimal- and thousands separator.
 *
 * @param float $floatNumber
 * @param int $intNrOfDecimals the number of decimals
 *
 * @return string
 */
function numberFormat($floatNumber, $intNrOfDecimals = 2)
{
    $strDecChar = Carrier::getInstance()->getObjLang()->getLang("numberStyleDecimal", "system");
    $strThousandsChar = Carrier::getInstance()->getObjLang()->getLang("numberStyleThousands", "system");
    return number_format((float)$floatNumber, $intNrOfDecimals, $strDecChar, $strThousandsChar);
}

/**
 * Converts a hex-string to its rgb-values
 *
 * @see http://www.jonasjohn.de/snippets/php/hex2rgb.htm
 *
 * @param string $color
 *
 * @return array
 */
function hex2rgb($color)
{
    $color = str_replace('#', '', $color);
    if (strlen($color) != 6) {
        return array(0, 0, 0);
    }
    $rgb = array();
    for ($x = 0; $x < 3; $x++) {
        $rgb[$x] = hexdec(substr($color, (2 * $x), 2));
    }
    return $rgb;
}

/**
 * Converts an array of R,G,B values to its matching hex-pendant
 *
 * @param $arrRGB
 *
 * @return string
 */
function rgb2hex($arrRGB)
{
    $strHex = "";
    foreach ($arrRGB as $intColor) {
        if ($intColor > 255) {
            $intColor = 255;
        }

        $strHexVal = dechex($intColor);
        if (StringUtil::length($strHexVal) == 1) {
            $strHexVal = '0'.$strHexVal;
        }
        $strHex .= $strHexVal;
    }
    return "#".$strHex;
}

/**
 * Splits up a html-link into its parts, such as
 * link, name, href
 *
 * @param string $strLink
 *
 * @return array
 */
function splitUpLink($strLink)
{
    //use regex to get href and name
    $arrHits = array();
    preg_match("/<a href=\"([^\"]+)\"\s+([^>]*)>(.*)<\/a>/i", $strLink, $arrHits);
    $arrReturn = array();
    $arrReturn["link"] = $strLink;
    $arrReturn["name"] = isset($arrHits[3]) ? $arrHits[3] : "";
    $arrReturn["href"] = isset($arrHits[1]) ? $arrHits[1] : "";
    return $arrReturn;
}

/**
 * Tries to find all links in a given string and creates a-tags around them.
 *
 * @param string $strText
 * @return string
 * @since 4.3
 */
function replaceTextLinks($strText)
{
    return preg_replace('/(^|\s)((http[s]{0,1}|ftp[s]{0,1}|file)\:\/\/([^\s\<\>]+))/ims', '$1<a href="$2">$2</a>', $strText);
}

/**
 * Changes HTML to simple printable strings
 *
 * @param string $strHtml
 * @param bool $bitEntities
 * @param bool $bitEscapeCrlf
 *
 * @return string
 */
function htmlToString($strHtml, $bitEntities = false, $bitEscapeCrlf = true)
{
    $strReturn = $strHtml;

    if ($bitEntities) {
        $strReturn = htmlentities($strHtml, ENT_COMPAT, "UTF-8");
    }
    else {
        if (get_magic_quotes_gpc() == 0) {
            $strReturn = str_replace("'", "\'", $strHtml);
        }
    }
    $arrSearch = array();
    if ($bitEscapeCrlf) {
        $arrSearch[] = "\r\n";
        $arrSearch[] = "\n\r";
        $arrSearch[] = "\n";
        $arrSearch[] = "\r";
    }
    $arrSearch[] = "%%";

    $arrReplace = array();
    if ($bitEscapeCrlf) {
        $arrReplace[] = "<br />";
        $arrReplace[] = "<br />";
        $arrReplace[] = "<br />";
        $arrReplace[] = "<br />";
    }
    $arrReplace[] = "\%\%";


    $strReturn = str_replace($arrSearch, $arrReplace, $strReturn);
    return $strReturn;
}

/**
 * Wrapper to phps strip_tags
 * Removes all html and php tags in a string
 *
 * @param string $strHtml
 * @param string $strAllowTags
 *
 * @return string
 */
function htmlStripTags($strHtml, $strAllowTags = "")
{
    $strReturn = strip_tags($strHtml, $strAllowTags);
    return $strReturn;
}

/**
 * Encodes an url to be more safe but being less strict than urlencode()
 *
 * @param string $strText
 *
 * @return string
 */
function saveUrlEncode($strText)
{
    $arraySearch = array(" ");
    $arrayReplace = array("%20");
    return str_replace($arraySearch, $arrayReplace, $strText);
}


/**
 * A helper to remove xss relevant chars from a string. To be used when embedding user-content into a js-call
 * @param $strText
 *
 * @return mixed|string
 */
function xssSafeString($strText) {
    if($strText == "")
        return $strText;

    $strText = urldecode($strText);
    $strText = strip_tags($strText);
    $strText = addslashes($strText);
    $strText = filter_var($strText, FILTER_SANITIZE_STRING, FILTER_SANITIZE_URL);
    //$strText = str_replace(array('\'', '"', '(', ')', ';'), '', $strText);
    return $strText;
}
/**
 * Removes traversals like ../ from the passed string
 *
 * @param string $strFilename
 * @todo move to class Filesystem
 *
 * @return string
 */
function removeDirectoryTraversals($strFilename)
{
    $strFilename = urldecode($strFilename);
    $strFilename = StringUtil::replace("..", "", $strFilename);
    return $strFilename;
//    return uniStrReplace("//", "/", $strFilename); //FIXME: should stay in place but breaks "phar:///". 
}

/**
 * Creates a filename valid for filesystems
 *
 * @param string $strName
 * @param bool $bitFolder
 * @todo move to class Filesystem
 *
 * @return string
 */
function createFilename($strName, $bitFolder = false)
{
    //$strName = StringUtil::toLowerCase($strName);

    if (!$bitFolder) {
        $strEnding = StringUtil::substring($strName, (StringUtil::lastIndexOf($strName, ".") + 1));
    }
    else {
        $strEnding = "";
    }

    if (!$bitFolder) {
        $strReturn = StringUtil::substring($strName, 0, (StringUtil::lastIndexOf($strName, ".")));
    }
    else {
        $strReturn = $strName;
    }

    //Filter non allowed chars
    $arrSearch = array(".", ":", "ä", "ö", "ü", "/", "ß", "!");
    $arrReplace = array("_", "_", "ae", "oe", "ue", "_", "ss", "_");

    $strReturn = StringUtil::replace($arrSearch, $arrReplace, $strReturn);

    //and the ending
    if (!$bitFolder) {
        $strEnding = StringUtil::replace($arrSearch, $arrReplace, $strEnding);
    }

    //remove all other special characters
    $strTemp = preg_replace("/[^A-Za-z0-9_\+\-\s]/", "", $strReturn);

    //do a replacing in the ending, too
    if ($strEnding != "") {
        //remove all other special characters
        $strEnding = ".".preg_replace("/[^A-Za-z0-9_-]/", "", $strEnding);

    }

    $strReturn = $strTemp.$strEnding;

    return $strReturn;
}

/**
 * Returns the file extension for a file (including the dot).
 *
 * @param string $strPath
 *
 * @return string
 */
function getFileExtension($strPath)
{
    return StringUtil::toLowerCase(StringUtil::substring($strPath, StringUtil::lastIndexOf($strPath, ".")));
}

/**
 * Validates if the passed string is a valid mail-address
 *
 * @param string $strAddress
 *
 * @return bool
 * @deprecated use EmailValidator instead
 */
function checkEmailaddress($strAddress)
{
    $objValidator = new EmailValidator();
    return $objValidator->validate($strAddress);
}

/**
 * Checks the length of a passed string
 *
 * @param string $strText
 * @param int $intMin
 * @param int $intMax
 *
 * @return bool
 *
 * @deprecated replaced by @link{TextValidator}
 * @see ValidatorInterface
 */
function checkText($strText, $intMin = 1, $intMax = 0)
{
    $objValidator = new TextValidator();
    return $objValidator->validate($strText);
}


/**
 * Generates a new SystemID
 *
 * @return string The new SystemID
 */
function generateSystemid()
{
    //generate md5 key
    $strKey = md5(_realpath_);
    $strTemp = "";
    //Do the magic: take out 6 characters randomly...
    for ($intI = 0; $intI < 7; $intI++) {
        $intTemp = rand(0, 31);
        $strTemp .= $strKey[$intTemp];
    }

    $intId = uniqid($strTemp);

    return $intId;
}


/**
 * Checks a systemid for the correct syntax
 *
 * @param string $strID
 *
 * @return bool
 */
function validateSystemid($strID)
{

    //Check against wrong characters
    if (strlen($strID) == 20 && ctype_alnum($strID)) {
        return true;
    }
    else {
        return false;
    }
}

/**
 * Makes a string safe for xml-outputs
 *
 * @param string $strString
 *
 * @return string
 */
function xmlSafeString($strString)
{

    $strString = html_entity_decode($strString, ENT_COMPAT, "UTF-8");
    //but: encode &, <, >
    $strString = str_replace(array("&", "<", ">"), array("&amp;", "&lt;", "&gt;"), $strString);

    return $strString;
}

// --- String-Functions ---------------------------------------------------------------------------------


/**
 * Wrapper to phps strpos
 *
 * @param string $strHaystack
 * @param string $strNeedle
 *
 * @return int
 * @deprecated (use Kajona\System\System\StringUtil::indexOf instead)
 */
function uniStrpos($strHaystack, $strNeedle)
{
    return StringUtil::indexOf($strHaystack, $strNeedle);
}


/**
 * Wrapper to phps strrpos
 *
 * @param string $strHaystack
 * @param string $strNeedle
 *
 * @return int
 * @deprecated (use Kajona\System\System\StringUtil::lastIndexOf instead)
 */
function uniStrrpos($strHaystack, $strNeedle)
{
    return StringUtil::lastIndexOf($strHaystack, $strNeedle);
}

/**
 * Wrapper to phps stripos
 *
 * @param string $strHaystack
 * @param string $strNeedle
 *
 * @return int
 * @deprecated (use Kajona\System\System\StringUtil::indexOf instead)
 */
function uniStripos($strHaystack, $strNeedle)
{
    return StringUtil::indexOf($strHaystack, $strNeedle, false);
}

/**
 * Wrapper to phps strlen
 *
 * @param string $strString
 *
 * @return int
 * @deprecated (use Kajona\System\System\StringUtil::length instead)
 */
function uniStrlen($strString)
{
    return StringUtil::length($strString);
}

/**
 * Wrapper to phps strtolower, due to problems with UTF-8 on some configurations
 *
 * @param string $strString
 *
 * @return string
 * @deprecated (use Kajona\System\System\StringUtil::toLowerCase instead)
 */
function uniStrtolower($strString)
{
    return StringUtil::toLowerCase($strString);
}

/**
 * Wrapper to phps strtoupper, due to problems with UTF-8 on some configurations
 *
 * @param string $strString
 *
 * @return string
 * @deprecated (use Kajona\System\System\StringUtil::toUpperCase instead)
 */
function uniStrtoupper($strString)
{
    return StringUtil::toUpperCase($strString);
}

/**
 * Wrapper to phps substr
 *
 * @param string $strString
 * @param int $intStart
 * @param int|string $intEnd
 *
 * @return string
 * @deprecated (use Kajona\System\System\StringUtil::substring instead)
 */
function uniSubstr($strString, $intStart, $intEnd = "")
{
    if($intEnd == "") {
        $intEnd = null;
    }
    return StringUtil::substring($strString, $intStart, $intEnd);
}

/**
 * Wrapper to phps ereg
 *
 * @param string $strPattern
 * @param string $strString
 *
 * @return int
 * @deprecated (use Kajona\System\System\StringUtil::matches instead)
 */
function uniEreg($strPattern, $strString)
{
    return StringUtil::matches($strString, $strPattern);
}

/**
 * Unicode-safe wrapper to strReplace
 *
 * @param mixed $mixedSearch array or string
 * @param mixed $mixedReplace array or string
 * @param string $strSubject
 * @param bool $bitUnicodesafe
 *
 * @return mixed
 * @deprecated (use Kajona\System\System\StringUtil::replace instead)
 */
function uniStrReplace($mixedSearch, $mixedReplace, $strSubject, $bitUnicodesafe = false)
{
    return StringUtil::replace($mixedSearch, $mixedReplace, $strSubject, $bitUnicodesafe);
}

/**
 * Unicode-safe string trimmer
 *
 * @param string $strString string to wrap
 * @param int $intLength
 * @param string $strAdd string to add after wrapped string
 *
 * @return string
 * @deprecated (use Kajona\System\System\StringUtil::truncate instead)
 */
function uniStrTrim($strString, $intLength, $strAdd = "…")
{
    return StringUtil::truncate($strString, $intLength, $strAdd);
}


