import $ from 'jquery'
import 'jquery-ui.custom'
import 'bootstrap/dist/js/bootstrap.min.js'
import 'bootstrap-datepicker/dist/js/bootstrap-datepicker.min'
import 'bootstrap-datepicker/dist/locales/bootstrap-datepicker.de.min.js'
import 'bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min'
import 'bootstrap-select/dist/js/bootstrap-select.min'
import 'bootstrap-switch'
import 'floatthead'
import Messaging from 'core/module_system/scripts/kajona/Messaging'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import Lang from 'core/module_system/scripts/kajona/Lang'
import Tooltip from 'core/module_system/scripts/kajona/Tooltip'
import Util from 'core/module_system/scripts/kajona/Util'
import Folderview from 'core/module_system/scripts/kajona/Folderview'
import WorkingIndicator from 'core/module_system/scripts/kajona/WorkingIndicator'
import Breadcrumb from 'core/module_system/scripts/kajona/Breadcrumb'

// import all the kajona styles
import 'jquery-ui.custom/dist/jquery-ui.custom.css'

class DefaultAutoComplete implements JQueryUI.AutocompleteOptions {
    public keepUi: boolean = false

    public minLength: number = 0

    public delay: number = 500

    public position = {
        collision: 'fit flip'
    }

    public messages = {
        noResults: '',
        results: function () {
            return ''
        }
    }

    public search: JQueryUI.AutocompleteEvent = function (event: any, ui: any) {
        // If input field changes -> reset hidden id field
        var $objCur = $(this)
        if (!$objCur.is('[readonly]')) {
            if ($('#' + $objCur.attr('id') + '_id')) {
                $('#' + $objCur.attr('id') + '_id')
                    .val('')
                    .trigger('change')
            }
        }

        // Formentry must have at least 2 characters to trigger search.
        if (event.target.value.length < 2) {
            event.stopPropagation()
            return false
        }
        $objCur
            .parent()
            .find('.loading-feedback')
            .html('<i class="fa fa-spinner fa-spin"></i>')
        WorkingIndicator.getInstance().start()
    }

    public response: JQueryUI.AutocompleteEvent = function (
        event: any,
        ui: any
    ) {
        $(this)
            .parent()
            .find('.loading-feedback')
            .html('')
        WorkingIndicator.getInstance().stop()
    }

    public focus: JQueryUI.AutocompleteEvent = function (event: any, ui: any) {
        return true
    }

    public close: JQueryUI.AutocompleteEvent = function (event: any, ui: any) {
        if (this.keepUi) {
            $(event.currentTarget).show()
        }
    }.bind(this)

    public select: JQueryUI.AutocompleteEvent = function (event: any, ui: any) {
        if (ui.item) {
            var $objCur = $(this)
            $objCur.val(ui.item.title)
            if ($('#' + $objCur.attr('id') + '_id')) {
                $('#' + $objCur.attr('id') + '_id')
                    .val(ui.item.systemid)
                    .trigger('change')
            }
            $objCur.trigger('change')
        }
    }

    public create: JQueryUI.AutocompleteEvent = function (event: any, ui: any) {
        var $objCur = $(this)
        $objCur.closest('.form-group').addClass('has-feedback')
        $objCur.after(
            "<span class='form-control-feedback loading-feedback'><i class='fa fa-keyboard-o'></i></span>"
        )
    }
}

class MessagingOptions {
    public static properties: any = null

    public static pollMessages () {
        Messaging.getRecentMessages(function (objResponse: any) {
            Messaging.updateCountInfo(objResponse.messageCount)

            $('#messagingShortlist').empty()
            $.each(objResponse.messages, function (index, item) {
                if (item.unread === 0) {
                    $('#messagingShortlist').append(
                        "<li><a href='" +
                            item.details +
                            "'><i class='fa fa-envelope'></i> <b>" +
                            item.title +
                            '</b></a></li>'
                    )
                } else {
                    $('#messagingShortlist').append(
                        "<li><a href='" +
                            item.details +
                            "'><i class='fa fa-envelope'></i> " +
                            item.title +
                            '</a></li>'
                    )
                }
            })
            $('#messagingShortlist').append(
                "<li class='divider'></li><li><a href='#/messaging'><i class='fa fa-envelope'></i> " +
                    V4skin.messaging.properties.show_all +
                    '</a></li>'
            )
        })
    }
}

interface Item {
    icon: string
    description: string
    module: string
    link: string
}

class V4skin {
    public static properties: any = {
        messaging: {},
        tags: {}
    }

    public static messaging = MessagingOptions

    public static breadcrumb = Breadcrumb

    public static defaultAutoComplete = DefaultAutoComplete

