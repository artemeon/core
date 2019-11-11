import { Vue, Component } from 'vue-property-decorator'
import Searchbar from 'core/module_search/scripts/components/Searchbar/Searchbar.vue'
import Menu from 'core/module_system/scripts/components/Menu/Menu.vue'
import Fragment from 'vue-fragment'

Vue.use(Fragment.Plugin)
@Component({ components: { Searchbar, Menu } })
class VueMain extends Vue {
    private mounted(): void {
    // register i18n globally to change the languages from outside Vue
        (window as any).i18n = this.$i18n
    }
}
export default VueMain
