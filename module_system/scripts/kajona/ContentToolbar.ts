import $ from 'jquery'
import Util from './Util'

/**
 * The object representing a single toolbar entry
 */
class Entry {
    public strContent: string
    public strIdentifier: string
    public bitActive: boolean

    constructor (strContent: string, strIdentifier?: string, bitActive?: any) {
        this.strContent = strContent
        this.strIdentifier = strIdentifier
        this.bitActive =
            bitActive !== undefined && bitActive !== '' ? bitActive : false
    }
}

/**
 * A module to handle the global toolbar. The toolbar is made out of Entry instances, new entries may be added, old ones
 * removed. The toolbar takes care of the general visibility, empty bars will be hidden.
 */
class ContentToolbar {
    private static $objToolbarContainer = $('.contentToolbar')
    private static $objActionToolbarContainer = $(
        '.contentToolbar .navbar-inner'
    )
    private static $objToolbarList = $('.contentToolbar ul:first')

    public static Entry = Entry

    /**
     * Adds a new entry to the toolbar
     *
     * @param objEntry {Entry}
     */
    public static registerContentToolbarEntry (objEntry: Entry) {
        if (objEntry.strContent !== '') {
            this.$objToolbarContainer = $('.contentToolbar')
            this.$objToolbarList = $('.contentToolbar ul:first')
            if (this.$objToolbarContainer.hasClass('hidden')) {
                this.$objToolbarContainer.removeClass('hidden')
            }

            let strIdentifier = ''
            let strClass = ''
            if (objEntry.strIdentifier !== '') {
                strIdentifier = ' id="' + objEntry.strIdentifier + '"'
            }

            if (objEntry.bitActive) {
                strClass += ' active '
            }

            let s = $('<div/>')
                .html(objEntry.strContent)
                .text()
            this.$objToolbarList.append(
                '<li ' +
                    strIdentifier +
                    ' class="' +
                    strClass +
                    '">' +
                    s +
                    '</li>'
            )
        }
    }

    /**
     * Adds a list of entries
     * @param arrEntries {Entry[]}
     */
    public static registerContentToolbarEntries (arrEntries: Array<Entry>) {
        if (arrEntries) {
            $.each(arrEntries, function (index: any, objEntry: Entry) {
                ContentToolbar.registerContentToolbarEntry(objEntry)
            })
        }
    }

    /**
     *
     * @param $objContainer
     */
    public static registerRecordActions ($objContainer: JQuery) {
        this.$objActionToolbarContainer = $('.contentToolbar .navbar-inner')
        if (!Util.isStackedDialog()) {
            let $objNode = $('<div>')
                .attr('class', 'actionToolbar pull-right')
                .append($objContainer.children())
            this.$objActionToolbarContainer.append($objNode)
            this.showBar()
        }
    }

    /**
     * Removes a sinvle entry
     * @param strIdentifier
     */
    public static removeEntry (strIdentifier: string) {
        this.$objToolbarList = $('.contentToolbar ul:first')
        if ($('#' + strIdentifier)) {
            $('#' + strIdentifier).remove()
        }

        if (this.$objToolbarList.children().length === 0) {
            this.resetBar()
        }
    }

    /**
     * Resets the whole bar and hides it
     */
    public static resetBar () {
        this.$objToolbarList.empty()
        this.$objToolbarContainer.find('.actionToolbar').remove()
        this.$objToolbarContainer.addClass('hidden')
    }

    /**
     * Enables the bar in general
     */
    public static showBar () {
        this.$objToolbarContainer.removeClass('hidden')
    }
}
;(<any>window).ContentToolbar = ContentToolbar
export default ContentToolbar
