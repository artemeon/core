import { Vue, Component } from 'vue-property-decorator'
import Searchbar from 'core/module_search/scripts/components/Searchbar/Searchbar.vue'
import Menu from 'core/module_system/scripts/components/menu/Menu.vue'
import Fragment from 'vue-fragment'

@Component({ components: { Searchbar, Menu } })
Vue.use(Fragment.Plugin)
class VueMain extends Vue {
    mounted () {
    // register i18n globally to change the languages from outside Vue
        (<any>window).i18n = this.$i18n
    }
}
export default VueMain
