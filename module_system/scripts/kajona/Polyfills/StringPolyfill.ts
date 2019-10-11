/* eslint-disable no-extend-native */
export default class StringPolyfill {
    /**
     * Polyfills for the String.prototype.includes
     */
    public static init (): void {
        if (!String.prototype.includes) {
            String.prototype.includes = function (search, start) {
                'use strict'
                if (typeof start !== 'number') {
                    start = 0
                }

                if (start + search.length > this.length) {
                    return false
                } else {
                    return this.indexOf(search, start) !== -1
                }
            }
        }
    }
}
