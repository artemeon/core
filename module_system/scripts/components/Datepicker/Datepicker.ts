import $ from 'jquery'
import {
    Component, Vue, Prop,
} from 'vue-property-decorator'
import uuid from 'uuid//v1'
import DateFormatter from 'core/module_system/scripts/kajona/DateFormatter'
import Tooltip from '../../kajona/Tooltip'

@Component
class Datepicker extends Vue {
    @Prop({ type: String, required: true }) label!: string

    @Prop({ type: String, required: true }) format!: string

    @Prop({ type: String, required: false }) displayType!: string

    @Prop({ type: String, required: false }) tooltip!: string

    @Prop({ type: [String, Number], required: false }) value!: string | number

    private id: string = uuid()

    private actionBtnId: string = uuid()

    private input !: JQuery<HTMLElement>

    private mounted(): void {
        if (this.tooltip) {
            Tooltip.addTooltip($(`#${this.actionBtnId}`), this.tooltip)
        }
        if (this.displayType === 'years') {
            this.input = $(`#${this.id}`).datepicker({
                format: this.format,
                startView: 2,
                minViewMode: 'years',
                autoclose: true,
                language: KAJONA_LANGUAGE || 'en',
            }).on('changeDate', this.onDateChange)
        } else if (this.displayType === 'months') {
            this.input = $(`#${this.id}`).datepicker({
                format: this.format,
                startView: 1,
                minViewMode: 'months',
                autoclose: true,
                language: KAJONA_LANGUAGE || 'en',
            }).on('changeDate', this.onDateChange)
        } else {
            this.input = $(`#${this.id}`).datepicker({
                format: this.format,
                weekStart: 1,
                autoclose: true,
                language: KAJONA_LANGUAGE || 'en',
                todayHighlight: true,
                todayBtn: 'linked',
                daysOfWeekHighlighted: '0,6',
                calendarWeeks: true,
            }).on('changeDate', this.onDateChange)
        }
        if (this.value) {
            this.input.datepicker('setDate', new Date(this.value))
        }
    }

    private onDateChange(): void {
        const date = DateFormatter.rfc3339($(`#${this.id}`).datepicker('getDate'))
        this.$emit('change', date)
    }

    private deleteInput(): void {
        $(`#${this.id}`).val('')
        this.$emit('change', null)
    }
}

export default Datepicker
