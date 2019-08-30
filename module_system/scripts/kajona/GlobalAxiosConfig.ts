import axios from 'axios'
import qs from 'qs'
import { Service } from 'axios-middleware'
import WorkingIndicator from './WorkingIndicator'
import StatusDisplay from './StatusDisplay'
import jwtDecode from 'jwt-decode'
import { Token } from './Interfaces/SystemInterfaces'

/**
 * a wrapper class for axios used to configure axios globally and adds middleware for loading animation
 */
class GlobalAxiosConfig {
    public static service : Service

    public static init () : void {
        // global parameter serializer for axios : converts json data to url params
        axios.defaults.paramsSerializer = (params : any) => {
            return qs.stringify(params, { arrayFormat: 'brackets' })
        }
        // global axios's baseURL config
        axios.defaults.baseURL = KAJONA_WEBPATH
        // global axios's config : Access Token
        axios.defaults.headers.common = { 'Authorization': `Bearer ${KAJONA_ACCESS_TOKEN}` }
        // Before each request, verify token
        axios.interceptors.request.use(config => {
            const token = KAJONA_ACCESS_TOKEN
            const jwt = jwtDecode<Token>(token)
            if ((token && Date.now() - (jwt.exp + 120000) > 0)) {
                config.headers.Authorization = `Bearer ${token}`
            } else {
                fetch('api.php/v1/authorization/refresh', { method: 'post',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ token: KAJONA_ACCESS_TOKEN }) }
                ).then(res => {
                    res.json().then(function (response) {
                        KAJONA_ACCESS_TOKEN = response.access_token
                        config.headers.Authorization = `Bearer ${response.access_token}`
                    })
                })
            }
            return config
        }, err => Promise.reject(err)
        )

        this.createMiddleware()
    }
    /**
     * use a middleware to be able to start/stop loader animation onRequest
     */
    public static createMiddleware () : void {
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
