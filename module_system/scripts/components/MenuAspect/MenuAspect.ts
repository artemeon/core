import { Component, Vue, Prop } from 'vue-property-decorator'
import ModuleNavigation from 'core/module_system/scripts/kajona/ModuleNavigation'
import MenuModule from '../MenuModule/MenuModule.vue'


@Component({ components: { MenuModule } })
class MenuAspect extends Vue {
    @Prop({ type: Object, required: true }) aspect !: object

    private combinedActive() {
        ModuleNavigation.combinedActive()
    }
}

export default MenuAspect
