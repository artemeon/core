///<reference path="../../../_buildfiles/jstests/definitions/kajona.d.ts" />
///<amd-module name="router"/>

import * as $ from "jquery";
import Quickhelp = require("./Quickhelp");
import Tooltip = require("./Tooltip");
import Ajax = require("./Ajax");
import Util = require("./Util");

declare global {
    interface Window {
        routie: any;
    }
}

interface Callbacks {
    [key: string] : Function;
}

interface MarkedElements {
    forms: Forms
}

interface Forms {
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
    private static arrLoadCallbacks : Callbacks = {};

    /**
     * An array of callbacks which is called after the url content was loaded.
     * At this point the html was already inserted into the main content. Gets only
     * called on form submits.
     *
     * @type {Object<string, Function>}
     */
    private static arrFormCallbacks : Callbacks = {};

    /**
     * Global markers to reference on leave / save monitored elements
     * @type {{forms: {monitoredEl: null, submittedEl: null}}}
     */
    public static markedElements : MarkedElements = {
        forms : {
            monitoredEl : null,
            submittedEl : null
        }
    };

    /**
     * Global namespace to store some montitored elements
     */
    public static markerElements : MarkedElements = Router.markedElements;

    public static init() {
        routie('*', function(url: string) {
            // in case we receive an absolute url with no hash redirect the user to this url
            // since we cant resolve this url to a hash route
            if (url.indexOf(KAJONA_WEBPATH) === 0 && url.indexOf("/#") === -1) {
                location.href = url;
                return;
            }

            var objUrl = Router.generateUrl(url);

            if(!objUrl) {
                return;
            }

            Router.cleanPage();
            //moduleNavigation.setModuleActive(objUrl.module);

            Router.applyLoadCallbacks();

            //split between post and get
            if (Router.markedElements.forms.submittedEl != null) {
                var data = $(Router.markedElements.forms.submittedEl).serialize();
                Router.markedElements.forms.submittedEl = null;
                Router.markedElements.forms.monitoredEl = null;
                Ajax.loadUrlToElement('#moduleOutput', objUrl.url, data, false, 'POST', function(){
                    Router.applyFormCallbacks();
                });
            } else {

                if (Router.markedElements.forms.monitoredEl != null) {
                    if (require('forms').hasChanged(Router.markedElements.forms.monitoredEl)) {
                        var doLeave = confirm(require('forms').leaveUnsaved);
                        if (!doLeave) {
                            return;
                        }
                        Router.markedElements.forms.monitoredEl = null;
                    }
                }

                Ajax.loadUrlToElement('#moduleOutput', objUrl.url, null, true);
            }


        });
    }

    public static generateUrl(url: string) {

        //if we have a php url, return directly
        if (url.indexOf("index.php") > 0) {
            return { url: url.replace(KAJONA_WEBPATH, ''), module: ''};
        }


        // detect where the page was loaded from an iframe and thus is displayed in a dialog
        var isStackedDialog = Util.isStackedDialog();

        //strip webpaths injected into the url
        if (url.indexOf(KAJONA_WEBPATH) === 0) {
            url = url.replace(KAJONA_WEBPATH + "/#", '');
        }

        if (url.trim() === '') {
            if ($('#loginContainer').length > 0) {
                // in case we are on the login template redirect to login action
                url = "login/login";
            } else if (isStackedDialog) {
                // in case we are inside a dialog and the url is empty we dont need to load a route
                return;
            } else {
                // otherwise we load the dashboard
                url = "dashboard";
            }
        }

        if(url.charAt(0) == "/") {
            url = url.substr(1);
        }

        if (isStackedDialog && url.indexOf('peClose=1') != -1) {
            //react on peClose statements by reloading the parent view
            parent.KAJONA.admin.folderview.dialog.hide();

            if (url.indexOf('peLoad=1') != -1) {
                // in this case we want that the parent routes to the provided url
                url = url.replace("peClose=1", "");
                url = url.replace("peLoad=1", "");
                parent.routie(url);
            } else {
                parent.routie.reload();
            }
            return;
        }


        //split to get module, action and params
        var strParams = '';
        if( url.indexOf('?') > 0) {
            strParams = url.substr(url.indexOf('?')+1);
            url = url.substr(0, url.indexOf('?'));
        }

        var arrSections = url.split("/");

        var strUrlToLoad = '/index.php?admin=1&module='+arrSections[0];
        if(arrSections.length >= 2) {
            strUrlToLoad += '&action='+arrSections[1];
        }
        if(arrSections.length >= 3) {
            strUrlToLoad += '&systemid='+arrSections[2];
        }

        strUrlToLoad += "&"+strParams;
        strUrlToLoad += "&contentFill=1";

        return { url: strUrlToLoad, module: arrSections[0]};
    };

    private static cleanPage() {
        //contentToolbar.resetBar(); //TODO: aktuell in ToolkitAdmin und RequestDispatcher, muss aber in einen Callback bevor der content in das target div geschrieben wird
        //breadcrumb.resetBar();
        Quickhelp.resetQuickhelp();
        Tooltip.removeTooltip($('*[rel=tooltip]'));
        //disable visible tooltips
        $('.qtip:visible').css('display', '');
    };

    private static applyLoadCallbacks() {
        var key;
        for (key in this.arrLoadCallbacks) {
            if (typeof this.arrLoadCallbacks[key] === 'function') {
                this.arrLoadCallbacks[key]();
                // we always delete the callback after it was executed
                delete this.arrLoadCallbacks[key];
            }
        }
    };

    private static applyFormCallbacks() {
        var key;
        for (key in this.arrFormCallbacks) {
            if (typeof this.arrFormCallbacks[key] === 'function') {
                this.arrFormCallbacks[key]();
                // we always delete the callback after it was executed
                delete this.arrFormCallbacks[key];
            }
        }
    }

    public static loadUrl(strUrl: string) {
        var actionHash = strUrl;
        if (strUrl.indexOf('#') > 0) {
            var parser = document.createElement('a');
            parser.href = strUrl;
            actionHash = parser.hash;
        }

        if (actionHash === location.hash) {
            routie.reload();
        } else {
            routie(actionHash);
        }
    }

    public static reload() {
        routie.reload();
    }

    /**
     * Adds a new callback fired as soon as a new url-request is fired
     *
     * @param {String} strName
     * @param {Function} objCallback
     */
    public static registerLoadCallback(strName: string, objCallback: Function) {
        this.arrLoadCallbacks[strName] = objCallback;
    }

    /**
     * Removes a registered load-callback
     *
     * @param strName
     */
    public static removeLoadCallback(strName: string) {
        delete this.arrLoadCallbacks[strName];
    }

    /**
     * Adds a new callback after a form was submitted
     *
     * @param {String} strName
     * @param {Function} objCallback
     */
    public static registerFormCallback(strName: string, objCallback: Function) {
        this.arrFormCallbacks[strName] = objCallback;
    }

    /**
     * Removes a registered form-callback
     *
     * @param strName
     */
    public static removeFormCallback(strName: string) {
        delete this.arrFormCallbacks[strName];
    }

}

export = Router;
