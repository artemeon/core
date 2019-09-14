import $ from 'jquery'
import Lang from 'core/module_system/scripts/kajona/Lang'
import Forms from 'core/module_system/scripts/kajona/Forms'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import 'blueimp-tmpl'
import 'blueimp-file-upload'
import 'blueimp-file-upload/js/jquery.fileupload-ui.js'
import DialogHelper from '../../../module_v4skin/scripts/kajona/DialogHelper'
import Messaging from '../../../module_system/scripts/kajona/Messaging'
import Router from '../../../module_system/scripts/kajona/Router'

declare global {
    interface Window {
        dropZoneTimeout: number
    }
}

interface UploadSettings {
    baseElement: JQuery
    uploadUrl: string
    autoUpload: boolean
    paramName: string
    formData: any
    readOnly: boolean
    multiUpload: boolean
    maxFileSize: number
    acceptFileTypes: string
    downloadTemplate: string
    uploadTemplate: string
    prependFiles: boolean
}

class UploadManager {
    private settings: UploadSettings
    private uploader: JQueryFileUpload

    public constructor (options: UploadSettings) {
        this.settings = $.extend(
            {
                baseElement: null,
                uploadUrl:
                    KAJONA_WEBPATH +
                    '/xml.php?admin=1&module=mediamanager&action=fileUpload',
                autoUpload: false,
                paramName: 'files',
                formData: [],
                readOnly: false,
                multiUpload: true,
                maxFileSize: 0,
                acceptFileTypes: '',
                downloadTemplate: null,
                uploadTemplate: null,
                prependFiles: false
            },
            options
        )

        var optionsMerged: any = {
            url: this.settings.uploadUrl,
            dataType: 'json',
            dropZone: this.settings.baseElement.find('.drop-zone'),
            pasteZone: $('body'),
            autoUpload: this.settings.autoUpload,
            paramName: this.settings.paramName,
            filesContainer: this.settings.baseElement.find('.files'),
            formData: this.settings.formData,
            maxFileSize: this.settings.maxFileSize,
            acceptFileTypes: this.settings.acceptFileTypes,
            uploadTemplateId: null,
            downloadTemplateId: null,
            downloadTemplate: this.settings.downloadTemplate,
            uploadTemplate: this.settings.uploadTemplate,
            prependFiles: this.settings.prependFiles
        }

        if (this.settings.autoUpload && !this.settings.multiUpload) {
            var self = this
            optionsMerged.add = function (e: any, data: any) {
                if (
                    !self.settings.multiUpload &&
                    (self.settings.baseElement.find('.drop-zone >').length >=
                        1 ||
                        data.originalFiles.length > 1)
                ) {
                    Forms.addHint(
                        self.settings.baseElement.find('.files').attr('id'),
                        "<span data-lang-property='mediamanager:upload_multiple_not_allowed'></span>"
                    )
                    Lang.initializeProperties(
                        self.settings.baseElement
                            .find('.files')
                            .closest('.form-group')
                    )
                    // alert('single upload only');
                    e.preventDefault()
                    return false
                }

                if (
                    data.autoUpload ||
                    (data.autoUpload !== false &&
                        $(this).fileupload('option', 'autoUpload'))
                ) {
                    data.process().done(function () {
                        data.submit()
                    })
                }
            }
        }

        this.uploader = this.settings.baseElement.fileupload(optionsMerged)

        if (this.settings.readOnly) {
            this.settings.baseElement.fileupload('disable')
        }
    }

    /**
     * Get the upload instance
     */
    public getUploader () {
        return this.uploader
    }

    /**
     * Query the backend to version all files
     */
    public fileVersioning () {
        var me = this
        Ajax.genericAjaxCall(
            'mediamanager',
            'documentVersioning',
            '&systemid=' +
                this.settings.formData[0].value +
                '&folder=' +
                this.settings.formData[2].value,
            function (e: any) {
                if (e.status && e.status === 'ok') {
                    // in case of success, flush the list
                    me.settings.baseElement.find('.files').empty()
                    me.renderArchiveList()
                }
            },
            null,
            null,
            'post',
            'json'
        )
    }

    /**
     * Query the backend to send all files to the archive
     */
    public fileArchiving (
        targetSystemId: string,
        alertTitle: string,
        alertBody: string,
        alertButton: string
    ) {
        let settings = this.settings
        let me = this
        DialogHelper.showConfirmationDialog(
            alertTitle,
            alertBody,
            alertButton,
            function () {
                Ajax.genericAjaxCall(
                    'mediamanager',
                    'documentArchiving',
                    '&systemid=' +
                        settings.formData[0].value +
                        '&folder=' +
                        settings.formData[2].value +
                        '&target=' +
                        targetSystemId,
                    function (e: any) {
                        if (e.status && e.status === 'ok') {
                            // in case of success, flush the list
                            me.settings.baseElement.find('.files').empty()
                            me.renderArchiveList()

                            // update messages
                            Messaging.pollMessages()
                            Router.reload()
                        }
                    },
                    null,
                    null,
                    'post',
                    'json'
                )
            }
        )
    }

    public renderArchiveList () {
        Ajax.loadUrlToElement(
            this.settings.baseElement.find('.archive-list'),
            '/xml.php?admin=1&module=mediamanager&action=getArchiveList&systemid=' +
                this.settings.formData[0].value +
                '&folder=' +
                this.settings.formData[2].value
        )
    }
}

/**
 * Helper for fileupload management.
 * Wraps the jquery Fileupload plugin
 *
 * the options object expects:
 */
class Fileupload {
    private static dropoverInitialized = false

    /**
     * Callback used when deleting a file
     * @param strFileId
     */
    public static deleteFile (strFileId: string) {
        Ajax.genericAjaxCall('system', 'delete', strFileId, null, function () {
            $(
                'tbody.template-upload[data-uploadid="' + strFileId + '"]'
            ).remove()
        })
    }

    /**
     * Removes all files from a upload-field
     * @param containerId
     */
    public static clearFileupload(containerId: string) {
        $(containerId).find('[data-uploadid]').each(function() {
            let $tr = $(this);
            if ($tr.data('uploadid') !== '') {
                Fileupload.deleteFile($tr.data('uploadid'));
            }
        })
    }

    /**
     * Inits the uploader and returns an instance.
     * Makes sure to init the dragover functions, too
     * @param options
     * @returns {UploadManager}
     */
    public static initUploader (options: UploadSettings) {
        var uploader = new UploadManager(options)
        this.initDragover()
        return uploader
    }

    private static initDragover = function () {
        // only once, plz
        if (Fileupload.dropoverInitialized) {
            return
        }

        $(document).bind('dragover', function (e) {
            var dropZone = $(
                '.fileupload-wrapper:not(.blueimp-fileupload-disabled) .drop-zone'
            )
            var timeout = window.dropZoneTimeout
            if (!timeout) {
                dropZone.addClass('active-dropzone')
            } else {
                clearTimeout(timeout)
            }

            var curDropZone = $(e.target).closest(
                '.fileupload-wrapper:not(.blueimp-fileupload-disabled) .drop-zone'
            )
            if (curDropZone) {
                $(curDropZone).addClass('hover')
            } else {
                dropZone.removeClass('hover')
            }
            ;(<any>window.dropZoneTimeout) = setTimeout(function () {
                window.dropZoneTimeout = null
                dropZone.removeClass('active-dropzone hover')
            }, 100)
        })

        Fileupload.dropoverInitialized = true
    }
}
;(<any>window).Fileupload = Fileupload
export default Fileupload
