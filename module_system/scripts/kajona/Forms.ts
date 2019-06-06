import $ from 'jquery'
import Util from './Util'
import Router from './Router'
import Tooltip from './Tooltip'
import Ajax from './Ajax'
import Messaging from './Messaging'
import DialogHelper from 'core/module_v4skin/scripts/kajona/DialogHelper'
import Lang from './Lang'

/**
 * Form management
 */
class Forms {
    public static changeLabel: string = ''
    public static changeConfirmation: string = ''
    public static leaveUnsaved: string = ''

    /**
     * Hides a field in the form
     *
     * @param objField - my be a jquery field or a id selector
     * @param isResetValue if enabled, sets the fields value to emtpy / ""
     */
    public static hideField (objField: string | JQuery, isResetValue?: boolean) {
        objField = Util.getElement(objField)

        var objFormGroup =
            objField.is('h3') || objField.is('h4') || objField.is('p')
                ? objField
                : objField.closest('.form-group')

        // 1. Hide field
        objFormGroup.slideUp(0)

        // 2. Hide hint -> check if previous element has 'form-group' and if <span> with .help-block exists
        var objHintFormGroup = objFormGroup.prev('.form-group')
        if (objHintFormGroup.find('div > span.help-block').length > 0) {
            objHintFormGroup.slideUp(0)
        }

        // reset value
        if (isResetValue) {
            objFormGroup.find('input, textarea, select').each(function () {
                if ($(this).is(':checkbox')) {
                    $(this).prop('checked', false)
                } else if ($(this).is(':radio')) {
                    $(this).prop('checked', false)
                } else {
                    $(this).val('')
                }
            })
        }
    }

    /**
     * Shows a field in the form
     *
     * @param objField - my be a jquery object or an id selector
     */
    public static showField (objField: string | JQuery) {
        objField = Util.getElement(objField)

        var objFormGroup =
            objField.is('h3') || objField.is('h4') || objField.is('p')
                ? objField
                : objField.closest('.form-group')

        // 1. Show field
        objFormGroup.slideDown(0)

        // 2. Show hint -> check if previous element has 'form-group' and if <span> with .help-block exists
        var objHintFormGroup = objFormGroup.prev('.form-group')
        if (objHintFormGroup.find('div > span.help-block').length > 0) {
            objHintFormGroup.slideDown(0)
        }
    }

    /**
     * Disables a field
     *
     * @param objField - my be a jquery object or an id selector
     */
    public static setFieldReadOnly (objField: string | JQuery) {
        objField = Util.getElement(objField)

        if (
            $('#' + objField.attr('id') + '_upl').length > 0 &&
            $('#' + objField.attr('id') + '_upl').fileupload
        ) {
            $('#' + objField.attr('id') + '_upl').fileupload('disable')
        } else if (
            objField.is('input:checkbox') ||
            objField.is('select') ||
            objField.data('datepicker') !== null
        ) {
            objField.prop('disabled', 'disabled')
        } else {
            objField.attr('readonly', 'readonly')
        }
    }

    /**
     * Enables a field
     *
     * @param objField - my be a jquery object or an id selector
     */
    public static setFieldEditable (objField: string | JQuery) {
        objField = Util.getElement(objField)

        if (
            $('#' + objField.attr('id') + '_upl').length > 0 &&
            $('#' + objField.attr('id') + '_upl').fileupload
        ) {
            $('#' + objField.attr('id') + '_upl').fileupload('enable')
        } else if (
            objField.is('input:checkbox') ||
            objField.is('select') ||
            objField.data('datepicker') !== null
        ) {
            objField.removeProp('disabled')
        } else {
            objField.removeProp('readonly')
        }
    }

    /**
     * Gets the jQuery object
     *
     * @param objField - my be a jquery object or an id selector
     * @deprecated
     */
    public static getObjField (objField: string | JQuery) {
        // If objField is already a jQuery object
        return Util.getElement(objField)
    }

