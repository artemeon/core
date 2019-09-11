import { Component, Vue } from 'vue-property-decorator'
import { namespace } from 'vuex-class'
import { SearchResult as SResult } from '../../Interfaces/SearchInterfaces'

@Component class SearchResult extends Vue {
@namespace('SearchModule').State searchResults : Array<SResult>
@namespace('SearchModule').Action closeDialog : any
@namespace('SearchModule').Action resetSearchResults : any
@namespace('SearchModule').Action resetSearchQuery : any
@namespace('SearchModule').State searchQuery : string
private DOWN : string = 'ArrowDown'
private UP : string = 'ArrowUp'
private NUMPADENTER : string = 'NumpadEnter'
private ENTER : string = 'Enter'
private selectedElementPointer : number = -1
private mounted () : void {
    document.getElementById('searchbarInput').addEventListener('keydown', this.keyHandler)
    document.getElementById('searchbarInput').addEventListener('focusout', this.onFocusOut)
}
private keyHandler (e : KeyboardEvent) : void {
    if (e.code === this.UP) {
        e.preventDefault()
        this.goUp()
    }
    if (e.code === this.DOWN) {
        e.preventDefault()
        this.goDown()
    }
    if ((e.code === this.ENTER || e.code === this.NUMPADENTER) && this.selectedElementPointer !== -1) {
        this.goToSelectedElement()
    }
}
private goUp () : void {
    if (this.selectedElementPointer > 0) {
        this.selectedElementPointer -= 1
    }
}
private goDown () : void {
    if (this.selectedElementPointer < this.searchResults.length - 1) {
        this.selectedElementPointer += 1
    }
}
private goToSelectedElement () : void {
    this.close(this.searchResults[this.selectedElementPointer].link)
}
private onFocusOut () : void {
    this.selectedElementPointer = -1
}
private close (link : string) : void {
    window.location.href = link
    this.closeDialog()
}
}
export default SearchResult
