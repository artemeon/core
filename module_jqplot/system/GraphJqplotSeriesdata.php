<?php
/*"******************************************************************************************************
*   (c) 2013-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                           *
********************************************************************************************************/

namespace Kajona\Jqplot\System;

use Kajona\System\System\Exception;
use Kajona\System\System\GraphDatapoint;

/**
 * This class contains the data for a series and their specific options.
 *
 * @package module_jqplot
 * @since 4.3
 * @author stefan.meyer1@yahoo.de
 */
class GraphJqplotSeriesdata
{

    private $arrDataPoints = null;
    private $intChartType = null;
    private $intSeriesDataOrder = null;

    //contains specific options for this series
    private $arrSeriesOptions = array(
        "renderer"        => null,
        "rendererOptions" => array(
            "showDataLabels"     => null,
            "fillToZero"         => null,
            "highlightMouseOver" => false
        ),
        "markerOptions"   => array(
            "style" => null
        ),
        "label"           => null,
        "pointLabels"     => array(
            "show"   => false,
            "labels" => null
        )
    );


    public function __construct($strChartType, $intSeriesDataOrder, &$arrGlobalOptions)
    {
        $this->intSeriesDataOrder = $intSeriesDataOrder;
        $this->intChartType = $strChartType;

        if($strChartType == GraphJqplotCharttype::LINE) {
            $this->arrSeriesOptions["renderer"] = "$.jqplot.LineRenderer";
            $this->arrSeriesOptions["lineWidth"] = 2;
            $this->arrSeriesOptions["shadow"] = false;
            $this->arrSeriesOptions["markerOptions"]["size"] = 6;
        }
        elseif($strChartType == GraphJqplotCharttype::LINE_Y2AXIS) {
            $this->arrSeriesOptions["renderer"] = "$.jqplot.LineRenderer";
            $this->arrSeriesOptions["lineWidth"] = 2;
            $this->arrSeriesOptions["shadow"] = false;
            $this->arrSeriesOptions["markerOptions"]["size"] = 6;
            $this->arrSeriesOptions["yaxis"] = "y2axis";
        }
        elseif($strChartType == GraphJqplotCharttype::BAR) {
            $this->arrSeriesOptions["renderer"] = "$.jqplot.BarRenderer";
            $this->arrSeriesOptions["rendererOptions"]["fillToZero"] = true;
            $this->arrSeriesOptions["rendererOptions"]["shadow"] = false;
            $this->arrSeriesOptions["pointLabels"]["hideZeros"] = false;
        }
        elseif($strChartType == GraphJqplotCharttype::BAR_HORIZONTAL) {
            $this->arrSeriesOptions["renderer"] = "$.jqplot.BarRenderer";
            $this->arrSeriesOptions["rendererOptions"]["barDirection"] = "horizontal";
            $this->arrSeriesOptions["rendererOptions"]["fillToZero"] = true;
            $this->arrSeriesOptions["rendererOptions"]["shadow"] = false;
            $this->arrSeriesOptions["pointLabels"]["hideZeros"] = false;

            //additionally set required global options
            $arrGlobalOptions["seriesDefaults"]["renderer"] = "$.jqplot.BarRenderer";
            $arrGlobalOptions["seriesDefaults"]["rendererOptions"]["barDirection"] = "horizontal";
        }
        elseif($strChartType == GraphJqplotCharttype::STACKEDBAR) {
            $this->arrSeriesOptions["renderer"] = "$.jqplot.BarRenderer";
            $this->arrSeriesOptions["rendererOptions"]["fillToZero"] = true;
            $this->arrSeriesOptions["rendererOptions"]["shadow"] = false;
            $this->arrSeriesOptions["pointLabels"]["hideZeros"] = true;
            $this->arrSeriesOptions["pointLabels"]["show"] = true;

        }
        elseif($strChartType == GraphJqplotCharttype::STACKEDBAR_HORIZONTAL) {
            $this->arrSeriesOptions["renderer"] = "$.jqplot.BarRenderer";
            $this->arrSeriesOptions["rendererOptions"]["barDirection"] = "horizontal";
            $this->arrSeriesOptions["rendererOptions"]["fillToZero"] = true;
            $this->arrSeriesOptions["rendererOptions"]["shadow"] = false;
            $this->arrSeriesOptions["pointLabels"]["hideZeros"] = true;
            $this->arrSeriesOptions["pointLabels"]["show"] = true;
            $this->arrSeriesOptions["pointLabels"]["formatString"] = '%s';

            //additionally set required global options
            $arrGlobalOptions["seriesDefaults"]["renderer"] = "$.jqplot.BarRenderer";
            $arrGlobalOptions["seriesDefaults"]["rendererOptions"]["barDirection"] = "horizontal";
        }
        elseif($strChartType == GraphJqplotCharttype::PIE) {
            $this->arrSeriesOptions["renderer"] = "$.jqplot.PieRenderer";
            $this->arrSeriesOptions["rendererOptions"]["showDataLabels"] = true;
            $this->arrSeriesOptions["rendererOptions"]["sliceMargin"] = 2;
            $this->arrSeriesOptions["rendererOptions"]["shadowOffset"] = 0;
            $this->arrSeriesOptions["rendererOptions"]["highlightMouseOver"] = true;
        }
        else {
            throw new Exception("Not a valid chart type", Exception::$level_ERROR);
        }
    }


    /**
     * @param bool $bitWriteValues
     */
    public function setBitWriteValues($bitWriteValues = false)
    {
        $this->arrSeriesOptions["pointLabels"]["show"] = $bitWriteValues;
    }

    /**
     * @return int
     */
    public function getIntChartType()
    {
        return $this->intChartType;
    }


    /**
     * @return int
     */
    public function getIntSeriesDataOrder()
    {
        return $this->intSeriesDataOrder;
    }


    /**
     * @param array $arrDataArray
     */
    public function setArrDataPoints($arrDataArray)
    {
        $this->arrDataPoints = $arrDataArray;

        //now process array -> all values which are not numeric will be converted to a 0
        foreach($this->arrDataPoints as $objDataPoint) {
            if(!is_numeric($objDataPoint->getFloatValue())) {
                $objDataPoint->setFloatValue(0);
            }
        }

        if(count($this->arrDataPoints) == 0) {
            $this->arrDataPoints = array(new GraphDatapoint(0));
        }
    }

    /**
     * @return array
     */
    public function getArrDataPoints()
    {
        return $this->arrDataPoints;
    }

    /**
     * @param string $strSeriesLabel
     */
    public function setStrSeriesLabel($strSeriesLabel)
    {
        $this->arrSeriesOptions["label"] = $strSeriesLabel;
    }

    /**
     * @return string
     */
    public function getStrSeriesLabel()
    {
        return $this->arrSeriesOptions["label"];
    }

    /**
     * Converts the php array to a JSON string for jqplot
     *
     * @return string
     */
    public function optionsToJSON()
    {
        return json_encode($this->arrSeriesOptions);
    }

    /**
     * @return array
     */
    public function getArrSeriesOptions()
    {
        return $this->arrSeriesOptions;
    }

    /**
     * @param array $arrSeriesOptions
     */
    public function setArrSeriesOptions($arrSeriesOptions)
    {
        $this->arrSeriesOptions = $arrSeriesOptions;
    }
}