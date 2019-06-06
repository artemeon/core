import $ from 'jquery'
import WorkingIndicator from './WorkingIndicator'
import Util from './Util'
import Tooltip from './Tooltip'
import StatusDisplay from './StatusDisplay'

interface SystemStatusMessages {
    strInActiveIcon: string
    strActiveIcon: string
}

/**
 * AJAX functions for connecting to the server
 */
class Ajax {
    /**
     * @deprecated
     */
    public static setSystemStatusMessages: SystemStatusMessages = {
        strInActiveIcon: '',
        strActiveIcon: ''
    }

    /**
     * Shorthand method to load a html fragement into a node identified by the selector.
     * Data is fetched by GET, loading indicators are triggered automatically.
     * Scripts in the response are executed, tooltips are enabled, too.
     * During loading, a loading container is shown and the general loading animation is enabled
     *
     * Possible usage:
     * ajax.loadUrlToElement('#report_container', '/xml.php?admin=1&module=stats&action=getReport', '&plugin=general');
     *
     * @param {String} strElementSelector (may be selector or a jquery object)
     * @param {String} strUrl
     * @param {String} strData
     * @param {Boolean} bitBlockLoadingContainer
     * @param {String} strMethod default is GET
     * @param {Function} objCallback - is called if the request was successful
     */
    public static loadUrlToElement (
        strElementSelector: string | JQuery,
        strUrl: string,
        strData?: any,
        bitBlockLoadingContainer?: boolean,
        strMethod?: string,
        objCallback?: Function
    ) {
        WorkingIndicator.start()

        var objElement = Util.getElement(strElementSelector)

        if (!bitBlockLoadingContainer) {
            objElement.html('<div class="loadingContainer"></div>')
        } else {
            objElement.css('opacity', '0.4')
        }

        if (!strMethod) {
            strMethod = 'GET'
        }

        var target = strElementSelector
        $.ajax({
            type: strMethod,
            url:
                strUrl.indexOf(KAJONA_WEBPATH) > -1
                    ? strUrl
                    : KAJONA_WEBPATH + strUrl,
            data: strData
        })
            .done(function (data, status, xhr) {
                // detect file download
                var disposition = xhr.getResponseHeader('Content-Disposition')
                if (disposition && disposition.indexOf('filename') !== -1) {
                    // @TODO workaround to fix old file downloads. In case the ajax request returns a
                    // Content-Disposition header we redirect the client to the url to trigger the file download
                    // through the browser. Note you need to update the url of your download to point the user
                    // directly to the file instead of using a hash route. With this workaround the user downloads
                    // the file twice, once through the ajax call and then through the redirect.
                    location.href = KAJONA_WEBPATH + strUrl
                } else {
                    objElement.html(data)
                    objElement.css('opacity', '1')

                    Tooltip.initTooltip()

                    if (typeof objCallback === 'function') {
                        objCallback()
                    }
                }
            })
            .always(function (response) {
                WorkingIndicator.stop()
                objElement.css('opacity', '1')
            })
            .fail(function (data) {
                if (data.status === 500) {
                    if (KAJONA_DEBUG === 1) {
                        objElement.html(data.responseText)
                    } else {
                        objElement.html(
                            '<div class="alert alert-danger" role="alert">An error occurred. Please contact the system admin.</div>'
                        )
                    }
                }

                if (data.status === 401) {
                    objElement.html(data.responseText)
                    objElement.css('opacity', '1')
                    return
                }

                // maybe it was xml, so strip
                StatusDisplay.messageError('<b>Request failed!</b><br />')
            })
    }

    public static getDataObjectFromString (
        strData: any,
        bitFirstIsSystemid: boolean
    ) {
        if (typeof strData === 'string') {
            // strip other params, backwards compatibility
            let arrElements = strData.split('&')
            let data: any = {}

            if (bitFirstIsSystemid) {
                data['systemid'] = arrElements[0]
            }

            // first one is the systemid
            if (arrElements.length > 1) {
                $.each(arrElements, function (index, strValue) {
                    if (!bitFirstIsSystemid || index > 0) {
                        var arrSingleParams = strValue.split('=')
                        data[arrSingleParams[0]] = arrSingleParams[1]
                    }
                })
            }
            return data
        } else {
            return strData
        }
    }

