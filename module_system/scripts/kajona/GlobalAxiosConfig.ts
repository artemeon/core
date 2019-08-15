import axios from 'axios'
import qs from 'qs'
import { Service } from 'axios-middleware'
import WorkingIndicator from './WorkingIndicator'
import StatusDisplay from './StatusDisplay'
/**
 * a wrapper class for axios used to configure axios globally and adds middleware for loading animation
 */
class GlobalAxiosConfig {
    private service : any
    constructor () {
        // global parameter serializer for axios : converts json data to url params
        axios.defaults.paramsSerializer = (params : any) => {
            return qs.stringify(params, { arrayFormat: 'brackets' })
        }
        // golbal axios's baseURL config
        axios.defaults.baseURL = KAJONA_WEBPATH
        this.createMiddleware()
    }
    /**
     * use a middleware to be able to start/stop loader animation onRequest
     */
    private createMiddleware () : void {
        this.service = new Service(axios)
        this.service.register({
            onRequest (config : any) {
                WorkingIndicator.start()
                return config
            },
            onResponse (response : any) {
                WorkingIndicator.stop()
                return response
            },
            onRequestError (error : any) {
                StatusDisplay.messageError('<b>Request failed!</b>')
                WorkingIndicator.stop()
                return error
            },
            onResponseError (error :any) {
                StatusDisplay.messageError('<b>Response failed!</b>')
                WorkingIndicator.stop()
                return error
            }

        })
    }
}

export default GlobalAxiosConfig
