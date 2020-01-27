<?php

declare(strict_types = 1);

namespace Kajona\Chartjs\Debug;


use Kajona\Chartjs\System\GraphChartjs;
use Kajona\System\Admin\AdminHelper;
use Kajona\System\System\GraphFactory;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\SystemSetting;

class ChartJS
{
    public function testCharts()
    {
        srand((int)microtime() * 1000000);
        //--- system kernel -------------------------------------------------------------------------------------
        echo "\tcreating a few charts...\n";

        //JS-Imports for minimal system setup
        echo "<script type=\"text/javascript\">KAJONA_LANGUAGE = 'de'</script>\n";
        echo "<script type=\"text/javascript\">KAJONA_WEBPATH = '"._webpath_."'; KAJONA_BROWSER_CACHEBUSTER = '".SystemSetting::getConfigValue("_system_browser_cachebuster_")."';</script>\n";
        echo "<script type=\"text/javascript\">KAJONA_PHARMAP = ".json_encode(array_values(\Kajona\System\System\Classloader::getInstance()->getArrPharModules())).";</script>";
        echo "<script language=\"javascript\" type=\"text/javascript\" src=\""._webpath_.Resourceloader::getInstance()->getCorePathForModule("module_system")."/module_system/scripts/agp.min.js\"></script>";

        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\""._webpath_.Resourceloader::getInstance()->getCorePathForModule("module_system")."/module_system/scripts/jqueryui/css/smoothness/jquery-ui.custom.css\"></link>";
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\""._webpath_.Resourceloader::getInstance()->getCorePathForModule("module_system")."/module_v4skin/admin/skins/kajona_v4/less/styles.min.css\"></link>";

        $objAdminHelper = new AdminHelper();

        echo "<script language=\"javascript\" type=\"text/javascript\" src=\""._webpath_.Resourceloader::getInstance()->getCorePathForModule("module_system")."/module_system/scripts/requirejs/require.js\"></script>";
        echo "<script type=\"text/javascript\">
            require(['app'], function(app) { app.init(); });
        </script>
        ";

        /** @var GraphChartjs $objGraph */
        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->addLinePlot(array(8.112, 1, 2, 4), "");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "");
        $objGraph->addLinePlot(array(4, 7, 1, 2), "");
        $objGraph->addLinePlot(array(4, 3, 2, 1), "");
        $objGraph->addLinePlot(array(-5, 3, -2, 1), "");
        $objGraph->setBitRenderLegend(true);
        $objGraph->setIntXAxisAngle(-20); // not works
        $objGraph->setStrXAxisTitle("XXX");
        $objGraph->setStrYAxisTitle("YYY");
        $objGraph->setStrBackgroundColor("#F0F0F0");
        $objGraph->setStrGraphTitle("01. My First Line Chart");
        $objGraph->setIntHeight(700);
        $objGraph->setIntWidth(700);
        $objGraph->setStrFontColor("#FF0000");
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4"));
        $objGraph->setStrFont("Open Sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->addLinePlot(array(8.112, 1, 2, 4), "");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "");
        $objGraph->addLinePlot(array(4, 7, 1, 2), "");
        $objGraph->addLinePlot(array(4, 3, 2, 1), "");
        $objGraph->addLinePlot(array(-5, 3, -2, 1), "");
        $objGraph->setBitRenderLegend(true);
        $objGraph->setIntXAxisAngle(-20);  // not works
        $objGraph->setStrXAxisTitle("XXX");
        $objGraph->setStrYAxisTitle("YYY");
        $objGraph->setStrBackgroundColor("#F0F0F0");
        $objGraph->setStrGraphTitle("02. My First Line Chart 2");
        $objGraph->setIntHeight(500);
        $objGraph->setIntWidth(700);
        $objGraph->setStrFontColor("#FF0000");
        $objGraph->setStrFont("Open Sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("03. A Bar Chart");
        $objGraph->addBarChartSet(array(1, 4, 3, 6), "serie 111111111111111");
        $objGraph->addBarChartSet(array(3, 3, 6, 2), "serie 2");
        $objGraph->addBarChartSet(array(4, 4, 8, 6), "serie 3");
        $objGraph->addBarChartSet(array(10, 7, 3, 3), "serie 4");
        $objGraph->addBarChartSet(array(6, 7, 3, 20), "serie 5");
        $objGraph->addBarChartSet(array(9, 2, 3, 40), "serie 9");
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4"));
        $objGraph->setIntXAxisAngle(-20);  // not works
        $objGraph->setIntHeight(350);
        $objGraph->setIntWidth(300);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFontColor("#FF0000");
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("04. One Bar Chart (In this case each bar has a differetn color)");
        $objGraph->addBarChartSet(array(9, 2, 3, 40), "serie 9");
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4"));
        $objGraph->setIntXAxisAngle(-20);  // not works
        $objGraph->setIntHeight(350);
        $objGraph->setIntWidth(300);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFontColor("#FF0000");
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("05. A Mixed Chart");
        $objGraph->setStrXAxisTitle("My new X-Axis");
        $objGraph->setStrYAxisTitle("My new Y-Axis");
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 3", true);
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 4", true);
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 5", true);
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 6", true);
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 7", true);
        $objGraph->addLinePlot(array(8, 1, 2, 4), "serie 8");
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 9", true);
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 10", true);
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 11");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 12");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 13");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 14");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 15");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 16");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 17");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 18");
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("06. A Mixed stacked Chart");
        $objGraph->setStrXAxisTitle("My new X-Axis");
        $objGraph->setStrYAxisTitle("My new Y-Axis");
        $objGraph->addStackedBarChartSet(array(4, 2, 3, 4), "serie 3");
        $objGraph->addStackedBarChartSet(array(1, 3, 3, 4), "serie 4");
        $objGraph->addStackedBarChartSet(array(1, 2, 2, 3), "serie 5");
        $objGraph->addStackedBarChartSet(array(2, 2, 3, 1), "serie 6");
        $objGraph->addStackedBarChartSet(array(1, 2, 3, 4), "serie 7");
        $objGraph->addLinePlot(array(8, 1, 2, 4), "serie 8");
        $objGraph->addLinePlot(array(1, 2, 3, 4), "serie 9");
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("07. A Bar Chart");
        $objGraph->setStrXAxisTitle("My new X-Axis");
        $objGraph->setStrYAxisTitle("My new Y-Axis");
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 9", true);
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 10", true);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("08. A Horizontal Bar Chart no xAxis and yAxis");
        $objGraph->setStrXAxisTitle("My new X-Axis");
        $objGraph->setStrYAxisTitle("My new Y-Axis");
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 9");
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 10");
        $objGraph->setBarHorizontal(true);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        $objGraph->setHideXAxis(true);
        $objGraph->setHideYAxis(true);
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("09. A Horizontal Bar Chart with labels");
        $objGraph->setStrXAxisTitle("My new X-Axis");
        $objGraph->setStrYAxisTitle("My new Y-Axis");
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 9");
        $objGraph->addBarChartSet(array(1, 2, 3, 4), "serie 10");
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4"));
        $objGraph->setBarHorizontal(true);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        /** @var GraphChartjs $objGraph */
        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->addLinePlot(array(1, 2, 7, 0, 0, 0, 2, 0, 0, 0, 5, 0, 0, 0, 0, 6, 0, 0, 0, 20, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1), "serie 1");
        $objGraph->addLinePlot(array(1, 2, 7, 0, 0, 0, 2, 0, 0, 0, 5, 0, 3, 0, 0, 5, 0, 0, 0, 18, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1), "serie 2");
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4", "v5", "v6", "v7", "v8", "v9", "v10", "v11", "v12", "v13", "v14", "v15", "v16", "v17", "v18", "v19", "v20", "v21", "v22", "v23", "v24", "v25", "v26", "v27", "v28", "v29", "v30", "v31", "v32", "v33", "v34", "v35", "v36", "v37", "v38", "v39", "v40"), 10);
        $objGraph->setMaxXAxesTicksLimit(10);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrXAxisTitle("XXX");
        $objGraph->setStrYAxisTitle("YYY");
        $objGraph->setStrGraphTitle("10. My First Line Chart");
        $objGraph->setIntHeight(500);
        $objGraph->setIntWidth(700);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        //create a stacked bar chart
        /** @var GraphChartjs $objGraph */
        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrXAxisTitle("x-axis");
        $objGraph->setStrYAxisTitle("y-axis");
        $objGraph->setStrGraphTitle("11. Test Stacked Bar Chart");
        $objGraph->addStackedBarChartSet(array(0, -5, 7, 8, 4, 12, 1, 1, 1, 3, 4, 5, 6), "serie 1");
        $objGraph->addStackedBarChartSet(array(3, -4, 6, 2, 5, 2, 2, 2, 2, 3, 4, 5, 6), "serie 2");
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4", "v5", "v6", "v7", "v8", "v9", "v10", "v11", "v12", "v13"));
        $objGraph->setIntXAxisAngle(-20);  // not works
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        //create a stacked bar chart
        /** @var GraphChartjs $objGraph */
        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrXAxisTitle("x-axis");
        $objGraph->setStrYAxisTitle("y-axis");
        $objGraph->setStrGraphTitle("12. Test Stacked Horizontal Bar Chart");
        $objGraph->addStackedBarChartSet(array(8, -5, 7, 8, 4, 12, 1, 1, 1, 3, 4, 5, 6), "serie 1");
        $objGraph->addStackedBarChartSet(array(3, 0, 6, 2, 5, 2, 2, 2, 2, 3, 4, 5, 6), "serie 2");
        $objGraph->addStackedBarChartSet(array(3, -4, 6, 2, 5, 2, 2, 2, 2, 3, 4, 5, 6), "serie 3");
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4", "v5", "v6", "v7", "v8", "v9", "v10", "v11", "v12", "v13"), 5);
        $objGraph->setIntXAxisAngle(-20);  // not works
        $objGraph->setStrFont("open sans");
        $objGraph->setBarHorizontal(true);
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        //create pie charts
        /** @var GraphChartjs $objGraph */
        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("13. A Pie Chart");
        $objGraph->createPieChart(array(231.23524234234, 20.2342344, 30, 40), array("val 1", "val 2", "val 3", "val 4"));
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("14. A Pie Chart");
        $objGraph->createPieChart(array(231, 20, 30, 40, 2, 3, 4, 5), array("val 1", "val 2", "val 3", "val 4", "v5", "v6", "v7", "v8"));
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("15. A Pie Chart 2");
        $objGraph->createPieChart(array(1), array("val 1"));
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("16. A Horizontal Bar Chart with labels");
        $objGraph->setStrXAxisTitle("My new X-Axis");
        $objGraph->setStrYAxisTitle("My new Y-Axis");
        $objGraph->addBarChartSet(array(2, 4, 6, 3.3), "serie 9", true);
        $objGraph->addBarChartSet(array(5, 1, 3, 4), "serie 10", true);
        $objGraph->addBarChartSet(array(4, 7, 1, 2), "serie 11", true);
        $objGraph->setArrXAxisTickLabels(array("v1", "v2", "v3", "v4"));
        $objGraph->setBarHorizontal(true);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFont("open sans");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->addLinePlot(array(0, 0, 0, 0, 0, 0, 0.5), "");
        $objGraph->setIntHeight(500);
        $objGraph->setIntWidth(700);
        $objGraph->setArrXAxisTickLabels(array("23", "24", "25", "26", "27", "28", "29"));
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("18. An empty chart");
        $objGraph->addBarChartSet(array(), "legend");
        $objGraph->setIntHeight(500);
        $objGraph->setIntWidth(700);
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("19. A Line Plot 2 Axis");
        $objGraph->addBarChartSet([10, 40, 30, 6], "serie 1");
        $objGraph->setIntHeight(350);
        $objGraph->setIntWidth(300);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFontColor("#FF0000");
        $objGraph->setStrFont("open sans");
        $objGraph->setStrYAxisTitle("serie");
        $objGraph->setStrY2AxisTitle("test");
        $objGraph->addLinePlotY2Axis([0, 200, 300, 450, 100], "test");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->setStrGraphTitle("20. A Bar Chart 2 Axis");
        $objGraph->addLinePlot([10000, 40000, 30000, 60000], "serie 1");
        $objGraph->setIntHeight(350);
        $objGraph->setIntWidth(300);
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrFontColor("#FF0000");
        $objGraph->setStrFont("open sans");
        $objGraph->setStrYAxisTitle("serie");
        $objGraph->setStrY2AxisTitle("test");
        $objGraph->setShowThousandSeparatorAxis();
        $objGraph->addBarChartY2Axis([0, 200000, 300000, 450000, 100000], "test");
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

        // === JQPLOT VS CHARTJS == BEGIN
        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_JQPLOT);
        $objGraph->addStackedBarChartSet([1], "xx1");
        $objGraph->addStackedBarChartSet([1], "xx2");
        $objGraph->addStackedBarChartSet([0], "xx3");
        $objGraph->addStackedBarChartSet([0], "xx4");
        $objGraph->addStackedBarChartSet([0], "xx5");
        $objGraph->setIntWidth(700);
        $objGraph->setBitRenderLegend(false);
        $objGraph->setXAxisRange(0, array_sum([1, 1, 0, 0, 0]));
        $objGraph->setAsHorizontalInLineStackedChart(true);
        echo $objGraph->renderGraph();

        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->addStackedBarChartSet([1], "xx1", true);
        $objGraph->addStackedBarChartSet([1], "xx2");
        $objGraph->addStackedBarChartSet([0], "xx3");
        $objGraph->addStackedBarChartSet([0], "xx4");
        $objGraph->addStackedBarChartSet([0], "xx5");
        $objGraph->setIntWidth(700);
        $objGraph->setBitRenderLegend(false);
        $objGraph->setBitIsResponsive(false);
        $objGraph->setAsHorizontalInLineStackedChart(true);
        echo $objGraph->renderGraph();
        // ==== JQPLOT VS CHARTJS == END

        /** @var GraphChartjs $objGraph */
        $objGraph = GraphFactory::getGraphInstance(GraphFactory::$STR_TYPE_CHARTJS);
        $objGraph->addLinePlot(array(8112, 12000, 22000, 4000), "");
        $objGraph->addLinePlot(array(11500, 2500, 330, 4780), "");
        $objGraph->addLinePlot(array(45880, 7100, 1000, 20000), "");
        $objGraph->setBitRenderLegend(true);
        $objGraph->setStrBackgroundColor("#F0F0F0");
        $objGraph->setStrGraphTitle("19. Test thousand Separator");
        $objGraph->setArrXAxisTickLabels(array("5000", "6000", "7000", "8000"));
        $objGraph->setShowThousandSeparatorAxis();
        echo '<div style="width: 600px; height: 600px">' . $objGraph->renderGraph() . '</div>';

    }
}

$objCharts = new ChartJS();
$objCharts->testCharts();