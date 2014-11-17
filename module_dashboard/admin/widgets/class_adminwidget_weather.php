<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2014 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$	                            *
********************************************************************************************************/

/**
 * @package module_dashboard
 */
class class_adminwidget_weather extends class_adminwidget implements interface_adminwidget {

    /**
     * Basic constructor, registers the fields to be persisted and loaded
     */
    public function __construct() {
        parent::__construct();
        //register the fields to be persisted and loaded
        $this->setPersistenceKeys(array("unit", "location"));
    }

    /**
     * Allows the widget to add additional fields to the edit-/create form.
     * Use the toolkit class as usual.
     *
     * @return string
     */
    public function getEditForm() {
        $strReturn = "";
        $strReturn .= $this->objToolkit->formInputDropdown(
            "unit",
            array(
                "f" => $this->getLang("weather_fahrenheit"),
                "c" => $this->getLang("weather_celsius")
            ),
            $this->getLang("weather_unit"),
            $this->getFieldValue("unit")
        );
        $strReturn .= $this->objToolkit->formInputText("location", $this->getLang("weather_location"), $this->getFieldValue("location"));
        return $strReturn;
    }

    /**
     * This method is called, when the widget should generate it's content.
     * Return the complete content using the methods provided by the base class.
     * Do NOT use the toolkit right here!
     *
     * @return string
     */
    public function getWidgetOutput() {
        $strReturn = "";

        if($this->getFieldValue("location") == "") {
            return "Please set up a location";
        }

        if(uniStrpos($this->getFieldValue("location"), "GM") !== false) {
            return "This widget changed, please update your location by editing the widget";
        }


        //request the xml...
        try {

            $strFormat = "metric";
            if($this->getFieldValue("unit") == "f")
                $strFormat = "imperial";

            $objRemoteloader = new class_remoteloader();
            $objRemoteloader->setStrHost("api.openweathermap.org");
            $objRemoteloader->setStrQueryParams("/data/2.5/forecast/daily?q=" . $this->getFieldValue("location") . "&units=" . $strFormat."&cnt=4");
            $strContent = $objRemoteloader->getRemoteContent();
        }
        catch(class_exception $objExeption) {
            $strContent = "";
        }

        if($strContent != "" && json_decode($strContent, true) !== null) {

            $arrResponse = json_decode($strContent, true);


            $strReturn .= $this->widgetText($this->getLang("weather_location_string") . $arrResponse["city"]["name"].", ".$arrResponse["city"]["country"]);


            foreach($arrResponse["list"] as $arrOneForecast) {
                $objDate = new class_date($arrOneForecast["dt"]);
                $strReturn .= "<div>";
                $strReturn .= $this->widgetText("<div style='float: left;'>".dateToString($objDate, false).": ".round($arrOneForecast["temp"]["day"], 1)."°</div>");
                $strReturn .= $this->widgetText("<img src='//openweathermap.org/img/w/".$arrOneForecast["weather"][0]["icon"].".png' style='float: right;' />");
                $strReturn .= "</div><div style='clear: both;'></div>";
            }

        }
        else {
            $strReturn .= $this->getLang("weather_errorloading");
        }


        return $strReturn;
    }

    /**
     * This callback is triggered on a users' first login into the system.
     * You may use this method to install a widget as a default widget to
     * a users dashboard.
     *
     * @param $strUserid
     *
     * @return bool
     */
    public function onFistLogin($strUserid) {
        return true;
    }


    /**
     * Return a short (!) name of the widget.
     *
     * @return string
     */
    public function getWidgetName() {
        return $this->getLang("weather_name");
    }

}


