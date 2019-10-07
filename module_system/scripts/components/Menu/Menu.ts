import { Component, Vue } from 'vue-property-decorator'
import { namespace } from 'vuex-class'

@Component class Menu extends Vue {
    @namespace('MenuModule').Action getMenu: any
    @namespace('MenuModule').State aspects: Array<any>

    private created(): void {
        this.getMenu()
    }
}


export default Menu
