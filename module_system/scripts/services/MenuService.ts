import axios from 'axios'
import to from 'await-to-js'

/**
 * makes all the necessary api calls of the menuModule
 */
class MenuServices {
    public static async getMenu(): Promise<any[]> {
        const [err, res] = await to(axios({
            url: 'api.php/v1/system/menu',
            method: 'GET',
        }))
        return [err, res]
    }
}

export default MenuServices
