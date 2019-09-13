import $ from 'jquery'
import WorkingIndicator from '../../../module_system/scripts/kajona/WorkingIndicator'
import Tooltip from '../../../module_system/scripts/kajona/Tooltip'
import Ajax from 'core/module_system/scripts/kajona/Ajax'
import DialogHelper from 'core/module_v4skin/scripts/kajona/DialogHelper'
import Lang from 'core/module_system/scripts/kajona/Lang'

import { Calendar } from '@fullcalendar/core'
import dayGridPlugin from '@fullcalendar/daygrid'
import listPlugin from '@fullcalendar/list'
import deLocale from '@fullcalendar/core/locales/de'
import enLocale from '@fullcalendar/core/locales/en-gb'

class DashboardCalendar {
    public static init () {
        let calendarEl: HTMLElement = document.getElementById('dashboard-calendar')!
        let calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, listPlugin],
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listMonth'
            },
            editable: false,
            locales: [deLocale, enLocale],
            locale: KAJONA_LANGUAGE,
            eventLimit: true,
            events: KAJONA_WEBPATH + '/xml.php?admin=1&module=dashboard&action=getCalendarEvents',
            eventRender: function (info) {
                Tooltip.addTooltip(info.el, info.event.extendedProps.tooltip)
                if (info.event.extendedProps.icon) {
                    info.el.innerHTML = info.el.innerHTML.replace('$ICON', info.event.extendedProps.icon)
                }
            },
            loading: function (isLoading) {
                if (isLoading) {
                    WorkingIndicator.start()
                } else {
                    WorkingIndicator.stop()
                }
            }
        })

        calendar.render()

        $('.fc-button-group').addClass('btn-group')
        $('.fc-button').addClass('btn btn-default')
    }

    public static getICalendarURL () {
        Ajax.genericAjaxCall(
            'dashboard',
            'apiGetOrCreateICalUrl',
            {},
            function (data: any) {
                if (data) {
                    var modalContent =
                        '<div class="input-group">' +
                        '<input type="text" class="form-control" value="' + data.url + '">' +
                        '<span class="input-group-btn">' +
                        '<button class="btn btn-default copy-btn" type="button" title="" onclick="Util.copyTextToClipboard(\'' + data.url + '\')">' +
                        "<i class='kj-icon fa fa-clipboard'>" +
                        '</button>' +
                        '</span>' +
                        '</div>'

                    DialogHelper.showInfoModal('', modalContent)

                    Lang.fetchSingleProperty('system', 'copy_to_clipboard', function (value: string) {
                        $('.copy-btn').attr('title', value)
                    })

                    Lang.fetchSingleProperty('system', 'copy_page_url', function (value: string) {
                        $('#jsDialog_0_title').text(value)
                    })
                }
            },
            null,
            null,
            null,
            'json'
        )
    }
}
;(<any>window).DashboardCalendar = DashboardCalendar
export default DashboardCalendar
