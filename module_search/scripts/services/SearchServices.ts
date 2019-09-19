import axios from 'axios'
import to from 'await-to-js'
import qs from 'qs'

/**
 * makes all the necessary api calls of the searchModule
 */
class SearchServices {
    // todo add correct payload types after SearchApiController is ready
    public static async triggerSearch (payload) : Promise<any[]> {
        const [err, res] = await to(axios({
            url: '/xml.php',
            method: 'POST',
            params: {
                module: 'search',
                action: 'getFilteredSearch',
                search_query: payload.searchQuery,
                filtermodules: payload.selectedIds,
                search_changestartdate: payload.startDate,
                search_changeenddate: payload.endDate,
                search_formfilteruser_id: payload.selectedUser
            },
            paramsSerializer: (params : any) => {
                return qs.stringify(params, { arrayFormat: 'comma' })
            }

        }))
        return [err, res]
    }
    public static async getFilterModules () : Promise<any[]> {
        const [err, res] = await to(axios({
            url: '/xml.php?',
            method: 'GET',
            params: {
                module: 'search',
                action: 'getModulesForFilter'
            }
        }))
        return [err, res]
    }

    public static async getAutocompleteUsers (payload) : Promise<any[]> {
        const [err, res] = await to(axios({
            url: '/api.php/v1/user/filter',
            method: 'GET',
            params: {
                user: true,
                group: false,
                filter: payload.userQuery
            }
        }))
        return [err, res]
    }
}

export default SearchServices
