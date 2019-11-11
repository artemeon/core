import $ from 'jquery'
import {
    Component, Vue, Prop, Watch,
} from 'vue-property-decorator'
import uuid from 'uuid/v1'

@Component class Modal extends Vue {
@Prop({ type: Boolean, required: true, default: false }) show: boolean

@Prop({ type: String, required: false }) title: string

private modalId: string = uuid()

private mounted(): void{
    $(`#${this.modalId}`).on('hidden.bs.modal', this.onClose)
    $(`#${this.modalId}`).on('shown.bs.modal', () => {
        this.$emit('open')
    })
}

private onClose(): void{
    this.$emit('close')
}

@Watch('show') onchange(): void{
    if (this.show) {
        $(`#${this.modalId}`).modal('show')
    } else {
        $(`#${this.modalId}`).modal('hide')
    }
}
}

export default Modal
