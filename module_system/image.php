<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/
namespace Kajona\System;

use Kajona\Mediamanager\System\MediamanagerFile;
use Kajona\System\System\AuthenticationException;
use Kajona\System\System\Carrier;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\Exception;
use Kajona\System\System\HttpStatuscodes;
use Artemeon\Image\Image;
use Artemeon\Image\Plugins\ImageLine;
use Artemeon\Image\Plugins\ImageOverlay;
use Artemeon\Image\Plugins\ImageRectangle;
use Artemeon\Image\Plugins\ImageScale;
use Artemeon\Image\Plugins\ImageScaleAndCrop;
use Artemeon\Image\Plugins\ImageText;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemEventidentifier;
use Kajona\System\System\SystemModule;

/**
 * This class can be used to create fast "on-the-fly" resizes of images
 * or to create a captcha image
 * To resize an image, you can call this class like
 * _webpath_/image.php?image=path/to/image.jpg&maxWidth=200&maxHeight=200
 * To resize and crop an image to a fixed size, you can call this class like
 * _webpath_/image.php?image=path/to/image.jpg&fixedWidth=200&fixedHeight=200
 * To create a captcha image, you can call this class like
 * _webpath_/image.php?image=kajonaCaptcha
 * If used with jpeg-pictures, the param quality=[1-95] can be used, default value is 90.
 * The images are directly sent to the browser, so include the calling in img-tags.
 * ATTENTION:
 * Make sure to use urlencoded image-paths!
 * The params maxWidth, maxHeight and quality are optional. If fixedWidth and fixedHeight
 * are set, maxWidth and maxHeight won't be used.
 *
 */
class Flyimage
{

    private $strFilename;
    private $intMaxWidth = 0;
    private $intMaxHeight = 0;
    private $intFixedWidth = 0;
    private $intFixedHeight = 0;

    private $intQuality;

    private $strSystemid;

    /**
     * constructor, init the parent-class
     */
    public function __construct()
    {
        //find the params to use
        $this->strFilename = urldecode(getGet("image"));
        //avoid directory traversing
        $this->strFilename = removeDirectoryTraversals($this->strFilename);

        $this->intMaxHeight = (int)getGet("maxHeight");
        if ($this->intMaxHeight < 0) {
            $this->intMaxHeight = 0;
        }

        $this->intMaxWidth = (int)getGet("maxWidth");
        if ($this->intMaxWidth < 0) {
            $this->intMaxWidth = 0;
        }

        $this->intFixedHeight = (int)getGet("fixedHeight");
        if ($this->intFixedHeight < 0 || $this->intFixedHeight > 2000) {
            $this->intFixedHeight = 0;
        }

        $this->intFixedWidth = (int)getGet("fixedWidth");
        if ($this->intFixedWidth < 0 || $this->intFixedWidth > 2000) {
            $this->intFixedWidth = 0;
        }

        $this->intQuality = (int)getGet("quality");
        if ($this->intQuality <= 0 || $this->intQuality > 100) {
            $this->intQuality = 90;
        }


        $this->strSystemid = getGet("systemid");

        ResponseObject::getInstance()->setObjEntrypoint(RequestEntrypointEnum::IMAGE());

    }

