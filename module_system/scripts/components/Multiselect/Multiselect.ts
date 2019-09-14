import { Component, Vue, Prop, Watch } from 'vue-property-decorator'
import uuid from 'uuid/v1'
import MSelect from 'vue-multiselect'
import Tooltip from '../../kajona/Tooltip'

@Component({ components: { MSelect } }) class Multiselect extends Vue {
@Prop({ type: Array, required: true }) options : Array<any>
@Prop({ type: String, required: true }) label : string
@Prop({ type: String, required: false }) tooltip : string // Text to display onHover over the action button
private id : string = uuid()
private actionBtnId : string = uuid()
private selectedOptions : Array<string> = []
private mounted () : void {
    if (this.tooltip) {
        Tooltip.addTooltip($('#' + this.actionBtnId), this.tooltip)
    }
}
@Watch('selectedOptions') onChange () : void {
    this.$emit('select', this.selectedOptions)
}
private deleteSelection () : void {
    this.selectedOptions = []
}
}
export default Multiselect
