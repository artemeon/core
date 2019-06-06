import $ from 'jquery'

class Breadcrumb {
    private static breadcrumbEl: JQuery<HTMLElement> = $(
        'div.pathNaviContainer ul.breadcrumb'
    )

    public static updateEllipsis () {
        this.updatePathNavigationEllipsis()
    }

    public static appendLinkToPathNavigation (linkContent: string) {
        this.breadcrumbEl = $('div.pathNaviContainer ul.breadcrumb')

        let link = $("<li class='pathentry'></li>").append(
            linkContent + '&nbsp;'
        )
        this.breadcrumbEl.append(link)
        this.updatePathNavigationEllipsis()
    }

    public static resetBar () {
        this.breadcrumbEl = $('div.pathNaviContainer ul.breadcrumb')
        this.breadcrumbEl.find('li.pathentry:not(.home)').remove()
    }

    private static updatePathNavigationEllipsis () {
        this.breadcrumbEl = $('div.pathNaviContainer ul.breadcrumb')
        let $arrPathLIs = $('.pathNaviContainer  .breadcrumb  li.pathentry')

        // first run: get the number of entries and a first styling
        let intEntries = $arrPathLIs.length
        let intWidth = this.breadcrumbEl.width()
        let intMaxWidth = Math.ceil(intWidth / intEntries)

        $arrPathLIs.css('max-width', intMaxWidth)

        // second run: calc the remaining x-space
        let intTotalUnused = this.getUnusedSpace(intMaxWidth)

        if (intTotalUnused > intMaxWidth) {
            intMaxWidth = Math.ceil(
                intWidth /
                    (intEntries - Math.floor(intTotalUnused / intMaxWidth))
            )
            $arrPathLIs.css('max-width', intMaxWidth)
        }
    }

    private static getUnusedSpace (intMaxWidth: number): number {
        let intTotalUnused = 0
        $('.pathNaviContainer  .breadcrumb  li.pathentry').each(function () {
            let $li = $(this)
            if ($li.width() < intMaxWidth) {
                intTotalUnused += intMaxWidth - $li.width()
            }
        })

        return intTotalUnused
    }
}
;(<any>window).Breadcrumb = Breadcrumb
export default Breadcrumb
