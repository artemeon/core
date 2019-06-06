import $ from 'jquery'

/**
 * Module to handle the general quickhelp entry
 */
class Quickhelp {
    public static setQuickhelp (strTitle: string, strText: string) {
        if (strText.trim() === '') {
            return
        }
        $('#quickhelp')
            .popover({
                title: strTitle,
                content: strText,
                placement: 'bottom',
                trigger: 'hover',
                html: true
            })
            .css('cursor', 'help')
            .show()
    }

    public static resetQuickhelp () {
        $('#quickhelp')
            .hide()
            .popover('destroy')
    }
}
;(<any>window).Quickhelp = Quickhelp
export default Quickhelp
