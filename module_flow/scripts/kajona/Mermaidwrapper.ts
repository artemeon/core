import $ from 'jquery'


//import mermaid from 'mermaid'


class Mermaidwrapper {
    private tempSystemid: string
    private linkTransition: string
    private linkTransitionAction: string
    private linkTransitionCondition: string

    private langAction: string
    private langCondition: string

    private isIe: boolean = false


    constructor(tempSystemid: string, linkTransition: string, linkTransitionAction: string, linkTransitionCondition: string, langAction: string, langCondition: string, isIe: boolean
    ) {
        this.tempSystemid = tempSystemid
        this.linkTransition = linkTransition
        this.linkTransitionAction = linkTransitionAction
        this.linkTransitionCondition = linkTransitionCondition
        this.langAction = langAction
        this.langCondition = langCondition
        this.isIe = isIe
    }


    public renderGraph() {

        return import(/* webpackChunkName: "mermaid" */ 'mermaid').then(({default: mermaid}) => {

            if (this.isIe) {
                mermaid.initialize({
                    flowchart: {
                        htmlLabels:false,
                        useMaxWidth:true
                    }
                })
            } else {
                mermaid.initialize({})
            }
            mermaid.init(undefined, $("#flow-graph"))

            let me = this
            $('div > span.edgeLabel > span').each(function () {
                var data = $(this).data()
                var transitionId
                for (var key in data) {
                    transitionId = key
                }

                var actionLink = me.linkTransitionAction.replace(me.tempSystemid, transitionId)
                var conditionLink = me.linkTransitionCondition.replace(me.tempSystemid, transitionId)

                var html = ''
                if (actionLink) {
                    html += '<a href="' + actionLink + '" title="' + me.langAction + '"><i class="kj-icon fa fa-play-circle-o"></i></a>'
                    html += ' '
                }
                if (conditionLink) {
                    html += '<a href="' + conditionLink + '" title="' + me.langCondition + '"><i class="kj-icon fa fa-table"></i></a>'
                }

                $(this).html(html)
            })

            $('.node').on('click', function () {
                var statusId = $(this).attr('id')
                var link = me.linkTransition
                if (link) {
                    location.href = link.replace(me.tempSystemid, statusId)
                }
            })

            $('.node div').css('cursor', 'pointer')
        })
    }
}


;(<any>window).Mermaidwrapper = Mermaidwrapper
export default Mermaidwrapper
