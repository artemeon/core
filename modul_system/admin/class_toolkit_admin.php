<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                 *
********************************************************************************************************/

include_once(_systempath_."/class_toolkit.php");

/**
 * Admin-Part of the toolkit-classes
 *
 * @package modul_system
 */
class class_toolkit_admin extends class_toolkit {

	/**
	 * Constructor
	 *
	 * @param string $strSystemid
	 */
	public function __construct($strSystemid = "") {
        $arrModul = array();
		$arrModul["name"] 			= "modul_elemente_admin";
		$arrModul["author"] 		= "sidler@mulchprod.de";

		//Calling the base class
		parent::__construct($arrModul, $strSystemid);
	}


	/**
	 * Creates a form to specifiy the Kajona internal dates as start, end, archive
	 * NOTE: this is just a wrapper to 3 times formDateSimple!
	 * The form-elements are named as followed:
	 * start_datum_tag, start_datum_monat, start_datum_jahr
	 * end_datum_tag, end_datum_monat, end_datum_jahr
	 * archive_datum_tag, archive_datum_monat, archive_datum_jahr
	 * Use generateDateTimestamps() to get the int-values!
	 *
	 * @param int $intStart
	 * @param int $intEnd
	 * @param int $intArchive
	 * @param string $strStart
	 * @param string $strEnd
	 * @param string $strArchive
	 * @return string
	 */
	public function formDate($intStart = 0, $intEnd = 0, $intArchive = 0, $strStart = "", $strEnd = "", $strArchive = "") {
		//anything to "pre" fill?
		if($intStart != 0 && $intStart != "")
			$arrStart = explode(".", date("d.m.Y", $intStart));
		else
			$arrStart = explode(".", date("d.m.Y",  time()));

		if($intEnd != 0 && $intEnd != "")
			$arrEnd = explode(".", date("d.m.Y", $intEnd));
		else
		    $arrEnd = array("", "", "");

		if($intArchive != 0 && $intArchive != "")
			$arrArchive = explode(".", date("d.m.Y", $intArchive));
		else
		    $arrArchive = array("", "", "");

		//Be lazy: call the date-simple ;)
		$strReturn = "";
		$strReturn .= $this->formDateSimple("start", $arrStart[0], $arrStart[1], $arrStart[2], $strStart, false);
		$strReturn .= $this->formDateSimple("end", $arrEnd[0], $arrEnd[1], $arrEnd[2], $strEnd, false);
		$strReturn .= $this->formDateSimple("archive", $arrArchive[0], $arrArchive[1], $arrArchive[2], $strArchive, false);
		return $strReturn;
	}


	/**
	 * If used formDate() before, this method could be used to generate int-timestamps out of the passed form-fields
	 *
	 * @param mixed $arrParams
	 * @return mixed
	 */
	public function generateDateTimestamps($arrParams) {
	    $arrReturn = array();
        //check passes values
		if(isset($arrParams["start_datum_tag"]) && $arrParams["start_datum_tag"] != "" && $arrParams["start_datum_monat"] != "" && $arrParams["start_datum_jahr"] != "")
			$arrReturn["start"] = strtotime($arrParams["start_datum_jahr"] ."-".$arrParams["start_datum_monat"] ."-".$arrParams["start_datum_tag"]);
		else
			$arrReturn["start"] = 0;

		if(isset($arrParams["end_datum_tag"]) && $arrParams["end_datum_tag"] != "" && $arrParams["end_datum_monat"] != "" && $arrParams["end_datum_jahr"] != "")
			$arrReturn["end"] = strtotime($arrParams["end_datum_jahr"] ."-".$arrParams["end_datum_monat"] ."-".$arrParams["end_datum_tag"]);
		else
			$arrReturn["end"] = 0;

		if(isset($arrParams["archive_datum_tag"]) && $arrParams["archive_datum_tag"] != "" && $arrParams["archive_datum_monat"] != "" && $arrParams["archive_datum_jahr"] != "")
			$arrReturn["archive"] = strtotime($arrParams["archive_datum_jahr"] ."-".$arrParams["archive_datum_monat"] ."-".$arrParams["archive_datum_tag"]);
		else
			$arrReturn["archive"] = 0;

        return $arrReturn;
	}




