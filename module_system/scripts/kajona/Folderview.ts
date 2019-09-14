import $ from 'jquery'
import Dialog from 'core/module_v4skin/scripts/kajona/Dialog'
import Util from './Util'
import Lists from './Lists'

declare global {
    interface Window {
        KAJONA: Kajona
        $: JQueryStatic
        require: Require
    }
}

interface ObjectListItem {
    strSystemId: string
    strDisplayName?: string
    strPath?: string
    strIcon?: string
    strValue?: string
    strEditLink?: string
}

/**
 * Folderview functions
 */
class Folderview {
    /**
     * holds a reference to the ModalDialog
     */
    public static dialog: Dialog

    /**
     * Loads the passed url to the parent frame and closes the dialog
     * @param url
     */
    public static loadParentUrl (url: string) {
        var context = window.opener ? window.opener : parent
        context.location = url
        this.close()
    }

    /**
     * To be called when the user selects an page/folder/file out of a folderview dialog/popup
     * Detects if the folderview is embedded in a dialog or popup to find the right context
     *
     * @param {Array} arrTargetsValues
     * @param {function} objCallback
     */
    public static selectCallback (
        arrTargetsValues: Array<Array<String>>,
        objCallback: Function
    ) {
        if (window.opener) {
            window.opener.Folderview.fillFormFields(arrTargetsValues)
        } else if (parent) {
            if (parent.KAJONA.util.folderviewHandler) {
                parent.KAJONA.util.folderviewHandler.fillFormFields(
                    arrTargetsValues
                )
                parent.KAJONA.util.folderviewHandler = null
            } else {
                ;(<any>parent).Folderview.fillFormFields(arrTargetsValues)
            }
        }

        if ($.isFunction(objCallback)) {
            objCallback()
        }

        this.close()
    }

    /**
     * fills the form fields with the selected values
     */
    public static fillFormFields (arrTargetsValues: Array<Array<string>>) {
        for (var i in arrTargetsValues) {
            let key = arrTargetsValues[i][0]
            let value = arrTargetsValues[i][1]

            let formField = $('#' + key)
            if (formField.length > 0) {
                if (formField.hasClass('inputWysiwyg')) {
                    CKEDITOR.instances[key].setData(value)
                } else {
                    formField.val(value)
                    formField.trigger('change')
                }
            }
        }
    }

    /**
     * Sets an array of items to an object list. We remove only elements which are available in the arrAvailableIds array
     *
     * @param {string} strElementName  - name of the objectlist element
     * @param {Array} arrItems        - array with item of the following format {strSystemId: <systemid>, strDisplayName:<displayname>, strIcon:<icon>, strEditLink: <string>}
     * @param {Array} arrAvailableIds -
     * @param {string} strDeleteButton -
     * @param {boolean} bitStayOpen
     */
    public static setObjectListItems (
        strElementName: string,
        arrItems: Array<ObjectListItem>,
        arrAvailableIds: Array<string | number | string[]>,
        strDeleteButton: string,
        bitStayOpen?: boolean
    ) {
        var table = Util.getElementFromOpener(strElementName)

        var tbody = table.find('tbody')
        let maxAmount =
            table.data('max-values') > 0 ? table.data('max-values') : 500
        if (tbody.length > 0) {
            // remove only elements which are in the arrAvailableIds array
            tbody.children().each(function () {
                var strId = $(this)
                    .find('input[type="hidden"]')
                    .val()
                if ($.inArray(strId, arrAvailableIds) !== -1) {
                    // if strId in array
                    $(this).remove()
                }
            })

            // add new elements
            for (var i = 0; i < arrItems.length; i++) {
                if (table.find('tr').length > maxAmount) {
                    break
                }

                var strEscapedTitle = $('<div></div>')
                    .text(arrItems[i].strDisplayName)
                    .html()
                var strEscapedPath = $('<div></div>')
                    .text(arrItems[i].strPath)
                    .html()

                var html = ''
                html +=
                    '<tr data-kajona-systemid="' +
                    arrItems[i].strSystemId +
                    '">'
                html +=
                    '    <td class="listimage">' + arrItems[i].strIcon + '</td>'
                html +=
                    '    <td class="title"><div class="smaller">' +
                    strEscapedPath +
                    '</div>' +
                    strEscapedTitle +
                    ' <input type="hidden" name="' +
                    strElementName +
                    '[]" value="' +
                    arrItems[i].strSystemId +
                    '" data-kajona-initval="" /></td>'
                if (strDeleteButton) {
                    html += '    <td class="icon-cell">'
                    html +=
                        '        <a href="#" class="removeLink" onclick="V4skin.removeObjectListItem(this);return false">' +
                        strDeleteButton +
                        '</a>'
                    html += '    </td>'
                }

                if (arrItems[i].strEditLink) {
                    html +=
                        '    <td class="icon-cell">' +
                        arrItems[i].strEditLink +
                        '</td>'
                }
                html += '</tr>'

                if (
                    tbody.find(
                        'tr[data-kajona-systemid=' +
                            arrItems[i].strSystemId +
                            ']'
                    ).length > 0
                ) {
                    tbody
                        .find(
                            'tr[data-kajona-systemid=' +
                                arrItems[i].strSystemId +
                                ']'
                        )
                        .replaceWith(html)
                } else {
                    tbody.append(html)
                }
            }
            table.trigger('updated')
        }

        if (bitStayOpen !== true) {
            this.close()
        }
    }

