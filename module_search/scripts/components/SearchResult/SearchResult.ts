import $ from 'jquery'
import { Component, Vue } from 'vue-property-decorator'
import { namespace } from 'vuex-class'
import KeysNavigation from 'core/module_system/scripts/components/KeysNavigation/KeysNavigation.vue'
import Tooltip from 'core/module_system/scripts/kajona/Tooltip'
import { SearchResult as SResult } from '../../Interfaces/SearchInterfaces'

@Component({ components: { KeysNavigation } })
class SearchResult extends Vue {
    @namespace('SearchModule').State searchResults: Array<SResult>

    @namespace('SearchModule').Action closeDialog: any

    @namespace('SearchModule').Action resetSearchResults: any

    @namespace('SearchModule').Action resetSearchQuery: any

    @namespace('SearchModule').State searchQuery: string

    private goToSelectedElement(selectedElementIndex: number): void {
        this.close(this.searchResults[selectedElementIndex].link)
    }

    private close(link: string): void {
        if (link) {
            window.location.href = link
        }
        this.closeDialog()
    }

    private mounted(): void {
        Tooltip.initTooltip()
    }
}

export default SearchResult