	/**
	 * Returns a simple Date-Form
	 *
	 * @param string $strName
	 * @param int $intDay
	 * @param int $intMonth
	 * @param int $intYear
	 * @param string $strTitle
	 * @param bool $bitToday If set true, the current date will be inserted, if no date is passed
	 * @param string $strClass
	 * @return string
	 */
	public function formDateSimple($strName = "", $intDay = "", $intMonth = "", $intYear = "", $strTitle = "", $bitToday = true, $strClass = "inputDate") {
		//no given values, use today
		if($bitToday) {
    		if($intDay == "")
    			$intDay = strftime("%d", time());
    		if($intMonth == "")
    			$intMonth = strftime("%m", time());
    		if($intYear == "")
    			$intYear = strftime("%Y", time());
		}
		if($strName != "")
			$strName .= "_";

		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_date_simple");
        $arrTemplate = array();
		$arrTemplate["class"] = $strClass;
		$arrTemplate["titleDay"] = $strName."datum_tag";
		$arrTemplate["titleMonth"] = $strName."datum_monat";
		$arrTemplate["titleYear"] = $strName."datum_jahr";
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["valueDay"] = $intDay;
		$arrTemplate["valueMonth"] = $intMonth;
		$arrTemplate["valueYear"] = $intYear;

		//commands and values for the calendar
		$arrTemplate["calendarCommands"] = "";

		//init the js-files
		$arrTemplate["calendarCommands"] .= "\n<script language=\"Javascript\" type=\"text/javascript\">kajonaAjaxHelper.loadCalendarBase();</script>";
		//and the css
		$arrTemplate["calendarCommands"] .= "\n<script language=\"Javascript\" type=\"text/javascript\">addCss(\""._webpath_."/admin/scripts/yui/calendar/assets/calendar-core.css\");</script>";
		$arrTemplate["calendarCommands"] .= "\n<script language=\"Javascript\" type=\"text/javascript\">addCss(\""._webpath_."/admin/scripts/yui/calendar/assets/calendar.css\");</script>";


		//set up the container div
        $strContainerId = $strName."jscalendarContainer";
        $arrTemplate["calendarContainerId"] = $strContainerId;

        //init the calendar
        $arrTemplate["calendarCommands"] .="<script type=\"text/javascript\">\n";

        $arrTemplate["calendarCommands"] .="
	        function initCalWrapper_".$strContainerId."() {
				if(typeof YAHOO == \"undefined\" || typeof YAHOO.widget.Calendar == \"undefined\") {
					YAHOO.util.Dom.addClass(YAHOO.util.Dom.get(\"".$strContainerId."\"), \"loadingContainer\");
			        window.setTimeout(\"initCalWrapper_".$strContainerId."()\", 1000);
			        return;
			    }
			    YAHOO.util.Dom.removeClass(YAHOO.util.Dom.get(\"".$strContainerId."\"), \"loadingContainer\");
			    initCal_".$strContainerId."();
			}
        ";

        $arrTemplate["calendarCommands"] .=" function initCal_".$strContainerId."() { \n";
        $arrTemplate["calendarCommands"] .="    YAHOO.namespace(\"kajona.calendar\"); \n";
        //set up the calendar
        $arrTemplate["calendarCommands"] .="    YAHOO.kajona.calendar.init = function() { \n";
        $arrTemplate["calendarCommands"] .="       if (YAHOO.lang.isUndefined(YAHOO.kajona.calendar.cal_".$strContainerId.")) {\n";
        $arrTemplate["calendarCommands"] .="          YAHOO.kajona.calendar.cal_".$strContainerId." = new YAHOO.widget.Calendar(\n";
        $arrTemplate["calendarCommands"] .="          \"cal_".$strContainerId."\",\n";
        $arrTemplate["calendarCommands"] .="          \"".$strContainerId."\" );\n";

        $arrTemplate["calendarCommands"] .="          YAHOO.kajona.calendar.cal_".$strContainerId.".cfg.setProperty(\"WEEKDAYS_SHORT\", [".class_carrier::getInstance()->getObjText()->getText("toolsetCalendarWeekday", "system", "admin")."]);\n";
        $arrTemplate["calendarCommands"] .="          YAHOO.kajona.calendar.cal_".$strContainerId.".cfg.setProperty(\"MONTHS_LONG\", [".class_carrier::getInstance()->getObjText()->getText("toolsetCalendarMonth", "system", "admin")."]);\n";
        $arrTemplate["calendarCommands"] .="       } \n";
        //check for values in date form
        $arrTemplate["calendarCommands"] .="       var formDate = [document.getElementById('".$arrTemplate["titleDay"]."').value, document.getElementById('".$arrTemplate["titleMonth"]."').value, document.getElementById('".$arrTemplate["titleYear"]."').value]; \n";
        $arrTemplate["calendarCommands"] .="       if (formDate[0] > 0 && formDate[1] > 0 && formDate[2] > 0) {\n";
        $arrTemplate["calendarCommands"] .="          YAHOO.kajona.calendar.cal_".$strContainerId.".selectEvent.unsubscribe(handleSelect_".$strContainerId.", YAHOO.kajona.calendar.cal_".$strContainerId.");\n";
		$arrTemplate["calendarCommands"] .="          YAHOO.kajona.calendar.cal_".$strContainerId.".select(formDate[1]+'/'+formDate[0]+'/'+formDate[2]);\n";
		$arrTemplate["calendarCommands"] .="          var selectedDates = YAHOO.kajona.calendar.cal_".$strContainerId.".getSelectedDates();\n";
		$arrTemplate["calendarCommands"] .="          if (selectedDates.length > 0) {\n";
		$arrTemplate["calendarCommands"] .="             var firstDate = selectedDates[0];\n";
		$arrTemplate["calendarCommands"] .="             YAHOO.kajona.calendar.cal_".$strContainerId.".cfg.setProperty(\"pagedate\", (firstDate.getMonth()+1) + \"/\" + firstDate.getFullYear());\n";
        $arrTemplate["calendarCommands"] .="          }\n";
        $arrTemplate["calendarCommands"] .="       }\n";
        $arrTemplate["calendarCommands"] .="       YAHOO.kajona.calendar.cal_".$strContainerId.".render();\n";
        $arrTemplate["calendarCommands"] .="       YAHOO.kajona.calendar.cal_".$strContainerId.".selectEvent.subscribe(handleSelect_".$strContainerId.", YAHOO.kajona.calendar.cal_".$strContainerId.", true);\n";
        $arrTemplate["calendarCommands"] .="    } \n";

        $arrTemplate["calendarCommands"] .="    YAHOO.kajona.calendar.init();\n";
        $arrTemplate["calendarCommands"] .=" }\n";

        $arrTemplate["calendarCommands"] .=" function handleSelect_".$strContainerId."(type,args,obj) {\n";
        $arrTemplate["calendarCommands"] .="    var dates = args[0]; \n";
        $arrTemplate["calendarCommands"] .="    var date = dates[0]; \n";
        $arrTemplate["calendarCommands"] .="    var year = date[0], month = (date[1] < 10 ? '0'+date[1]:date[1]), day = (date[2] < 10 ? '0'+date[2]:date[2]);\n";
        //write to fields
		$arrTemplate["calendarCommands"] .="    document.getElementById('".$arrTemplate["titleDay"]."').value=day+\"\";\n";
		$arrTemplate["calendarCommands"] .="    document.getElementById('".$arrTemplate["titleMonth"]."').value=month+\"\";\n";
        $arrTemplate["calendarCommands"] .="    document.getElementById('".$arrTemplate["titleYear"]."').value=year+\"\";\n";
        $arrTemplate["calendarCommands"] .="    calClose_".$strContainerId."();\n";
        $arrTemplate["calendarCommands"] .=" } \n";

        $arrTemplate["calendarCommands"] .="</script>\n" ;

		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}


	/**
	 * Returns a text-field using the cool wysiwyg
	 *
	 * @param string $strName
	 * @param string $strTitle
	 * @param string $strContent
	 * @return string
	 */
	public function formWysiwygEditor($strName = "inhalt", $strTitle = "", $strContent = "", $strToolbarset = "standard") {
		$strReturn = "";
		//Import fckedit js
		$strReturn .= "	<script type=\"text/javascript\" src=\""._webpath_."/admin/scripts/fckeditor/fckeditor.js\"></script>\n";
        //Create the html-input element
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "wysiwyg_fckedit");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["content"] = $strContent;
		$strReturn .=  $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
		//For the Popups, we need the skinwebpath
		$strReturn .= $this->formInputHidden("skinwebpath", _skinwebpath_);
        //Init the editor
		$strReturn .= "	<script type=\"text/javascript\">\n";
		$strReturn .= " var sBasePath='"._webpath_."/admin/scripts/fckeditor/' ; \n";
		$strReturn .= " var objFCKeditor = new FCKeditor( '".$strName."' ) ; \n";
		//Load the default kajona-config.

		//To load role-based editors, this would be the right place to load a different config
		$strReturn .= " objFCKeditor.Config[\"CustomConfigurationsPath\"] = \""._webpath_."/admin/scripts/fckeditor/fckedit_kajona_standard.js\" ;\n";

		//set the language the user defined for the admin
		$strLanguage = class_session::getInstance()->getAdminLanguage();
		if($strLanguage == "")
		    $strLanguage = "de";
		$strReturn .= " objFCKeditor.Config[\"AutoDetectLanguage\"]	= \"false\" ; \n";
		$strReturn .= " objFCKeditor.Config[\"DefaultLanguage\"]	= \"".$strLanguage."\" ; \n";

		$strReturn .= " objFCKeditor.BasePath = sBasePath ; \n";
		//Set the skin-directory
		$strReturn .= " objFCKeditor.Config[\"SkinPath\"] = \""._skinwebpath_."/fckeditor/\" ; \n";
		//Load the defined toolbar
		$strReturn .= " objFCKeditor.ToolbarSet = \"".$strToolbarset."\" ; \n";

		//include the settings made by the admin-skins
		$strTemplateInitID = $this->objTemplate->readTemplate("/elements.tpl", "wysiwyg_fckedit_inits");
		$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateInitID);

		$strReturn .= " objFCKeditor.ReplaceTextarea() ; \n";
		$strReturn .= "	</script>\n";

		return $strReturn;
	}


	/**
	 * Returns a divider to split up a page in logical sections
	 *
	 * @param string $strClass
	 * @return string
	 */
	public function divider($strClass = "divider") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "divider");
        $arrTemplate = array();
		$arrTemplate["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}


	/**
	 * Creates a percent-beam to illustrate proportions
	 *
	 * @param float $floatPercent
	 * @param int $intLength
	 * @return string
	 */
	public function percentBeam($floatPercent, $intLength = "300") 	{
		$strReturn = "";
		//Calc width
		$intWidth = $intLength - 50;
		$intBeamLength = (int)($intWidth * $floatPercent / 100);
		if($intBeamLength == 0)
			$intBeamLength = 1;

		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "percent_beam");
        $arrTemplate = array();
		$arrTemplate["length"] = $intLength;
		$arrTemplate["percent"] = number_format($floatPercent, 2);
		$arrTemplate["width"] = $intWidth;
		if($arrTemplate["percent"] == "100.00")
		    $arrTemplate["beamwidth"] = $intBeamLength-2;
		else
		    $arrTemplate["beamwidth"] = $intBeamLength-1;
		if(($intWidth - $intBeamLength) <= 0 || $arrTemplate["percent"] == "100.00")
		    $arrTemplate["transTillEnd"] = "";
		 else
		    $arrTemplate["transTillEnd"] = "<img src=\"_skinwebpath_/trans.gif\" width=\"".($intWidth - $intBeamLength-1)."\" height=\"1\" />";
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}


/*"*****************************************************************************************************/
// --- FORM-Elements ------------------------------------------------------------------------------------

	/**
	 * Returns a checkbox
	 *
	 * @param string $strName
	 * @param string $strTitle
	 * @param bool $bitChecked
	 * @param string $strClass
	 * @return string
	 */
	public function formInputCheckbox($strName, $strTitle, $bitChecked = false, $strClass = "") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_checkbox");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["checked"] = ($bitChecked ? "checked=\"checked\"" : "");
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a regular hidden-input-field
	 *
	 * @param string $strName
	 * @param string $strValue
	 * @return string
	 */
	public function formInputHidden($strName, $strValue = "") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_hidden");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["value"] = $strValue;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}


	/**
	 * Returns a regular text-input field
	 *
	 * @param string $strName
	 * @param string $strTitle
	 * @param string $strValue
	 * @param string $strOpener
	 * @param bool $bitReadonly
	 * @return string
	 */
	public function formInputText($strName, $strTitle = "", $strValue = "", $strClass = "inputText", $strOpener = "", $bitReadonly = false) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_text");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["value"] = $strValue;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["class"] = $strClass;
		$arrTemplate["opener"] = $strOpener;
		$arrTemplate["readonly"] = ($bitReadonly ? "readonly=\"readonly\"" : "");

		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID, true);
	}

   /**
	 * Returns a regular text-input field
	 *
	 * @param string $strName
	 * @param string $strTitle
	 * @param string $strValue
	 * @param string $strClass
	 * @return string
	 */
	public function formInputPageSelector($strName, $strTitle = "", $strValue = "", $strClass = "inputText") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_pageselector");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["value"] = $strValue;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["class"] = $strClass;
		$arrTemplate["opener"] = getLinkAdminPopup("folderview",
		                                           "pagesFolderBrowser",
		                                           "&pages=1&form_element=".$strName,
		                                           class_carrier::getInstance()->getObjText()->getText("browser", "system", "admin"),
		                                           class_carrier::getInstance()->getObjText()->getText("browser", "system", "admin"),
		                                           "icon_externalBrowser.gif",
		                                           500,
		                                           500,
		                                           "ordneransicht");


        $strNameCleaned = uniStrReplace(array("", "[", "]"), array("_", "bo", "bc"), $strName);
		$arrTemplate["ajaxScript"] = "
		<script type=\"text/javascript\" language=\"Javascript\">
            kajonaAjaxHelper.loadAutocompleteBase();
            function initAC_".$strNameCleaned."() {
            	document.getElementById('".$strName."').onfocus = function() {};

				var pageDataSource = new YAHOO.util.XHRDataSource(\"xml.php\");
				pageDataSource.responseType = YAHOO.util.XHRDataSource.TYPE_XML;
				pageDataSource.responseSchema = {
					resultNode : \"page\",
					fields : [\"title\"]
				};

                var pageautocomplete = new YAHOO.widget.AutoComplete(\"".$strName."\", \"".$strName."_container\", pageDataSource, {
					queryMatchCase: false,
                	allowBrowserAutocomplete: false,
                	useShadow: false
				});
				pageautocomplete.generateRequest = function(sQuery) {
					return \"?admin=1&module=pages&action=getPagesByFilter&filter=\" + sQuery ;
				};
			}

            YAHOO.util.Event.onDOMReady(function () {document.getElementById('".$strName."').onfocus = function () {initAC_".$strNameCleaned."();};});
		</script>
		";

		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID, true);
	}

	/**
	 * Returns a text-input field as textarea
	 *
	 * @param string $strName
	 * @param string $strTitle
	 * @param string $strValue
	 * @return string
	 */
	public function formInputTextArea($strName, $strTitle = "", $strValue = "", $strClass = "inputTextarea") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_textarea");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["value"] = $strValue;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a password text-input field
	 *
	 * @param string $strName
	 * @param string $strTitle
	 * @param string $strValue
	 * @return string
	 */
	public function formInputPassword($strName, $strTitle = "", $strValue = "", $strClass = "inputText") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_password");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["value"] = $strValue;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a button to submit a form
	 *
	 * @param string $strValue
	 * @param string $strName
	 * @param string $strEventhandler
	 * @return string
	 */
	public function formInputSubmit($strValue = "Submit", $strName = "Submit", $strEventhandler = "", $strClass = "inputSubmit") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_submit");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["value"] = $strValue;
		$arrTemplate["eventhandler"] = $strEventhandler;
		$arrTemplate["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

    /**
     * Returns a input-file element
     *
     * @param string $strName
     * @param string $strTitle
     * @param bool $bitMultiple
     * @param string $strClass
     * @return string
     */
    public function formInputUpload($strName, $strTitle = "", $strClass = "inputText") {
        $strReturn = "";

		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_upload");
        $arrTemplate = array();
		$arrTemplate["name"] = $strName;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["class"] = $strClass;

		$objText = class_carrier::getInstance()->getObjText();
		$arrTemplate["maxSize"] = $objText->getText("max_size", "filemanager", "admin")." ".bytesToString(class_config::getInstance()->getPhpMaxUploadSize());

		$strReturn = $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);

		return $strReturn;
	}

    /**
     * Returns a input-file element for uploading multiple files with progress bar
     *
     * @param string $strName
     * @param string $strTitle
     * @param string $strAllowedFileTypes
     * @param bool $bitMultiple
     * @param bool $bitFallback
     * @return string
     */
    public function formInputUploadFlash($strName, $strTitle, $strAllowedFileTypes, $bitMultiple = false, $bitFallback = false) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_uploadFlash");
        $arrTemplate = array();
        $arrTemplate["title"] = $strTitle;

        $strAllowedFileTypes = uniStrReplace(array(".", ","), array("*.", ";"), $strAllowedFileTypes);
        if($strAllowedFileTypes == "")
            $strAllowedFileTypes = "*.*";

		$objConfig = class_config::getInstance();
		$objText = class_carrier::getInstance()->getObjText();

		$arrTemplate["javascript"] = "
			<script type=\"text/javascript\">
				var uploader;

				function initUploader() {
					window.setTimeout('checkUploader()', 50);

					YAHOO.widget.Uploader.SWFURL = \""._webpath_."/admin/scripts/yui/uploader/assets/uploader.swf\";
					uploader = new KajonaUploader({
						\"overlayContainerId\": \"kajonaUploadButtonsOverlay\",
						\"selectLinkId\": \"kajonaUploadSelectLink\",
						\"uploadLinkId\": \"kajonaUploadUploadLink\",
						\"cancelLinkId\": \"kajonaUploadCancelLink\",
						\"multipleFiles\": ".($bitMultiple ? "true" : "false").",
						\"allowedFileTypes\": \"".$strAllowedFileTypes."\",
						\"allowedFileTypesDescription\": \"".$strAllowedFileTypes."\",
						\"maxFileSize\": ".$objConfig->getPhpMaxUploadSize().",
						\"uploadUrl\": \""._webpath_."/xml.php?admin=1&module=filemanager&action=fileUpload&".$objConfig->getPhpIni("session.name")."=".class_session::getInstance()->getSessionId()."\",
						\"uploadUrlParams\": {\"systemid\" : document.getElementById(\"flashuploadSystemid\").value,
									          \"folder\" : document.getElementById(\"flashuploadFolder\").value,
		                                      \"inputElement\" : \"".$strName."\"}, //create valid input-name element. no array needed!
		                \"uploadInputName\": \"".$strName."\"
					});
					uploader.init();
			    }
			    kajonaAjaxHelper.loadUploaderBase(initUploader);

			    function checkUploader() {
			    	if (uploader.uploader == undefined) {
    					document.getElementById('kajonaUploadFallbackContainer').style.display = 'block';
						document.getElementById('kajonaUploadButtonsContainer').style.display = 'none';
    				}
    			}

    			jsDialog_0.setTitle('".$objText->getText("upload_multiple_dialogHeader", "filemanager", "admin")."');
			</script>";

		$arrTemplate["upload_fehler_filter"] = $objText->getText("upload_fehler_filter", "filemanager", "admin");
		$arrTemplate["upload_multiple_uploadFiles"] = $objText->getText("upload_multiple_uploadFiles", "filemanager", "admin");
		$arrTemplate["upload_multiple_cancel"] = $objText->getText("upload_multiple_cancel", "filemanager", "admin");
		$arrTemplate["upload_multiple_totalFilesAndSize"] = $objText->getText("upload_multiple_totalFilesAndSize", "filemanager", "admin");
		$arrTemplate["upload_multiple_errorFilesize"] = $objText->getText("upload_multiple_errorFilesize", "filemanager", "admin")." ".bytesToString($objConfig->getPhpMaxUploadSize());

		$arrTemplate["modalDialog"] = $this->jsDialog(0);

		//Fallback code if no or old Flash Player available
		if ($bitFallback) {
			$strFallbackForm = $this->formInputUpload($strName, $strTitle);
			$strFallbackForm .= $this->formInputSubmit($objText->getText("upload_multiple_uploadFiles", "filemanager", "admin"));
			$arrTemplate["fallbackContent"] = $strFallbackForm;
		} else {
		    $arrTemplate["fallbackContent"] = $objText->getText("upload_multiple_errorFlash", "filemanager", "admin");
		}

		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
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
	 * @return string
	 */
	public function formInputDropdown($strName, $arrKeyValues, $strTitle = "", $strKeySelected = "", $strClass = "inputDropdown", $bitEnabled = true) {
		$strOptions = "";
		$strTemplateOptionID = $this->objTemplate->readTemplate("/elements.tpl", "input_dropdown_row");
		$strTemplateOptionSelectedID = $this->objTemplate->readTemplate("/elements.tpl", "input_dropdown_row_selected");
		//Iterating over the array to create the options
		foreach ($arrKeyValues as $strKey => $strValue) {
            $arrTemplate = array();
			$arrTemplate["key"] = $strKey;
			$arrTemplate["value"] = $strValue;
			if($strKey == $strKeySelected)
				$strOptions .= $this->objTemplate->fillTemplate($arrTemplate, $strTemplateOptionSelectedID);
			else
				$strOptions .= $this->objTemplate->fillTemplate($arrTemplate, $strTemplateOptionID);
		}
		$arrTemplate = array();
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "input_dropdown");
		$arrTemplate["name"] = $strName;
		$arrTemplate["title"] = $strTitle;
		$arrTemplate["class"] = $strClass;
		$arrTemplate["disabled"] = ($bitEnabled ? "" : "disabled=\"disabled\"");
		$arrTemplate["options"] = $strOptions;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID, true);
	}

	/**
	 * Creates the header needed to open a form-element
	 *
	 * @param string $strAction
	 * @param string $strName
	 * @return string
	 */
	public function formHeader($strAction, $strName = "", $strEncoding = "") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "form_start");
        $arrTemplate = array();
		$arrTemplate["name"] = ($strName != "" ? $strName : "form".time());
		$arrTemplate["action"] = $strAction;
		$arrTemplate["enctype"] = $strEncoding;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a single TextRow in a form
	 *
	 * @param string $strText
	 * @return string
	 */
	public function formTextRow($strText, $strClass = "text") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "text_row_form");
        $arrTemplate = array();
		$arrTemplate["text"] = $strText;
		$arrTemplate["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID, true);
	}

	/**
	 * Returns a headline in a form
	 *
	 * @param string $strText
	 * @param string $strClass
	 * @return string
	 */
	public function formHeadline($strText, $strClass = "heading") {
	    $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "headline_form");
        $arrTemplate = array();
	    $arrTemplate["text"] = $strText;
	    $arrTemplate["class"] = $strClass;
	    return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID, true);
	}

	/**
	 * Returns the tags to close an open form
	 *
	 * @return string
	 */
	public function formClose() {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "form_close");
		return $this->objTemplate->fillTemplate(array(), $strTemplateID);
	}