    /**
     * Internal callback to initialize a form
     * @param strFormid
     * @param onChangeDetection
     */
    public static initForm (strFormid: string, onChangeDetection: Function) {
        $(
            '#' +
                strFormid +
                ' input , #' +
                strFormid +
                ' select , #' +
                strFormid +
                ' textarea '
        ).each(function () {
            if ($(this).data('kajona-block-initval')) {
                return
            }
            $(this).data('kajona-initval', $(this).val())
        })

        if (onChangeDetection) {
            Router.markerElements.forms.monitoredEl = $('#' + strFormid)
        }
    }

    /**
     * May be triggered to determine whether a form has been changed or not
     * @param $objForm
     */
    public static hasChanged ($objForm: JQuery) {
        var changed = false
        $objForm.find('[data-kajona-initval]').each(function () {
            var el = $(this)
            if (el.val() !== el.attr('data-kajona-initval')) {
                changed = true
                return false
            }
        })

        return changed
    }

    /**
     * Fires the animation on the submit button
     * @param objForm
     */
    public static animateSubmitStart (objForm: HTMLFormElement) {
        var processingElemet

        if ($('button.clicked').length === 1) {
            processingElemet = $('button.clicked')
        } else if ($(document.activeElement).prop('tagName') === 'BUTTON') {
            // try to get the button currently clicked
            processingElemet = $(document.activeElement)
        } else {
            processingElemet = $(objForm).find('.savechanges[name=submitbtn]')
        }
        processingElemet.addClass('processing')
        processingElemet.attr('disabled', 'disabled')
    }

    public static animateSubmitStop (objForm: HTMLFormElement) {
        var processingElemet = $(objForm).find('.savechanges')

        processingElemet.removeClass('processing')
        processingElemet.removeClass('clicked')
        processingElemet.removeAttr('disabled')
    }

    /**
     * Adds an onchange listener to the formentry with the passed ID. If the value is changed, a warning is rendered below the field.
     * In addition, a special confirmation may be required to change the field to the new value.
     *
     * @param strElementId
     * @param bitConfirmChange
     */
    public static addChangelistener (
        strElementId: string,
        bitConfirmChange: boolean
    ) {
        $('#' + strElementId).on('change', function (objEvent) {
            if ($(this).val() !== $(this).attr('data-kajona-initval')) {
                if (
                    $(this)
                        .closest('.form-group')
                        .find('div.changeHint').length === 0
                ) {
                    if (bitConfirmChange && bitConfirmChange === true) {
                        var bitResponse = confirm(Forms.changeConfirmation)
                        if (!bitResponse) {
                            $(this).val($(this).attr('data-kajona-initval'))
                            objEvent.preventDefault()
                            return
                        }
                    }

                    Forms.addHint(strElementId, Forms.changeLabel)
                }
            } else {
                Forms.removeHint(strElementId)
            }
        })
    }

    /**
     * Renders a hint below an input field
     * @param strElementId
     * @param strHint
     */
    public static addHint (strElementId: string, strHint: string) {
        var $objTarget = $('#' + strElementId)
        Forms.removeHint(strElementId)
        $objTarget.closest('.form-group').addClass('has-warning')
        $objTarget
            .closest('.form-group')
            .children('div:first')
            .append(
                $(
                    '<div class="changeHint text-warning"><span class="glyphicon glyphicon-warning-sign"></span> ' +
                        strHint +
                        '</div>'
                )
            )
    }

    /**
     * Removes a hint from an input field
     * @param strElementId
     */
    public static removeHint (strElementId: string) {
        var $objTarget = $('#' + strElementId)
        $objTarget.closest('.form-group').removeClass('has-warning')
        if ($objTarget.closest('.form-group').find('div.changeHint')) {
            $objTarget
                .closest('.form-group')
                .find('div.changeHint')
                .remove()
        }
    }

    public static renderMandatoryFields (arrFields: Array<Array<string>>) {
        for (var i = 0; i < arrFields.length; i++) {
            var arrElement = arrFields[i]
            if (arrElement.length === 2) {
                var $objElement = $('#' + arrElement[0])
                if ($objElement) {
                    $objElement.addClass('mandatoryFormElement')
                    $objElement.trigger('kajona.forms.mandatoryAdded')
                }
            }
        }
    }

