import { Component, Watch, Mixins } from 'vue-property-decorator'
import { namespace } from 'vuex-class'
import SearchResult from '../SearchResult/SearchResult.vue'
import Loader from 'core/module_system/scripts/components/Loader/Loader.vue'
import SearchbarFilter from '../SearchbarFilter/SearchbarFilter.vue'
import Modal from 'core/module_system/scripts/components/Modal/Modal.vue'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'

@Component({ components: { SearchResult, Loader, SearchbarFilter, Modal } })
class Searchbar extends Mixins(LangMixin(['search', 'dashboard', 'system'])) {
     @namespace('SearchModule').Action triggerSearch: any
     @namespace('SearchModule').Action resetSearchResults: any
     @namespace('SearchModule').State searchResults : Array<any>
     @namespace('SearchModule').State dialogIsOpen : boolean
     @namespace('SearchModule').State searchQuery : String
     @namespace('SearchModule').State showResultsNumber : boolean
     @namespace('SearchModule').Action openDialog: any
     @namespace('SearchModule').Action closeDialog: any
     @namespace('SearchModule').Action resetSearchQuery: any
     @namespace('SearchModule').Action setSearchQuery : any
     @namespace('SearchModule').Action setShowResultsNumber : any
     @namespace('SearchModule').State isLoading : boolean
     @namespace('SearchModule').State filterIsOpen : boolean
     @namespace('SearchModule').Action setFilterIsOpen : any

     private userInput : string = ''
     private inputTimer : number
     private mounted () : void {
         var parent = document.getElementById('content')
         parent.appendChild(this.$el)
         document.body.addEventListener('openSearchbar', this.shortcutHandler)
     }
     private onSubmit (e : Event) : void {
         e.preventDefault()
     }
     private onInput (e: Event) : void {
         this.setShowResultsNumber(false)
         clearTimeout(this.inputTimer)
         this.setSearchQuery(this.userInput)
         this.inputTimer = window.setTimeout(() => {
             if (this.userInput.length > 1) {
                 this.triggerSearch()
             }
         }, 500)
     }
     private open () : void {
         this.openDialog()
     }
     private close () : void {
         this.closeDialog()
     }
      @Watch('searchQuery') onSearchQueryChange () {
         if (this.searchQuery === '') {
             this.userInput = ''
         }
     }
      private shortcutHandler () : void {
          if (!this.dialogIsOpen) {
              this.open()
          }
      }
      private get dialogClassName () : string {
          if (!this.dialogIsOpen) {
              return ''
          } else {
              if (this.userInput === '') {
                  return 'searchbarContainerSemiOpen searchBarInnerContainer'
              } else {
                  return 'searchbarContainerOpen searchBarInnerContainer'
              }
          }
      }
      private onModalOpen () : void {
          document.getElementById('searchbarInput').focus()
      }
      private toggleFilter () : void {
          this.setFilterIsOpen(!this.filterIsOpen)
      }
}

export default Searchbar
