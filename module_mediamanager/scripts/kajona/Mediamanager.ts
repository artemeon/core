import $ from 'jquery'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import StatusDisplay from 'core/module_system/scripts/kajona/StatusDisplay'

class Mediamanager {
    public static createFolder (strInputId: string, strRepoId: string) {
        var strNewFoldername = $('#' + strInputId).val()
        if (strNewFoldername !== '') {
            this.createFolderBackend(strRepoId, strNewFoldername + '')
        }
    }

    private static createFolderBackend (strFmRepoId: string, strFolder: string) {
        Ajax.genericAjaxCall(
            'mediamanager',
            'createFolder',
            strFmRepoId + '&folder=' + strFolder,
            function (data: any, status: string, jqXHR: XMLHttpRequest) {
                if (status === 'success') {
                    // check if answer contains an error
                    if (data.indexOf('<error>') !== -1) {
                        StatusDisplay.displayXMLMessage(data)
                    } else {
                        Ajax.genericAjaxCall(
                            'mediamanager',
                            'partialSyncRepo',
                            strFmRepoId,
                            function (
                                data: any,
                                status: string,
                                jqXHR: XMLHttpRequest
                            ) {
                                if (status === 'success') location.reload()
                                else {
                                    StatusDisplay.messageError(
                                        '<b>Request failed!</b><br />' + data
                                    )
                                }
                            }
                        )
                    }
                } else {
                    StatusDisplay.messageError(
                        '<b>Request failed!</b><br />' + data
                    )
                }
            }
        )
    }
}
;(<any>window).Mediamanager = Mediamanager
export default Mediamanager
