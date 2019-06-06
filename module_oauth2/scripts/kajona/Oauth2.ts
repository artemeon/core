class Oauth2 {
    /**
     * @param {string} url
     */
    public static redirect (url: string) {
        location.href = url
    }
}
;(<any>window).Oauth2 = Oauth2
export default Oauth2
