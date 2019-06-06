class CacheManager {
    private static container: any

    /**
     * @param {String} strKey
     * @return {String}
     */
    public static get (strKey: string) {
        strKey = KAJONA_WEBPATH + '/' + strKey
        if (localStorage) {
            return localStorage.getItem(strKey)
        }

        if (this.container[strKey]) {
            return this.container[strKey]
        }

        return false
    }

    /**
     * @param {String} strKey
     * @param {String} strValue
     */
    public static set (strKey: string, strValue: string) {
        strKey = KAJONA_WEBPATH + '/' + strKey
        if (localStorage) {
            localStorage.setItem(strKey, strValue)
            return
        }

        this.container[strKey] = strValue
    }
}

export default CacheManager
