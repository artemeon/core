<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                          *
********************************************************************************************************/

namespace Kajona\System\Admin\Systemtasks;

use Kajona\System\System\Filesystem;
use Artemeon\Image\Image;
use Artemeon\Image\Plugins\ImageScale;
use Kajona\System\System\SystemModule;


/**
 * Resizes and compresses all uploaded pictures in "/files/images" to save disk space
 *
 * @package module_system
 */
class SystemtaskCompresspicuploads extends SystemtaskBase implements AdminSystemtaskInterface
{

    //class vars
    private $strPicsPath = "/files/images";
    private $intMaxWidth = 1024;
    private $intMaxHeight = 1024;

    private $intFilesTotal = 0;
    private $intFilesProcessed = 0;

    /**
     * constructor to call the base constructor
     */
    public function __construct()
    {
        parent::__construct();

        //Increase max execution time
        if (@ini_get("max_execution_time") < 3600 && @ini_get("max_execution_time") > 0) {
            @ini_set("max_execution_time", "3600");
        }
    }


    /**
     * @inheritdoc
     */
    public function getGroupIdentifier()
    {
        return "";
    }


    /**
     * @inheritdoc
     */
    public function getStrInternalTaskName()
    {
        return "compresspicuploads";
    }

    /**
     * @inheritdoc
     */
    public function getStrTaskName()
    {
        return $this->getLang("systemtask_compresspicuploads_name");
    }

    /**
     * @inheritdoc
     */
    public function executeTask()
    {

        if (!SystemModule::getModuleByName("system")->rightRight2()) {
            return $this->getLang("commons_error_permissions");
        }

        $strReturn = "";

        $this->intMaxWidth = (int)$this->getParam("intMaxWidth");
        $this->intMaxHeight = (int)$this->getParam("intMaxHeight");

        $this->recursiveImageProcessing($this->strPicsPath);

        //build the return string
        $strReturn .= $this->getLang("systemtask_compresspicuploads_done")."<br />";
        $strReturn .= $this->getLang("systemtask_compresspicuploads_found").": ".$this->intFilesTotal."<br />";
        $strReturn .= $this->getLang("systemtask_compresspicuploads_processed").": ".$this->intFilesProcessed;
        return $strReturn;
    }

    /**
     * @param $strPath
     *
     * @return void
     */
    private function recursiveImageProcessing($strPath)
    {
        $objFilesystem = new Filesystem();

        $arrFilesFolders = $objFilesystem->getCompleteList($strPath, array(".jpg", ".jpeg", ".png", ".gif"), array(), array(".", "..", ".svn"));
        $this->intFilesTotal += $arrFilesFolders["nrFiles"];

        foreach ($arrFilesFolders["folders"] as $strOneFolder) {
            $this->recursiveImageProcessing($strPath."/".$strOneFolder);
        }

        foreach ($arrFilesFolders["files"] as $arrOneFile) {
            $strImagePath = $strPath."/".$arrOneFile["filename"];

            $objImage = new Image(_images_cachepath_);
            $objImage->setUseCache(false);
            $objImage->load(_realpath_.$strImagePath);
            $objImage->addOperation(new ImageScale($this->intMaxWidth, $this->intMaxHeight));
            if ($objImage->save($strImagePath)) {
                $this->intFilesProcessed++;
            };
        }
    }

    /**
     * @inheritdoc
     */
    public function getAdminForm()
    {
        $strReturn = "";

        //show input fields to choose maximal width and height
        $strReturn .= $this->objToolkit->getTextRow($this->getLang("systemtask_compresspicuploads_hint"));
        $strReturn .= $this->objToolkit->divider();
        $strReturn .= $this->objToolkit->formInputText("intMaxWidth", $this->getLang("systemtask_compresspicuploads_width"), $this->intMaxWidth);
        $strReturn .= $this->objToolkit->formInputText("intMaxHeight", $this->getLang("systemtask_compresspicuploads_height"), $this->intMaxHeight);

        return $strReturn;
    }

    /**
     * @inheritdoc
     */
    public function getSubmitParams()
    {
        return "&intMaxWidth=".$this->getParam('intMaxWidth')."&intMaxHeight=".$this->getParam('intMaxHeight');
    }

}
