import $ from 'jquery'
import 'jquery-ui.custom'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import StatusDisplay from 'core/module_system/scripts/kajona/StatusDisplay'
import Lang from 'core/module_system/scripts/kajona/Lang'
import Tooltip from 'core/module_system/scripts/kajona/Tooltip'
import Util from 'core/module_system/scripts/kajona/Util'

class ListSortable {
    /**
     * Initializes a new sort-manager
     *
     * @param strListId
     * @param strTargetModule
     * @param bitMoveToTree
     */
    public static init (
        strListId: string,
        strTargetModule: string,
        bitMoveToTree: boolean
    ) {
        var $objListNode = $('#' + strListId)

        var oldPos: number = null
        var intCurPage = parseInt($objListNode.attr('data-kajona-pagenum'))
        var intElementsPerPage = parseInt(
            $objListNode.attr('data-kajona-elementsperpage')
        )

        $('#' + strListId + '_prev').sortable({
            placeholder: 'dndPlaceholder',
            over: function (event, ui) {
                $(ui.placeholder).hide()
                $(this)
                    .removeClass('alert-info')
                    .addClass('alert-success')
            },
            out: function (event, ui) {
                $(this)
                    .removeClass('alert-success')
                    .addClass('alert-info')
                $(ui.placeholder).show()
            },
            receive: function (event, ui) {
                $(ui.placeholder).hide()
                if (intCurPage > 1) {
                    Ajax.setAbsolutePosition(
                        ui.item.find('tr').data('systemid'),
                        intElementsPerPage * (intCurPage - 1),
                        null,
                        function (data: any, status: string, jqXHR: any) {
                            if (status === 'success') {
                                location.reload()
                            } else {
                                StatusDisplay.messageError(
                                    '<b>Request failed!</b>'
                                )
                            }
                        },
                        strTargetModule
                    )
                } else {
                    ui.sender.sortable('cancel')
                }
            }
        })

        $('#' + strListId + '_next').sortable({
            over: function (event, ui) {
                $(ui.placeholder).hide()
                $(this)
                    .removeClass('alert-info')
                    .addClass('alert-success')
            },
            out: function (event, ui) {
                $(this)
                    .removeClass('alert-success')
                    .addClass('alert-info')
                $(ui.placeholder).show()
            },
            receive: function (event, ui) {
                $(ui.placeholder).hide()
                var intOnPage =
                    $('#' + strListId + ' tbody:has(tr[data-systemid!=""])')
                        .length + 1
                if (intOnPage >= intElementsPerPage) {
                    Ajax.setAbsolutePosition(
                        ui.item.find('tr').data('systemid'),
                        intElementsPerPage * intCurPage + 1,
                        null,
                        function (data: any, status: string, jqXHR: any) {
                            if (status === 'success') {
                                location.reload()
                            } else {
                                StatusDisplay.messageError(
                                    '<b>Request failed!</b>'
                                )
                            }
                        },
                        strTargetModule
                    )
                } else {
                    ui.sender.sortable('cancel')
                }
            }
        })

        $objListNode.sortable({
            items: 'tbody:has(tr[data-systemid!=""])',
            handle: 'td.listsorthandle',
            cursor: 'move',
            forcePlaceholderSize: true,
            forceHelperSize: true,
            placeholder: 'dndPlaceholder table',
            connectWith: '.divPageTarget',
            start: function (event, ui) {
                let pageNum = parseInt(
                    $('#' + strListId).attr('data-kajona-pagenum')
                )
                if (pageNum > 1) {
                    $('#' + strListId + '_prev').css('display', 'block')
                }

                let elPerPage = parseInt(
                    $('#' + strListId).attr('data-kajona-elementsperpage')
                )
                if (
                    $(
                        '#' +
                            strListId +
                            ' tbody:has(tr[data-systemid!=""][data-systemid!="batchActionSwitch"])'
                    ).length -
                        1 >=
                    elPerPage
                ) {
                    $('#' + strListId + '_next').css('display', 'block')
                }

                oldPos = ui.item.index()

                // hack the placeholder
                ui.placeholder.html(ui.helper.html())
                ui.placeholder.height(ui.item.height())
            },
            stop: function (event, ui) {
                if (oldPos !== ui.item.index() && !ui.item.parent().is('div')) {
                    var intOffset = 1
                    // see, if there are nodes not being sortable - would lead to another offset
                    $('#' + strListId + ' > tbody').each(function (index) {
                        if (
                            $(this)
                                .find('tr')
                                .data('systemid') === ''
                        ) { intOffset-- }
                        if (
                            $(this)
                                .find('tr')
                                .data('systemid') ===
                            ui.item.find('tr').data('systemid')
                        ) { return false }
                    })

                    // calc the page-offset
                    var intCurPage = parseInt(
                        $('#' + strListId).attr('data-kajona-pagenum')
                    )
                    var intElementsPerPage = parseInt(
                        $('#' + strListId).attr('data-kajona-elementsperpage')
                    )

                    var intPagingOffset = 0
                    if (intCurPage > 1 && intElementsPerPage > 0) {
                        intPagingOffset =
                            intCurPage * intElementsPerPage - intElementsPerPage
                    }

                    Ajax.setAbsolutePosition(
                        ui.item.find('tr').data('systemid'),
                        ui.item.index() + intOffset + intPagingOffset,
                        null,
                        null,
                        strTargetModule
                    )
                }
                oldPos = 0
                $('div.divPageTarget').css('display', 'none')
            },
            delay: Util.isTouchDevice() ? 500 : 0
        })

        $(
            '#' +
                strListId +
                ' > tbody:has(tr[data-systemid!=""][data-deleted=""][data-systemid!="batchActionSwitch"]) > tr'
        ).each(function (index) {
            $(this)
                .find('td.listsorthandle')
                .css('cursor', 'move')
                .append("<i class='fa fa-arrows-v'></i>")

            var self = this
            Lang.fetchSingleProperty(
                'commons',
                'commons_sort_vertical',
                function (strText: string) {
                    Tooltip.addTooltip(
                        $(self).find('td.listsorthandle'),
                        strText
                    )
                }
            )

            if (bitMoveToTree) {
                $(this)
                    .find('td.treedrag')
                    .css('cursor', 'move')
                    .addClass('jstree-listdraggable')
                    .append(
                        "<i class='fa fa-arrows-h' data-systemid='" +
                            $(this).data('systemid') +
                            "'></i>"
                    )
                Lang.fetchSingleProperty(
                    'commons',
                    'commons_sort_totree',
                    function (strText: string) {
                        Tooltip.addTooltip($(self).find('td.treedrag'), strText)
                    }
                )
            }
        })
    }
}
;(<any>window).ListSortable = ListSortable
export default ListSortable