/*"*****************************************************************************************************/
// --- LIST-Elements ------------------------------------------------------------------------------------

	/**
	 * Returns the htmlcode needed to start a proper list
	 *
	 * @return string
	 */
	public function listHeader() {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_header");
		return $this->objTemplate->fillTemplate(array(), $strTemplateID);
	}

    /**
	 * Returns the htmlcode needed to start a proper list, supporting drag n drop to
	 * reorder list-items
	 *
	 * @return string
	 */
	public function dragableListHeader($strListId) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "dragable_list_header");
		return $this->objTemplate->fillTemplate(array("listid" => $strListId), $strTemplateID);
	}

	/**
	 * Returns the code to finish the opened list
	 *
	 * @return string
	 */
	public function listFooter() {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_footer");
		return $this->objTemplate->fillTemplate(array(), $strTemplateID);
	}

    /**
	 * Returns the code to finish the opened list
	 *
	 * @return string
	 */
	public function dragableListFooter($strListId) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "dragable_list_footer");
		return $this->objTemplate->fillTemplate(array("listid" => $strListId), $strTemplateID);
	}

	/**
	 * Returns a row in a list with 2 columns
	 *
	 * @param string $strName
	 * @param string $strActions
	 * @param int $intCount, used to determing the class needed
	 * @param string $strType to react on special cases
	 * @param string $strListitemID id of row-entry, e.g. to use in ajax elements
	 * @return string
	 */
	public function listRow2($strName, $strActions, $intCount, $strType = "", $strListitemID = "") {
		if($intCount % 2 == 0)
			$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_row_2_1".$strType);
		else
			$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_row_2_2".$strType);

        $arrTemplate = array();
		$arrTemplate["title"] = $strName;
		$arrTemplate["actions"] = $strActions;
		$arrTemplate["listitemid"] = $strListitemID;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a row in a list with 2 columns and a leading image
	 *
	 * @param string $strImage
	 * @param string $strName
	 * @param string $strActions
	 * @param int $intCount, used to determing the class needed
	 * @param string $strType to react on special cases
	 * @param string $strListitemID id of row-entry, e.g. to use in ajax elements
	 * @return string
	 */
	public function listRow2Image($strImage, $strName, $strActions, $intCount, $strType = "", $strListitemID = "") {
		if($intCount % 2 == 0)
			$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_row_2image_1".$strType);
		else
			$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_row_2image_2".$strType);

        $arrTemplate = array();
		$arrTemplate["image"] = $strImage;
		$arrTemplate["title"] = $strName;
		$arrTemplate["actions"] = $strActions;
		$arrTemplate["listitemid"] = $strListitemID;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a row in a list with 3 columns, with leading image
	 *
	 * @param string $strName
	 * @param string $strCenter
	 * @param string $strActions
	 * @param string $strImage
	 * @param int $intCount, used to determing the class needed
	 * @param string $strListitemID id of row-entry, e.g. to use in ajax elements
	 * @return string
	 */
	public function listRow3($strName, $strCenter, $strActions, $strImage, $intCount, $strListitemID = "") {
		if($intCount % 2 == 0)
			$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_row_3_1");
		else
			$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_row_3_2");

        $arrTemplate = array();
		$arrTemplate["image"] = $strImage;
		$arrTemplate["title"] = $strName;
		$arrTemplate["center"] = $strCenter;
		$arrTemplate["actions"] = $strActions;
		$arrTemplate["listitemid"] = $strListitemID;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}


	/**
	 * Returns a table filled with infos
	 *
	 * @param mixed $arrHeader the first row to name the columns
	 * @param mixed $arrValues every entry is one row
	 * @return string
	 */
	public function dataTable($arrHeader, $arrValues) {
		$strReturn = "";
		$intCounter = "";
		//The Table header & the templates
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "datalist_header");
		$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateID);
		$strTemplateHeaderHeaderID = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_head_header");
		$strTemplateHeaderContentID = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_head");
		$strTemplateHeaderFooterID = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_head_footer");
		$strTemplateContentHeaderID1 = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_header_1");
		$strTemplateContentHeaderID2 = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_header_2");
		$strTemplateContentContentID1 = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_1");
		$strTemplateContentContentID2 = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_1");
		$strTemplateContentFooterID1 = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_footer_2");
		$strTemplateContentFooterID2 = $this->objTemplate->readTemplate("/elements.tpl", "datalist_column_footer_2");
		$strTemplateFooterID = $this->objTemplate->readTemplate("/elements.tpl", "datalist_footer");
		//Iterating over the rows
		$intNrRows = count($arrHeader);
		//Starting with the header, column by column
		$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateHeaderHeaderID);
		foreach ($arrHeader as $strHeader)
			$strReturn .= $this->objTemplate->fillTemplate(array("value" => $strHeader), $strTemplateHeaderContentID);
		$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateHeaderFooterID);
		//And the content, row by row, column by column
		foreach ($arrValues as $arrValueRow) {
			if(++$intCounter % 2 == 0)
				$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateContentHeaderID1);
			else
				$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateContentHeaderID2);
			foreach($arrValueRow as $strValue) {
				if($intCounter % 2 == 0)
					$strReturn .= $this->objTemplate->fillTemplate(array("value" => $strValue), $strTemplateContentContentID1);
				else
					$strReturn .= $this->objTemplate->fillTemplate(array("value" => $strValue), $strTemplateContentContentID2);
			}
			if($intCounter % 2 == 0)
				$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateContentFooterID1);
			else
				$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateContentFooterID2);
		}

		//And the footer
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "datalist_footer");
		$strReturn .= $this->objTemplate->fillTemplate(array(), $strTemplateID);
		return $strReturn;
	}


