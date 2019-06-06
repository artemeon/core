import $ from 'jquery'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import StatusDisplay from 'core/module_system/scripts/kajona/StatusDisplay'

class Imageeditor {
    public static cropArea: any = null
    // eslint-disable-next-line camelcase
    public static fm_cropObj: any = null
    // eslint-disable-next-line camelcase
    public static fm_image_isScaled = true

    public static strCropEnabled = ''
    public static strCropDisabled = ''

    // eslint-disable-next-line camelcase
    public static fm_image_rawurl = ''
    // eslint-disable-next-line camelcase
    public static fm_image_scaledurl = ''
    // eslint-disable-next-line camelcase
    public static fm_image_scaledMaxWidth = ''
    // eslint-disable-next-line camelcase
    public static fm_image_scaledMaxHeight = ''
    // eslint-disable-next-line camelcase
    public static fm_file = ''

    // eslint-disable-next-line camelcase
    public static init_fm_crop_save_warning_dialog: Function = null
    // eslint-disable-next-line camelcase
    public static init_fm_screenlock_dialog: Function = null
    // eslint-disable-next-line camelcase
    public static hide_fm_screenlock_dialog: Function = null

    public static saveImageCropping (
        intX: number,
        intY: number,
        intWidth: number,
        intHeight: number,
        strFile: string,
        objCallback: Function
    ) {
        var postBody =
            'file=' +
            strFile +
            '&intX=' +
            intX +
            '&intY=' +
            intY +
            '&intWidth=' +
            intWidth +
            '&intHeight=' +
            intHeight +
            ''
        Ajax.genericAjaxCall(
            'mediamanager',
            'saveCropping',
            '&' + postBody,
            objCallback
        )
    }

    public static saveImageRotating (
        intAngle: number,
        strFile: string,
        objCallback: Function
    ) {
        var postBody = 'file=' + strFile + '&angle=' + intAngle + ''
        Ajax.genericAjaxCall(
            'mediamanager',
            'rotate',
            '&' + postBody,
            objCallback
        )
    }

    public static showRealSize () {
        $('#fm_mediamanagerPic').attr(
            'src',
            this.fm_image_rawurl + '&x=' + new Date().getMilliseconds()
        )
        this.fm_image_isScaled = false
        this.hideCropping()
    }

    public static showPreview () {
        $('#fm_mediamanagerPic').attr(
            'src',
            this.fm_image_scaledurl
                .replace('__width__', this.fm_image_scaledMaxWidth)
                .replace('__height__', this.fm_image_scaledMaxHeight) +
            '&x=' +
            new Date().getMilliseconds()
        )
        this.fm_image_isScaled = true
        this.hideCropping()
    }

    public static showCropping () {
        // init the cropping
        var iE = this
        if (this.fm_cropObj == null) {
            $('#fm_mediamanagerPic').Jcrop({}, function () {
                iE.fm_cropObj = this
            })

            this.fm_cropObj.animateTo([120, 120, 80, 80])

            $('#accept_icon').html(this.strCropEnabled)
            $('#fm_mediamanagerPic_wrap').bind('dblclick', function (event) {
                Imageeditor.saveCropping()
            })
        } else {
            this.hideCropping()
        }
    }

    public static hideCropping () {
        if (this.fm_cropObj != null) {
            this.fm_cropObj.destroy()
            this.fm_cropObj = null
            $('#fm_mediamanagerPic').css('visibility', 'visible')
            $('#accept_icon').html(this.strCropDisabled)
        }
    }

    public static saveCropping () {
        if (this.fm_cropObj != null) {
            this.init_fm_crop_save_warning_dialog()
        }
    }

    public static saveCroppingToBackend () {
        jsDialog_1.hide()
        this.init_fm_screenlock_dialog()
        this.cropArea = this.fm_cropObj.tellSelect()

        if (this.fm_image_isScaled) {
            // recalculate the "real" crop-coordinates
            var intScaledWidth = parseInt(
                $('#fm_mediamanagerPic').attr('width')
            )
            var intScaledHeight = parseInt(
                $('#fm_mediamanagerPic').attr('height')
            )
            var intOriginalWidth = parseInt(
                $('#fm_int_realwidth').attr('value')
            )
            var intOriginalHeigth = parseInt(
                $('#fm_int_realheight').attr('value')
            )

            this.cropArea.x = Math.floor(
                this.cropArea.x * (intOriginalWidth / intScaledWidth)
            )
            this.cropArea.y = Math.floor(
                this.cropArea.y * (intOriginalHeigth / intScaledHeight)
            )
            this.cropArea.w = Math.floor(
                this.cropArea.w * (intOriginalWidth / intScaledWidth)
            )
            this.cropArea.h = Math.floor(
                this.cropArea.h * (intOriginalHeigth / intScaledHeight)
            )
        }

        var iE = this
        var callback = function (
            data: any,
            status: string,
            jqXHR: XMLHttpRequest
        ) {
            if (status === 'success') {
                StatusDisplay.displayXMLMessage(data)
                iE.fm_cropObj.destroy()
                iE.fm_cropObj = null
                $('#accept_icon').html(iE.strCropEnabled)
                $('#fm_image_dimensions').html(
                    iE.cropArea.w + ' x ' + iE.cropArea.h
                )
                $('#fm_image_size').html('n.a.')
                $('#fm_int_realwidth').val(iE.cropArea.w)
                $('#fm_int_realheight').val(iE.cropArea.h)

                $('#fm_mediamanagerPic').css('visibility', 'visible')
                if (iE.fm_image_isScaled) {
                    iE.showPreview()
                } else {
                    iE.showRealSize()
                }

                iE.cropArea = null

                location.reload()
                iE.hide_fm_screenlock_dialog()
            } else {
                StatusDisplay.messageError('<b>Request failed!</b>' + data)
                iE.hide_fm_screenlock_dialog()
            }
        }

        this.saveImageCropping(
            this.cropArea.x,
            this.cropArea.y,
            this.cropArea.w,
            this.cropArea.h,
            this.fm_file,
            callback
        )
    }

    public static rotate (intAngle: number) {
        this.init_fm_screenlock_dialog()

        var iE = this
        var callback = function (
            data: any,
            status: string,
            jqXHR: XMLHttpRequest
        ) {
            if (status === 'success') {
                StatusDisplay.displayXMLMessage(data)

                if (iE.fm_cropObj != null) {
                    iE.fm_cropObj.destroy()
                    iE.fm_cropObj = null
                    $('#accept_icon').html(iE.strCropDisabled)
                }

                // switch width and height
                var intScaledMaxWidthOld = iE.fm_image_scaledMaxWidth
                iE.fm_image_scaledMaxWidth = iE.fm_image_scaledMaxHeight
                iE.fm_image_scaledMaxHeight = intScaledMaxWidthOld

                if (iE.fm_image_isScaled) {
                    iE.showPreview()
                } else {
                    iE.showRealSize()
                }

                // update size-info & hidden elements
                var intWidthOld = $('#fm_int_realwidth').val()
                var intHeightOld = $('#fm_int_realheight').val()
                $('#fm_int_realwidth').val(intHeightOld)
                $('#fm_int_realheight').val(intWidthOld)
                $('#fm_image_dimensions').html(
                    intHeightOld + ' x ' + intWidthOld
                )

                iE.hide_fm_screenlock_dialog()
            } else {
                StatusDisplay.messageError('<b>Request failed!</b>' + data)
                iE.hide_fm_screenlock_dialog()
            }
        }

        this.saveImageRotating(intAngle, this.fm_file, callback)
    }
}
;(<any>window).Imageeditor = Imageeditor
export default Imageeditor
