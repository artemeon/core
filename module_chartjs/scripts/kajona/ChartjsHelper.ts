import $ from 'jquery'
import Lang from 'core/module_system/scripts/kajona/Lang'
import Folderview from 'core/module_system/scripts/kajona/Folderview'

const ChartDataLabels = require('chartjs-plugin-datalabels')
let Chart = require('chart.js')

/**
 * Chartjs service with helper procedures.
 */
class ChartjsHelper {
    /**
   * Chartjs onClick handler
   *
   * @param ev
   * @param seriesIndex
   * @param pointIndex
   * @param dataPoint
   */
    public static onClickHandler (
        ev: any,
        seriesIndex: any,
        pointIndex: number,
        dataPoint: any
    ) {
        if (dataPoint.actionhandler && dataPoint.actionhandler != null) {
            /* eslint no-eval: 0 */
            // FIXME find another alternative for eval because it can be harmful
            let objFunction = eval('(' + dataPoint.actionhandler + ')')
            if ($.isFunction(objFunction)) {
                objFunction.call(this, ev, seriesIndex, pointIndex, null, dataPoint)
            }
        } else {
            this.dataPointOnClickURLHandler(dataPoint)
        }
    }

    /**
   * Opens URL in dialog pop-up window
   *
   * @param dataPoint
   */
    public static dataPointOnClickURLHandler (dataPoint: any) {
        if (dataPoint.actionhandlervalue && dataPoint.actionhandlervalue.length > 0)  {
            Folderview.dialog.setContentIFrame(dataPoint.actionhandlervalue)
            Folderview.dialog.setTitle('')
            Folderview.dialog.init()
        }
    }

    /**
   * Converts data value in a percentage view
   *
   * @param value
   * @param ctx
   * @returns {string}
   */
    public static dataShowPercentage (value: number, ctx: any) {
        let sum: number = 0
        let dataArr = ctx.chart.data.datasets[0].data
        dataArr.map(function (data: number) {
            sum += data
        })
        let percentage = ((value * 100) / sum).toFixed(0)

        return percentage !== '0' ? percentage + '%' : ''
    }

    /**
   * Add thousand separator in big numeric values
   *
   * @param value
   * @param ctx
   * @returns {string}
   */
    public static addThousandSeparator (value: number, ctx: any) {
        let strValue = value.toString()
        let strThousandSeparator = '.'
        Lang.fetchSingleProperty('system', 'numberStyleThousands', function (
            strText: string
        ) {
            strThousandSeparator = strText
        })
        strValue = strValue.replace(/\B(?=(\d{3})+(?!\d))/g, strThousandSeparator)

        return strValue
    }

    /**
   * Changes "0" values to empty string
   *
   * @param value
   * @returns {string}
   */
    public static dataNotShowNullValues (value: number) {

        return value !== 0 ? value : ''
    }

    /**
     *
     * @param deepMerge : if false, normal merge will happen
     * @param extendedObj : object that will be extended
     * @param newObj : object that contains override properties
     */
    public static extend (deepMerge: boolean, extendedObj: any, newObj: any): any {
        let extended = {}
        let deep = false
        let i = 0

        if (typeof arguments[0] === 'boolean') {
            deep = arguments[0]
            i++
        }

        let merge = function (obj) {
            for (let prop in obj) {
                if (obj.hasOwnProperty(prop)) {
                    if (
                        deep &&
                        Object.prototype.toString.call(obj[prop]) === '[object Object]'
                    ) {
                        extended[prop] = ChartjsHelper.extend(
                            true,
                            extended[prop],
                            obj[prop]
                        )
                    } else {
                        extended[prop] = obj[prop]
                    }
                }
            }
        }

        for (; i < arguments.length; i++) {
            merge(arguments[i])
        }

        return extended
    }

    /**
   * Creates the chart using "chartjs" library and set parameters
   *
   * @param ctx
   * @param chartData
   * @param chartOptions
   */
    public static createChart (ctx: any, chartData: any, chartOptions: any) {
        Chart.defaults.global = ChartjsHelper.extend(
            true,
            Chart.defaults.global,
            chartData['defaults']['global']
        )
        ctx.style.backgroundColor = chartOptions['backgroundColor']

        if (chartOptions['createImageLink']) {
            chartData['options']['animation'] = {
                onComplete: createExportLink
            }
        }

        if (chartOptions['notShowNullValues']) {
            chartData['options']['plugins']['datalabels'] = {
                formatter: function (value: number) {

                    return ChartjsHelper.dataNotShowNullValues(value)
                }
            }
        }

        if (chartOptions['percentageValues']) {
            chartData['options']['plugins']['datalabels'] = {
                formatter: function (value: number, ctx: any) {

                    return ChartjsHelper.dataShowPercentage(value, ctx)
                }
            }
        }

        if (chartOptions['addThousandSeparator']) {
            chartData['options']['scales']['xAxes'][0]['ticks']['userCallback'] = function (value: number, ctx: any) {

                return ChartjsHelper.addThousandSeparator(value, ctx)
            }
            chartData['options']['scales']['yAxes'][0]['ticks']['userCallback'] = function (value: number, ctx: any) {

                return ChartjsHelper.addThousandSeparator(value, ctx)
            }
        }

        chartData['options']['onClick'] = function (evt: any) {
            let item = this.getElementAtEvent(evt)[0]
            if (typeof item !== 'undefined') {
                let datasetIndex = item._datasetIndex
                let index = item._index
                ChartjsHelper.onClickHandler(
                    evt,
                    index,
                    datasetIndex,
                    chartData['data']['datasets'][datasetIndex]['dataPoints'][index]
                )
            }
        }

        let myChart = new Chart.Chart(ctx, {
            type: chartData['type'],
            data: chartData['data'],
            options: chartData['options']
        })

        function createExportLink () {
            let url = myChart.toBase64Image()
            $('#' + chartOptions['strLinkExportId']).attr('href', url)
        }
    }
}

(<any>window).ChartjsHelper = ChartjsHelper
export default ChartjsHelper
