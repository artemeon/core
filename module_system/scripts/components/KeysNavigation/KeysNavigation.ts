import { Component, Vue, Prop } from 'vue-property-decorator'

@Component class KeysNavigation extends Vue {
@Prop({ type: String, required: true }) eventsParent !: string

private DOWN: string = 'ArrowDown'

private UP: string = 'ArrowUp'

private NUMPADENTER: string = 'NumpadEnter'

private ENTER: string = 'Enter'

private selectedElementPointer: number = -1

private mounted(): void {
    document.getElementById(this.eventsParent).addEventListener('keydown', this.keyHandler)
    document.getElementById(this.eventsParent).addEventListener('focusout', this.onFocusOut)
    if (document.getElementById(this.eventsParent).nodeName === 'INPUT') {
        document.getElementById(this.eventsParent).addEventListener('input', this.resetPointer)
    }
}

private beforeDestroy(): void {
    document.getElementById(this.eventsParent).removeEventListener('keydown', this.keyHandler)
    document.getElementById(this.eventsParent).removeEventListener('focusout', this.onFocusOut)
}

private keyHandler(e: KeyboardEvent): void {
    if (e.code === this.UP) {
        e.preventDefault()
        this.goUp()
    }
    if (e.code === this.DOWN) {
        e.preventDefault()
        this.goDown()
    }
    if ((e.code === this.ENTER || e.code === this.NUMPADENTER) && this.selectedElementPointer !== -1) {
        this.$emit('select', this.selectedElementPointer)
    }
}

private goUp(): void {
    if (this.selectedElementPointer > 0) {
        this.removeClass()
        this.selectedElementPointer -= 1;
        (this.$slots.default[this.selectedElementPointer].elm as HTMLElement).scrollIntoView(false)
        this.addClass()
    }
}

private goDown(): void {
    if (this.selectedElementPointer >= 0 && this.selectedElementPointer < this.$slots.default.length - 1) {
        this.removeClass()
    }
    if (this.selectedElementPointer < this.$slots.default.length - 1) {
        this.selectedElementPointer += 1;
        (this.$slots.default[this.selectedElementPointer].elm as HTMLElement).scrollIntoView(false)
        this.addClass()
    }
}

private onFocusOut(): void {
    if (this.selectedElementPointer >= 0) {
        this.removeClass()
    }
    this.selectedElementPointer = -1
}

private addClass(): void {
    (this.$slots.default[this.selectedElementPointer].elm as HTMLElement).classList.add('keysNavigationSelectedElement')
}

private removeClass(): void {
    (this.$slots.default[this.selectedElementPointer].elm as HTMLElement).classList.remove('keysNavigationSelectedElement')
}

private resetPointer(): void {
    this.removeClass()
    this.selectedElementPointer = -1
}
}

export default KeysNavigation
