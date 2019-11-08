import { Component, Mixins, Prop } from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import uuid from 'uuid/v1'
import Tooltip from 'core/module_system/scripts/kajona/Tooltip'

@Component class Pagination extends Mixins(LangMixin(['commons', 'system'])) {
// for performance mode you need to pass lastPage as a property and ignore totalPages prop
@Prop({ type: Number, required: false }) totalPages !: number

@Prop({ type: Number, required: true }) currentPage !: number

@Prop({ type: [Number, String], required: true }) totalElements !: string | number

@Prop({ type: Boolean, required: false }) lastPage !: boolean

@Prop({ type: String, required: false }) tooltip !: string


private current: number = null as number

private totalEntriesNumberId: string = uuid()

private mounted(): void {
    this.current = this.currentPage
    if (this.tooltip) {
        Tooltip.addTooltip(document.getElementById(this.totalEntriesNumberId), this.tooltip)
    }
}

private changePage(page: number): void{
    if (this.current !== page) {
        this.current = page
        this.$emit('change', this.current)
    }
}

private next(): void {
    if (this.totalPages) {
        if (this.current + 1 <= this.totalPages) {
            this.current = this.current + 1
            this.$emit('change', this.current)
        }
    } else if (!this.lastPage) {
        this.current = this.current + 1
        this.$emit('change', this.current)
    }
}

private previous(): void {
    if (this.current - 1 >= 1) {
        this.current = this.current - 1
        this.$emit('change', this.current)
    }
}

private onTotalElementsClick(): void {
    this.$emit('totalElementsClick')
}

private get items(): any[] {
    const valPrev = this.current > 1 ? (this.current - 1) : 1
    const valNext = this.current < this.totalPages ? (this.current + 1) : this.totalPages
    const extraPrev = valPrev === 3 ? 2 : null
    const extraNext = valNext === (this.totalPages - 2) ? this.totalPages - 1 : null
    const dotsBefore = valPrev > 3 ? 2 : null
    const dotsAfter = valNext < this.totalPages - 2 ? this.totalPages - 1 : null
    const output = []
    for (let i = 1; i <= this.totalPages; i += 1) {
        if ([1, this.totalPages, this.current, valPrev, valNext, extraPrev, extraNext, dotsBefore, dotsAfter].includes(i)) {
            output.push({
                label: i,
                active: this.current === i,
                disable: [dotsBefore, dotsAfter].includes(i),
            })
        }
    }
    return output
}
}

export default Pagination
