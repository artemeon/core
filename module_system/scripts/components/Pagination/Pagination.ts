import { Component, Mixins, Prop } from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'

@Component class Pagination extends Mixins(LangMixin(['commons', 'system'])) {
@Prop({ type: Number, required: true }) totalPages !: number

@Prop({ type: Number, required: true }) currentPage !: number

@Prop({ type: Number, required: true }) totalElements !: number

private current: number = null as number

private mounted(): void {
    this.current = this.currentPage
}

private changePage(page: number): void{
    if (this.current !== page) {
        this.current = page
        this.$emit('change', this.current)
    }
}

private next(): void {
    if (this.current + 1 <= this.totalPages) {
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

get items(): any[] {
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