    public static initTagMenu () {
        Ajax.genericAjaxCall('tags', 'getFavoriteTags', '', function (
            data: any,
            status: string,
            jqXHR: XMLHttpRequest
        ) {
            if (status === 'success') {
                $('#tagsSubemenu').empty()
                $.each($.parseJSON(data), function (index, item) {
                    $('#tagsSubemenu').append(
                        "<li><a href='" +
                            item.url +
                            "'><i class='fa fa-tag'></i> " +
                            item.name +
                            '</a></li>'
                    )
                })
                $('#tagsSubemenu').append(
                    "<li class='divider'></li><li><a href='#/tags'><i class='fa fa-tag'></i> <span data-lang-property='tags:action_show_all'></span></a></li>"
                )
                Lang.initializeProperties('#tagsSubemenu')
            }
        })
    }

    /**
     * Removes an object list row from the list
     *
     * @param el
     */
    public static removeObjectListItem (el: any) {
        // remove all active tooltips
        Tooltip.removeTooltip(el)

        // trigger table changed event
        var table = $(el)
            .closest('.form-group')
            .find('.table')
            .first()

        // remove marker
        if (table.siblings('.initval-marker').length > 0) {
            let marker = table.siblings('.initval-marker').val() + ''
            marker = marker.replace(
                $(el)
                    .closest('tr')
                    .find('input')
                    .val() + '',
                ''
            )

            table.siblings('.initval-marker').val(marker)
        }

        // remove element
        $(el)
            .parent()
            .parent()
            .fadeOut(0, function () {
                $(this).remove()
            })

        table.trigger('updated')
    }

    public static removeAllObjectListItems (strTableId: string) {
        $('#' + strTableId)
            .find('.removeLink')
            .trigger('click')
    }

    /**
     * Gets all items containd in the object list
     *
     * @param strElementName
     * @returns {Array}
     */
    public static getObjectListItems (
        strElementName: string
    ): Array<string | number | string[]> {
        var table = Util.getElementFromOpener(strElementName)

        var arrItems: Array<string | number | string[]> = []

        var tbody = table.find('tbody')
        if (tbody.length > 0) {
            // remove only elements which are in the arrAvailableIds array
            tbody.children().each(function () {
                var strId = $(this)
                    .find('input[type="hidden"]')
                    .val()
                arrItems.push(strId)
            })
        }

        return arrItems
    }

    /**
     * Use folderview.setObjectListItems
     *
     * @deprecated
     * @param strElementName
     * @param arrItems
     * @param arrAvailableIds
     * @param strDeleteButton
     */
    public static setObjectListItems (
        strElementName: string,
        arrItems: Array<any>,
        arrAvailableIds: Array<string>,
        strDeleteButton: string
    ) {
        console.log(
            'v4skin.setObjectListItems is deprecated please use folderview.setObjectListItems instead'
        )
        Folderview.setObjectListItems(
            strElementName,
            arrItems,
            arrAvailableIds,
            strDeleteButton
        )
    }

    /**
     * Use folderview.setCheckboxArrayObjectListItems
     *
     * @deprecated
     * @param strElementName
     * @param arrItems
     */
    public static setCheckboxArrayObjectListItems (
        strElementName: string,
        arrItems: Array<any>
    ) {
        console.log(
            'v4skin.setCheckboxArrayObjectListItems is deprecated please use folderview.setCheckboxArrayObjectListItems instead'
        )
        Folderview.setCheckboxArrayObjectListItems(strElementName, arrItems)
    }

    /**
     * We get the current tree selection from the iframe element and set the selection in the object list
     *
     * @param objIframeEl
     * @param strElementName
     * @param strDeleteButton
     */
    public static updateCheckboxTreeSelection (
        objIframeEl: any,
        strElementName: string,
        strDeleteButton: string
    ) {
        if (objIframeEl && objIframeEl.contentWindow) {
            var jstree = objIframeEl.contentWindow.$('.jstree')
            if (jstree.length > 0) {
                // we modify only the ids which are visible for the user all other ids stay untouched
                var arrAvailableIds: Array<string> = []
                jstree.find('li').each(function () {
                    arrAvailableIds.push($(this).attr('systemid'))
                })

                var arrEls = jstree.jstree('get_checked')
                var arrItems = []
                for (var i = 0; i < arrEls.length; i++) {
                    var el = $(arrEls[i])
                    var strSystemId = el.attr('id')
                    var strDisplayName = el.text().trim()
                    var strIcon = el.find('[rel="tooltip"]').html()

                    arrItems.push({
                        strSystemId: strSystemId,
                        strDisplayName: strDisplayName,
                        strIcon: strIcon
                    })
                }

                Folderview.setObjectListItems(
                    strElementName,
                    arrItems,
                    arrAvailableIds,
                    strDeleteButton
                )

                jsDialog_1.hide()
            }
        }
    }