    public static regularCallback (
        data: any,
        status: string,
        jqXHR: XMLHttpRequest
    ) {
        if (status === 'success') {
            StatusDisplay.displayXMLMessage(data)
        } else {
            StatusDisplay.messageError('<b>Request failed!</b>')
        }
    }

    /**
     * General helper to fire an ajax request against the backend
     *
     * @param module
     * @param action
     * @param systemid
     * @param objCallback
     * @param objDoneCallback
     * @param objErrorCallback
     * @param strMethod default is POST
     * @param dataType
     */
    public static genericAjaxCall (
        module: string,
        action: string,
        systemid: any,
        objCallback?: Function,
        objDoneCallback?: Function,
        objErrorCallback?: Function,
        strMethod?: string,
        dataType?: string
    ) {
        var postTarget =
            KAJONA_WEBPATH +
            '/xml.php?admin=1&module=' +
            module +
            '&action=' +
            action
        var data
        if (systemid) {
            data = this.getDataObjectFromString(systemid, true)
        }

        WorkingIndicator.start()
        $.ajax({
            type: strMethod || 'POST',
            url: postTarget,
            data: data,
            error: function (
                xhr: JQuery.jqXHR,
                textStatus: string,
                errorThrown: string
            ) {
                if (objCallback) {
                    objCallback(xhr, textStatus, errorThrown)
                }
            },
            success: function (
                data: any,
                textStatus: string,
                xhr: JQuery.jqXHR
            ) {
                if (objCallback) {
                    objCallback(data, textStatus, xhr)
                }
            },
            dataType: dataType || 'text'
        })
            .always(function () {
                WorkingIndicator.stop()
            })
            .fail(function () {
                if (objErrorCallback) {
                    objErrorCallback()
                }
            })
            .done(function () {
                if (objDoneCallback) {
                    objDoneCallback()
                }
            })
    }

    public static setAbsolutePosition (
        systemIdToMove: string,
        intNewPos: number,
        strIdOfList: string,
        objCallback: Function,
        strTargetModule?: string
    ) {
        if (strTargetModule == null || strTargetModule === '') { strTargetModule = 'system' }

        if (typeof objCallback === 'undefined' || objCallback == null) { objCallback = this.regularCallback }

        this.genericAjaxCall(
            strTargetModule,
            'setAbsolutePosition',
            systemIdToMove + '&listPos=' + intNewPos,
            objCallback
        )
    }

    public static setSystemStatus (
        strSystemIdToSet: string,
        bitReload: boolean
    ) {
        var me = this
        var objCallback = function (
            data: any,
            status: string,
            jqXHR: JQuery.jqXHR
        ) {
            if (status === 'success') {
                StatusDisplay.displayXMLMessage(data)

                if (bitReload !== null && bitReload === true) {
                    location.reload()
                }

                if (
                    data.indexOf('<error>') === -1 &&
                    data.indexOf('<html>') === -1
                ) {
                    var newStatus = $($.parseXML(data))
                        .find('newstatus')
                        .text()
                    var link = $('#statusLink_' + strSystemIdToSet)

                    var adminListRow = link
                        .parents('.admintable > tbody')
                        .first()
                    if (!adminListRow.length) {
                        adminListRow = link.parents('.grid > ul > li').first()
                    }

                    if (parseInt(newStatus) === 0) {
                        link.html(me.setSystemStatusMessages.strInActiveIcon)
                        adminListRow.addClass('disabled')
                    } else {
                        link.html(me.setSystemStatusMessages.strActiveIcon)
                        adminListRow.removeClass('disabled')
                    }

                    Tooltip.addTooltip(
                        $('#statusLink_' + strSystemIdToSet).find(
                            "[rel='tooltip']"
                        )
                    )
                }
            } else {
                // in the error case the arguments are (jqXHR, status) so we need to get the responseText from the
                // xhr object
                StatusDisplay.messageError(data.responseText)
            }
        }

        Tooltip.removeTooltip(
            $('#statusLink_' + strSystemIdToSet).find("[rel='tooltip']")
        )
        this.genericAjaxCall(
            'system',
            'setStatus',
            strSystemIdToSet,
            objCallback
        )
    }
}
;(<any>window).Ajax = Ajax
export default Ajax