/*"*****************************************************************************************************/
// --- Action-Elements ----------------------------------------------------------------------------------

	/**
	 * Creates a action-Entry in a list
	 *
	 * @param string $strContent
	 * @return string
	 */
	public function listButton($strContent) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "list_button");
        $arrTemplate = array();
		$arrTemplate["content"] = $strContent;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}




	/**
	 * Generates a delete-button. The passed element name and question is shown as a modal dialog
	 * when the icon was clicked. So set the link-href-param for the final deletion, otherwise the
	 * user has no more chance to delete the record!
	 *
	 * @param $strElementName
	 * @param $strQuestion
	 * @param $strLinkHref
	 * @return string
	 */
	public function listDeleteButton($strElementName, $strQuestion, $strLinkHref) {
	    //place it into a standard-js-dialog
        $strDialog = $this->jsDialog(1);

        $strQuestion = uniStrReplace("%%element_name%%", htmlToString($strElementName, true), $strQuestion);

	    //create the list-button and the js code to show the dialog
	    $strButton = getLinkAdminManual("href=\"#\" onclick=\"javascript:jsDialog_1.setTitle('".class_carrier::getInstance()->getObjText()->getText("dialog_deleteHeader", "system", "admin")."'); jsDialog_1.setContent('".$strQuestion."', '".class_carrier::getInstance()->getObjText()->getText("dialog_deleteButton", "system", "admin")."',  '".$strLinkHref."'); jsDialog_1.init(); return false;\"",
	                                     "",
	                                     class_carrier::getInstance()->getObjText()->getText("deleteButton", "system", "admin"),
	                                     "icon_ton.gif" );

	    return $this->listButton($strButton).$strDialog;
	}

	/**
	 * Generates a button allowing to change the status of the record passed.
	 * Therefore an ajax-method is called.
	 *
	 * @param string $strSystemid
	 * @return string
	 */
	public function listStatusButton($strSystemid) {
	    //read the current status
	    $strButton = "";
	    include_once(_systempath_."/class_modul_system_common.php");
	    $objRecord = new class_modul_system_common($strSystemid);
	    $strImage = "";
	    $strNewImage = "";
	    $strText = "";
	    if($objRecord->getStatus() == 1) {
	        $strImage = "icon_enabled.gif";
	        $strNewImage = "icon_disabled.gif";
	        $strText = class_carrier::getInstance()->getObjText()->getText("status_active", "system", "admin");
	    }
	    else {
	        $strImage = "icon_disabled.gif";
	        $strNewImage = "icon_enabled.gif";
	        $strText = class_carrier::getInstance()->getObjText()->getText("status_inactive", "system", "admin");
	    }

	    $strJavascript = "<script type=\"text/javascript\">
			var statusCallback_".$strSystemid." = {
				success: function(o) { systemStatusCallback(o, true); },
				failure: function(o) { systemStatusCallback(o, false); },
				argument: ['".$strSystemid."']
			};
		</script>";

	    //output texts and image paths only once
		if(class_carrier::getInstance()->getObjSession()->getSession("statusButton", class_session::$intScopeRequest) === false) {
            $strJavascript .= "<script type=\"text/javascript\">
				var strActiveText = '".class_carrier::getInstance()->getObjText()->getText("status_active", "system", "admin")."';
				var strInActiveText = '".class_carrier::getInstance()->getObjText()->getText("status_inactive", "system", "admin")."';
				var strActiveImageSrc = '"._skinwebpath_."/pics/icon_enabled.gif';
				var strInActiveImageSrc = '"._skinwebpath_."/pics/icon_disabled.gif';

				kajonaAjaxHelper.loadAjaxBase();
			</script>";
            class_carrier::getInstance()->getObjSession()->setSession("statusButton", "true", class_session::$intScopeRequest);
        }

	    $strButton = getLinkAdminManual("href=\"javascript:kajonaAdminAjax.setSystemStatus('".$strSystemid."', statusCallback_".$strSystemid." );\"", "", $strText, $strImage, "statusImage_".$strSystemid, "statusLink_".$strSystemid);

	    return $this->listButton($strButton).$strJavascript;
	}

