import axios from 'axios'
import to from 'await-to-js'
import qs from 'qs'

/**
 * makes all the necessary api calls of the searchModule
 */
class SearchServices {
    public static async triggerSearch (payload) : Promise<any[]> {
        const [err, res] = await to(axios({
            url: '/api.php/v1/search',
            method: 'GET',
            params: {
                search_query: payload.searchQuery,
                filtermodules: payload.selectedIds,
                search_changestartdate: payload.startDate,
                search_changeenddate: payload.endDate,
                search_formfilteruser_id: payload.selectedUser
            }
        }))
        return [err, res]
    }
    public static async getFilterModules () : Promise<any[]> {
        const [err, res] = await to(axios({
            url: '/api.php/v1/search/modules',
            method: 'GET'
        }))
        return [err, res]
    }

    public static async getAutocompleteUsers (payload) : Promise<any[]> {
        const [err, res] = await to(axios({
            url: '/xml.php',
            method: 'POST',
            params: {
                module: 'user',
                action: 'getUserByFilter',
                user: true,
                group: false,
                filter: payload.userQuery
            }
        }))
        return [err, res]
    }
}

export default SearchServices
