import $ from 'jquery'
import CacheManager from './CacheManager'

interface QueueEntry {
    text: string
    module: string
    params?: Array<string>
    callback?: Function
    scope?: any
}

/**
 * language module to load properties / localized strings from the backend
 */
class Lang {
    /**
     * Contains the list of lang properties which must be resolved
     *
     * @type {Array}
     */
    private static queue: Array<QueueEntry> = []

    /**
     * Searches inside the container for all data-lang-property attributes and loads the specific property and replaces the
     * html content with the value. If no container element was provided we search in the entire body. I.e.
     * <span data-lang-property="faqs:action_new_faq" data-lang-params="foo,bar"></span>
     *
     * @param {HTMLElement} containerEl
     * @param {function} onReady
     */
    public static initializeProperties (containerEl?: any, onReady?: Function) {
        if (!containerEl) {
            containerEl = 'body'
        }
        $(containerEl)
            .find('*[data-lang-property]')
            .each(function () {
                var strProperty = $(this).data('lang-property')
                if (strProperty) {
                    var arrValues = strProperty.split(':', 2)
                    if (arrValues.length === 2) {
                        var arrParams = []
                        var strParams = $(this).data('lang-params')
                        if (strParams) {
                            arrParams = strParams.split('|')
                        }

                        var objCallback = function (strText: string) {
                            $(this).html(strText)
                        }

                        Lang.queue.push({
                            text: arrValues[1],
                            module: arrValues[0],
                            params: arrParams,
                            callback: objCallback,
                            scope: this
                        })
                    }
                }
            })

        this.fetchProperties(onReady)
    }

    /**
     * Fetches a single property and passes the value to the callback as soon as the entry was loaded from the backend
     *
     * @param module
     * @param key
     * @param callback
     */
    public static fetchSingleProperty (
        module: string,
        key: string,
        callback: Function
    ) {
        this.queue.push({
            text: key,
            module: module,
            params: [],
            callback: callback
        })

        this.fetchProperties()
    }

    /**
     * Fetches all properties for the given module and stores them in the local storage. Calls then the callback with the
     * fitting property value as argument. The callback is called directly if the property exists already in the storage.
     * The requests are triggered sequential so that we send per module only one request
     *
     * @param {function} onReady
     */
    public static fetchProperties (onReady?: Function) {
        if (this.queue.length === 0) {
            if (onReady) {
                onReady.apply(this)
            }
            return
        }

        var arrData = this.queue[0]
        var strKey =
            arrData.module +
            '_' +
            KAJONA_LANGUAGE +
            '_' +
            KAJONA_BROWSER_CACHEBUSTER
        var objCache = CacheManager.get(strKey)

        if (objCache) {
            objCache = $.parseJSON(objCache)
            var strResp = null
            for (var strCacheKey in objCache) {
                if (arrData.text === strCacheKey) {
                    strResp = objCache[strCacheKey]
                }
            }
        }

        if (strResp) {
            arrData = this.queue.shift()

            strResp = this.replacePropertyParams(strResp, arrData.params)
            if (typeof arrData.callback === 'function') {
                arrData.callback.apply(arrData.scope ? arrData.scope : this, [
                    strResp,
                    arrData.module,
                    arrData.text
                ])
            }

            this.fetchProperties(onReady)
            return
        }

        $.ajax({
            type: 'POST',
            url:
                KAJONA_WEBPATH +
                '/xml.php?admin=1&module=system&action=fetchProperty',
            data: { target_module: arrData.module },
            dataType: 'json',
            success: function (objResp) {
                var arrData = Lang.queue.shift()
                if (arrData === undefined) {
                    Lang.fetchProperties(onReady)
                    return
                }

                CacheManager.set(
                    arrData.module +
                        '_' +
                        KAJONA_LANGUAGE +
                        '_' +
                        KAJONA_BROWSER_CACHEBUSTER,
                    JSON.stringify(objResp)
                )

                var strResp = null
                for (strKey in objResp) {
                    if (arrData.text === strKey) {
                        strResp = objResp[strKey]
                    }
                }
                if (strResp !== null) {
                    strResp = Lang.replacePropertyParams(
                        strResp,
                        arrData.params
                    )
                    if (typeof arrData.callback === 'function') {
                        arrData.callback.apply(
                            arrData.scope ? arrData.scope : this,
                            [strResp, arrData.module, arrData.text]
                        )
                    }
                }

                Lang.fetchProperties(onReady)
            }
        })
    }

    /**
     * Replaces all wildcards i.e. {0} with the value of the array
     *
     * @param {String} strText
     * @param {Array} arrParams
     */
    public static replacePropertyParams (
        strText: string,
        arrParams: Array<string>
    ) {
        for (var i = 0; i < arrParams.length; i++) {
            strText = strText.replace('{' + i + '}', arrParams[i])
        }
        return strText
    }
}
;(<any>window).Lang = Lang
export default Lang
