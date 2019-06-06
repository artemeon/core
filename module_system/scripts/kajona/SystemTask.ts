import $ from 'jquery'
import Ajax from './Ajax'
import StatusDisplay from './StatusDisplay'
import Dialog from 'core/module_v4skin/scripts/kajona/Dialog'

declare var KAJONA_SYSTEMTASK_TITLE: string
declare var KAJONA_SYSTEMTASK_TITLE_DONE: string
declare var KAJONA_SYSTEMTASK_CLOSE: string
declare var kajonaSystemtaskDialogContent: string

/**
 * Functions to execute system tasks
 */
class SystemTask {
    public static executeTask (
        strTaskname: string,
        strAdditionalParam: string,
        bitNoContentReset: boolean
    ) {
        if (bitNoContentReset === null || bitNoContentReset === undefined) {
            let paramsEl = $('#taskParamForm')
            if (paramsEl.length > 0) {
                paramsEl.css('display', 'none')
            }

            // eslint-disable-next-line camelcase
            jsDialog_0 = new Dialog('jsDialog_0', 0)
            jsDialog_0.setTitle(KAJONA_SYSTEMTASK_TITLE)
            jsDialog_0.setContentRaw(kajonaSystemtaskDialogContent)
            $('#' + jsDialog_0.getContainerId())
                .find('div.modal-dialog')
                .removeClass('modal-lg')
            $('#systemtaskCancelButton').click(SystemTask.cancelExecution)
            jsDialog_0.init()
        }

        Ajax.genericAjaxCall(
            'system',
            'executeSystemTask',
            '&task=' + strTaskname + strAdditionalParam,
            function (data: any, status: string, jqXHR: XMLHttpRequest) {
                if (status === 'success') {
                    var strResponseText = data

                    // parse the response and check if it's valid
                    if (strResponseText.indexOf('<error>') !== -1) {
                        StatusDisplay.displayXMLMessage(strResponseText)
                    } else if (strResponseText.indexOf('<statusinfo>') === -1) {
                        StatusDisplay.messageError(
                            '<b>Request failed!</b><br />' + strResponseText
                        )
                    } else {
                        var intStart =
                            strResponseText.indexOf('<statusinfo>') + 12
                        var strStatusInfo = strResponseText.substr(
                            intStart,
                            strResponseText.indexOf('</statusinfo>') - intStart
                        )

                        // parse text to decide if a reload is necessary
                        var strReload = ''
                        if (strResponseText.indexOf('<reloadurl>') !== -1) {
                            intStart =
                                strResponseText.indexOf('<reloadurl>') + 11
                            strReload = strResponseText.substr(
                                intStart,
                                strResponseText.indexOf('</reloadurl>') -
                                intStart
                            )
                        }

                        // show status info
                        $('#systemtaskStatusDiv').html(strStatusInfo)

                        if (strReload === '') {
                            jsDialog_0.setTitle(KAJONA_SYSTEMTASK_TITLE_DONE)
                            $('#systemtaskLoadingDiv').css('display', 'none')
                            $('#systemtaskCancelButton').attr(
                                'value',
                                KAJONA_SYSTEMTASK_CLOSE
                            )
                        } else {
                            SystemTask.executeTask(strTaskname, strReload, true)
                        }
                    }
                } else {
                    jsDialog_0.hide()
                    StatusDisplay.messageError(
                        '<b>Request failed!</b><br />' + data
                    )
                }
            }
        )
    }

    public static cancelExecution () {
        jsDialog_0.hide()
    }

    public static setName (strName: string) {
        $('#systemtaskNameDiv').html(strName)
    }
}
;(<any>window).SystemTask = SystemTask
export default SystemTask
