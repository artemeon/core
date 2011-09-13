<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                               *
********************************************************************************************************/

/**
 * Model for files & folders of the downloads
 *
 * @package modul_downloads
 * @author sidler@mulchprod.de
 */
class class_modul_downloads_file extends class_model implements interface_model, interface_sortable_rating {

    private $strName = "";
    private $strFilename = "";
    private $strDescription = "";
    private $strChecksum = "";
    private $intSize = 0;
    private $intHits = 0;
    private $intType = 0;
    private $intMaxKb = 0;
    private $intCatType = -1; //internal, undocumented field. used for kajonabase.net!
    private $strScreen1 = ""; //internal, undocumented field. used for kajonabase.net!
    private $strScreen2 = ""; //internal, undocumented field. used for kajonabase.net!
    private $strScreen3 = ""; //internal, undocumented field. used for kajonabase.net!

    /**
     * Constructor to create a valid object
     *
     * @param string $strSystemid (use "" on new objects)
     */
    public function __construct($strSystemid = "") {
        $arrModule = array();
        $arrModule["name"] 				= "modul_downloads";
		$arrModule["moduleId"] 			= _downloads_modul_id_;
		$arrModule["table"]       		= _dbprefix_."downloads_file";
		$arrModule["modul"]				= "downloads";

		//base class
		parent::__construct($arrModule, $strSystemid);

		//init current object
		if($strSystemid != "")
		    $this->initObject();
    }

     /**
     * @see class_model::getObjectTables();
     * @return array
     */
    protected function getObjectTables() {
        return array(_dbprefix_."downloads_file" => "downloads_id");
    }

    /**
     * @see class_model::getObjectDescription();
     * @return string
     */
    protected function getObjectDescription() {
        return "downloads file ".$this->getFilename();
    }

    /**
     * inits this object with the values from the db. needs a given systemid
     *
     */
    public function initObject() {
        $strQuery = "SELECT * FROM "._dbprefix_."system,
		            ".$this->arrModule["table"]."
					WHERE system_id = downloads_id
						AND system_id= ?";
        $arrRow = $this->objDB->getPRow($strQuery, array($this->getSystemid()));
        if(count($arrRow) > 0) {
            $this->setDescription($arrRow["downloads_description"]);
            $this->setFilename($arrRow["downloads_filename"]);
            $this->setHits($arrRow["downloads_hits"]);
            $this->setMaxKb($arrRow["downloads_max_kb"]);
            $this->setName($arrRow["downloads_name"]);
            $this->setSize($arrRow["downloads_size"]);
            $this->setType($arrRow["downloads_type"]);
            $this->setChecksum($arrRow["downloads_checksum"]);
            $this->setIntCatType($arrRow["downloads_cattype"]);
            $this->setStrScreen1($arrRow["downloads_screen_1"]);
            $this->setStrScreen2($arrRow["downloads_screen_2"]);
            $this->setStrScreen3($arrRow["downloads_screen_3"]);
        }
    }

    /**
     * Updates the object to the database
     *
     * @return bool
     */
    protected function updateStateToDb() {
        $strQuery = "UPDATE ".$this->arrModule["table"]."
					SET downloads_name=?,
					    downloads_hits=?,
					    downloads_filename=?,
					    downloads_description=?,
					    downloads_size=?,
					    downloads_max_kb=?,
                        downloads_checksum=?,
                        downloads_screen_1=?,
                        downloads_screen_2=?,
                        downloads_screen_3=?,
                        downloads_cattype=?,
                        downloads_type=?
				  WHERE downloads_id=?";
        return $this->objDB->_pQuery($strQuery, array(
            $this->getName(),
			$this->getHits(),
			$this->getFilename(),
			$this->getDescription(),
			(int)$this->getSize(),
			(int)$this->getMaxKb(),
            $this->getChecksum(),
            $this->getStrScreen1(),
            $this->getStrScreen2(),
            $this->getStrScreen3(),
            (int)$this->getIntCatType(),
            $this->getType(),
			$this->getSystemid()
        ), array(true, true, true, false));
    }


