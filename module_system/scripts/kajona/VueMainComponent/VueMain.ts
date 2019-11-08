import { Vue, Component } from 'vue-property-decorator'
import Searchbar from 'core/module_search/scripts/components/Searchbar/Searchbar.vue'
import CommentsMain from 'core/module_comments/scripts/components/CommentsMain/CommentsMain.vue'
import Menu from 'core/module_system/scripts/components/Menu/Menu.vue'

@Component({ components: { Searchbar, CommentsMain, Menu } })
class VueMain extends Vue {
    mounted () {
    // register i18n globally to change the languages from outside Vue
        (<any>window).i18n = this.$i18n
    }
}
export default VueMain
