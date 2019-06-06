import $ from 'jquery'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import Tooltip from 'core/module_system/scripts/kajona/Tooltip'
import StatusDisplay from 'core/module_system/scripts/kajona/StatusDisplay'
import Util from 'core/module_system/scripts/kajona/Util'

/**
 * Tags-handling
 */
class Tags {
    public static createFavorite (strSystemid: string, objLink: any) {
        Ajax.genericAjaxCall('tags', 'addFavorite', strSystemid, function (
            data: any,
            status: string,
            jqXHR: XMLHttpRequest
        ) {
            Tooltip.removeTooltip($(objLink).find("[rel='tooltip']"))

            if (
                $(objLink).find("[data-kajona-icon='icon_favorite']").length > 0
            ) {
                $(objLink).html(this.createFavoriteDisabledIcon) // createFavoriteDisabledIcon set via class_module_tags_admin->renderAdditionalActions
            } else {
                $(objLink).html(this.createFavoriteEnabledIcon) // createFavoriteEnabledIcon set via class_module_tags_admin->renderAdditionalActions
            }

            Tooltip.addTooltip($(objLink).find("[rel='tooltip']"))

            Ajax.regularCallback(data, status, jqXHR)
        })
    }

    public static saveTag (
        strTagname: string,
        strSystemid: string,
        strAttribute: string
    ) {
        Ajax.genericAjaxCall(
            'tags',
            'saveTag',
            strSystemid +
                '&tagname=' +
                strTagname +
                '&attribute=' +
                strAttribute,
            function (data: any, status: string, jqXHR: XMLHttpRequest) {
                if (status === 'success') {
                    Tags.reloadTagList(strSystemid, strAttribute)
                    $('#tagname').val('')
                } else {
                    StatusDisplay.messageError(
                        '<b>Request failed!</b><br />' + data
                    )
                }
            }
        )
    }

    public static reloadTagList (strSystemid: string, strAttribute: string) {
        $('#tagsLoading_' + strSystemid).addClass('loadingContainer')

        Ajax.genericAjaxCall(
            'tags',
            'tagList',
            strSystemid + '&attribute=' + strAttribute,
            function (data: any, status: string, jqXHR: XMLHttpRequest) {
                if (status === 'success') {
                    var intStart = data.indexOf('<tags>') + 6
                    var strContent = data.substr(
                        intStart,
                        data.indexOf('</tags>') - intStart
                    )
                    $('#tagsLoading_' + strSystemid).removeClass(
                        'loadingContainer'
                    )
                    $('#tagsWrapper_' + strSystemid).html(strContent)
                    Util.evalScript(strContent)
                } else {
                    StatusDisplay.messageError(
                        '<b>Request failed!</b><br />' + data
                    )
                    $('#tagsLoading_' + strSystemid).removeClass(
                        'loadingContainer'
                    )
                }
            }
        )
    }

    public static removeTag (
        strTagId: string,
        strTargetSystemid: string,
        strAttribute: string
    ) {
        Ajax.genericAjaxCall(
            'tags',
            'removeTag',
            strTagId +
                '&targetid=' +
                strTargetSystemid +
                '&attribute=' +
                strAttribute,
            function (data: any, status: string, jqXHR: XMLHttpRequest) {
                if (status === 'success') {
                    Tags.reloadTagList(strTargetSystemid, strAttribute)
                    $('#tagname').val('')
                } else {
                    StatusDisplay.messageError(
                        '<b>Request failed!</b><br />' + data
                    )
                }
            }
        )
    }

    public static loadTagTooltipContent (
        strTargetSystemid: string,
        strAttribute: string,
        strTargetContainer: string
    ) {
        $('#' + strTargetContainer).addClass('loadingContainer')

        Ajax.genericAjaxCall(
            'tags',
            'tagList',
            strTargetSystemid + '&attribute=' + strAttribute + '&delete=false',
            function (data: any, status: string, jqXHR: XMLHttpRequest) {
                if (status === 'success') {
                    var intStart = data.indexOf('<tags>') + 6
                    var strContent = data.substr(
                        intStart,
                        data.indexOf('</tags>') - intStart
                    )
                    $('#' + strTargetContainer).removeClass('loadingContainer')
                    $('#' + strTargetContainer).html(strContent)
                    Util.evalScript(strContent)
                } else {
                    StatusDisplay.messageError(
                        '<b>Request failed!</b><br />' + data
                    )
                    $('#' + strTargetContainer).removeClass('loadingContainer')
                }
            }
        )
    }
}
;(<any>window).Tags = Tags
export default Tags
