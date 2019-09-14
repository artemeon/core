import $ from 'jquery'
import { Component, Vue, Prop } from 'vue-property-decorator'
import uuid from 'uuid//v1'
import Tooltip from '../../kajona/Tooltip'

@Component class Datepicker extends Vue {
@Prop({ type: String, required: true }) label : string
@Prop({ type: String, required: true }) format : string
@Prop({ type: String, required: false }) tooltip : string
@Prop({ type: String, required: false }) value : string
private id : string = uuid()
private actionBtnId : string = uuid()
private mounted () : void {
    if (this.tooltip) {
        Tooltip.addTooltip($('#' + this.actionBtnId), this.tooltip)
    }
    var input = $('#' + this.id).datepicker({
        format: this.format,
        weekStart: 1,
        autoclose: true,
        language: KAJONA_LANGUAGE || 'de',
        todayHighlight: true,
        todayBtn: 'linked',
        daysOfWeekHighlighted: '0,6',
        calendarWeeks: true
    }).on('changeDate', this.onDateChange)
}
private onDateChange (e : DatepickerEventObject) : void {
    this.$emit('change', $('#' + this.id).val())
}
private deleteInput () : void {
    $('#' + this.id).val('')
    this.$emit('change', $('#' + this.id).val())
}
}
export default Datepicker
