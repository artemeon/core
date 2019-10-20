import { AxiosResponse } from 'axios'
import HttpClient from 'core/module_system/scripts/kajona/HttpClient'

/**
 * makes all the necessary api calls of the searchModule
 */
class SearchServices {
    public static async triggerSearch(payload): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.get('/api.php/v1/search', {
            searchQuery: payload.searchQuery,
            filterModules: payload.selectedIds,
            searchChangeStartDate: payload.startDate,
            searchChangeEndDate: payload.endDate,
            searchFormFilterUserId: payload.selectedUser,
        })
        return [err, res]
    }

    public static async getFilterModules(): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.get('/api.php/v1/search/modules')
        return [err, res]
    }

    public static async getAutocompleteUsers(payload): Promise<[Error, AxiosResponse]> {
        const [err, res] = await HttpClient.post('/xml.php', {
            module: 'user',
            action: 'getUserByFilter',
            user: true,
            group: false,
            filter: payload.userQuery,
        })
        return [err, res]
    }
}

export default SearchServices