/*"*****************************************************************************************************/
// --- Misc-Elements ------------------------------------------------------------------------------------

	/**
	 * Returns a warning box, e.g. shown before deleting a record
	 *
	 * @param string $strContent
	 * @param string $strClass
	 * @return string
	 */
	public function warningBox($strContent, $strClass = "warnbox") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "warning_box");
        $arrTemplate = array();
		$arrTemplate["content"] = $strContent;
		$arrTemplate["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a single TextRow
	 *
	 * @param unknown_type $strText
	 * @return unknown
	 */
	public function getTextRow($strText, $strClass = "text") {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "text_row");
        $arrTemplate = array();
		$arrTemplate["text"] = $strText;
		$arrTemplate["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}


	/**
	 * Creates the mechanism to fold parts of the site / make them visible or invisible
	 *
	 * @param string $strContent
	 * @param string $strLinkText The text / content,
	 * @param bool $bitVisible
	 * @return mixed 0: The html-layout code
	 * 				 1: The link to fold / unfold
	 */
	public function getLayoutFolder($strContent, $strLinkText, $bitVisible = false) {
		$arrReturn = array();
		$strID = str_replace(array(" ", "."), array("", ""), microtime());
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "layout_folder");
        $arrTemplate = array();
		$arrTemplate["id"] = $strID;
		$arrTemplate["content"] = $strContent;
		$arrTemplate["display"] = ($bitVisible ? "block" : "none");
		$arrReturn[0] = $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
		$arrReturn[1] = "<a href=\"javascript:fold('".$strID."')\">".$strLinkText."</a>";
		return $arrReturn;
	}

	/**
	 * Creates the mechanism to fold parts of the site / make them vivsible oder invisible
	 * In recent times called "klapper"
	 *
	 * @param string $strContent
	 * @param string $strLinkText Mouseovertext
	 * @param string $strImageVisible clickable
	 * @param string $strImageInvisible clickable
	 * @param bool $bitVisible
	 * @return string
	 */
	public function getLayoutFolderPic($strContent, $strLinkText = "", $strImageVisible = "icon_folderOpen.gif", $strImageInvisible = "icon_folderClosed.gif", $bitVisible = true) {
		$arrReturn = array();
		$strID = str_replace(array(" ", "."), array("", ""), microtime());
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "layout_folder_pic");
        $arrTemplate = array();
		$arrTemplate["id"] = $strID;
		$arrTemplate["content"] = $strContent;
		$arrTemplate["display"] = ($bitVisible ? "block" : "none");
		$arrTemplate["link"] = "<a href=\"javascript:foldImage('".$strID."', '".$strID."_img', '"._skinwebpath_."/pics/".$strImageVisible."', '"._skinwebpath_."/pics/".$strImageInvisible."')\" title=\"".$strLinkText."\">".getImageAdmin(($bitVisible ? $strImageVisible : $strImageInvisible), $strLinkText, false, $strID."_img")."</a>";
		return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
	}

	/**
	 * Returns a infobox about the page being edited
	 *
	 * @param mixed $arrContent
	 * @return string
	 */
	public function getPageInfobox($arrContent) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "page_infobox");
		return $this->objTemplate->fillTemplate($arrContent, $strTemplateID);
	}

	/**
	 * Returns a infox used by the filemanager
	 *
	 * @param mixed $arrContent
	 * @return string
	 */
	public function getFilemanagerInfoBox($arrContent) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "filemanager_infobox");
		return $this->objTemplate->fillTemplate($arrContent, $strTemplateID);
	}

    /**
     * Creates the page to view & manipulate image.
     *
     * @since 3.2
     * @replaces class_toolkit_admin::getFileDetails()
     * @param array $arrContent
     * @return string
     */
    public function getFilemanagerImageDetails($arrContent) {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "folderview_image_details");
        return $this->objTemplate->fillTemplate($arrContent, $strTemplateID);
    }


	/**
	 * Creates a fieldset to structure elements
	 *
	 * @param string $strTitle
	 * @param string $strContent
	 * @param string $strClass
	 * @return string
	 */
	public function getFieldset($strTitle, $strContent, $strClass="fieldset") {
	    //remove old placeholder from content
	    $this->objTemplate->setTemplate($strContent);
	    $this->objTemplate->deletePlaceholder();
	    $strContent = $this->objTemplate->getTemplate();
	    $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "misc_fieldset");
	    $arrContent = array();
	    $arrContent["title"] = $strTitle;
	    $arrContent["content"] = $strContent;
	    $arrContent["class"] = $strClass;
		return $this->objTemplate->fillTemplate($arrContent, $strTemplateID);
	}

	/**
	 * Container for graphs, e.g. used by stats.
	 *
	 * @param string $strImgSrc
	 * @return string
	 */
	public function getGraphContainer($strImgSrc) {
	    $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "graph_container");
	    $arrContent = array();
	    $arrContent["imgsrc"] = $strImgSrc;
		return $this->objTemplate->fillTemplate($arrContent, $strTemplateID);
	}

