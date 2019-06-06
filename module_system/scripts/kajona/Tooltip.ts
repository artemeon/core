import $ from 'jquery'
import Tags from 'core/module_tags/scripts/kajona/Tags'
import 'qtip2'
import 'qtip2/dist/jquery.qtip.min.css'
/**
 * Common tooltips
 */
class Tooltip {
    public static initTooltip () {
        $('*[rel=tooltip][title!=""]').qtip({
            position: {
                viewport: $('body'),
                adjust: {
                    method: 'flip'
                }
            },
            show: {
                solo: true
            },
            style: {
                classes: 'qtip-bootstrap'
            }
        })

        // tag tooltips
        $('*[rel=tagtooltip][title!=""]').each(function () {
            $(this).qtip({
                position: {
                    viewport: $('body'),
                    adjust: {
                        method: 'flip'
                    }
                },
                show: {
                    solo: true
                },
                style: {
                    classes: 'qtip-bootstrap'
                },
                content: {
                    text:
                        $(this).attr('title') +
                        "<div id='tags_" +
                        $(this).data('systemid') +
                        "' data-systemid='" +
                        $(this).data('systemid') +
                        "'></div>"
                },
                events: {
                    render: function (event, api: any) {
                        Tags.loadTagTooltipContent(
                            $(api.elements.content)
                                .find('div')
                                .data('systemid'),
                            '',
                            $(api.elements.content)
                                .find('div')
                                .attr('id')
                        )
                    }
                }
            })
        })
    }

    public static addTooltip (objElement: any, strText?: string) {
        if (strText) {
            $(objElement).qtip({
                position: {
                    viewport: $('body'),
                    adjust: {
                        method: 'flip'
                    }
                },
                show: {
                    solo: true
                },
                style: {
                    classes: 'qtip-bootstrap'
                },
                content: {
                    text: strText
                }
            })
        } else {
            $(objElement).qtip({
                style: {
                    classes: 'qtip-bootstrap'
                }
            })
        }
    }

    public static removeTooltip (objElement: any) {
        $(objElement).qtip('destroy')
    }
}
;(<any>window).Tooltip = Tooltip
export default Tooltip
