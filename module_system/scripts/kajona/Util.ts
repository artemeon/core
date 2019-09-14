import $ from 'jquery'
import * as toastr from 'toastr'
import Lang from 'core/module_system/scripts/kajona/Lang'

declare global {
    interface Window {
        KAJONA: Kajona
        $: JQueryStatic
        execScript: any
    }
}

class Util {
    public static isEllipsisActive (element: HTMLElement) {
        return element.offsetWidth + 2 < element.scrollWidth
    }

    /**
     * Function to get the element from the current opener.
     *
     * @param strElementId
     * @returns {*}
     */
    public static getElementFromOpener (strElementId: string) {
        try {
            if (window.opener && window.opener.KAJONA) {
                return $('#' + strElementId, window.opener.document)
            }
        } catch (ex) {
        }
        if (parent && parent !== window) {
            if (parent.KAJONA.util.folderviewHandler) {
                // in case we are coming from a nested view
                var el = parent
                    .$('#folderviewDialog_iframe')
                    .contents()
                    .find('#' + strElementId)
                if (el.length > 0) {
                    return el
                }
            }
            return parent.$('#' + strElementId)
        }
        return $('#' + strElementId)
    }

    /**
     * Function to evaluate the script-tags in a passed string, e.g. loaded by an ajax-request
     *
     * @param {String} scripts
     * @see http://wiki.ajax-community.de/know-how:nachladen-von-javascript
     */
    public static evalScript (scripts: string) {
        try {
            if (scripts !== '') {
                var script = ''
                scripts = scripts.replace(
                    /<script[^>]*>([\s\S]*?)<\/script>/gi,
                    function () {
                        if (scripts !== null) script += arguments[1] + '\n'
                        return ''
                    }
                )
                if (script) {
                    window.execScript
                        ? window.execScript(script)
                        : window.setTimeout(script, 0)
                }
            }
            return false
        } catch (e) {
            alert(e)
        }
    }

    public static isTouchDevice () {
        return 'ontouchstart' in window ? 1 : 0
    }

    /**
     * Checks if the given array contains the given string
     *
     * @param {String} strNeedle
     * @param {String[]} arrHaystack
     */
    public static inArray (strNeedle: string, arrHaystack: Array<any>) {
        for (var i = 0; i < arrHaystack.length; i++) {
            if (arrHaystack[i] === strNeedle) {
                return true
            }
        }
        return false
    }

    /**
     * Detects if the current viewport is embedded in an iframe or a popup
     * @returns {boolean}
     */
    public static isStackedDialog () {
        return !!(
            window.frameElement &&
            window.frameElement.nodeName &&
            window.frameElement.nodeName.toLowerCase() === 'iframe'
        )
    }

    /**
     * Used to show/hide an html element
     *
     * @param {String} strElementId
     * @param {Function} objCallbackVisible
     * @param {Function} objCallbackInvisible
     */
    public static fold (
        strElementId: string,
        objCallbackVisible: Function,
        objCallbackInvisible: Function
    ) {
        var $element = $('#' + strElementId)
        if ($element.hasClass('folderHidden')) {
            $element.removeClass('folderHidden')
            $element.addClass('folderVisible')
            if ($.isFunction(objCallbackVisible)) {
                objCallbackVisible(strElementId)
            }
        } else {
            $element.removeClass('folderVisible')
            $element.addClass('folderHidden')
            if ($.isFunction(objCallbackInvisible)) {
                objCallbackInvisible(strElementId)
            }
        }
    }

    /**
     * Used to show/hide an html element and switch an image (e.g. a button)
     *
     * @param {String} strElementId
     * @param {String} strImageId
     * @param {String} strImageVisible
     * @param {String} strImageHidden
     */
    public static foldImage (
        strElementId: string,
        strImageId: string,
        strImageVisible: string,
        strImageHidden: string
    ) {
        var element = document.getElementById(strElementId)
        var image = document.getElementById(strImageId)
        if (element.style.display === 'none') {
            element.style.display = 'block'
            image.setAttribute('src', strImageVisible)
        } else {
            element.style.display = 'none'
            image.setAttribute('src', strImageHidden)
        }
    }

    public static setBrowserFocus (strElementId: string) {
        $(function () {
            try {
                let focusElement = $('#' + strElementId)
                if (focusElement.hasClass('inputWysiwyg')) {
                    CKEDITOR.config.startupFocus = true
                } else {
                    focusElement.focus()
                }
            } catch (e) {}
        })
    }

    /**
     * Simple method to generate a system id
     */
    public static generateSystemId () {
        var chars = [
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9'
        ]
        var result = ''

        for (var i = 0; i < 20; i++) {
            var k = parseInt('' + chars.length * Math.random())
            if (chars[k]) {
                result += chars[k]
            }
        }

        return result
    }

    /**
     * Converts a string into an integer representation of the string regarding thousands and decimal separator.
     * e.g. "1.000.000" => "1000000"
     * e.g. "7.000" => "7000"
     *
     * @param objValue
     * @param strStyleThousand
     * @param strStyleDecimal
     * @returns {string}
     */
    public static convertValueToInt (
        objValue: any,
        strStyleThousand: string,
        strStyleDecimal: string
    ) {
        var strValue = objValue + ''

        var strRegExpThousand = new RegExp('\\' + strStyleThousand, 'g')
        strValue = strValue.replace(strRegExpThousand, '') // remove first thousand separator

        return parseInt(strValue)
    }

