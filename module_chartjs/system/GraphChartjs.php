<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Chartjs\System;

use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\GraphCommons;
use Kajona\System\System\GraphDatapoint;
use Kajona\System\System\GraphInterfaceFronted;

/**
 * This class could be used to create graphs based on the chartjs API.
 * chartjs renders charts on the client side.
 *
 * @since 7.1
 * @author sascha.broening@artemeon.de
 * @author andrii.konoval@artemeon.de
 * @author stefan.idler@artemeon.de
 */
class GraphChartjs implements GraphInterfaceFronted
{

    /**
     * Contains all data of the chart
     *
     * @var array
     */
    private $arrChartData = [
        "type" => "bar",
        "options" => [
            'plugins' => [
                'datalabels' => [
                    'display' => false,
                ]
            ],
            "title" => [
                "display" => false,
            ],
            'scales' => [
                'xAxes' => [
                    [
                        'ticks' => [
                            'beginAtZero' => true
                        ]
                    ]
                ],
                'yAxes' => [
                    [
                        'id' => 'defaultYID',
                        'ticks' => [
                            'beginAtZero' => true
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * Contains all global options of the chart
     * @todo: wozu gibt es das? gehört das nicht in das $arrChartData set? "Global" darf hier an sicht nicht genannt werden!
     *
     * @var array
     */
    private $arrChartGlobalOptions = [

    ];

    /**
     * @var int
     */
    private $intXLabelsCount = 0;

    /**
     * Default color set for chats.
     *
     * @var array
     */
    private $arrColors = [
        "#8bbc21", "#2f7ed8", "#f28f43", "#1aadce", "#77a1e5", "#0d233a", "#c42525", "#a6c96a", "#910000",
        '#0048Ba', '#B0BF1A', '#C46210', '#FFBF00', '#9966CC', '#841B2D', '#FAEBD7', '#8DB600', '#D0FF14', '#FF9966', '#007FFF', '#FF91AF', '#E94196', '#CAE00D', '#54626F'
    ];

    /**
     * Defines the width for the canvas but ONLY if respnsive is set to FALSE
     *
     * @var integer
     */
    private $intWidth = null;

    /**
     * Defines the height for the canvas
     *
     * @var integer
     */
    private $intHeight = null;

    /**
     * Defines if we need to show the download link of chart image on the chart
     *
     * @var bool
     */
    private $bitDownloadLink = false;

    /**
     * @return array
     */
    public function getArrChartData(): array
    {
        return $this->arrChartData;
    }

    /**
     * Converts array of dataPoint object to array of array
     *
     * @param $arrDataPointObjects
     * @return array
     */
    private function dataPointObjArrayToArray(array $arrDataPointObjects)
    {
        $arrDataPoints = [];
        foreach ($arrDataPointObjects as $objDataPoint) {
            $arrDataPoints[] = [
                "floatvalue" => $objDataPoint->getFloatValue(),
                "actionhandlervalue" => $objDataPoint->getObjActionHandlerValue(),
                "actionhandler" => $objDataPoint->getObjActionHandler(),
            ];
        }
        return $arrDataPoints;
    }

    /**
     * Add new data set to the chart
     *
     * @param array $arrValues
     * @param string $strLegend
     * @param string $type
     * @param bool $bitWriteValues
     * @param null $yAxisID
     * @param float $lineTension
     */
    private function addChartSet(array $arrValues, string $strLegend = "", $type = null, $bitWriteValues = false, $yAxisID = null, $lineTension = 0.2) //TODO: warum an einer privaten methode optional parameter?
    {
        $arrDataPointObjects = GraphCommons::convertArrValuesToDataPointArray($arrValues);

        $intDatasetNumber = isset($this->arrChartData['data']['datasets']) ? count($this->arrChartData['data']['datasets']) : 0;
        $this->arrChartData['data']['datasets'][] = [
            "dataPoints" => $this->dataPointObjArrayToArray($arrDataPointObjects),
            "type" => $type,
            "label" => !empty($strLegend) ? $strLegend : "Dataset ".$intDatasetNumber,
            "data" => GraphCommons::getDataPointFloatValues($arrDataPointObjects),
            "backgroundColor" => $this->arrColors[$intDatasetNumber] /*'rgba('.implode(', ', hex2rgb($this->arrColors[$intDatasetNumber])).', 0.3)'*/,
            "borderColor" => $this->arrColors[$intDatasetNumber],
            "borderWidth" => 1,
            "yAxisID" => empty($yAxisID) ? "defaultYID" : $yAxisID,
            "datalabels" => $bitWriteValues ? ["display" => true] : ["display" => false],
            "lineTension" => $lineTension
        ];
        $this->intXLabelsCount = count($arrValues);
    }

    /**
     * Add new data set to the Bar chart
     *
     * @param array $arrValues
     * @param string $strLegend
     * @param bool $bitWriteValues
     *
     * @see GraphInterface::addBarChartSet()
     */
    public function addBarChartSet($arrValues, $strLegend, $bitWriteValues = false)
    {
        $this->addChartSet($arrValues, $strLegend, null, $bitWriteValues);
    }

    /**
     * Add new data set to the StackedBar chart
     *
     * @param array $arrValues
     * @param string $strLegend
     * @param bool $bitWriteValues
     *
     * @see GraphInterface::addStackedBarChartSet()
     */
    public function addStackedBarChartSet($arrValues, $strLegend = "", $bitWriteValues = true)
    {
        $this->addChartSet($arrValues, $strLegend, null, $bitWriteValues);
        $this->arrChartData['options']['scales']['xAxes'][0]['stacked'] = true;
        $this->arrChartData['options']['scales']['yAxes'][0]['stacked'] = true;
        $this->setNotShowNullValues(true);
    }

    /**
     * Add new data set to the Line chart
     *
     * @param array $arrValues
     * @param string $strLegend
     *
     * @param bool $bitWriteValues
     * @param float $lineTension
     * @see GraphInterface::addLinePlot()
     */
    public function addLinePlot($arrValues, $strLegend = "", $bitWriteValues = false, $lineTension = 0.2)
    {
        $this->addChartSet($arrValues, $strLegend, "line", $bitWriteValues, null, $lineTension);
    }

    /**
     * Add additional data set to the Line chart
     *
     * @param array $arrValues
     * @param string $strLegend
     *
     * @param bool $bitWriteValues
     * @param float $lineTension
     * @see GraphInterface::addLinePlot()
     */
    public function addLinePlotY2Axis($arrValues, $strLegend, $bitWriteValues = false, $lineTension = 0.2)
    {
        $this->addChartSet($arrValues, $strLegend, "line", $bitWriteValues, "2YID", $lineTension);
        $this->arrChartData['options']['scales']['yAxes'][1]['id'] = "2YID";
        $this->arrChartData['options']['scales']['yAxes'][1]['type'] = "linear";
        $this->arrChartData['options']['scales']['yAxes'][1]['position'] = "right";
    }

    /**
     * Add new data set to the Pie chart
     *
     * @param array $arrValues
     * @param array $arrLegends
     *
     * @see GraphInterface::createPieChart()
     */
    public function createPieChart($arrValues, $arrLegends = "")
    {
        $arrDataPointObjects = GraphCommons::convertArrValuesToDataPointArray($arrValues, true);

        $nrOfNonNullValues = 0;
        array_map(function (GraphDatapoint $point) use (&$nrOfNonNullValues) {
            if ($point->getFloatValue() > 0) {
                $nrOfNonNullValues++;
            }
        }, $arrDataPointObjects);


        $this->setPieChart(true);
        foreach ($this->arrColors as $arrColor) {
            $arrBackgroundColors[] = $arrColor;
            $arrBorderColors[] =  $nrOfNonNullValues <= 1 ? $arrColor : '#FFFFFF';
        }
        $this->arrChartData['data']['datasets'][] = [
            "dataPoints" => $this->dataPointObjArrayToArray($arrDataPointObjects),
            "data" => GraphCommons::getDataPointFloatValues($arrDataPointObjects),
            "backgroundColor" => $arrBackgroundColors,
            "borderColor" => $arrBorderColors
        ];
        $this->intXLabelsCount = count($arrValues);
        $this->arrChartData['data']['labels'] = $arrLegends;

        $this->arrChartData['options']['scales']['xAxes'][0]['gridLines']['display'] = false;
        $this->arrChartData['options']['scales']['xAxes'][0]['display'] = false;
        $this->arrChartData['options']['scales']['yAxes'][0]['gridLines']['display'] = false;
        $this->arrChartData['options']['scales']['yAxes'][0]['display'] = false;
        $this->setHideXAxis(true);
        $this->setHideYAxis(true);
        $this->setValueTypePercentage(false);
    }

    /**
     * @see GraphInterface::showGraph()
     */
    public function showGraph()
    {
        $this->renderGraph();
    }

    /**
     * @param $strFilename
     *
     * @see GraphInterface::saveGraph()
     */
    public function saveGraph($strFilename)
    {
        //not supported
    }

    /**
     * @param bool $isResizable
     */
    public function setBitIsResizeable(bool $isResizable = true)
    {
        //not supported
    }

    /**
     * @param bool $bitDrawBorder
     */
    public function drawBorder(bool $bitDrawBorder = true)
    {
        //not supported
    }

    /**
     * @param string $strTitle
     *
     * @see GraphInterface::setStrXAxisTitle()
     */
    public function setStrXAxisTitle($strTitle)
    {
        $this->arrChartData['options']['scales']['xAxes'][0]['scaleLabel']['display'] = true;
        $this->arrChartData['options']['scales']['xAxes'][0]['scaleLabel']['labelString'] = $strTitle;
    }

    /**
     * @param string $strTitle
     *
     * @see GraphInterface::setStrYAxisTitle()
     */
    public function setStrYAxisTitle($strTitle)
    {
        $this->arrChartData['options']['scales']['yAxes'][0]['scaleLabel']['display'] = true;
        $this->arrChartData['options']['scales']['yAxes'][0]['scaleLabel']['labelString'] = $strTitle;
    }

    /**
     * @param string $strTitle
     *
     * @see GraphInterface::setStrY2AxisTitle()
     */
    public function setStrY2AxisTitle($strTitle)
    {
        $this->arrChartData['options']['scales']['yAxes'][1]['scaleLabel']['display'] = true;
        $this->arrChartData['options']['scales']['yAxes'][1]['scaleLabel']['labelString'] = $strTitle;
    }

    /**
     * @param string $strTitle
     *
     * @see GraphInterface::setStrGraphTitle()
     */
    public function setStrGraphTitle($strTitle)
    {
        $this->arrChartData['options']['title']['display'] = true;
        $this->arrChartData['options']['title']['text'] = $strTitle;
    }

    /**
     * @param string $strColor
     *
     * @see GraphInterface::setStrBackgroundColor()
     */
    public function setStrBackgroundColor($strColor)
    {
        $this->arrChartGlobalOptions['backgroundColor'] = $strColor;
    }

    /**
     * @param int $intWidth
     *
     * @see GraphInterface::setIntWidth()
     */
    public function setIntWidth($intWidth)
    {
        $this->intWidth = $intWidth;
    }

    /**
     * @param int $intHeight
     *
     * @see GraphInterface::setIntHeight()
     */
    public function setIntHeight($intHeight)
    {
        $this->intHeight = $intHeight;
    }

    /**
     * Sets array of labels.
     *
     * @param array $arrXAxisTickLabels
     * @param int $intNrOfWrittenLabels
     *
     * @see GraphInterface::setArrXAxisTickLabels()
     */
    public function setArrXAxisTickLabels($arrXAxisTickLabels, $intNrOfWrittenLabels = 12)
    {
        $this->arrChartData['data']['labels'] = $arrXAxisTickLabels;
        $this->setMaxXAxesTicksLimit($intNrOfWrittenLabels);
    }

    /**
     * @param bool $bitRenderLegend
     *
     * @see GraphInterface::setBitRenderLegend()
     */
    public function setBitRenderLegend($bitRenderLegend)
    {
        $this->arrChartData['options']['legend']['display'] = $bitRenderLegend;
    }

    /**
     * @param string $strFont
     *
     */
    public function setDefaultFont($strFont)
    {
        $this->arrChartGlobalOptions['defaultFontFamily'] = $strFont;
    }

    /**
     * @param string $strFont
     *
     * @see GraphInterface::setStrFont()
     */
    public function setStrFont($strFont)
    {
        $this->arrChartData['options']['legend']['labels']['fontFamily'] = $strFont;
        $this->arrChartData['options']['title']['fontFamily'] = $strFont;
        $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['fontFamily'] = $strFont;
        $this->arrChartData['options']['scales']['yAxes'][0]['ticks']['fontFamily'] = $strFont;
        $this->arrChartData['options']['scales']['xAxes'][0]['scaleLabel']['fontFamily'] = $strFont;
        $this->arrChartData['options']['scales']['yAxes'][0]['scaleLabel']['fontFamily'] = $strFont;

    }

    /**
     * @param string $strFontColor
     *
     */
    public function setStrDefaultFontColor($strFontColor)
    {
        $this->arrChartGlobalOptions['defaultFontColor'] = $strFontColor;
    }

    /**
     * @param string $strFontColor
     *
     * @see GraphInterface::setStrFontColor()
     */
    public function setStrFontColor($strFontColor)
    {
        $this->arrChartData['options']['legend']['labels']['fontColor'] = $strFontColor;
        $this->arrChartData['options']['title']['fontColor'] = $strFontColor;
        $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['fontColor'] = $strFontColor;
        $this->arrChartData['options']['scales']['yAxes'][0]['ticks']['fontColor'] = $strFontColor;
        $this->arrChartData['options']['scales']['xAxes'][0]['scaleLabel']['fontColor'] = $strFontColor;
        $this->arrChartData['options']['scales']['yAxes'][0]['scaleLabel']['fontColor'] = $strFontColor;
    }

    /**
     * @see GraphInterface::setIntXAxisAngle()
     */
    public function setIntXAxisAngle($intXAxisAngle)
    {
        //not supported
    }

    /**
     * @param array $arrSeriesColors
     * @return void
     *
     * @see GraphInterface::setArrSeriesColors()
     */
    public function setArrSeriesColors($arrSeriesColors)
    {
        if (!empty($arrSeriesColors)) {
            $this->arrColors = $arrSeriesColors;
        }

        //TODO @ako: warum kam das rein?
        //das hier klappt für line charts
        $colorIndex = 0;
        $colorCount = count($arrSeriesColors);
        if (!empty($this->arrChartData['data']['datasets'])) {
            foreach ($this->arrChartData['data']['datasets'] as $index => $dataset) {
                $this->arrChartData['data']['datasets'][$index]["backgroundColor"] = $arrSeriesColors[$colorIndex];
                if ($dataset["borderColor"] !== '#FFFFFF') {
                    $this->arrChartData['data']['datasets'][$index]["borderColor"] = $arrSeriesColors[$colorIndex];
                }
                if (++$colorIndex > $colorCount-1) {
                    $colorIndex = 0;
                }
            }
        }

        //TODO das hier klappt für pie charts
//        if (!empty($this->arrChartData['data']['datasets'])) {
//            $this->arrChartData['data']['datasets'][0]["backgroundColor"] = $arrSeriesColors;
//            $oldBorder = $this->arrChartData['data']['datasets'][0]["borderColor"];
//            $this->arrChartData['data']['datasets'][0]["borderColor"] = $oldBorder[0] == '#FFFFFF' ? $oldBorder : $arrSeriesColors;
//        }
    }

    /**
     * Enables general repsonsiveness of the chart.
     *
     * @param bool $bitResponsive
     * @deprecated
     */
    public function setBitIsResponsive(bool $bitResponsive)
    {
    }

    /**
     * @return bool
     * @deprecated
     */
    public function isBitIsResponsive(): bool
    {
    }

    /**
     * @param bool $bitHorizontal
     */
    public function setBarHorizontal(bool $bitHorizontal)
    {
        $this->arrChartData['type'] = $bitHorizontal ? "horizontalBar" : "bar";
    }

    /**
     * @param bool $bitPie
     */
    public function setPieChart(bool $bitPie)
    {
        if ($bitPie) {
            $this->arrChartData['type'] = "pie";
        }
    }

    /**
     * @param bool $bitHideXAxis
     */
    public function setHideXAxis(bool $bitHideXAxis = true)
    {
        $this->arrChartGlobalOptions['xAxesTickDispaly'] = !$bitHideXAxis;
        $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['display'] = !$bitHideXAxis;
    }

    /**
     * @param bool $bitHideYAxis
     */
    public function setHideYAxis(bool $bitHideYAxis = true)
    {
        $this->arrChartGlobalOptions['yAxesTickDispaly'] = !$bitHideYAxis;
        $this->arrChartData['options']['scales']['yAxes'][0]['ticks']['display'] = !$bitHideYAxis;
    }

    /**
     * @param int $bitHideXAxis
     */
    public function setTickStepXAxis(int $intStep)
    {
        $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['stepSize'] = $intStep;
    }

    /**
     * @param int $bitHideYAxis
     */
    public function setTickStepYAxis(int $intStep)
    {
        $this->arrChartData['options']['scales']['yAxes'][0]['ticks']['stepSize'] = $intStep;
    }

    /**
     * @param int $bitHideY2Axis
     */
    public function setTickStepY2Axis(int $intStep)
    {
        $this->arrChartData['options']['scales']['yAxes'][1]['ticks']['stepSize'] = $intStep;
    }

    /**
     * @param bool $bitHideGridLines
     */
    public function setHideGridLinesXAxis(bool $bitHideGridLines = true)
    {
        $this->arrChartData['options']['scales']['xAxes'][0]['gridLines']['display'] = !$bitHideGridLines;
    }

    /**
     * @param bool $bitHideGridLines
     */
    public function setHideGridLinesYAxis(bool $bitHideGridLines = true)
    {
        $this->arrChartData['options']['scales']['yAxes'][0]['gridLines']['display'] = !$bitHideGridLines;
    }

    /**
     * @param int $maxXAxesTicksLimit
     */
    public function setMaxXAxesTicksLimit(int $maxXAxesTicksLimit)
    {
        $this->arrChartGlobalOptions['maxXAxesTicksLimit'] = $maxXAxesTicksLimit;
        $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['autoSkip'] = true;
        $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['maxTicksLimit'] = $maxXAxesTicksLimit;
    }

    /**
     * @param bool $beginAtZero
     */
    public function setBeginAtZero(bool $beginAtZero = true)
    {
        $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['beginAtZero'] = $beginAtZero;
    }

    /**
     * @return bool
     */
    public function isBitDownloadLink(): bool
    {
        return $this->bitDownloadLink;
    }

    /**
     * @param bool $bitDownloadLink
     */
    public function setBitDownloadLink(bool $bitDownloadLink = true)
    {
        $this->bitDownloadLink = $bitDownloadLink;
    }

    /**
     * Set if you need to show values straight on the chart in percentage view
     *
     * @param bool $bitSetPercentageValues
     */
    public function setValueTypePercentage(bool $bitSetPercentageValues = true)
    {
        $this->arrChartGlobalOptions['percentageValues'] = $bitSetPercentageValues;
    }

    /**
     * Set if you don't want to show 0 values on the chart
     *
     * @param bool $bitNotShowNullValues
     */
    public function setNotShowNullValues(bool $bitNotShowNullValues = true)
    {
        $this->arrChartGlobalOptions['notShowNullValues'] = $bitNotShowNullValues;
    }

    /**
     * Switch on default tooltip.
     * By default chartjs render used customized tooltip.
     *
     * @param bool $bitSetDefaultTooltip
     */
    public function setDefaultTooltip(bool $bitSetDefaultTooltip = true)
    {
        $this->arrChartGlobalOptions['setDefaultTooltip'] = $bitSetDefaultTooltip;
    }

    /**
     * @param null $intMin
     * @param null $intMax
     * @param null $intTickInterval
     * @return mixed|void
     */
    public function setXAxisRange($intMin = null, $intMax = null, $intTickInterval = null)
    {
        if ($intMin !== null) {
            $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['suggestedMin'] = $intMin;
        }
        if ($intMax !== null) {
            $this->arrChartData['options']['scales']['xAxes'][0]['ticks']['suggestedMax'] = $intMax;
        }
        if ($intTickInterval !== null) {
            $this->setTickStepYAxis((int)$intTickInterval);
        }
    }

    /**
     * @param $minVal
     * @param $maxVal
     */
    public function setYAxisRange($intMin = null, $intMax = null, $intTickInterval = null)
    {
        if ($intMin !== null) {
            $this->arrChartData['options']['scales']['yAxes'][0]['ticks']['suggestedMin'] = $intMin;
        }
        if ($intMax !== null) {
            $this->arrChartData['options']['scales']['yAxes'][0]['ticks']['suggestedMax'] = $intMax;
        }
        if ($intTickInterval !== null) {
            $this->setTickStepYAxis((int)$intTickInterval);
        }
    }

    /**
     * @param $minVal
     * @param $maxVal
     */
    public function setY2AxisRange($intMin = null, $intMax = null, $intTickInterval = null)
    {
        if ($intMin !== null) {
            $this->arrChartData['options']['scales']['yAxes'][1]['ticks']['suggestedMin'] = $intMin;
        }
        if ($intMax !== null) {
            $this->arrChartData['options']['scales']['yAxes'][1]['ticks']['suggestedMax'] = $intMax;
        }
        if ($intTickInterval !== null) {
            $this->setTickStepY2Axis((int)$intTickInterval);
        }
    }

    /**
     * @param bool $autoHeight
     * @return mixed|void
     *
     * @inheritdoc
     */
    public function setAsHorizontalInLineStackedChart($autoHeight = false)
    {
        $this->setBarHorizontal(true);
        $this->setHideXAxis(true);
        $this->setHideYAxis(true);
        $this->setTickStepXAxis(1);
        $this->setTickStepYAxis(1);
        $this->setHideGridLinesYAxis(true);
        $this->setHideGridLinesXAxis(true);
        $this->setBitDownloadLink(false);
        if ($autoHeight && isset($this->arrChartData['data']) && count($this->arrChartData['data']) != 0) {
            $countGraphs = count($this->arrChartData['data']['datasets'][0]['dataPoints']);
            $this->setIntHeight(30 + $countGraphs * 40);
        }
    }


    /**
     * @return mixed|string
     *
     * @see GraphInterface::renderGraph()
     * @throws Exception
     */
    public function renderGraph()
    {
        if (!isset($this->arrChartData['data']) || count($this->arrChartData['data']) == 0) {
            throw new Exception("Chart not initialized yet", Exception::$level_ERROR);
        }

        if (!isset($this->arrChartData['data']['labels']) || count($this->arrChartData['data']['labels']) == 0) {
            $this->arrChartData['data']['labels'] = range(1, $this->intXLabelsCount);
        }

        $strSystemId = generateSystemid();
        $strResizeableId = "resize_".$strSystemId;
        $strChartId = "chart_".$strSystemId;
        $strLinkExportId = $strChartId."_exportlink";

//        $strWidth = $this->isBitIsResponsive() ? "100%" : $this->intWidth."px";
//        $strHeight = $this->isBitIsResponsive() ? "100%" : $this->intHeight."px";
//        $strReturn = "<div onmouseover='$(\"#$strLinkExportId\").show();' onmouseout='$(\"#$strLinkExportId\").hide();' id=\"$strResizeableId\" style=\"width:{$strWidth}; height:{$strHeight};\">";

        $style = "";
        if ($this->intWidth !== null) {
            $style .= " width: {$this->intWidth}px; ";
        }

        if ($this->intHeight !== null) {
            $style .= " height: {$this->intHeight}px; ";
        }


        $strReturn = "<div onmouseover='$(\"#{$strLinkExportId}\").show();' onmouseout='$(\"#{$strLinkExportId}\").hide();' id='{$strResizeableId}' style='{$style}'>";
        $strReturn .= "<canvas id='{$strChartId}' style=' width: 100%; height: 100%' ></canvas>";
        if ($this->isBitDownloadLink()) {
            $strImage = AdminskinHelper::getAdminImage("icon_downloads", Carrier::getInstance()->getObjLang()->getLang("commons_save_as_image", "system"));
            $strReturn .= "<div class=\"chartjs-link-bar\"><a class=\"chartjs-image-link\" id=\"$strLinkExportId\" download>$strImage</a></div>";
            $this->arrChartGlobalOptions['createImageLink'] = true;
        }
        $strReturn .= "</div>";

        $strReturn .= "<script type='text/javascript'>
            require(['chartjsHelper'], function(chartjsHelper) {
                var chartData = ".json_encode($this->arrChartData, JSON_NUMERIC_CHECK).";
    	        var chartGlobalOptions = ".json_encode($this->arrChartGlobalOptions, JSON_NUMERIC_CHECK).";
                var ctx = document.getElementById('".$strChartId."');
                chartjsHelper.createChart(ctx, chartData, chartGlobalOptions);
            });
        </script>";

        return $strReturn;
    }
}
