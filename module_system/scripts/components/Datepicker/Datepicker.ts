import $ from 'jquery'
import {Component, Vue, Prop} from 'vue-property-decorator'
import uuid from 'uuid//v1'
import Tooltip from '../../kajona/Tooltip'
import DateFormatter from "core/module_system/scripts/kajona/DateFormatter";

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
        if (this.value) {
            input.datepicker('setDate', new Date(this.value))
        }
    }

    private onDateChange(e: DatepickerEventObject): void {
        const date = DateFormatter.rfc3339($('#' + this.id).datepicker('getDate'));
        this.$emit('change', date)
    }

    private deleteInput(): void {
        $('#' + this.id).val('');
        this.$emit('change', null)
    }
}

export default Datepicker
