import axios from 'axios'
import to from 'await-to-js'
import * as toastr from 'toastr'
import { SearchResult, FilterModule, User } from '../Interfaces/SearchInterfaces'
import qs from 'qs'

const SearchModule = {
    namespaced: true,
    state: { searchResults: [],
        dialogIsOpen: false,
        searchQuery: '',
        filterModules: null,
        selectedIds: [],
        fetchingUsers: false,
        showResultsNumber: false,
        startDate: '',
        endDate: '',
        selectedUser: '',
        autoCompleteUsers: [],
        isLoading: false,
        filterIsOpen: false
    },
    mutations: {
        SET_SEARCH_RESULTS (state : any, payload : Array<SearchResult>) : void {
            state.searchResults = payload
        },
        RESET_SEARCH_RESULTS (state :any) : void{
            state.searchResults = []
        },
        CLOSE_SEARCH_DIALOG (state : any) : void {
            state.dialogIsOpen = false
        },
        OPEN_SEARCH_DIALOG (state : any) : void {
            state.dialogIsOpen = true
        },
        SET_SEARCH_QUERY (state :any, payload : String) : void {
            state.searchQuery = payload
        },
        RESET_SEARCH_QUERY (state : any) : void {
            state.searchQuery = ''
        },
        SET_FILTER_MODULES (state : any, payload : Array<FilterModule>) {
            state.filterModules = payload
        },
        SET_SELECTED_IDS (state : any, payload : string) {
            state.selectedIds = payload
        },
        RESET_SELECTED_IDS (state : any) {
            state.selectedIds = ''
        },
        SET_SHOW_RESULTS_NUMBER (state : any, payload : boolean) {
            state.showResultsNumber = payload
        },
        SET_START_DATE (state : any, payload : Date) {
            state.startDate = payload
        },
        SET_END_DATE (state : any, payload : Date) {
            state.endDate = payload
        },
        RESET_END_DATE (state : any) {
            state.endDate = ''
        },
        RESET_START_DATE (state : any) {
            state.startDate = ''
        },
        SET_AUTOCPMPLETE_USERS (state : any, payload : Array<User>) {
            state.autoCompleteUsers = payload
        },
        SET_SELECTED_USER (state : any, payload : string) {
            state.selectedUser = payload
        },
        RESET_SELECTED_USER (state : any) {
            state.selectedUser = ''
        },
        SET_FETCHING_USERS (state : any, payload : boolean) {
            state.fetchingUsers = payload
        },
        START_LOADING (state : any) : void {
            state.isLoading = true
        },
        STOP_LOADING (state : any) : void {
            state.isLoading = false
        },
        SET_FILTER_IS_OPEN (state : any, payload : boolean): void {
            state.filterIsOpen = payload
        }

    },
    actions: {
        async triggerSearch ({ commit, state }) : Promise<void> {
            commit('SET_SHOW_RESULTS_NUMBER', false)
            commit('START_LOADING')
            const [err, res] = await to(axios({
                url: 'api.php/v1/search',
                method: 'POST',
                data: {
                    search_query: state.searchQuery !== '' ? state.searchQuery : undefined,
                    filtermodules: state.selectedIds.length !== 0 ? state.selectedIds : undefined,
                    search_changestartdate: state.startDate !== '' ? state.startDate : undefined,
                    search_changeenddate: state.endDate !== '' ? state.endDate : undefined,
                    search_formfilteruser_id: state.selectedUser !== '' ? state.selectedUser : undefined
                },
                paramsSerializer: (params : any) => {
                    return qs.stringify(params, { arrayFormat: 'comma' })
                }

            }))
            if (res) {
                commit('SET_SEARCH_RESULTS', res.data)
            }
            commit('SET_SHOW_RESULTS_NUMBER', true)
            commit('STOP_LOADING')
        },
        setSearchQuery ({ commit, state }, searchQuery : string) : void {
            if (state.searchQuery.length > searchQuery.length && searchQuery.length < 2 && state.searchResults.length !== 0) {
                commit('RESET_SEARCH_RESULTS')
            }
            commit('SET_SEARCH_QUERY', searchQuery)
        },
        resetSearchResults ({ commit }) : void {
            commit('RESET_SEARCH_RESULTS')
        },
        closeDialog ({ commit }) : void {
            commit('CLOSE_SEARCH_DIALOG')
            commit('RESET_SEARCH_RESULTS')
            commit('RESET_SEARCH_QUERY')
            commit('RESET_END_DATE')
            commit('RESET_START_DATE')
            commit('RESET_SELECTED_IDS')
            commit('RESET_SELECTED_USER')
            commit('SET_SHOW_RESULTS_NUMBER', false)
            commit('STOP_LOADING')
            commit('SET_FILTER_IS_OPEN', false)
        },
        openDialog ({ commit }) : void {
            commit('OPEN_SEARCH_DIALOG')
        },
        resetSearchQuery ({ commit }) : void {
            commit('RESET_SEARCH_QUERY')
        },
        async getFilterModules ({ commit }) : Promise<void> {
            commit('START_LOADING')
            const [err, res] = await to(axios({
                url: 'api.php/v1/search/modules',
                method: 'GET'
            }))

            if (res) {
                commit('SET_FILTER_MODULES', res.data)
            }
            commit('STOP_LOADING')
        },
        setSelectedIds ({ commit }, ids: string) : void {
            commit('SET_SELECTED_IDS', ids)
        },
        async getAutocompleteUsers ({ commit }, userQuery :string) : Promise<void> {
            commit('SET_FETCHING_USERS', true)
            const [err, res] = await to(axios({
                url: '/xml.php',
                method: 'POST',
                params: {
                    module: 'user',
                    action: 'getUserByFilter',
                    user: true,
                    group: false,
                    filter: userQuery !== '' ? userQuery : undefined
                }
            }))

            if (res) {
                commit('SET_AUTOCPMPLETE_USERS', res.data)
            }
            commit('SET_FETCHING_USERS', false)
        },
        setSelectedUser ({ commit }, user:string) : void {
            commit('SET_SELECTED_USER', user)
        },
        resetSelectedUser ({ commit }) : void {
            commit('RESET_SELECTED_USER')
        },
        setStartDate ({ commit }, startDate : Date) : void {
            commit('SET_START_DATE', startDate)
        },
        setEndDate ({ commit }, endDate : Date) : void {
            commit('SET_END_DATE', endDate)
        },
        setShowResultsNumber ({ commit }, payload : boolean) : void {
            commit('SET_SHOW_RESULTS_NUMBER', payload)
        },
        setFilterIsOpen ({ commit }, payload: boolean) : void {
            commit('SET_FILTER_IS_OPEN', payload)
        }
    },
    getters: {}
}

export default SearchModule