    /**
     * Sets an array of items to an checkbox object list
     *
     * @param {string} strElementName  - name of the objectlist element
     * @param {Array} arrItems        - array with item of the following format {strSystemId: <systemid>, strDisplayName:<displayname>, strIcon:<icon>, strPath:<string>}
     */
    public static setCheckboxArrayObjectListItems (
        strElementName: string,
        arrItems: Array<ObjectListItem>
    ) {
        var form = Util.getElementFromOpener(strElementName)

        var table = form.find('table')
        if (table.length > 0) {
            // add new elements
            for (var i = 0; i < arrItems.length; i++) {
                var strEscapedTitle = $('<div></div>')
                    .text(arrItems[i].strDisplayName)
                    .html()
                var html = ''

                // check whether form entry exists already in the table if so skip. We need to escape the form element name
                // since it contains brackets
                var formElementName =
                    strElementName + '[' + arrItems[i].strSystemId + ']'
                var existingFormEls = table.find(
                    'input[name=' +
                        formElementName.replace(/(:|\.|\[|\]|,)/g, '\\$1') +
                        ']'
                )
                if (existingFormEls.length > 0) {
                    continue
                }

                html += '<tbody>'
                html += '<tr data-systemid="' + arrItems[i].strSystemId + '">'

                var value
                if (arrItems[i].strValue) {
                    value = JSON.stringify(arrItems[i].strValue)
                    value = value.replace(/"/g, '&quot;')
                } else {
                    value = 'on'
                }

                html +=
                    '    <td class="listcheckbox"><input type="checkbox" name="' +
                    formElementName +
                    '" value="' +
                    value +
                    '" data-systemid="' +
                    arrItems[i].strSystemId +
                    '" data-kajona-initval="" checked></td>'
                html +=
                    '    <td class="listimage">' + arrItems[i].strIcon + '</td>'
                html += '    <td class="title">'
                html +=
                    '        <div class="small text-muted">' +
                    arrItems[i].strPath +
                    '</div>'
                html += '        ' + arrItems[i].strDisplayName
                html += '    </td>'
                html += '</tr>'
                html += '</tbody>'

                table.append(html)
            }
            form.trigger('updated')
        }

        this.close()
    }

    /**
     * closes the current dialog
     */
    public static close () {
        try {
            if (window.opener && window.opener.KAJONA) {
               window.close()
            }
        } catch (ex) {
        }

        if (parent && parent !== window) {
            var context = (<any>parent).Folderview
            // in case we call setCheckboxArrayObjectListItems without dialog
            if (context.dialog) {
                context.dialog.hide()
                context.dialog.setContentRaw('')
            }
        }
    }

    /**
     * Enables selection by clicking a row-entry
     * @deprecated
     */
    public static initRowClick () {
        return Lists.initRowClick()
    }
}
;(<any>window).Folderview = Folderview
export default Folderview