/*"*****************************************************************************************************/
// --- Navigation-Elements ------------------------------------------------------------------------------

	/**
	 * Generates the module-navigation in the admin-area
	 *
	 * @param mixed $arrModules
	 * @param string $strCurrent
	 * @return string
	 */
	public function getAdminModuleNavi($arrModules, $strCurrent) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main");
		$strTemplateRowID = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main_row");
		$strTemplateRowHiddenID = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main_row_hidden");
		$strTemplateRowIDFirst = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main_row_first");
		$strTemplateRowIDLast = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main_row_last");
		$strTemplateRowSelectedID = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main_row_selected");
		$strTemplateRowSelectedIDFirst = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main_row_selected_first");
		$strTemplateRowSelectedIDLast = $this->objTemplate->readTemplate("/elements.tpl", "modulenavi_main_row_selected_last");
		$strRows = "";
		$strCurrent = uniSubstr($strCurrent, uniStrpos($strCurrent, "_")+1);
		$intCount = 1;
		$intMax = count($arrModules);
		foreach ($arrModules as $arrOneModule) {
		    if($strCurrent == $arrOneModule["rawName"]) {
		        if($intCount == 1)
			        $strRows .= $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowSelectedIDFirst);
			    elseif ($intCount == $intMax)
			        $strRows .= $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowSelectedIDLast);
			    else
			        $strRows .= $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowSelectedID);
		    }
			else {
			    if($intCount == 1)
			        $strRows .= $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowIDFirst);
			    elseif ($intCount == $intMax)
			        $strRows .= $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowIDLast);
			    else {
			    	//allow to hide modules if too much given
			    	if($intCount >= 8) {
			    		$strTemp = $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowHiddenID);
			    		if($strTemp == "")
			    			$strRows .= $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowID);
			    		else
			    			$strRows .= $strTemp;
			    	}
			    	else
			    		$strRows .= $this->objTemplate->fillTemplate($arrOneModule, $strTemplateRowID);
			    }

			}

			$intCount++;
		}
		return $this->objTemplate->fillTemplate(array("rows" => $strRows), $strTemplateID);
	}

	/**
	 * Generates the moduleaction-navigation in the admin-area
	 *
	 * @param mixed $arrModules
	 * @return string
	 */
	public function getAdminModuleActionNavi($arrActions) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "moduleactionnavi_main");
		$strTemplateRowID = $this->objTemplate->readTemplate("/elements.tpl", "moduleactionnavi_row");
		$strTemplateSpacerID = $this->objTemplate->readTemplate("/elements.tpl", "moduleactionnavi_spacer");
		$strRows = "";
		foreach ($arrActions as $strOneAction) {
		    //spacer or a regular navigationpoint given?
		    if($strOneAction == "") {
                $strRows .= $this->objTemplate->fillTemplate(array(), $strTemplateSpacerID);
		    }
		    else {
    		    $arrRow = array();
    		    $arrRow = splitUpLink($strOneAction);
    			$strRows .= $this->objTemplate->fillTemplate($arrRow, $strTemplateRowID);
		    }
		}
		return $this->objTemplate->fillTemplate(array("rows" => $strRows), $strTemplateID);
	}

