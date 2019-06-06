import $ from 'jquery'
import Router from 'core/module_system/scripts/kajona/Router'
import Util from 'core/module_system/scripts/kajona/Util'
import Folderview from 'core/module_system/scripts/kajona/Folderview'

class Dialog {
    private intDialogType: number
    private bitDragging: boolean
    private bitResizing: boolean

    private containerId: string
    private iframeId: string = null
    private iframeURL: string = null
    private bitLarge: boolean = false

    private unbindOnClick: boolean = false

    constructor (
        strDialogId: string,
        intDialogType: number,
        bitDragging?: boolean,
        bitResizing?: boolean
    ) {
        this.intDialogType = intDialogType
        this.bitDragging = bitDragging
        this.bitResizing = bitResizing

        this.containerId = strDialogId
        this.iframeId = null
        this.iframeURL = null
        this.bitLarge = false

        /** Set this variable to false if you don't want to remove actions on click */
        this.unbindOnClick = true

        // register event to reset the dialog with default settings (only if the dialog has template dialog)
        if ($('#template_' + this.containerId).length > 0) {
            $('#' + this.containerId).on('hidden', function (e) {
                // @ts-ignore
                this.resetDialog()
            })
        }
    }

    public setTitle (strTitle: string) {
        if (strTitle === '') {
            $('#' + this.containerId + '_title').html('&nbsp;')
        } else {
            $('#' + this.containerId + '_title').text(strTitle)
        }
    }

    public setBitLarge (bitLarge: boolean) {
        this.bitLarge = bitLarge
    }

    public getContainerId (): string {
        return this.containerId
    }

    public setContent (
        strContent: string,
        strConfirmButton: string,
        strLinkHref: string | Function,
        blockHide?: boolean
    ) {
        if (this.intDialogType === 1) {
            this.unbindEvents()

            $('#' + this.containerId + '_content').html(strContent)
            var self = this

            var $confirmButton = $('#' + this.containerId + '_confirmButton')
            $confirmButton.html(strConfirmButton)

            var bitUnbind = this.unbindOnClick

            if (jQuery.isFunction(strLinkHref)) {
                $confirmButton.click(function () {
                    var objReturn = strLinkHref()

                    if (!blockHide) {
                        self.hide()
                    }

                    if (bitUnbind) {
                        $confirmButton.unbind()
                        $confirmButton.click(function () {
                            return false
                        })
                    }

                    return objReturn !== undefined ? objReturn : false
                })
            } else {
                $confirmButton.click(function () {
                    window.location.href = strLinkHref

                    if (!blockHide) {
                        self.hide()
                    }

                    if (bitUnbind) {
                        $confirmButton.unbind()
                        $confirmButton.click(function () {
                            return false
                        })
                    }

                    return false
                })
            }
        }
    }

    public isVisible () {
        return $('#' + this.containerId + '.modal-dialog').is(':visible')
    }

    public setContentRaw (strContent: string) {
        $('#' + this.containerId + '_content').html(strContent)
    }

    public setContentIFrame (strUrl: string) {
        this.iframeId = this.containerId + '_iframe'
        let result = Router.generateUrl(strUrl)
        strUrl = KAJONA_WEBPATH + result.url + '&combinedLoad=1'
        this.iframeURL = strUrl
    }

