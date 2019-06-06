import $ from 'jquery'
import Router from 'core/module_system/scripts/kajona/Router'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import Tooltip from 'core/module_system/scripts/kajona/Tooltip'
import StatusDisplay from 'core/module_system/scripts/kajona/StatusDisplay'

class Search {
    public static triggerFullSearch () {
        var strQuery = $('#search_query').val()
        if (strQuery === '') return

        $('#search_container').html('<div class="loadingContainer"></div>')

        var filtermodules = ''
        $('input[name=search_formfiltermodules\\[\\]]:checked').each(
            function () {
                if (filtermodules !== '') filtermodules += ','

                filtermodules += $(this).val()
            }
        )

        var startdate = $('#search_changestartdate').val()
        var enddate = $('#search_changeenddate').val()
        var userfilter = $('#search_formfilteruser_id').val()

        Router.markerElements.forms.monitoredEl = null

        Ajax.genericAjaxCall(
            'search',
            'renderSearch',
            '&search_query=' +
                encodeURIComponent('' + strQuery) +
                '&filtermodules=' +
                filtermodules +
                '&search_changestartdate=' +
                startdate +
                '&search_changeenddate=' +
                enddate +
                '&search_formfilteruser_id=' +
                userfilter +
                '',
            function (data: any, status: string, jqXHR: XMLHttpRequest) {
                var intStart
                if (status === 'success') {
                    intStart = data.indexOf('[CDATA[') + 7
                    $('#search_container').html(
                        data.substr(
                            intStart,
                            data.lastIndexOf(']]>') - intStart
                        )
                    )
                    if (data.indexOf('[CDATA[') < 0) {
                        intStart = data.indexOf('<error>') + 7
                        $('#search_container').html(
                            data.substr(
                                intStart,
                                data.indexOf('</error>') - intStart
                            )
                        )
                    }
                    Tooltip.initTooltip()
                } else {
                    StatusDisplay.messageError(
                        '<b>Request failed!</b><br />' + data
                    )
                }
            }
        )
    }
}
;(<any>window).Search = Search
export default Search
