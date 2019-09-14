import { Component, Vue } from 'vue-property-decorator'
import { namespace } from 'vuex-class'
import Loader from 'core/module_system/scripts/components/Loader/Loader.vue'
import Multiselect from 'core/module_system/scripts/components/Multiselect/Multiselect.vue'
import { FilterModule, User } from '../../Interfaces/SearchInterfaces'
import Datepicker from 'core/module_system/scripts/components/Datepicker/Datepicker.vue'
import Autocomplete from 'core/module_system/scripts/components/Autocomplete/Autocomplete.vue'
import { AutocompleteInterface, AutocompleteItem } from 'core/module_system/scripts/components/Autocomplete/AutcompleteInterfaces'
import Util from 'core/module_system/scripts/kajona/Util'

    @Component({ components: { Loader, Multiselect, Datepicker, Autocomplete } }) class SearchbarFilter extends Vue implements AutocompleteInterface {
    @namespace('SearchModule').Action getFilterModules : any
    @namespace('SearchModule').Action setSelectedIds : any
    @namespace('SearchModule').Action triggerSearch : any
    @namespace('SearchModule').Action setStartDate: any
    @namespace('SearchModule').Action setEndDate: any
    @namespace('SearchModule').Action setSelectedUser : any
    @namespace('SearchModule').Action resetSelectedUser : any
    @namespace('SearchModule').Action getAutocompleteUsers : any
    @namespace('SearchModule').State filterModules : Array<FilterModule>
    @namespace('SearchModule').State searchQuery : string
    @namespace('SearchModule').State selectedIds : Array<string>
    @namespace('SearchModule').State autoCompleteUsers : Array<User>
    @namespace('SearchModule').State fetchingUsers : boolean

    private mounted () : void {
        if (this.filterModules === null) {
            this.getFilterModules()
        }
    }
    private get moduleNames () : Array<string> {
        return this.filterModules.map(element => element.module)
    }
    onModulesChange (filters : Array<string>) : void {
        let ids = []
        filters.map(selectedFilter => {
            this.filterModules.map(filter => {
                if (filter.module === selectedFilter) {
                    ids.push(filter.id.toString())
                }
            })
        })
        this.setSelectedIds(ids)
        if (this.searchQuery.length >= 2) {
            this.triggerSearch()
        }
    }
    private onStartDateChange (startDate : string) : void {
        this.setStartDate(startDate)
        if (this.searchQuery.length >= 2) {
            this.triggerSearch()
        }
    }
    private onEndDateChange (endDate : string) : void {
        this.setEndDate(endDate)
        if (this.searchQuery.length >= 2) {
            this.triggerSearch()
        }
    }
    private onUserSelect (userId : string) : void {
        this.setSelectedUser(userId)
        if (this.searchQuery.length >= 2) {
            this.triggerSearch()
        }
    }
    private onUserDelete () : void {
        this.resetSelectedUser()
        if (this.searchQuery.length >= 2) {
            this.triggerSearch()
        }
    }
    private async onAutocompleteInput (e : string) : Promise<void> {
        this.getAutocompleteUsers(e)
    }
    public get parsedAutoCompleteData () : Array<AutocompleteItem> {
        return this.autoCompleteUsers.map(user => {
            return { label: user.icon + user.title, value: user.systemid, title: user.title }
        })
    }
    private get datepickerFormat () : string {
        return Util.transformDateFormat(<string> this.$i18n.t('system.dateStyleShort'), 'bootstrap-datepicker')
    }
    }

export default SearchbarFilter
