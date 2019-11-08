import $ from 'jquery'
import Quickhelp from './Quickhelp'
import Tooltip from './Tooltip'
import Ajax from './Ajax'
import Util from './Util'
import Forms from './Forms'
import routie from 'routie'
import ContentToolbar from './ContentToolbar'
import BreadCrumb from './Breadcrumb'

declare global {
    interface Window {
        routie: any
    }
}

interface Callbacks {
    [key: string]: Function
}

interface MarkedElements {
    forms: FormsInterface
}

interface FormsInterface {
    monitoredEl: JQuery<HTMLElement>
    submittedEl: HTMLFormElement
}

class Router {
    /**
     * An array / list of callbacks to be fired as soon as a url is being loaded.
     * There's no indication on whether loading finished or not.
     *
     * @type {Object<string, Function>}
     */
    private static arrLoadCallbacks: Callbacks = {}

    /**
     * An array of callbacks which is called after the url content was loaded.
     * At this point the html was already inserted into the main content. Gets only
     * called on form submits.
     *
     * @type {Object<string, Function>}
     */
    private static arrFormCallbacks: Callbacks = {}

    /**
     * Global markers to reference on leave / save monitored elements
     * @type {{Interface: {monitoredEl: null, submittedEl: null}}}
     */
    public static markedElements: MarkedElements = {
        forms: {
            monitoredEl: null,
            submittedEl: null
        }
    }

    /**
     * Global namespace to store some montitored elements
     */
    public static markerElements: MarkedElements = Router.markedElements

    public static init () {
        routie('*', Router.defaultRoutieCallback)
    }

    public static defaultRoutieCallback (url: string) {
        console.log('parent vue',document.querySelectorAll('[data-field-id]'))
        if (url.includes('/vm/') && KAJONA_ACCESS_TOKEN) {
            return
        } else if (!KAJONA_ACCESS_TOKEN) {
            url = 'login/login'
        }
        // in case we receive an absolute url with no hash redirect the user to this url
        // since we cant resolve this url to a hash route
        if (url.indexOf(KAJONA_WEBPATH) === 0 && url.indexOf('/#') === -1) {
            location.href = url
            return
        }

        var objUrl = Router.generateUrl(url)

        if (!objUrl) {
            return
        }

        Router.cleanPage()
        // moduleNavigation.setModuleActive(objUrl.module);

        Router.applyLoadCallbacks()

        // split between post and get
        if (Router.markedElements.forms.submittedEl != null) {
            var data = $(Router.markedElements.forms.submittedEl).serialize()
            Router.markedElements.forms.submittedEl = null
            Router.markedElements.forms.monitoredEl = null
            Ajax.loadUrlToElement(
                '#moduleOutput',
                objUrl.url,
                data,
                false,
                'POST',
                function () {
                    Router.applyFormCallbacks()
                }
            )
        } else {
            if (Router.markedElements.forms.monitoredEl != null) {
                if (Forms.hasChanged(Router.markedElements.forms.monitoredEl)) {
                    var doLeave = confirm(Forms.leaveUnsaved)
                    if (!doLeave) {
                        return
                    }
                    Router.markedElements.forms.monitoredEl = null
                }
            }

            Ajax.loadUrlToElement('#moduleOutput', objUrl.url, null, true)
        }
    }

