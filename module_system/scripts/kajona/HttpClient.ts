import axios, { AxiosResponse } from 'axios'
import to from 'await-to-js'

/**
 * AJAX functions for connecting to the server
 */
class HttpClient {
    /**
  * GET function : returns a promise containing the response object + error if occurs
  * can be used as an async function with async/await or with .then
  * @param url url of the ajax call
  * @param params params is an object example {action: 'getUnreadMessagesCount' , module: 'messaging'  }. The params will be automatically serialized
  */
    public static async get(url: string, params: object): Promise<[ Error, AxiosResponse ]> {
        const [error, response] = await to(axios({
            method: 'GET',
            url,
            params,
        }))
        return [error, response]
    }

    /**
  * POST function : returns a promise containing the response object + error if occurs
  * can be used as an async function with async/await or with .then
  * @param url url of the ajax call
  * @param params params is an object example {action: 'getUnreadMessagesCount' , module: 'messaging'  }. The params will be automatically serialized
  */
    public static async post(url: string, params: object): Promise<[Error, AxiosResponse ]> {
        const [error, response] = await to(axios({
            method: 'POST',
            url,
            params,
        }))
        return [error, response]
    }

    /**
  * DELETE function : returns a promise containing the response object + error if occurs
  * can be used as an async function with async/await or with .then
  * @param url url of the ajax call
  * @param params params is an object example {action: 'getUnreadMessagesCount' , module: 'messaging'  }. The params will be automatically serialized
  */
    public static async delete(url: string, params: any): Promise<[Error, AxiosResponse ]> {
        const [error, response] = await to(axios({
            method: 'DELETE',
            url,
            params,
        }))
        return [error, response]
    }
}
(window as any).HttpClient = HttpClient
export default HttpClient
