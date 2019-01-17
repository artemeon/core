<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                            *
********************************************************************************************************/

namespace Kajona\Dashboard\Admin\Widgets;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryDropdown;
use Kajona\System\Admin\Formentries\FormentryText;
use Kajona\System\System\Exception;
use Kajona\System\System\Remoteloader;
use Kajona\System\System\XmlParser;

/**
 * @package module_dashboard
 *
 */
class AdminwidgetRssfeed extends Adminwidget implements AdminwidgetInterface
{

    /**
     * Basic constructor, registers the fields to be persisted and loaded
     *
     */
    public function __construct()
    {
        parent::__construct();
        //register the fields to be persisted and loaded
        $this->setPersistenceKeys(array("feedurl", "posts"));
    }

    /**
     * @inheritdoc
     */
    public function getEditFormContent(AdminFormgenerator $form)
    {
        $form->addField(new FormentryText("feedurl", ""), "")
            ->setStrValue($this->getFieldValue("feedurl"))
            ->setStrLabel($this->getLang("rssfeed_feedurl"));
        $form->addField(new FormentryDropdown("posts", ""))->setArrKeyValues([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10])
            ->setStrValue(($this->getFieldValue("posts")))
            ->setStrLabel($this->getLang("rssfeed_posts"));
    }

    /**
     * This method is called, when the widget should generate it's content.
     * Return the complete content using the methods provided by the base class.
     * Do NOT use the toolkit right here!
     *
     * @return string
     */
    public function getWidgetOutput()
    {
        if ($this->getFieldValue("feedurl") == "") {
            return $this->getEditWidgetForm();
        }

        $strReturn = "";

        //request the xml...

        try {

            $arrUrl = parse_url(trim($this->getFieldValue("feedurl")));
            $objRemoteloader = new Remoteloader();

            $intPort = isset($arrUrl["port"]) ? $arrUrl["port"] : "";
            if ($intPort == "") {
                if ($arrUrl["scheme"] == "https" ? 443 : 80) {
                    ;
                }
            }

            $objRemoteloader->setStrHost($arrUrl["host"]);
            $objRemoteloader->setStrQueryParams($arrUrl["path"].(isset($arrUrl["query"]) ? $arrUrl["query"] : ""));
            $objRemoteloader->setIntPort($intPort);
            $objRemoteloader->setStrProtocolHeader($arrUrl["scheme"]."://");
            $strContent = $objRemoteloader->getRemoteContent();
        }
        catch (Exception $objExeption) {
            $strContent = "";
        }

        if ($strContent != "") {
            $objXmlparser = new XmlParser();
            $objXmlparser->loadString($strContent);

            $arrFeed = $objXmlparser->xmlToArray();

            if (count($arrFeed) >= 1) {

                //rss feed
                if (isset($arrFeed["rss"])) {
                    $intCounter = 0;
                    foreach ($arrFeed["rss"][0]["channel"][0]["item"] as $arrOneItem) {

                        $strTitle = (isset($arrOneItem["title"][0]["value"]) ? $arrOneItem["title"][0]["value"] : "");
                        $strLink = (isset($arrOneItem["link"][0]["value"]) ? $arrOneItem["link"][0]["value"] : "");

                        $strReturn .= $this->widgetText("<a href=\"".$strLink."\" target=\"_blank\">".$strTitle."</a>");
                        $strReturn .= $this->widgetSeparator();

                        if (++$intCounter >= $this->getFieldValue("posts")) {
                            break;
                        }

                    }
                }

                //atom feed
                if (isset($arrFeed["feed"]) && isset($arrFeed["feed"][0]["entry"])) {
                    $intCounter = 0;
                    foreach ($arrFeed["feed"][0]["entry"] as $arrOneItem) {

                        $strTitle = (isset($arrOneItem["title"][0]["value"]) ? $arrOneItem["title"][0]["value"] : "");
                        $strLink = (isset($arrOneItem["link"][0]["attributes"]["href"]) ? $arrOneItem["link"][0]["attributes"]["href"] : "");

                        $strReturn .= $this->widgetText("<a href=\"".$strLink."\" target=\"_blank\">".$strTitle."</a>");
                        $strReturn .= $this->widgetSeparator();

                        if (++$intCounter >= $this->getFieldValue("posts")) {
                            break;
                        }

                    }
                }
            } else {
                $strContent = $this->getLang("rssfeed_errorparsing");
            }

        } else {
            $strReturn .= $this->getLang("rssfeed_errorloading");
        }


        return $strReturn;
    }

    /**
     * Return a short (!) name of the widget.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return $this->getLang("rssfeed_name");
    }


    /**
     * @inheritdoc
     */
    public function getWidgetDescription()
    {
        return $this->getLang("rssfeed_description");
    }

    /**
     * @return string
     */
    public function getWidgetImg()
    {
        return "/files/extract/widgets/newsfeed.png";
    }

}