    public static renderMissingMandatoryFields (
        arrFields: Array<Array<string>>
    ) {
        $(arrFields).each(function (intIndex, strField) {
            var strFieldName = strField[0]
            if (
                $('#' + strFieldName) &&
                !$('#' + strFieldName).hasClass('inputWysiwyg')
            ) {
                $('#' + strFieldName)
                    .closest('.form-group')
                    .addClass('has-error has-feedback')
                var objNode = $(
                    '<span class="glyphicon glyphicon-warning-sign form-control-feedback" aria-hidden="true"></span>'
                )
                $('#' + strFieldName)
                    .closest('div:not(.input-group)')
                    .append(objNode)
            }
        })
    }

    public static loadTab (strEl: string, strHref: string) {
        if (strHref && $('#' + strEl).length > 0) {
            $('#' + strEl).html('')
            $('#' + strEl).addClass('loadingContainer')
            $.get(strHref, function (data) {
                $('#' + strEl).removeClass('loadingContainer')
                $('#' + strEl).html(data)
                Tooltip.initTooltip()
            })
        }
    }

    public static defaultOnSubmit (objForm: HTMLFormElement) {
        $(objForm).on('submit', function () {
            return false
        })
        Router.markerElements.forms.submittedEl = objForm
        $(window).off('unload')

        this.animateSubmitStart(objForm)

        // disable polling on form submit
        Messaging.setPollingEnabled(false)

        var $btn = $('button.clicked')
        /* there is an activeElement at all && it's a child of the form && it's really a submit element && it has a "name" attribute */
        if (
            $btn.length &&
            $(objForm).has($btn.get(0)) &&
            $btn.is(
                'button[type="submit"], input[type="submit"], input[type="image"]'
            ) &&
            $btn.is('[name]')
        ) {
            // name, value
            $(objForm).append(
                $('<input type="hidden">')
                    .attr('name', $btn.attr('name'))
                    .attr('value', <string>$btn.val())
            )
            /* access $btn.attr("name") and $btn.val() for data */
        }

        Router.registerFormCallback('activate_polling', function () {
            // enable polling after we receive the response of the form
            Messaging.setPollingEnabled(true)
        })

        Router.defaultRoutieCallback(objForm.action)

        return false
    }

    public static registerUnlockId (strId: string) {
        Router.registerLoadCallback('form_unlock', function () {
            $.ajax({
                url:
                    KAJONA_WEBPATH +
                    '/xml.php?admin=1&module=system&action=unlockRecord&systemid=' +
                    strId
            })
        })
    }

    public static getFilterURL () {
        var filterUrl =
            $('.contentFolder form').attr('action') +
            '&' +
            $('.contentFolder form').serialize()
        Ajax.genericAjaxCall(
            'tinyurl',
            'getShortUrl',
            { url: filterUrl },
            function (data: any) {
                if (data) {
                    var modalContent =
                        '<div class="input-group">' +
                        '<input type="text" class="form-control" value="' +
                        data.url +
                        '">' +
                        '<span class="input-group-btn">' +
                        '<button class="btn btn-default copy-btn" type="button" title="" onclick="Util.copyTextToClipboard(\'' +
                        data.url +
                        '\')">' +
                        "<i class='kj-icon fa fa-clipboard'>" +
                        '</button>' +
                        '</span>' +
                        '</div>'

                    DialogHelper.showInfoModal('', modalContent)

                    Lang.fetchSingleProperty(
                        'system',
                        'copy_to_clipboard',
                        function (value: string) {
                            $('.copy-btn').attr('title', value)
                        }
                    )

                    Lang.fetchSingleProperty(
                        'system',
                        'copy_page_url',
                        function (value: string) {
                            $('#jsDialog_0_title').text(value)
                        }
                    )
                }
            },
            null,
            null,
            null,
            'json'
        )
    }
}
;(<any>window).Forms = Forms
export default Forms
