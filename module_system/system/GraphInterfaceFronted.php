<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System;

/**
 * Interface for all based on java script chart-engines.
 * Concrete instances may be returned by GraphFactory.
 * This interface defines only the least subset of methods, so each implementation may
 * provide additional methods.
 *
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 * @see GraphFactory
 * @package module_system
 */
interface GraphInterfaceFronted extends GraphInterface
{
    /**
     * Sets Bar chart as horizontal
     *
     * @param bool $bitHorizontal
     * @return mixed
     */
    public function setBarHorizontal(bool $bitHorizontal);

    /**
     * Hides X Axis if true
     *
     * @param bool $bitHideXAxis
     * @return mixed
     */
    public function setHideXAxis(bool $bitHideXAxis = true);

    /**
     * Hides Y Axis if true
     *
     * @param bool $bitHideYAxis
     * @return mixed
     */
    public function setHideYAxis(bool $bitHideYAxis = true);

    /**
     * Draws borders around the chart
     *
     * @param bool $bitDrawBorder
     * @return mixed
     */
    public function drawBorder(bool $bitDrawBorder = true);

    /**
     * If true sets width as 100%
     *
     * @param bool $bitIsResizeable
     * @return mixed
     */
    public function setBitIsResizeable(bool $bitIsResizeable = true);

    /**
     * Adds a download link pop-up on mouse hover event
     *
     * @param bool $bitDownloadLink
     * @return mixed
     */
    public function setBitDownloadLink(bool $bitDownloadLink = true);


    /**
     * Sets min, max value and step for X axis
     *
     * @param null $intMin
     * @param null $intMax
     * @param null $intTickInterval
     * @return mixed
     */
    public function setXAxisRange($intMin = null, $intMax = null, $intTickInterval = null);

    /**
     * Sets min, max value and step for Y axis
     *
     * @param null $intMin
     * @param null $intMax
     * @param null $intTickInterval
     * @return mixed
     */
    public function setYAxisRange($intMin = null, $intMax = null, $intTickInterval = null);

    /**
     * A set of parameters to make the horizontal stacked inline chart view the same for all chart providers
     *
     * @param bool $autoSize
     * @return mixed
     */
    public function setAsHorizontalInLineStackedChart($autoSize = false);

    /**
     * For each series the bar colors will vary
     *
     * @return mixed
     */
    public function setVaryBarColorsForAllSeries($bitVaryBarColors = true);

    /**
     * Set the title of the second y-axis
     *
     * @param string $strTitle
     */
    public function setStrY2AxisTitle($strTitle);

    /**
     * Registers a new plot to the current graph. Works in line-plot-mode only.
     * Add a set of linePlot to a graph to get more then one line.
     * If you created a bar-chart before, it it is possible to add line-plots on top of
     * the bars. Nevertheless, the scale is calculated out of the bars, so make
     * sure to remain inside the visible range!
     * A sample-code could be:
     *  $objGraph = new class_graph();
     *  $objGraph->setStrXAxisTitle("x-axis");
     *  $objGraph->setStrYAxisTitle("y-axis");
     *  $objGraph->setStrGraphTitle("Test Graph");
     *
     *  //simple array
     *      $objGraph->addLinePlot(array(1,4,6,7,4), "serie 1");
     *
     * //datapoints array
     *      $objDataPoint1 = new GraphDatapoint(1);
     *      $objDataPoint2 = new GraphDatapoint(2);
     *      $objDataPoint3 = new GraphDatapoint(4);
     *      $objDataPoint4 = new GraphDatapoint(5);
     *
     *      //set action handler example
     *      $objDataPoint1->setObjActionHandler("<javascript code here>");
     *      $objDataPoint1->getObjActionHandlerValue("<value_object> e.g. some json");
     *
     *      $objGraph->addLinePlot(array($objDataPoint1, $objDataPoint2, $objDataPoint3, $objDataPoint4) "serie 1");
     *
     *
     * @param array $arrValues - an array with simple values or an array of data points (GraphDatapoint).
     *                           The advantage of a data points are that action handlers can be defined for each data point which will be executed when clicking on the data point in the chart.
     * @param string $strLegend the name of the single plot
     * @param bool $bitWriteValues
     * @param float $lineTension
     */
    public function addLinePlotY2Axis($arrValues, $strLegend);

    /**
     * Enables general repsonsiveness of the chart.
     *
     * @param bool $bitResponsive
     * @deprecated
     */
    public function setBitIsResponsive(bool $bitResponsive);

    /**
     * Sets tick step of x-axis
     *
     * @param int $bitHideXAxis
     */
    public function setTickStepXAxis(int $intStep);

    /**
     * Sets tick step of y-axis
     *
     * @param int $bitHideYAxis
     */
    public function setTickStepYAxis(int $intStep);

    /**
     * Sets tick step of second y-axis
     *
     * @param int $intStep
     */
    public function setTickStepY2Axis(int $intStep);

    /**
     * Sets thousand separator of x and y-axis
     *
     * @param bool $addSeparator
     */
    public function setShowThousandSeparatorAxis(bool $addSeparator = true);

    /**
     * Hides grid lines of  x-axis
     *
     * @param bool $bitHideGridLines
     */
    public function setHideGridLinesXAxis(bool $bitHideGridLines = true);

    /**
     * Hides grid lines of  y-axis
     *
     * @param bool $bitHideGridLines
     */
    public function setHideGridLinesYAxis(bool $bitHideGridLines = true);

    /**
     * Sets maximum number of ticks and gridlines to show.
     *
     * @param int $maxXAxesTicksLimit
     */
    public function setMaxXAxesTicksLimit(int $maxXAxesTicksLimit);

    /**
     * Returns is download link
     *
     * @return bool
     */
    public function isBitDownloadLink();

    /**
     * Sets min, max value and step for second y-axis
     * @param null $intMin
     * @param null $intMax
     * @param null $intTickInterval
     * @return mixed
     */
    public function setY2AxisRange($intMin = null, $intMax = null, $intTickInterval = null);


}