    public init (intWidth?: number, intHeight?: number) {
        var $modal = $('#' + this.containerId).modal({
            backdrop: true,
            keyboard: false,
            show: false
        })

        if (!intHeight) {
            if (
                $('#' + this.containerId + ' .modal-dialog').hasClass(
                    'modal-lg'
                )
            ) {
                intHeight = $(window).height() * 0.6
            } else intHeight = undefined
        }

        if (Util.isStackedDialog()) {
            // trigger a new dialog on the base window

            if (this.iframeURL != null) {
                // TODO: POC:
                parent.KAJONA.util.dialogHelper.showIframeDialogStacked(
                    this.iframeURL,
                    $('#' + this.containerId + '_title').text()
                )
                parent.KAJONA.util.folderviewHandler = Folderview

                // open the iframe in a regular popup
                // workaround for stacked dialogs. if a modal is already opened, the second iframe is loaded in a popup window.
                // stacked modals still face issues with dimensions and scrolling. (see http://trace.kajona.de/view.php?id=724)
                if (!intWidth) {
                    intWidth = 500
                }

                if (!intHeight) {
                    intHeight = 500
                }

                // window.open(this.iframeURL, $('#' + this.containerId + '_title').text(), 'scrollbars=yes,resizable=yes,width=' + (intWidth) + ',height=' + (intHeight));
                return
            }
        }

        if (this.iframeURL != null) {
            $('#' + this.containerId + '_loading').css('display', 'block')
            $('#' + this.containerId + '_content').html(
                '<iframe src="' +
                    this.iframeURL +
                    '" width="100%" height="' +
                    intHeight +
                    '" name="' +
                    this.iframeId +
                    '" id="' +
                    this.iframeId +
                    '" class="seamless" seamless></iframe>'
            )
            this.iframeURL = null

            var id = this.iframeId
            var containerId = this.containerId
            $('#' + this.iframeId).on('load', function () {
                $('#' + containerId + '_loading').css('display', 'none')
                $('#' + id)
                    .contents()
                    .find('body')
                    .addClass('dialogBody')
            })
        }

        if (!Util.isStackedDialog() && this.bitLarge) {
            $('#' + this.containerId + ' .modal-dialog').addClass('modal-lg-lg')

            $('#' + this.containerId).on('hidden.bs.modal', function (e) {
                $(this)
                    .find('.modal-dialog')
                    .removeClass('modal-lg-lg')
            })

            this.bitLarge = false
        }

        // finally show the modal
        $('#' + this.containerId).modal('show')

        if (this.bitDragging) {
            this.enableDragging()
        }
        if (this.bitResizing) {
            this.enableResizing()
        }
    }

    public hide () {
        $('#' + this.containerId).modal('hide')
        this.unbindEvents()
    }

    public enableDragging () {}

    public enableResizing () {
        // $('#' + this.containerId).resizable();
        $('#' + this.containerId + ' .modal-content')
            .resizable()
            .on('resize', function (event, ui) {
                ui.element.css(
                    'height',
                    ui.size.height + $('.modal-footer').outerHeight()
                )

                $(ui.element)
                    .find('.modal-body')
                    .each(function () {
                        $(this).css(
                            'max-height',
                            ui.size.height -
                                $('.modal-header').outerHeight() -
                                $('.modal-footer').outerHeight()
                        )

                        $(ui.element)
                            .find('iframe.seamless')
                            .each(function () {
                                // -12 = resizable handle, -15 = padding
                                $(this).css(
                                    'height',
                                    ui.size.height -
                                        $('.modal-header').outerHeight() -
                                        $('.modal-footer').outerHeight() -
                                        12 -
                                        15
                                )
                            })
                    })
            })
    }

    public unbindEvents () {
        if (this.intDialogType === 1) {
            $('#' + this.containerId + '_cancelButton').unbind()
            $('#' + this.containerId + '_confirmButton').unbind()
            this.unbindOnClick = true
            this.bitLarge = false
        }
    }

    public resetDialog () {
        // clone the template
        var clone = $('#template_' + this.containerId).clone()

        // remove "template_" from all id's of the clone
        clone.find('*[id]').each(function () {
            $(this).attr(
                'id',
                $(this)
                    .attr('id')
                    .substring(9)
            )
        })

        // replace the current dialog with the clone
        $('#' + this.containerId).replaceWith(clone)

        // set hidden event again (needed as when replacing the events are not set anymore)
        $('#' + this.containerId).on('hidden', function (e) {
            // @ts-ignore
            this.resetDialog()
        })
    }
}
;(<any>window).Dialog = Dialog
export default Dialog
