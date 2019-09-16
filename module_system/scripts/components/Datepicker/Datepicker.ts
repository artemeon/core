import $ from 'jquery'
import {Component, Vue, Prop} from 'vue-property-decorator'
import uuid from 'uuid//v1'
import Tooltip from '../../kajona/Tooltip'

@Component
class Datepicker extends Vue {
    @Prop({type: String, required: true}) label !: string;
    @Prop({type: String, required: true}) format !: string;
    @Prop({type: String, required: true}) displayType !: string;
    @Prop({type: String, required: false}) tooltip !: string;
    @Prop({type: String, required: false}) value !: string;

    private id: string = uuid();
    private actionBtnId: string = uuid();

    private mounted(): void {
        if (this.tooltip) {
            Tooltip.addTooltip($('#' + this.actionBtnId), this.tooltip)
        }
        let input: JQuery<HTMLElement>;
        if (this.displayType === 'years') {
            input = $('#' + this.id).datepicker({
                format: this.format,
                startView: 2,
                minViewMode: 'years',
                autoclose: true,
                language: KAJONA_LANGUAGE || 'en'
            }).on('changeDate', this.onDateChange)
        } else if (this.displayType === 'months') {
            input = $('#' + this.id).datepicker({
                format: this.format,
                startView: 1,
                minViewMode: 'months',
                autoclose: true,
                language: KAJONA_LANGUAGE || 'en'
            }).on('changeDate', this.onDateChange)
        } else {
            input = $('#' + this.id).datepicker({
                format: this.format,
                weekStart: 1,
                autoclose: true,
                language: KAJONA_LANGUAGE || 'en',
                todayHighlight: true,
                todayBtn: 'linked',
                daysOfWeekHighlighted: '0,6',
                calendarWeeks: true
            }).on('changeDate', this.onDateChange)
        }
        input.datepicker('setDate', new Date(this.value))
    }

    private onDateChange(e: DatepickerEventObject): void {
        this.$emit('change', $('#' + this.id).val())
    }

    private deleteInput(): void {
        this.$emit('change', '')
    }
}

export default Datepicker
