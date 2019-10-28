import { AxiosResponse } from 'axios'
import HttpClient from 'core/module_system/scripts/kajona/HttpClient'

/**
 * makes all the necessary api calls of the menuModule
 */
class MenuServices {
    public static async getMenu(): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.get('api.php/v1/system/menu')
        return [err, res]
    }
}

export default MenuServices
