import { Component, Mixins } from 'vue-property-decorator'
import { namespace } from 'vuex-class'
import ModuleNavigation from 'core/module_system/scripts/kajona/ModuleNavigation'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import MenuAspect from '../MenuAspect/MenuAspect.vue'

@Component({ components: { MenuAspect } })
class Menu extends Mixins(LangMixin(['commons_product_title', 'system', 'commons'])) {
    @namespace('MenuModule').Action getMenu: any

    @namespace('MenuModule').State aspects: Array<any>

    @namespace('MenuModule').State isLoaded: boolean

    private async mounted(): Promise<void> {
        await this.getMenu()
        this.switchAspect(this.aspects[0].Aspect_id)
    }

    private switchAspect(aspectId: string): boolean {
        ModuleNavigation.switchAspect(aspectId); return false
    }
}

export default Menu
