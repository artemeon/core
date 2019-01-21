///<reference path="../../../_buildfiles/jstests/definitions/kajona.d.ts" />
///<amd-module name="dashboard-calendar"/>

import * as $ from "jquery";
import "fullcalendar-locale";
import WorkingIndicator = require("../../../module_system/scripts/kajona/WorkingIndicator");
import Tooltip = require("../../../module_system/scripts/kajona/Tooltip");
import Loader = require("../../../module_system/scripts/kajona/Loader");

class DashboardCalendar {

    public static init() {
        Loader.loadFile(['/core/module_dashboard/scripts/fullcalendar/fullcalendar.min.css']);

        $('#dashboard-calendar').fullCalendar({
            header: {
                left: 'prev,next',
                center: 'title',
                right: ''
            },
            editable: false,
            //theme: false,
            //lang: KAJONA_LANGUAGE,
            eventLimit: true,
            events: KAJONA_WEBPATH + '/xml.php?admin=1&module=dashboard&action=getCalendarEvents',
            eventRender: function (event, el) {
                Tooltip.addTooltip(el, event.tooltip);
                if (event.icon) {
                    el.find("span.fc-title").prepend(event.icon);
                }
            },
            loading: function (isLoading) {
                if (isLoading) {
                    WorkingIndicator.start();
                } else {
                    WorkingIndicator.stop();
                }
            }
        });
        $('.fc-button-group').removeClass().addClass('btn-group');
        $('.fc-button').removeClass().addClass('btn btn-default');
    }

}

export = DashboardCalendar;
