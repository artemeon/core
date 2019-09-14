import { Component, Prop, Vue } from 'vue-property-decorator'
import BreadCrumbMaker from 'core/module_system/scripts/kajona/Breadcrumb'

@Component class BreadCrumb extends Vue {
    @Prop({ type: Array, required: true }) data !: Array<{link : string, title : string}>
    private mounted () : void {
        this.data.forEach(element => {
            BreadCrumbMaker.appendLinkToPathNavigation(`<a href="${element.link}">${element.title}</a>`)
        })
    }
}

export default BreadCrumb