/*"*****************************************************************************************************/
// --- Validation Errors --------------------------------------------------------------------------------

    /**
     * Generates a list of errors found by the form-validation
     *
     * @param mixed $arrErrors
     * @return string
     */
    public function getValidationErrors($objCalling) {
        $arrErrors = $objCalling->getValidationErrors();
        if(count($arrErrors) == 0)
            return "";
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "error_container");
		$strTemplateRowID = $this->objTemplate->readTemplate("/elements.tpl", "error_row");
		$strRows = "";
        foreach ($arrErrors as $strOneError) {
            $strRows .= $this->objTemplate->fillTemplate(array("field_errortext" => $strOneError), $strTemplateRowID);
        }
        $arrTemplate = array();
        $arrTemplate["errorrows"] = $strRows;
        $arrTemplate["errorintro"] = $objCalling->getText("errorintro");
        if($arrTemplate["errorintro"] == "!errorintro!")
            $arrTemplate["errorintro"] = $objCalling->getText("errorintro", "system");
        return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
    }

/*"*****************************************************************************************************/
// --- Path Navigation ----------------------------------------------------------------------------------

	/**
	 * Generates the layout for a small navigation
	 *
	 * @param mixed $arrEntries
	 * @return string
	 */
	public function getPathNavigation($arrEntries) {
		$strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "path_container");
		$strTemplateRowID = $this->objTemplate->readTemplate("/elements.tpl", "path_entry");
		$strRows = "";
		foreach ($arrEntries as $strOneEntry) {
		    $strRows .= $this->objTemplate->fillTemplate(array("pathlink" => $strOneEntry), $strTemplateRowID);
		}
		return $this->objTemplate->fillTemplate(array("pathnavi" => $strRows), $strTemplateID);

	}

/*"*****************************************************************************************************/
// --- Pre-formatted ------------------------------------------------------------------------------------


    /**
     * Returns a simple <pre>-Element to display pre-formatted text such as logfiles
     *
     * @param array $arrLines
     * @param int $nrRows number of rows to display
     * @return string
     */
    public function getPreformatted($arrLines, $nrRows = 0) {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "preformatted");
		$strRows = "";
		$intI = 0;
		foreach ($arrLines as $strOneLine) {
		    if($nrRows != 0 && $intI++ > $nrRows)
		        break;
            $strOneLine = str_replace(array("<pre>", "</pre>", "\n"), array(" ", " ", "\r\n"), $strOneLine);
		    $strRows .= htmlToString($strOneLine, true);
		}
		return $this->objTemplate->fillTemplate(array("pretext" => $strRows), $strTemplateID);
    }

/*"*****************************************************************************************************/
// --- Language handling --------------------------------------------------------------------------------

    /**
     * Creates the sourrounding code of a language switch, places the buttons
     *
     * @param string $strLanguageButtons
     * @return string
     */
    public function getLanguageSwitch($strLanguageButtons) {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "language_switch");
        $arrTemplate = array();
        $arrTemplate["languagebuttons"] = $strLanguageButtons;
        return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
    }

    /**
     * Creates the code for one button for a specified language, part of a language switch
     *
     * @param string $strLanguage The full name of the language
     * @param string $strOnClickHandler
     * @param bool $bitActive
     * @return string
     */
    public function getLanguageButton($strLanguage, $strOnClickHandler, $bitActive = false) {
        //active language?
        if($bitActive)
            $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "language_switch_button_active");
        else
            $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "language_switch_button");
        $arrTemplate = array();
        $arrTemplate["onclickHandler"] = $strOnClickHandler;
        $arrTemplate["languageName"] = $strLanguage;
        return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
    }


    /**
     * Creates a pageview
     *
     * @param array $arrData
     * @param int $intCurrentpage
     * @param string $strModule
     * @param string $strAction
     * @param string $strLinkAdd
     * @param int $intElementPerPage
     * @return mixed a one-dimensional array: ["elements"] and ["pageview"]
     */
    public function getPageview($arrData, $intCurrentpage, $strModule, $strAction, $strLinkAdd = "", $intElementPerPage = 15) {
        $arrReturn = array();

        include_once(_systempath_."/class_array_iterator.php");
        $objArrayIterator = new class_array_iterator($arrData);
        $objArrayIterator->setIntElementsPerPage($intElementPerPage);
        $intNrOfPages = $objArrayIterator->getNrOfPages();
        $intNrOfElements = $objArrayIterator->getNumberOfElements();

        $arrReturn["elements"] = $objArrayIterator->getElementsOnPage($intCurrentpage);
        //read templates
        $strTemplateBodyID = $this->objTemplate->readTemplate("/elements.tpl", "pageview_body");
        $strTemplateForwardID = $this->objTemplate->readTemplate("/elements.tpl", "pageview_link_forward");
        $strTemplateBackwardID = $this->objTemplate->readTemplate("/elements.tpl", "pageview_link_backward");
        $strTemplateListID = $this->objTemplate->readTemplate("/elements.tpl", "pageview_page_list");
        $strTemplateListItemActiveID = $this->objTemplate->readTemplate("/elements.tpl", "pageview_list_item_active");
        $strTemplateListItemID = $this->objTemplate->readTemplate("/elements.tpl", "pageview_list_item");
        //build layout
        $arrTemplate = array();

        $strListItems = "";

        //just load the current +-4 pages and the first/last +-2
        $intCounter2 = 1;
        for($intI = 1; $intI <= $intNrOfPages; $intI++) {
            $bitDisplay = false;
            if($intCounter2 <= 2) {
                $bitDisplay = true;
            }
            elseif ($intCounter2 >= ($intNrOfPages-1)) {
                $bitDisplay = true;
            }
            elseif ($intCounter2 >= ($intCurrentpage-2) && $intCounter2 <= ($intCurrentpage+2)) {
                $bitDisplay = true;
            }


            if($bitDisplay) {
                $arrLinkTemplate = array();
                $arrLinkTemplate["href"] = getLinkAdminHref($strModule, $strAction, $strLinkAdd."&pv=".$intI);
                $arrLinkTemplate["pageNr"] = $intI;

                if($intI == $intCurrentpage)
                    $strListItems .= $this->objTemplate->fillTemplate($arrLinkTemplate, $strTemplateListItemActiveID);
                else
                    $strListItems .= $this->objTemplate->fillTemplate($arrLinkTemplate, $strTemplateListItemID);
            }
            $intCounter2++;
        }
        $arrTemplate["pageList"] = $this->objTemplate->fillTemplate(array("pageListItems" => $strListItems), $strTemplateListID);
        $arrTemplate["nrOfElementsText"] = class_carrier::getInstance()->getObjText()->getText("pageview_total", "system", "admin");
        $arrTemplate["nrOfElements"] = $intNrOfElements;
        if($intCurrentpage < $intNrOfPages)
            $arrTemplate["linkForward"] = $this->objTemplate->fillTemplate(array("linkText" => class_carrier::getInstance()->getObjText()->getText("pageview_forward", "system", "admin"),
                                                                                 "href" => getLinkAdminHref($strModule, $strAction, $strLinkAdd."&pv=".($intCurrentpage+1))), $strTemplateForwardID);
        if($intCurrentpage > 1)
            $arrTemplate["linkBackward"] = $this->objTemplate->fillTemplate(array("linkText" => class_carrier::getInstance()->getObjText()->getText("pageview_backward", "system", "admin"),
                                                                                  "href" => getLinkAdminHref($strModule, $strAction, $strLinkAdd."&pv=".($intCurrentpage-1))), $strTemplateBackwardID);


        $arrReturn["pageview"] = $this->objTemplate->fillTemplate($arrTemplate, $strTemplateBodyID);
        return $arrReturn;
    }


