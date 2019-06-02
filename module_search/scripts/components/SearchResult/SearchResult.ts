import { Component, Vue } from 'vue-property-decorator'
import { namespace } from 'vuex-class'
@Component class SearchResult extends Vue {
@namespace('SearchModule').State searchResults : Array<any>
@namespace('SearchModule').Action closeDialog : any
@namespace('SearchModule').Action resetSearchResults : any
@namespace('SearchModule').Action resetSearchQuery : any
@namespace('SearchModule').State searchQuery : string
private close () : void {
    this.closeDialog()
    this.resetSearchQuery()
    this.resetSearchResults()
}
}
export default SearchResult
