import $ from 'jquery'
import Ajax from './Ajax'
import moment from 'moment'
import calendarHeatMap from 'calendar-heatmap'
import 'calendar-heatmap/dist/calendar-heatmap.css'
const d3 = require('d3')
declare global {
    // the d3 type definition does not contains the time API
    interface d3 {
        time: any
    }
}
interface Lang {
    months?: Array<string>
    days?: Array<string>
    tooltipColumn?: string
    tooltipUnit?: string
    tooltipHtml?: string
    tooltipUnitPlural?: string
}

interface DatePoint {
    date: Date
    count: number
}

class Changelog {
    private static systemId: string = null
    private static now: Date = null
    private static yearAgo: Date = null
    private static selectedColumn: string = null
    private static lang: Lang = {}

    /**
     * Method to compare and highlite changes of two version properties table
     */
    public static compareTable () {
        var strType = Changelog.selectedColumn
        var propsLeft = Changelog.getTableProperties(strType)
        var propsRight = Changelog.getTableProperties(
            Changelog.getInverseColumn(strType)
        )
        for (var key in propsLeft) {
            if (propsLeft[key] !== '' || propsRight[key] !== '') {
                if (propsLeft[key] !== propsRight[key]) {
                    $('#property_' + key + '_' + strType)
                        .parent()
                        .parent()
                        .css('background-color', '#CEC')
                } else {
                    $('#property_' + key + '_' + strType)
                        .parent()
                        .parent()
                        .css('background-color', '')
                }
            } else {
                $('#property_' + key + '_' + strType)
                    .parent()
                    .parent()
                    .css('background-color', '')
            }
        }
    }

    /**
     * Selects the column which should change if a user clicks on the chart
     *
     * @param {string} strType
     */
    public static selectColumn (strType: string) {
        $('#date_' + strType).css('background-color', '#ccc')
        $('#date_' + Changelog.getInverseColumn(strType)).css(
            'background-color',
            ''
        )
        Changelog.selectedColumn = strType
    }

    /**
     * Returns the opposite column of the provided type
     *
     * @param strType
     * @returns {string}
     */
    public static getInverseColumn (strType: string) {
        return strType === 'left' ? 'right' : 'left'
    }

    /**
     * Returns an object containing all version properties from either the left or right table
     *
     * @param {string} type
     * @returns {object}
     */
    public static getTableProperties (type: string): any {
        var props: any = {}
        $('.changelog_property_' + type).each(function () {
            props[$(this).data('name')] = '' + $(this).html()
        })
        return props
    }

    /**
     * Loads the version properties for a specific date and inserts the values either in the left or right table
     *
     * @param {string} strSystemId
     * @param {string} strDate
     * @param {string} strType
     * @param {function} objCallback
     */
    public static loadDate (
        strSystemId: string,
        strDate: string,
        strType: string,
        objCallback?: Function
    ) {
        $('#date_' + strType).html('')
        $('.changelog_property_' + strType).html('')
        Ajax.genericAjaxCall(
            'system',
            'changelogPropertiesForDate',
            '&systemid=' + strSystemId + '&date=' + strDate,
            function (body: any, status: string, jqXHR: any) {
                let data = JSON.parse(body)
                let props = data.properties
                $('#date_' + strType).html(
                    "<a href='#' onclick='Changelog.selectColumn(\"" +
                        strType +
                        "\");return false;' style='display:block;'>" +
                        data.date +
                        '</a>'
                )
                for (var prop in props) {
                    $('#property_' + prop + '_' + strType).html(props[prop])
                }

                $('#date_' + strType + ' a').qtip({
                    content: Changelog.lang.tooltipColumn,
                    position: {
                        at: 'top center',
                        my: 'bottom center'
                    },
                    style: {
                        classes: 'qtip-bootstrap'
                    }
                })

                if (typeof objCallback === 'function') {
                    objCallback()
                }
            }
        )
    }

    /**
     * Loads the chart for the next year
     */
    public static loadNextYear () {
        $('#changelogTimeline').fadeOut()

        Changelog.now = moment(Changelog.now)
            .add(1, 'years')
            .toDate()
        Changelog.yearAgo = moment(Changelog.yearAgo)
            .add(1, 'years')
            .toDate()
        Changelog.loadChartData()
    }

    /**
     * Loads the chart for the previous year
     */
    public static loadPrevYear () {
        $('#changelogTimeline').fadeOut()

        Changelog.now = moment(Changelog.now)
            .subtract(1, 'years')
            .toDate()
        Changelog.yearAgo = moment(Changelog.yearAgo)
            .subtract(1, 'years')
            .toDate()
        Changelog.loadChartData()
    }

    /**
     * Loads the chart
     */
    public static loadChartData () {
        var now = moment(Changelog.now).format('YYYYMMDD235959')
        var yearAgo = moment(Changelog.yearAgo).format('YYYYMMDD235959')

        Ajax.genericAjaxCall(
            'system',
            'changelogChartData',
            '&systemid=' +
                Changelog.systemId +
                '&now=' +
                now +
                '&yearAgo=' +
                yearAgo,
            function (body: any, status: string, jqXHR: any) {
                let data = JSON.parse(body)

                var chartData = d3.time
                    .days(Changelog.yearAgo, Changelog.now)
                    .map(function (dateElement) {
                        var count = 0
                        if (
                            data.hasOwnProperty(
                                moment(dateElement).format('YYYYMMDD')
                            )
                        ) {
                            count = data[moment(dateElement).format('YYYYMMDD')]
                        }
                        return {
                            date: dateElement,
                            count: count
                        }
                    })

                var heatmap = calendarHeatMap
                    .data(chartData)
                    .selector('#changelogTimeline')
                    .months(Changelog.lang.months)
                    .days(Changelog.lang.days)
                    .width(700)
                    .padding(16)
                    .tooltipEnabled(true)
                    .tooltipUnit(Changelog.lang.tooltipUnit)
                    .tooltipUnitPlural(Changelog.lang.tooltipUnitPlural)
                    .tooltipDateFormat('DD.MM.YYYY')
                    .tooltipHtml(Changelog.lang.tooltipHtml)
                    .legendEnabled(false)
                    .toggleDays(false)
                    .colorRange(['#eeeeee', '#6cb121'])
                    .onClick(function (data: DatePoint) {
                        var date = moment(data.date).format('YYYYMMDD235959')
                        Changelog.loadDate(
                            Changelog.systemId,
                            date,
                            Changelog.selectedColumn,
                            Changelog.compareTable
                        )
                    })
                heatmap(Changelog.now, Changelog.yearAgo) // render the chart

                $('#changelogTimeline').fadeIn()
            }
        )
    }
}
;(<any>window).Changelog = Changelog
export default Changelog
