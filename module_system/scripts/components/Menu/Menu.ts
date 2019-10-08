import { Component, Vue } from 'vue-property-decorator'
import { namespace } from 'vuex-class'
import ModuleNavigation from 'core/module_system/scripts/kajona/ModuleNavigation'
import Lang from 'core/module_system/scripts/kajona/Lang'
import MenuAspect from '../MenuAspect/MenuAspect.vue'

@Component({ components: { MenuAspect } })
class Menu extends Vue {
    @namespace('MenuModule').Action getMenu: any

    @namespace('MenuModule').State aspects: Array<any>

    @namespace('MenuModule').State isLoaded: boolean

    private version: string

    private async mounted(): Promise<void> {
        Lang.fetchSingleProperty(
            'commons',
            'commons_product_title',
            (value: string) => {
                this.version = value
            },
        )
        await this.getMenu()
        this.switchAspect(this.aspects[0].onclick)
    }

    private switchAspect(href: string): string {
        const splitted = href.split(/'|return /)
        ModuleNavigation.switchAspect(splitted[1]); return splitted[3]
    }
}

export default Menu