    /**
     * Converts a string into a float representation of the string regarding thousands and decimal separator.
     * e.g. "1.000.000,23" => "1000000.23"
     *
     * @param objValue
     * @param strStyleThousand
     * @param strStyleDecimal
     * @returns {string}
     */
    public static convertValueToFloat (
        objValue: any,
        strStyleThousand: string,
        strStyleDecimal: string
    ) {
        var strValue = objValue + ''

        var strRegExpThousand = new RegExp('\\' + strStyleThousand, 'g')
        var strRegExpDecimal = new RegExp('\\' + strStyleDecimal, 'g')
        var strRegExpComma = new RegExp('\\,', 'g')

        strValue = strValue.replace(strRegExpThousand, '') // remove first thousand separator
        strValue = strValue.replace(strRegExpComma, '.') // replace decimal with decimal point for db
        strValue = strValue.replace(strRegExpDecimal, '.') // replace decimal with decimal point for db

        return parseFloat(strValue)
    }

    /**
     * Formats a number into a formatted string
     * @see http://stackoverflow.com/questions/149055/how-can-i-format-numbers-as-money-in-javascript
     *
     * .format(12345678.9, 2, 3, '.', ',');  // "12.345.678,90"
     * .format(123456.789, 4, 4, ' ', ':');  // "12 3456:7890"
     * .format(12345678.9, 0, 3, '-');       // "12-345-679"
     *
     * @param floatValue mixed: number to be formatted
     * @param intDecimalLength integer: length of decimal
     * @param intLengthWholePart integer: length of whole part
     * @param strDelimiterSections mixed: sections delimiter
     * @param strDelimiterDecimal mixed: decimal delimiter
     */
    public static formatNumber (
        floatValue: number,
        intDecimalLength: number,
        intLengthWholePart: number,
        strDelimiterSections: string,
        strDelimiterDecimal: string
    ) {
        var re =
                '\\d(?=(\\d{' +
                (intLengthWholePart || 3) +
                '})+' +
                (intDecimalLength > 0 ? '\\D' : '$') +
                ')'
        var num = floatValue.toFixed(Math.max(0, ~~intDecimalLength))

        return (strDelimiterDecimal
            ? num.replace('.', strDelimiterDecimal)
            : num
        ).replace(new RegExp(re, 'g'), '$&' + (strDelimiterSections || ','))
    }

    /**
     * Formats a kajona date format to a specific javascript format string
     *
     * @param {string} format
     * @param {string} type
     */
    public static transformDateFormat (format: string, type: string) {
        if (type === 'bootstrap-datepicker') {
            return format
                .replace('d', 'dd')
                .replace('m', 'mm')
                .replace('Y', 'yyyy')
        } else if (type === 'momentjs') {
            return format
                .replace('d', 'DD')
                .replace('m', 'MM')
                .replace('Y', 'YYYY')
        } else {
            return format
        }
    }

    /**
     * Extracts an query parameter from the location query string
     *
     * @param {string} name
     * @returns string
     */
    public static getQueryParameter (name: string) {
        var pos = location.search.indexOf('&' + name + '=')
        if (pos !== -1) {
            var endPos = location.search.indexOf('&', pos + 1)
            if (endPos === -1) {
                return location.search.substr(pos + name.length + 2)
            } else {
                return location.search.substr(
                    pos + name.length + 2,
                    endPos - (pos + name.length + 2)
                )
            }
        }
        return null
    }

    /**
     * Returns all available query parameters from the has route
     *
     * @return object
     */
    public static getQueryParameters (): any {
        let result: any = {}
        let hash = location.hash
        let pos = hash.indexOf('?')
        if (pos !== -1) {
            let rawQuery = hash.substr(pos + 1)
            let parts = rawQuery.split('&')
            for (let i = 0; i < parts.length; i++) {
                let kv = parts[i].split('=', 2)
                result[kv[0]] = kv[1]
            }
        }

        return result
    }

    /**
     * Gets the jQuery object
     *
     * @param objElement - my be a jquery object or an id selector
     */
    public static getElement (objElement: any): JQuery {
        // If objElement is already a jQuery object
        if (objElement instanceof jQuery) {
            return <JQuery>objElement
        } else {
            // Convert to jQuery object
            return $(objElement)
        }
    }

    /**
     * Copies text to clipboard
     *
     * @param text
     */
    public static copyTextToClipboard (text: string) {
        let textArea = document.createElement('textarea')
        textArea.style.background = 'transparent'
        textArea.value = text
        document.body.appendChild(textArea)
        textArea.select()
        try {
            document.execCommand('copy')
            Lang.fetchSingleProperty('system', 'link_was_copied', function (
                value: string
            ) {
                toastr.success(value)
            })
        } catch (err) {}
        document.body.removeChild(textArea)
    }

    private static element = document.createElement('div')

    /**
     * decodes html entites, call it just like
     * util decodeHTMLEntities(strText)
     *
     * Taken from stackoverflow
     * @see http://stackoverflow.com/a/9609450
     * @see http://stackoverflow.com/questions/5796718/html-entity-decode/9609450#9609450
     *
     */
    public static decodeHtmlEntities (strText: string) {
        // this prevents any overhead from creating the object each time
        if (strText && typeof strText === 'string') {
            // strip script/html tags
            strText = strText.replace(
                /<script[^>]*>([\S\s]*?)<\/script>/gim,
                ''
            )
            strText = strText.replace(
                /<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gim,
                ''
            )
            this.element.innerHTML = strText
            strText = this.element.textContent
            this.element.textContent = ''
        }

        return strText
    }
}
;(<any>window).Util = Util
export default Util