    /**
     * Deletes the given dl-record from the database
     *
     * @param string $strSystemid
     * @return bool
     */
    public function deleteRecord() {
        class_logger::getInstance()->addLogRow("deleted dl-file ".$this->getSystemid(), class_logger::$levelInfo);
        $bitReturn = false;
        $objDB = $this->objDB;
		//Modul-Table
		$strQuery = "DELETE FROM "._dbprefix_."downloads_file WHERE downloads_id= ?";
		if($objDB->_pQuery($strQuery, array($this->getSystemid()))) {
    	    if($this->deleteSystemRecord($this->getSystemid()))
			    $bitReturn = true;
		}

		return $bitReturn;
    }


  /**
    * Loads all FOLDERS (!) under the given systemid
    *
    * @param string $strPrevId
    * @return mixed
    * @static
    */
	public static function getFolderLevel($strPrevId, $strPathFilter = "") {
        $arrParams = array($strPrevId);
        if($strPathFilter != "")
            $arrParams[] = $strPathFilter;
        
		$strQuery = "SELECT system_id FROM "._dbprefix_."system,
		                           "._dbprefix_."downloads_file
						WHERE system_id = downloads_id
						AND system_prev_id= ?
                        ".($strPathFilter != "" ? "AND downloads_filename = ? " : "")."
						AND downloads_type = 1
						ORDER BY system_sort";

		$objDB = class_carrier::getInstance()->getObjDB();

		$arrIds =  $objDB->getPArray($strQuery, $arrParams);
		$arrReturn = array();
		foreach ($arrIds as $arrOneId)
		    $arrReturn[] = new class_modul_downloads_file($arrOneId["system_id"]);

		return $arrReturn;
	}

	/**
	 * Loads all files, and only file under a given folderlevel recusively.
	 *
	 * @param string $strFolderlevel
	 * @return array
	 */
    public static function getAllFilesUnderFolderLevelRecursive($strFolderlevel) {
        $arrFiles = class_modul_downloads_file::getFilesDB($strFolderlevel);

        $arrChilds = array();
        $arrReturn = array();
        foreach ($arrFiles as $objOneFile) {
           if($objOneFile->getType() == 1) {
              $arrChilds = class_modul_downloads_file::getAllFilesUnderFolderLevelRecursive($objOneFile->getSystemid());
           }
           else if($objOneFile->getType() == 0) {
           	  $arrReturn[$objOneFile->getSystemid()] = $objOneFile;
           }
        }
        $arrReturn = array_merge($arrReturn, $arrChilds);

        return $arrReturn;
    }

   /**
	 * Loads all files AND folders from db
	 *
	 * @param string $strPrevId
	 * @param bool $bitFilesOnly
     * @param int $intStartNr
     * @param int $intEndNr
	 * @return mixed
	 * @static
	 */
	public static function getFilesDB($strPrevId, $bitFilesOnly = false, $bitJustActive = false, $intStartNr = false, $intEndNr = false) {

		$strQuery = "SELECT * FROM "._dbprefix_."system,
		                           "._dbprefix_."downloads_file
						WHERE system_id = downloads_id
						  AND system_prev_id=?
							".(!$bitFilesOnly ? "" : " AND downloads_type = 0 ")."
							".(!$bitJustActive ? "" : " AND system_status = 1 ")."
						ORDER BY system_sort ASC,
							downloads_type DESC,
							downloads_name ASC";

		$objDB = class_carrier::getInstance()->getObjDB();

        if($intStartNr !== false && $intEndNr !== false)
            $arrIds =  $objDB->getPArraySection($strQuery, array($strPrevId), $intStartNr, $intEndNr);
        else
            $arrIds =  $objDB->getPArray($strQuery, array($strPrevId));
		$arrReturn = array();
		foreach ($arrIds as $arrOneId)
		    $arrReturn[] = new class_modul_downloads_file($arrOneId["system_id"]);

		return $arrReturn;
	}


