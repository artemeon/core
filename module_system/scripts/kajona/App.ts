/// <reference path="../../../_buildfiles/definitions/kajona.d.ts" />
import Router from './Router'
import 'jquery-ui.custom'
import 'jquery-ui-touch-punch'
import * as toastr from 'toastr'
import V4skin from 'core/module_v4skin/scripts/kajona/V4skin'
import Dialog from 'core/module_v4skin/scripts/kajona/Dialog'
import Folderview from './Folderview'
import DialogHelper from 'core/module_v4skin/scripts/kajona/DialogHelper'
import VueMain from './VueMainComponent/VueMain.vue'
import Vue from 'vue'
import store from './VueMainComponent/Store'
import VueRouter from './VueMainComponent/VueRouter'
import i18n from './VueMainComponent/VueLang'
import GlobalAxiosConfig from './GlobalAxiosConfig'
import VueI18n from 'vue-i18n'
import KeymapsController from './KeymapsController'
import StringPolyfill from './polyfills/StringPolyfill'
import EventDispatcher from './EventDispatcher'

declare global {
    interface Window {
        i18n : VueI18n
        VueContainer : Vue
        KAJONA: Kajona
        // eslint-disable-next-line camelcase
        jsDialog_0: Dialog
        // eslint-disable-next-line camelcase
        jsDialog_1: Dialog
        // eslint-disable-next-line camelcase
        jsDialog_2: Dialog
        // eslint-disable-next-line camelcase
        jsDialog_3: Dialog
    }
}

class App {
    public static init () {
        // backwards compatibility
        window.KAJONA = {
            util: {
                dialogHelper: DialogHelper,
                folderviewHandler: null
            },
            portal: {
                lang: {}
            },
            admin: {
                folderview: {
                    dialog: new Dialog('folderviewDialog', 0)
                },
                lang: {},
                forms: {
                    submittedEl: null,
                    monitoredEl: null
                }
            }
        }

        // load polyfills
        StringPolyfill.init()

        Folderview.dialog = KAJONA.admin.folderview.dialog

        // register the global router
        Router.init()

        // V4skin
        V4skin.initCatComplete()
        V4skin.initPopover()
        V4skin.initScroll()
        V4skin.initBreadcrumb()
        V4skin.initMenu()
        V4skin.initTopNavigation()

        // BC layer

        /** @deprecated */
        window.jsDialog_0 = new Dialog('jsDialog_0', 0)
        /** @deprecated */
        window.jsDialog_1 = new Dialog('jsDialog_1', 1)
        /** @deprecated */
        window.jsDialog_2 = new Dialog('jsDialog_2', 2)
        /** @deprecated */
        window.jsDialog_3 = new Dialog('jsDialog_3', 3)

        // configure toastr global
        toastr.options.positionClass = 'toast-bottom-right'
        // Axios Wrapper
        KeymapsController.init()
        GlobalAxiosConfig.init()
    }
    public static initVue (): void {
        Vue.config.productionTip = false
        if (process.env.NODE_ENV === 'development') {
            Vue.config.devtools = true
        }

        window.i18n = i18n
        window.VueContainer = new Vue({
            el: '#vueContainer',
            // @ts-ignore
            router: VueRouter,
            // @ts-ignore
            store: store,
            i18n: i18n,
            render: h => h(VueMain)
        })
    }
}

// register all the global dependencies in window object
;(<any>window).App = App
;(<any>window).$ = (<any>window).jQuery = require('jquery')
;

(<any>window).kajonaCreateEvent = EventDispatcher.kajonaCreateEvent

// (<any>window).moment = moment
export default App
