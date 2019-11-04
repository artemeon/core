import { Component, Vue, Prop } from 'vue-property-decorator'
import ModuleNavigation from '../../kajona/ModuleNavigation'

@Component class MenuModule extends Vue {
    @Prop({ type: Object, required: true }) module !: object

    private combinedInactive() {
        ModuleNavigation.combinedInactive()
    }
}

export default MenuModule