    /**
	 * Loads the number of files
	 *
	 * @param string $strPrevId
	 * @param bool $bitFilesOnly
	 * @return mixed
	 * @static
	 */
	public static function getNumberOfFilesDB($strPrevId, $bitFilesOnly = false, $bitJustActive = false) {
		$strQuery = "SELECT COUNT(*) FROM "._dbprefix_."system,
		                           "._dbprefix_."downloads_file
						WHERE system_id = downloads_id
						  AND system_prev_id=?
							".(!$bitFilesOnly ? "" : " AND downloads_type = 0 ")."
							".(!$bitJustActive ? "" : " AND system_status = 1 ")."
						ORDER BY system_sort ASC,
							downloads_type DESC,
							downloads_name ASC";

		$objDB = class_carrier::getInstance()->getObjDB();

		$arrRow =  $objDB->getPRow($strQuery, array($strPrevId));
		return $arrRow["COUNT(*)"];
	}

   /**
	 * Synchronises the filesystem with the database
	 *
	 * @param string $strPrevId, id to append new records to
	 * @param string $strPath, path to scan for folders and files
     * @param bool $bitRecursive
	 * @static
	 * @return mixed
	 */
	public static function syncRecursive($strPrevId, $strPath, $bitRecursive = true) {
	    $objDB = class_carrier::getInstance()->getObjDB();
        $arrReturn = array();
        $arrReturn["insert"] = 0;
	    $arrReturn["delete"] = 0;
	    $arrReturn["update"] = 0;
		//Load Files from DB
		$arrDB = class_modul_downloads_file::getFilesDB($strPrevId);

		//Files from filesystem
		$objFilesystem = new class_filesystem();
		$arrFilesystem = $objFilesystem->getCompleteList($strPath, array(), array(".htaccess"), array(".", "..", ".svn"));
		//Sync both arrays
		//start witht the files
		foreach($arrFilesystem["files"] as $intKeyFs => $arrOneFileFilesystem) {
			//Lopp over db-array
			foreach($arrDB as $intKeyDb => $objOneFileDatabase) {
				//File or folder?
				if($objOneFileDatabase->getType() == 0) {
					//Compare
					if($objOneFileDatabase->getFilename() == str_replace(_realpath_, "", $arrOneFileFilesystem["filepath"])) {
						//if checksum differs, update record
                        if(@md5_file($arrOneFileFilesystem["filepath"]) != $objOneFileDatabase->getChecksum()) {
							$objOneFileDatabase->setSize($arrOneFileFilesystem["filesize"]);
                            $objOneFileDatabase->setChecksum(@md5_file($arrOneFileFilesystem["filepath"]));
							$objOneFileDatabase->updateObjectToDB();
							$arrReturn["update"]++;
						}
						//Remove from both arrays
						unset($arrFilesystem["files"][$intKeyFs]);
						unset($arrDB[$intKeyDb]);
					}
				}
			}
		}
		//ok, loop the folders
		foreach($arrFilesystem["folders"] as $intKeyFs => $arrOneFolderFilesystem) {
			//Iterate over the db-array
			foreach($arrDB as $intKeyDb => $objOneFolderDatabase) {
				//folder?
				if($objOneFolderDatabase->getType() == 1) {
					//compare
					if($objOneFolderDatabase->getFilename() == $strPath."/".$arrOneFolderFilesystem) {
						//remove from arrays
						unset($arrFilesystem["folders"][$intKeyFs]);
						unset($arrDB[$intKeyDb]);
					}
				}
			}
		}

		//The remaining records from the db have to be deleted
		if(count($arrDB) > 0) {
			//start tx
			$objDB->transactionBegin();
			$bitCommit = true;
			foreach($arrDB as $objOneRecordDb) {
				//special: folder, then recursive!
				if($objOneRecordDb->getType() == 1) {
					//if childs, recursive
					if(count(class_modul_downloads_file::getFilesDB($objOneRecordDb->getSystemid())) > 0) {
					    $objArchive = new class_modul_downloads_archive($objOneRecordDb->getSystemid());
					    $objArchive->deleteArchiveRecursive($objOneRecordDb->getSystemid(), true);
                    }

					$objFile = new class_modul_downloads_file($objOneRecordDb->getSystemid() );
					if(!$objFile->deleteRecord())
						$bitCommit = false;

                    $arrReturn["delete"]++;
				}
				elseif ($objOneRecordDb->getType() == 0)
					$objFile = new class_modul_downloads_file($objOneRecordDb->getSystemid());
					if(!$objFile->deleteRecord($objOneRecordDb->getSystemid()))
						$bitCommit = false;
					$arrReturn["delete"]++;
			}

			//End tx
			if($bitCommit)
				$objDB->transactionCommit();
			else {
				$objDB->transactionRollback();
				echo "Rollback!";
			}
		}

		//The remaining records have to be put into the database
		foreach($arrFilesystem["files"] as $arrOneFileFilesystem) {
			$strDlName = $arrOneFileFilesystem["filename"];
			$strDlNameIntern = str_replace(_realpath_, "", $arrOneFileFilesystem["filepath"]);
			$intSize = $arrOneFileFilesystem["filesize"];
			$objDlFile = new class_modul_downloads_file("");
			$objDlFile->setName($strDlName);
			$objDlFile->setFilename($strDlNameIntern);
			$objDlFile->setSize($intSize);
			$objDlFile->setType(0);
			$objDlFile->updateObjectToDB($strPrevId);
            $arrReturn["insert"]++;
		}

		foreach($arrFilesystem["folders"] as $arrOneFolderFilesystem) {
			$strDlName = $arrOneFolderFilesystem;
			$strDlFilename = $strPath."/".$arrOneFolderFilesystem;
			$intSize = 0;
			$objDlFile = new class_modul_downloads_file("");
			$objDlFile->setName($strDlName);
			$objDlFile->setFilename($strDlFilename);
			$objDlFile->setSize($intSize);
			$objDlFile->setType(1);
			$objDlFile->updateObjectToDB($strPrevId);
            $arrReturn["insert"]++;
		}

		//And call all subfolders
        if($bitRecursive) {
            $arrFolders = class_modul_downloads_file::getFolderLevel($strPrevId);
            foreach($arrFolders as $objOneFolderDatabase) {
                $arrTemp = class_modul_downloads_file::syncRecursive($objOneFolderDatabase->getSystemid(), $objOneFolderDatabase->getFilename());
                $arrReturn["insert"] += $arrTemp["insert"];
                $arrReturn["update"] += $arrTemp["update"];
                $arrReturn["delete"] += $arrTemp["delete"];
            }
        }

		return $arrReturn;
	}


// --- GETTERS / SETTERS --------------------------------------------------------------------------------