/*"*****************************************************************************************************/
// --- Admnwidget / Dashboard ---------------------------------------------------------------------------


    public function getMainDashboard($arrColumn) {
        $strReturn = "<table class=\"dashBoard\"><tr>";
        foreach ($arrColumn as $strOneColumn)
            $strReturn .= "<td>".$strOneColumn."</td>";
        $strReturn .= "</tr></table>";
        return $strReturn;
    }

    /**
     * Generates the header for a column on the dashboard.
     * Inits the ajax-componentes for this list
     *
     * @param string $strColumnId
     * @return string
     */
    public function getDashboardColumnHeader($strColumnId) {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "dashboard_column_header");
        return $this->objTemplate->fillTemplate(array("column_id" => $strColumnId), $strTemplateID);
    }

    /**
     * The footer of a dashboard column.
     *
     * @return string
     */
    public function getDashboardColumnFooter() {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "dashboard_column_footer");
        return $this->objTemplate->fillTemplate(array(), $strTemplateID);
    }

    /**
     * The widget-enclose is the code-fragment to be built around the widget itself.
     * Used to handle the widget on the current column.
     *
     * @param string $strDashboardEntryId
     * @param string $strWidgetContent
     * @return string
     */
    public function getDashboardWidgetEncloser($strDashboardEntryId, $strWidgetContent) {
         $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "dashboard_encloser");
        $arrTemplate = array();
        $arrTemplate["entryid"] = $strDashboardEntryId;
        $arrTemplate["content"] = $strWidgetContent;
        return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
    }

    /**
     * Builds the widget out of its main components.
     *
     * @param string $strSystemid
     * @param string $strName
     * @param string $strContent
     * @param string $strEditLink
     * @param string $strDeleteLink
     * @return string
     */
    public function getAdminwidget($strSystemid, $strName, $strContent, $strEditLink = "", $strDeleteLink = "") {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "adminwidget_widget");
        $arrTemplate = array();
        $arrTemplate["widget_name"] = $strName;
        $arrTemplate["widget_content"] = $strContent;
        $arrTemplate["widget_id"] = $strSystemid;
        $arrTemplate["widget_edit"] = $strEditLink;
        $arrTemplate["widget_delete"] = $strDeleteLink;
        return $this->objTemplate->fillTemplate($arrTemplate, $strTemplateID);
    }

    /**
     * Generates a text-row in a widget
     *
     * @param string $strText
     * @return string
     */
    public function adminwidgetText($strText) {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "adminwidget_text");
        return $this->objTemplate->fillTemplate(array("text" => $strText), $strTemplateID);
    }

    /**
     * Generate a separator / divider in a widget
     *
     * @return string
     */
    public function adminwidgetSeparator() {
        $strTemplateID = $this->objTemplate->readTemplate("/elements.tpl", "adminwidget_separator");
        return $this->objTemplate->fillTemplate(array(""), $strTemplateID);
    }

//--- modal dialog --------------------------------------------------------------------------------------

    /**
     * Creates a modal dialog on the page. By default, the dialog is hidden, so has to be set visible.
     * The type-param decides what template is used for the dialog-layout. The name of the dialog is built via jsDialog_$intTypeNr.
     * Set the contents via js-calls.
     *
     * @param int $intDialogType (0 = regular modal dialog, 1 = confirmation dialog, 2 = rawDialog, 3 = loadingDialog)
     * @return string
     */
    public function jsDialog($intDialogType) {
        $strContent = "";
        //create the html-part
        $arrTemplate = array();
        $strContainerId = generateSystemid();
        $arrTemplate["dialog_id"] = $strContainerId;

        $strTemplateId = null;
        if($intDialogType == 0 && class_carrier::getInstance()->getObjSession()->getSession("jsDialog_".$intDialogType, class_session::$intScopeRequest) === false) {
            $strTemplateId = $this->objTemplate->readTemplate("/elements.tpl", "dialogContainer");
            class_carrier::getInstance()->getObjSession()->setSession("jsDialog_".$intDialogType, "true",  class_session::$intScopeRequest);
        }
        else if($intDialogType == 1 && class_carrier::getInstance()->getObjSession()->getSession("jsDialog_".$intDialogType, class_session::$intScopeRequest) === false) {
            $arrTemplate["dialog_cancelButton"] = class_carrier::getInstance()->getObjText()->getText("dialog_cancelButton", "system", "admin");

        	$strTemplateId = $this->objTemplate->readTemplate("/elements.tpl", "dialogConfirmationContainer");
            class_carrier::getInstance()->getObjSession()->setSession("jsDialog_".$intDialogType, "true",  class_session::$intScopeRequest);
        }
        else if($intDialogType == 2 && class_carrier::getInstance()->getObjSession()->getSession("jsDialog_".$intDialogType, class_session::$intScopeRequest) === false) {
            $strTemplateId = $this->objTemplate->readTemplate("/elements.tpl", "dialogRawContainer");
            class_carrier::getInstance()->getObjSession()->setSession("jsDialog_".$intDialogType, "true",  class_session::$intScopeRequest);
        }
        else if($intDialogType == 3 && class_carrier::getInstance()->getObjSession()->getSession("jsDialog_".$intDialogType, class_session::$intScopeRequest) === false) {
            $arrTemplate["dialog_title"] = class_carrier::getInstance()->getObjText()->getText("dialog_loadingHeader", "system", "admin");
            $strTemplateId = $this->objTemplate->readTemplate("/elements.tpl", "dialogLoadingContainer");
            class_carrier::getInstance()->getObjSession()->setSession("jsDialog_".$intDialogType, "true",  class_session::$intScopeRequest);
        }

        if($strTemplateId != null) {
            $strContent .= $this->objTemplate->fillTemplate($arrTemplate, $strTemplateId);

	        //and create the java-script
	        $strContent .="<script type=\"text/javascript\">
	        	kajonaAjaxHelper.loadDialogBase();
	            var jsDialog_".$intDialogType." = new ModalDialog('".$strContainerId."', ".$intDialogType.");
	        </script>";
        }

        return $strContent;
    }

}
?>
