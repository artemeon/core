import axios from 'axios'

class HttpClient {
    public static get() {}

    public static post() {}

    public static delete() {}

    /**
 *
 * @param url
 * @param data
 */
    public static async asyncGet(url: string, params: any): Promise<any> {
        const res = await axios({
            method: 'GET',
            url,
            params,
        })
        return res
    }

    /**
 *
 * @param url
 * @param params
 */
    public static async asyncPost(url: string, params: any): Promise<any> {
        const res = await axios({
            method: 'POST',
            url,
            params,
        })
        return res
    }

    public static async asyncDelete(url: string, params: any): Promise<any> {
        const res = await axios({
            method: 'DELETE',
            url,
            params,
        })
        return res
    }
}
(window as any).HttpClient = HttpClient
export default HttpClient