    public static generateUrl (url: string) {
        // if we have a php url, return directly
        if (url.indexOf('index.php') > 0) {
            return { url: url.replace(KAJONA_WEBPATH, ''), module: '' }
        }

        // detect where the page was loaded from an iframe and thus is displayed in a dialog
        var isStackedDialog = Util.isStackedDialog()

        // strip webpaths injected into the url
        if (url.indexOf(KAJONA_WEBPATH) === 0) {
            url = url.replace(KAJONA_WEBPATH + '/#', '')
        }
        if (url.charAt(0) === '/') {
            url = url.substr(1)
        }
        if (url.trim() === '') {
            if ($('#loginContainer').length > 0) {
                // in case we are on the login template redirect to login action
                url = 'login/login'
            } else if (isStackedDialog) {
                // in case we are inside a dialog and the url is empty we dont need to load a route
                return
            } else {
                // otherwise we load the dashboard
                url = 'dashboard'
            }
        }

        if (isStackedDialog && url.indexOf('peClose=1') !== -1) {
            // react on peClose statements by reloading the parent view
            parent.KAJONA.admin.folderview.dialog.hide()

            if (url.indexOf('peLoad=1') !== -1) {
                // in this case we want that the parent routes to the provided url
                url = url.replace('peClose=1', '')
                url = url.replace('peLoad=1', '')

                // @ts-ignore
                parent.window.Router.loadUrl(url)

            } else {
                // @ts-ignore
                parent.window.Router.reload()
            }
            return
        }

        // split to get module, action and params
        var strParams = ''
        if (url.indexOf('?') > 0) {
            strParams = url.substr(url.indexOf('?') + 1)
            url = url.substr(0, url.indexOf('?'))
        }

        var arrSections = url.split('/')

        var strUrlToLoad = '/index.php?admin=1&module=' + arrSections[0]
        if (arrSections.length >= 2) {
            strUrlToLoad += '&action=' + arrSections[1]
        }
        if (arrSections.length >= 3) {
            strUrlToLoad += '&systemid=' + arrSections[2]
        }

        if (strParams !== '') {
            strUrlToLoad += '&' + strParams
        }
        strUrlToLoad += '&contentFill=1'

        return { url: strUrlToLoad, module: arrSections[0] }
    }
    /**
    * clean contenttoolbar/breadcrumb/quickhelp/tooltip before route enter
    * @param vueClean  : if set to true it will clean contenttoolbar and breadcrum , else this will be done in the backend
     */
    public static cleanPage (vueClean : boolean = false) {
        // TODO: aktuell in ToolkitAdmin und RequestDispatcher, muss aber in einen Callback bevor der content in das target div geschrieben wird
        if (vueClean) {
            ContentToolbar.resetBar()
            BreadCrumb.resetBar()
            document.getElementById('moduleOutput').innerHTML = ''
        }
        Quickhelp.resetQuickhelp()
        Tooltip.removeTooltip($('*[rel=tooltip]'))
        // disable visible tooltips
        $('.qtip:visible').css('display', '')
    }

    private static applyLoadCallbacks () {
        var key
        for (key in this.arrLoadCallbacks) {
            if (typeof this.arrLoadCallbacks[key] === 'function') {
                this.arrLoadCallbacks[key]()
                // we always delete the callback after it was executed
                delete this.arrLoadCallbacks[key]
            }
        }
    }

    private static applyFormCallbacks () {
        var key
        for (key in this.arrFormCallbacks) {
            if (typeof this.arrFormCallbacks[key] === 'function') {
                this.arrFormCallbacks[key]()
                // we always delete the callback after it was executed
                delete this.arrFormCallbacks[key]
            }
        }
    }

    public static loadUrl (strUrl: string) {
        var actionHash = strUrl
        if (strUrl.indexOf('#') > 0) {
            var parser = document.createElement('a')
            parser.href = strUrl
            actionHash = parser.hash
        }

        if (actionHash === location.hash) {
            routie.reload()
        } else {
            routie(actionHash)
        }
    }

    public static reload () {
        routie.reload()
    }

    /**
     * Adds a new callback fired as soon as a new url-request is fired
     *
     * @param {String} strName
     * @param {Function} objCallback
     */
    public static registerLoadCallback (strName: string, objCallback: Function) {
        this.arrLoadCallbacks[strName] = objCallback
    }

    /**
     * Removes a registered load-callback
     *
     * @param strName
     */
    public static removeLoadCallback (strName: string) {
        delete this.arrLoadCallbacks[strName]
    }

    /**
     * Adds a new callback after a form was submitted
     *
     * @param {String} strName
     * @param {Function} objCallback
     */
    public static registerFormCallback (strName: string, objCallback: Function) {
        this.arrFormCallbacks[strName] = objCallback
    }

    /**
     * Removes a registered form-callback
     *
     * @param strName
     */
    public static removeFormCallback (strName: string) {
        delete this.arrFormCallbacks[strName]
    }
}
;(<any>window).Router = Router
export default Router
