import { storiesOf } from '@storybook/vue'
import { action } from '@storybook/addon-actions'
import { withKnobs, text } from '@storybook/addon-knobs'
import uuid from 'uuid//v1'
import Tooltip from '../../kajona/Tooltip'
import $ from 'jquery'
import 'bootstrap-datepicker'

const stories = storiesOf('Datepicker', module)
const template = `
<div class="form-group core-component-formentry-datesingle">
<label :for="id" class="col-sm-3 control-label">{{label}}</label>
<div class="col-sm-6">
  <div class="input-group">
    <div class="input-group-addon">
      <i class="fa fa-calendar-o"></i>
    </div>
    <input type="text" :id="id" class="form-control" :value="value" />
  </div>
</div>
<div class="col-sm-2 form-opener">
  <span class="listButton" :id="actionBtnId">
    <a @click="deleteInput">
      <i class="kj-icon fa fa-trash-o"></i>
    </a>
  </span>
</div>
</div>
`
stories.addDecorator(withKnobs)
stories.add('Datepicker : default', () => {
    return {
        template: template,
        props: {
            label: { default: text('label', 'Label') },
            tooltip: {
                default: text('tooltip', 'Delete')
            },
            format: {
                default: text('format', 'dd.mm.yyyy')
            },
            value: {
                default: text('value', '01.01.2019')
            }
        },
        mounted: function () : void {
            if (this.tooltip) {
                Tooltip.addTooltip($('#' + this.actionBtnId), this.tooltip)
            }
            let input : JQuery<HTMLElement>
            if (this.format === 'yyyy') {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    startView: 2,
                    minViewMode: 'years',
                    autoclose: true,
                    language: 'de'
                }).on('changeDate', this.onDateChange)
            } else if (this.format === 'mm') {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    startView: 1,
                    minViewMode: 'months',
                    autoclose: true,
                    language: 'de'
                }).on('changeDate', this.onDateChange)
            } else {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    weekStart: 1,
                    autoclose: true,
                    language: 'de',
                    todayHighlight: true,
                    todayBtn: 'linked',
                    daysOfWeekHighlighted: '0,6',
                    calendarWeeks: true
                }).on('changeDate', this.onDateChange)
            }
        },
        data: function () {
            return {
                id: uuid(),
                actionBtnId: uuid(),
                value: ''
            }
        },
        methods: {
            onDateChange: action('Emit change'),
            deleteInput: function () : void{
                $('#' + this.id).val('')
                this.$emit('change', $('#' + this.id).val())
            }
        }

    }
}, {
    notes: `
    To properly use the date-picker , please use the formats defined in the Lang files
- How to import the formats in a vue component : 
Util.transformDateFormat(<string> this.$i18n.t('system.dateStyleShort'), 'bootstrap-datepicker')
this helper method returns the format based on the user's language 
    `
})

stories.add('Datepicker : month picker', () => {
    return {
        template: template,
        props: {
            label: { default: text('label', 'Label') },
            tooltip: {
                default: text('tooltip', 'Delete')
            },
            format: {
                default: text('format', 'mm')
            }
        },
        mounted: function () : void {
            if (this.tooltip) {
                Tooltip.addTooltip($('#' + this.actionBtnId), this.tooltip)
            }
            let input : JQuery<HTMLElement>
            if (this.format === 'yyyy') {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    startView: 2,
                    minViewMode: 'years',
                    autoclose: true,
                    language: 'de'
                }).on('changeDate', this.onDateChange)
            } else if (this.format === 'mm') {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    startView: 1,
                    minViewMode: 'months',
                    autoclose: true,
                    language: 'de'
                }).on('changeDate', this.onDateChange)
            } else {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    weekStart: 1,
                    autoclose: true,
                    language: 'de',
                    todayHighlight: true,
                    todayBtn: 'linked',
                    daysOfWeekHighlighted: '0,6',
                    calendarWeeks: true
                }).on('changeDate', this.onDateChange)
            }
        },
        data: function () {
            return {
                id: uuid(),
                actionBtnId: uuid(),
                value: ''
            }
        },
        methods: {
            onDateChange: action('Emit change'),
            deleteInput: function () : void{
                $('#' + this.id).val('')
                this.$emit('change', $('#' + this.id).val())
            }
        }

    }
}, {
    notes: `
    To use the month picker you simply need to pass "mm" to the format property : 
    <Datepicker :format="'mm'"....
    `
})

stories.add('Datepicker : Year picker', () => {
    return {
        template: template,
        props: {
            label: { default: text('label', 'Label') },
            tooltip: {
                default: text('tooltip', 'Delete')
            },
            format: {
                default: text('format', 'yyyy')
            }
        },
        mounted: function () : void {
            if (this.tooltip) {
                Tooltip.addTooltip($('#' + this.actionBtnId), this.tooltip)
            }
            let input : JQuery<HTMLElement>
            if (this.format === 'yyyy') {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    startView: 2,
                    minViewMode: 'years',
                    autoclose: true,
                    language: 'de'
                }).on('changeDate', this.onDateChange)
            } else if (this.format === 'mm') {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    startView: 1,
                    minViewMode: 'months',
                    autoclose: true,
                    language: 'de'
                }).on('changeDate', this.onDateChange)
            } else {
                input = $('#' + this.id).datepicker({
                    format: this.format,
                    weekStart: 1,
                    autoclose: true,
                    language: 'de',
                    todayHighlight: true,
                    todayBtn: 'linked',
                    daysOfWeekHighlighted: '0,6',
                    calendarWeeks: true
                }).on('changeDate', this.onDateChange)
            }
        },
        data: function () {
            return {
                id: uuid(),
                actionBtnId: uuid(),
                value: ''
            }
        },
        methods: {
            onDateChange: action('Emit change'),
            deleteInput: function () : void{
                $('#' + this.id).val('')
                this.$emit('change', $('#' + this.id).val())
            }
        }

    }
}, {
    notes: `
    To use the year picker you simply need to pass "yyyy" to the format property : 
    <Datepicker :format="'yyyy'"...
    `
})