    /**
     * Here happens the magic: creating the image and sending it to the browser
     *
     * @return void
     */
    public function generateImage()
    {
        if (!Session::getInstance()->isLoggedin()) {
            ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_FORBIDDEN);
            ResponseObject::getInstance()->setStrContent(
                Exception::renderException(
                    new AuthenticationException(
                        "Access forbidden, you are not allowed to access this resource",
                        Exception::$level_ERROR
                    )
                )
            );
            ResponseObject::getInstance()->sendHeaders();
            ResponseObject::getInstance()->sendContent();
            return;
        }
        //switch the different modes - may be want to generate a detailed image-view
        Carrier::getInstance()->getObjSession()->sessionClose();
        $this->resizeImage();
    }


    /**
     * Wrapper to the real, fast resizing
     *
     * @return void
     */
    private function resizeImage()
    {
        if (is_file(_realpath_.$this->strFilename) && (StringUtil::indexOf($this->strFilename, "files") !== false || StringUtil::indexOf($this->strFilename, "templates") !== false)) {
            //Load the image-dimensions
            $strConditionalGetChecksum = md5(_realpath_.$this->strFilename.$this->intMaxWidth.$this->intMaxHeight.$this->intFixedWidth.$this->intFixedHeight);
            //check headers, maybe execution could be terminated right here  - done f
            if(ResponseObject::getInstance()->processConditionalGetHeaders($strConditionalGetChecksum)) {
                ResponseObject::getInstance()->sendHeaders();
                return;
            }

            $objImage = new Image(_realpath_._images_cachepath_);
            $objImage->load(_realpath_.$this->strFilename);
            $objImage->addOperation(new ImageScaleAndCrop($this->intFixedWidth, $this->intFixedHeight));
            $objImage->addOperation(new ImageScale($this->intMaxWidth, $this->intMaxHeight));

            //send the headers for conditional gets
            ResponseObject::getInstance()->sendConditionalGetHeader($strConditionalGetChecksum);

            //and send it to the browser
            $objImage->setJpegQuality((int)$this->intQuality);
            $objImage->sendToBrowser();
            return;
        }


        ResponseObject::getInstance()->setStrStatusCode(HttpStatuscodes::SC_NOT_FOUND);
        ResponseObject::getInstance()->sendHeaders();
    }


    /**
     * Generates a captcha image to defend bots.
     * To generate a captcha image, use "kajonaCaptcha" as image-param
     * when calling image.php
     * Up to now, the size-params are ignored during the creation of a
     * captcha image
     *
     * @return void
     * @deprecated remove in 8.x
     */
    public function generateCaptchaImage()
    {
        if ($this->intMaxWidth == 0 || $this->intMaxWidth > 500) {
            $intWidth = 200;
        }
        else {
            $intWidth = $this->intMaxWidth;
        }

        if ($this->intMaxHeight == 0 || $this->intMaxHeight > 500) {
            $intHeight = 50;
        }
        else {
            $intHeight = $this->intMaxHeight;
        }

        $intMinfontSize = 15;
        $intMaxFontSize = 22;
        $intWidthPerChar = 30;
        $strCharsPossible = "abcdefghijklmnpqrstuvwxyz123456789";
        $intHorizontalOffset = 10;
        $intVerticalOffset = 10;
        $intForegroundOffset = 2;

        $strCharactersPlaced = "";

        //init the random-function
        srand((double)microtime() * 1000000);

        //v2 version
        $objImage = new Image(_realpath_._images_cachepath_);
        $objImage->create($intWidth, $intHeight);
        $objImage->addOperation(new ImageRectangle(0, 0, $intWidth, $intHeight, "#FFFFFF"));

        //draw vertical lines
        $intStart = 5;
        while ($intStart < $intWidth - 5) {
            $objImage->addOperation(new ImageLine($intStart, 0, $intStart, $intWidth, $this->generateGreyLikeColor()));
            $intStart += rand(10, 17);
        }
        //draw horizontal lines
        $intStart = 5;
        while ($intStart < $intHeight - 5) {
            $objImage->addOperation(new ImageLine(0, $intStart, $intWidth, $intStart, $this->generateGreyLikeColor()));
            $intStart += rand(10, 17);
        }

        //draw floating horizontal lines
        for ($intI = 0; $intI <= 3; $intI++) {
            $intXPrev = 0;
            $intYPrev = rand(0, $intHeight);
            while ($intXPrev <= $intWidth) {
                $intNewX = rand($intXPrev, $intXPrev + 50);
                $intNewY = rand(0, $intHeight);
                $objImage->addOperation(new ImageLine($intXPrev, $intYPrev, $intNewX, $intNewY, $this->generateGreyLikeColor()));
                $intXPrev = $intNewX;
                $intYPrev = $intNewY;
            }
        }

        //calculate number of characters on the image
        $intNumberOfChars = floor($intWidth / $intWidthPerChar);

        //place characters in the image
        for ($intI = 0; $intI < $intNumberOfChars; $intI++) {
            //character to place
            $strCurrentChar = $strCharsPossible[rand(0, (StringUtil::length($strCharsPossible) - 1))];
            $strCharactersPlaced .= $strCurrentChar;
            //color to use
            $intCol1 = rand(0, 200);
            $intCol2 = rand(0, 200);
            $intCol3 = rand(0, 200);
            //fontsize
            $intSize = rand($intMinfontSize, $intMaxFontSize);
            //calculate x and y pos
            $intX = $intHorizontalOffset + ($intI * $intWidthPerChar);
            $intY = $intHeight - rand($intVerticalOffset, $intHeight - $intMaxFontSize);
            //the angle
            $intAngle = rand(-30, 30);
            //place the background character
            $objImage->addOperation(new ImageText($strCurrentChar, $intX, $intY, $intSize, "rgb(".$intCol1.",".$intCol2.",".$intCol3.")", "dejavusans.ttf", $intAngle));
            //place the foreground charater
            $objImage->addOperation(new ImageText($strCurrentChar, $intX + $intForegroundOffset, $intY + $intForegroundOffset, $intSize, "rgb(".($intCol1 + 50).",".($intCol2 + 50).",".($intCol3 + 50).")", "dejavusans.ttf", $intAngle));
        }

        //register placed string to session
        Carrier::getInstance()->getObjSession()->setCaptchaCode($strCharactersPlaced);

        //and send it to the browser

        //force no-cache headers
        ResponseObject::getInstance()->addHeader("Expires: Thu, 19 Nov 1981 08:52:00 GMT");
        ResponseObject::getInstance()->addHeader("Cache-Control: no-store, no-cache, must-revalidate, private");
        ResponseObject::getInstance()->addHeader("Pragma: no-cache");

        $objImage->setUseCache(false);
        $objImage->sendToBrowser(Image::FORMAT_JPG);
    }

    /**
     * Generates a grayish color and registers the color to the image
     *
     * @return int color-id in image
     */
    private function generateGreyLikeColor()
    {
        return "rgb(".(rand(150, 230).", ".rand(150, 230).", ".rand(150, 230).")");
    }

    /**
     * Returns the filename of the current image
     *
     * @return string
     */
    public function getImageFilename()
    {
        return $this->strFilename;
    }
}

define("_autotesting_", false);

$objImage = new Flyimage();
if ($objImage->getImageFilename() == "kajonaCaptcha") {
    $objImage->generateCaptchaImage();
}
else {
    $objImage->generateImage();
}

CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_AFTERCONTENTSEND, array(RequestEntrypointEnum::IMAGE()));

