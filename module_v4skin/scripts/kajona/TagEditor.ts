import $ from 'jquery'
import 'jquery-tageditor/jquery.caret.min'
import 'jquery-tageditor/jquery.tag-editor.min'

import V4skin from './V4skin'
import WorkingIndicator from 'core/module_system/scripts/kajona/WorkingIndicator'
import Util from 'core/module_system/scripts/kajona/Util'

class TagEditor {
    public static updateMandatoryRendering ($objInput: JQuery) {
        var $tagInput = $objInput.closest('.form-group').find('.tag-editor')
        if ($tagInput && $objInput.hasClass('mandatoryFormElement')) {
            $tagInput.addClass('mandatoryFormElement')
        }
    }

    /**
     * initializes the tag-editor for a given input element
     * @param strElementId
     * @param strSource
     * @param initialTags
     * @param onChangeCallback
     */
    public static init (
        strElementId: string,
        strSource: string,
        initialTags: Array<string>,
        onChangeCallback: Function
    ) {
        // eslint-disable-next-line new-cap
        let objConfig: JQueryUI.AutocompleteOptions = new V4skin.defaultAutoComplete()

        objConfig.search = function (event: any, ui: any) {
            if (event.target.value.length < 2) {
                event.stopPropagation()
                return false
            }
            $(this)
                .closest('ul.tag-editor')
                .parent()
                .find('.loading-feedback')
                .html('<i class="fa fa-spinner fa-spin"></i>')
            WorkingIndicator.getInstance().start()
        }

        objConfig.response = function (event: any, ui: any) {
            $(this)
                .closest('ul.tag-editor')
                .parent()
                .find('.loading-feedback')
                .html('')
            WorkingIndicator.getInstance().stop()
        }

        objConfig.select = function (event: any, ui: any) {
            var found = false
            $('#' + strElementId + '-list')
                .find('input')
                .each(function () {
                    if ($(this).val() === ui.item.systemid) {
                        found = true
                    }
                })
            if (!found) {
                $('#' + strElementId + '-list').append(
                    '<input type="hidden" name="' +
                        strElementId +
                        '_id[]" value="' +
                        ui.item.systemid +
                        '" data-title="' +
                        ui.item.title +
                        '" data-kajona-initval="" />'
                )
            }
        }

        objConfig.create = function (event: any, ui: any) {
            $(this).data('ui-autocomplete')._renderItem = function (
                ul: any,
                item: any
            ) {
                return $('<li></li>')
                    .data('ui-autocomplete-item', item)
                    .append(
                        "<div class='ui-autocomplete-item'>" +
                            item.icon +
                            item.title +
                            '</div>'
                    )
                    .appendTo(ul)
            }
        }

        objConfig.source = function (request: any, response: Function) {
            $.ajax({
                url: strSource,
                type: 'POST',
                dataType: 'json',
                data: {
                    filter: request.term
                },
                success: function (resp) {
                    if (resp) {
                        // replace commas
                        for (var i = 0; i < resp.length; i++) {
                            // eslint-disable-next-line no-useless-escape
                            resp[i].title = resp[i].title.replace(/\,/g, '')
                            // eslint-disable-next-line no-useless-escape
                            resp[i].value = resp[i].value.replace(/\,/g, '')
                        }
                    }
                    response.call(this, resp)
                }
            })
        }

        var $objInput = $('#' + strElementId)
        $objInput.tagEditor({
            initialTags: initialTags,
            forceLowercase: false,
            sortable: false,
            autocomplete: objConfig,
            onChange: function (field: any, editor: any, tags: Array<string>) {
                // sync with exiting list to remove hidden input elements
                $('#' + strElementId + '-list')
                    .find('input')
                    .each(function () {
                        if (!Util.inArray($(this).data('title'), tags)) {
                            $(this).remove()
                        }
                    })
                onChangeCallback()
            },
            beforeTagSave: function (
                field: any,
                editor: any,
                tags: Array<string>,
                tag: string,
                val: any
            ) {
                var found = false
                $('#' + strElementId + '-list')
                    .find('input')
                    .each(function () {
                        if ($(this).data('title') === val) {
                            found = true
                        }
                    })
                if (!found) {
                    return false
                }
            },
            beforeTagDelete: function (
                field: any,
                editor: any,
                tags: Array<string>,
                val: any
            ) {
                $('#' + strElementId + '-list')
                    .find('input')
                    .each(function () {
                        if ($(this).data('title') === val) {
                            $(this).remove()
                        }
                    })
            }
        })
        $objInput
            .parent()
            .find('ul.tag-editor')
            .after(
                "<span class='form-control-feedback loading-feedback' style='right: 15px;'><i class='fa fa-keyboard-o'></i></span>"
            )

        // listen on mandatory change events
        $objInput.on('kajona.forms.mandatoryAdded', function () {
            TagEditor.updateMandatoryRendering($(this))
        })
        TagEditor.updateMandatoryRendering($objInput)

        // highlight current input
        $('#tageditor_' + strElementId + ' .tag-editor').on(
            'click',
            function () {
                $('#tageditor_' + strElementId)
                    .find('ul.tag-editor')
                    .addClass('active')
            }
        )

        // set all othter inactive
        $('.tag-editor').on('click', function (e, el) {
            var objOuter = $(this)
            $('.tag-editor.active').each(function () {
                if (
                    $(this)
                        .closest('.inputTagEditor')
                        .attr('id') !==
                    objOuter.closest('.inputTagEditor').attr('id')
                ) {
                    $(this).removeClass('active')
                }
            })
        })

        // general outer click
        $('*:not(.tag-editor)').on('click', function () {
            if ($('.tag-editor').hasClass('active')) {
                $('.tag-editor').removeClass('active')
            }
        })
    }
}
;(<any>window).TagEditor = TagEditor
export default TagEditor