    /**
     * Returns all systemids which are available in the object list. The name of the object list element name must be
     * available as GET parameter "element_name"
     *
     * @returns array
     */
    public static getCheckboxTreeSelectionFromParent () {
        if ($('.jstree').length > 0) {
            // the query parameter contains the name of the form element where we insert the selected elements
            var strElementName = Util.getQueryParameter('element_name')
            var table = parent.$('#' + strElementName)
            var arrSystemIds: Array<string | number | string[]> = []
            if (table.length > 0) {
                table.find('input[type="hidden"]').each(function () {
                    arrSystemIds.push($(this).val())
                })
            }

            return arrSystemIds
        }
    }

    public static initCatComplete () {
        $.widget('custom.catcomplete', $.ui.autocomplete, {
            _create: function () {
                this._super()
                this.widget().menu(
                    'option',
                    'items',
                    '> :not(.ui-autocomplete-category)'
                )
            },
            _renderMenu: function (ul: any, items: Array<Item>) {
                var self = this
                var currentCategory = ''

                $.each(items, function (index: number, item: Item) {
                    if (item.module !== currentCategory) {
                        ul.append(
                            '<li class="ui-autocomplete-category"><h3>' +
                                item.module +
                                '</h3></li>'
                        )
                        currentCategory = item.module
                    }
                    self._renderItemData(ul, item)
                })

                $('<li class="ui-menu-item detailedResults"></li>')
                    .data('ui-autocomplete-item', {})
                    .append(
                        '<div><i class="fa fa-search"></i> <span data-lang-property="search:search_details"></span></div>'
                    )
                    .appendTo(ul)

                ul.addClass('dropdown-menu')
                ul.addClass('search-dropdown-menu')

                ul.find('.detailedResults div').on('click', function () {
                    $('.navbar-search').submit()
                })

                Lang.initializeProperties(ul)
            },
            _renderItemData: function (ul: any, item: Item) {
                return $('<li></li>')
                    .data('ui-autocomplete-item', item)
                    .append('<div>' + item.icon + item.description + '</div>')
                    .appendTo(ul)
            }
        })

        $('#globalSearchInput').catcomplete({
            minLength: 2,
            delay: 500,

            source: function (request: any, response: any) {
                $.ajax({
                    url: KAJONA_WEBPATH + '/xml.php?admin=1',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        search_query: request.term,
                        module: 'search',
                        action: 'searchXml',
                        asJson: '1'
                    },
                    success: response
                })
            },
            select: function (event: any, ui: any) {
                if (ui.item && ui.item.link) {
                    document.location = ui.item.link
                }
            },
            messages: {
                noResults: '',
                results: function () {}
            },
            search: function (event: any, ui: any) {
                $(this)
                    .parent()
                    .find('.input-group-addon')
                    .html('<i class="fa fa-spinner fa-spin"></i></span>')
                WorkingIndicator.start()
            },
            response: function (event: any, ui: any) {
                $(this)
                    .parent()
                    .find('.input-group-addon')
                    .html('<i class="fa fa-search"></i></span>')
                WorkingIndicator.stop()
            }
        })
    }

    public static initPopover () {
        // init popovers & tooltips
        $('#content a[rel=popover]').popover()
        Tooltip.initTooltip()
    }

    public static initScroll () {
        let kajonaScroll: string = null
        $(window).scroll(function () {
            var scroll = $(this).scrollTop()
            if (scroll > 10 && kajonaScroll !== 'top') {
                $('ul.breadcrumb').addClass('breadcrumbTop')
                $('#quickhelp').addClass('quickhelpTop')
                $('.pathNaviContainer').addClass('pathNaviContainerTop')
                kajonaScroll = 'top'
            } else if (scroll <= 10 && kajonaScroll !== 'margin') {
                $('ul.breadcrumb').removeClass('breadcrumbTop')
                $('#quickhelp').removeClass('quickhelpTop')
                $('.pathNaviContainer').removeClass('pathNaviContainerTop')
                kajonaScroll = 'fixed'
            }
        })
    }

    public static initBreadcrumb () {
        Breadcrumb.updateEllipsis()
        $(window).on('resize', function () {
            Breadcrumb.updateEllipsis()
        })
    }

    public static initMenu () {
        // init offacanvas menu
        $('[data-toggle="offcanvas"]').click(function () {
            $('.row-offcanvas').toggleClass('active')
        })
    }

    public static initTopNavigation () {
        // enable the top navigation
        if (!Util.isStackedDialog()) {
            $('div.navbar-fixed-top .navbar-topbar').removeClass('hidden')
            $('div.pathNaviContainer').removeClass('hidden')
        }
    }
}
;(<any>window).V4skin = V4skin
export default V4skin
