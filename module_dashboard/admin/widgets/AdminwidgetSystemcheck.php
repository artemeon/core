<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                        *
********************************************************************************************************/

namespace Kajona\Dashboard\Admin\Widgets;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryCheckbox;
use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\SystemModule;

/**
 * @package module_dashboard
 *
 */
class AdminwidgetSystemcheck extends Adminwidget implements AdminwidgetInterface
{
    /**
     * @var string
     */
    private $imgFileName = "systemcheck.png";

    /**
     * Basic constructor, registers the fields to be persisted and loaded
     *
     */
    public function __construct()
    {
        parent::__construct();
        //register the fields to be persisted and loaded
        $this->setPersistenceKeys(array("php", "kajona"));
    }

    /**
     * @inheritdoc
     */
    public function getEditFormContent(AdminFormgenerator $form)
    {

        $form->addField(new FormentryCheckbox("php", ""), "")
            ->setStrLabel($this->getLang("systemcheck_checkboxphp"))
            ->setStrValue($this->getFieldValue("php"));
        $form->addField(new FormentryCheckbox("kajona", ""), "")
            ->setStrLabel($this->getLang("systemcheck_checkboxkajona"))
            ->setStrValue($this->getFieldValue("kajona"));
    }

    /**
     * This method is called, when the widget should generate it's content.
     * Return the complete content using the methods provided by the base class.
     * Do NOT use the toolkit right here!
     *
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    public function getWidgetOutput()
    {

        if (!SystemModule::getModuleByName("system")->rightView() || !Carrier::getInstance()->getObjSession()->isSuperAdmin()) {
            return $this->getLang("commons_error_permissions");
        }

        if ($this->getFieldValue("php") == "" && $this->getFieldValue("kajona") == "") {
            return $this->getEditWidgetForm();
        }

        $strReturn = "<style type=\"text/css\">
            .adminwidget_systemcheck .ok {
                color: green;
            }
            .adminwidget_systemcheck .nok {
                color: red;
                font-weight: bold;
            }
        </style>";

        //check wich infos to produce
        if ($this->getFieldValue("php") == "checked") {
            $strReturn .= $this->widgetText($this->getLang("systemcheck_php_safemode").(ini_get("safe_mode") ? $this->getLang("commons_yes") : $this->getLang("commons_no")));
            $strReturn .= $this->widgetText($this->getLang("systemcheck_php_urlfopen").(ini_get("allow_url_fopen") ? $this->getLang("commons_yes") : $this->getLang("commons_no")));
            $strReturn .= $this->widgetText(
                $this->getLang("systemcheck_php_regglobal").(ini_get("register_globals") ?
                    "<span class=\"nok\">".$this->getLang("commons_yes")."</span>" :
                    "<span class=\"ok\">".$this->getLang("commons_no")."</span>")
            );
            $strReturn .= $this->widgetSeparator();
        }
        if ($this->getFieldValue("kajona") == "checked") {
            $arrFilesAvailable = array(
                "/installer.php", "/upgrade.php", "/debug.php", "/v3_v4_postupdate.php"
            );

            foreach ($arrFilesAvailable as $strOneFile) {
                $strReturn .= $this->widgetText(
                    $strOneFile." ".$this->getLang("systemcheck_kajona_filepresent").(is_file(_realpath_.$strOneFile) ?
                        " <span class=\"nok\">".$this->getLang("commons_yes")."</span>" :
                        " <span class=\"ok\">".$this->getLang("commons_no")."</span>")
                );
            }

            $strReturn .= $this->widgetText(
                $this->getLang("systemcheck_kajona_writeper")." /project/system/config/config.php ".(is_writable(_realpath_."project/system/config/config.php") ?
                    "<span class=\"nok\">".$this->getLang("commons_yes")."</span>" :
                    "<span class=\"ok\">".$this->getLang("commons_no")."</span>")
            );
            $strReturn .= $this->widgetText(
                $this->getLang("systemcheck_kajona_writeper")." /project/log/ ".(is_writable(_realpath_."project/log/") ?
                    "<span class=\"ok\">".$this->getLang("commons_yes")."</span>" :
                    "<span class=\"nok\">".$this->getLang("commons_no")."</span>")
            );
            $strReturn .= $this->widgetText(
                $this->getLang("systemcheck_kajona_writeper")." /project/dbdumps/ ".(is_writable(_realpath_."project/dbdumps/") ?
                    "<span class=\"ok\">".$this->getLang("commons_yes")."</span>" :
                    "<span class=\"nok\">".$this->getLang("commons_no")."</span>")
            );
            $strReturn .= $this->widgetText(
                $this->getLang("systemcheck_kajona_writeper")." /project/temp ".(is_writable(_realpath_."project/temp") ?
                    "<span class=\"ok\">".$this->getLang("commons_yes")."</span>" :
                    "<span class=\"nok\">".$this->getLang("commons_no")."</span>")
            );
            $strReturn .= $this->widgetText(
                $this->getLang("systemcheck_kajona_writeper")." "._images_cachepath_." ".(is_writable(_realpath_._images_cachepath_) ?
                    "<span class=\"ok\">".$this->getLang("commons_yes")."</span>" :
                    "<span class=\"nok\">".$this->getLang("commons_no")."</span>")
            );

            foreach (Classloader::getCoreDirectories() as $strOneCore) {
                $strReturn .= $this->widgetText(
                    $this->getLang("systemcheck_kajona_writeper")." /".$strOneCore." ".(is_writable(_realpath_.$strOneCore) ?
                        "<span class=\"ok\">".$this->getLang("commons_yes")."</span>" :
                        "<span class=\"nok\">".$this->getLang("commons_no")."</span>")
                );
            }

        }
        return "<div class=\"adminwidget_systemcheck\">".$strReturn."</div>";
    }


    /**
     * Return a short (!) name of the widget.
     *
     * @return string
     */
    public function getWidgetName()
    {
        return $this->getLang("systemcheck_name");
    }

    /**
     * @inheritdoc
     */
    public function getWidgetDescription()
    {
        return $this->getLang("systemcheck_description");
    }

    /**
     * @return string
     */
    public function getImgFileName(): string
    {
        return $this->imgFileName;
    }
}

