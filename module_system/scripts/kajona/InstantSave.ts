import $ from 'jquery'
import Ajax from './Ajax'

/**
 * The saveIndicator is used to show a working-indicator associated with a ui element.
 * currently the indicator may represent various states:
 * - showProgress showing the indicator
 * - addClass adding a class, e.g. to indicate a new status
 * - hide destroying the indicator completely
 *
 * @param $objSourceElement
 */
class SaveIndicator {
    private objDiv: JQuery
    private objSourceElement: JQuery<any>

    constructor (objSourceElement: JQuery<any>) {
        this.objSourceElement = objSourceElement
    }

    public showProgress () {
        this.objDiv = $('<div>').addClass('peProgressIndicator peSaving')
        $('body').append(this.objDiv)
        this.objDiv
            .css(
                'left',
                this.objSourceElement.offset().left +
                    this.objSourceElement.width() +
                    10
            )
            .css('top', this.objSourceElement.offset().top)
    }

    public addClass (strClass: string) {
        this.objDiv.addClass(strClass)
    }

    public hide () {
        this.objSourceElement.removeClass('peFailed')
        this.objDiv.remove()
        this.objDiv = null
    }
}

/**
 * The instant save module is used to save changes made to input elements directly to the baackend.
 * Therefore an attribute data-kajona-instantsave='systemid#strPropertyname' is required to be present at the
 * input element.
 * On success or error, the handler throws a 'kajona.instantsave.updated' event.
 * Register for them like this:
 * $('#id').on('kajona.instantsave.updated', function(){console.log('update registered')});
 */
class InstantSave {
    private static saveChangeHandler () {
        var $objChanged = $(this)
        var keySplitted = $objChanged.data('kajona-instantsave').split('#')

        $objChanged.addClass('peSaving')
        var objStatusIndicator = new SaveIndicator($objChanged)

        objStatusIndicator.showProgress()
        Ajax.genericAjaxCall(
            'system',
            'updateObjectProperty',
            keySplitted[0] +
                '&property=' +
                keySplitted[1] +
                '&value=' +
                $objChanged.val(),
            null,
            function () {
                objStatusIndicator.addClass('peSaved')
                window.setTimeout(function () {
                    objStatusIndicator.hide()
                }, 5000)
                $objChanged.trigger('kajona.instantsave.updated', [
                    'success',
                    keySplitted[0]
                ])
            },
            function () {
                objStatusIndicator.addClass('peFailed')
                window.setTimeout(function () {
                    objStatusIndicator.hide()
                }, 5000)
                $objChanged.trigger('kajona.instantsave.updated', [
                    'error',
                    keySplitted[0]
                ])
            }
        )
    }

    private static scanElements () {
        $('[data-kajona-instantsave][data-kajona-instantsave != ""]').each(
            function (key, value) {
                if (!$(this)[0].hasAttribute('data-kajona-instantsave-init')) {
                    $(this).on('change', InstantSave.saveChangeHandler)
                    $(this).attr('data-kajona-instantsave-init', 'true')
                }
            }
        )
    }

    public static init () {
        this.scanElements()
    }
}
;(<any>window).InstantSave = InstantSave
export default InstantSave