    public function getName() {
        return $this->strName;
    }
    /* Just for the portal path navi */
    public function getTitle() {
        return $this->strName;
    }
    public function getHits() {
        return $this->intHits;
    }
    public function getMaxKb() {
        return $this->intMaxKb;
    }
    public function getSize() {
        return $this->intSize;
    }
    public function getType() {
        return $this->intType;
    }
    public function getDescription() {
        return $this->strDescription;
    }
    public function getFilename() {
        return $this->strFilename;
    }
    /**
     * @deprecated Use getStrChecksum instead!
     * @return string
     */
    public function getMd5Sum() {
        return @md5_file(_realpath_.$this->getFilename());
    }
    public function getChecksum() {
        return $this->strChecksum;
    }

    public function setName($strName) {
        $this->strName = $strName;
    }
    public function setHits($intHits) {
        $this->intHits = $intHits;
    }
    public function setMaxKb($intMaxKB) {
        $this->intMaxKb = $intMaxKB;
    }
    public function setSize($intSize) {
        $this->intSize = $intSize;
    }
    public function setType($intType) {
        $this->intType = $intType;
    }
    public function setDescription($strDescription) {
        $this->strDescription = $strDescription;
    }
    public function setFilename($strFilename) {
        $this->strFilename = $strFilename;
    }
    public function setChecksum($strChecksum) {
        $this->strChecksum = $strChecksum;
    }

    public function getIntCatType() {
        return $this->intCatType;
    }

    public function setIntCatType($intCatType) {
        if($intCatType != "" && is_numeric($intCatType))
        $this->intCatType = $intCatType;
    }

    public function getStrScreen1() {
        return $this->strScreen1;
    }

    public function setStrScreen1($strScreen1) {
        $this->strScreen1 = $strScreen1;
    }

    public function getStrScreen2() {
        return $this->strScreen2;
    }

    public function setStrScreen2($strScreen2) {
        $this->strScreen2 = $strScreen2;
    }

    public function getStrScreen3() {
        return $this->strScreen3;
    }

    public function setStrScreen3($strScreen3) {
        $this->strScreen3 = $strScreen3;
    }


}

?>