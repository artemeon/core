import { Component, Mixins ,Watch} from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'


@Component
class CommentsEdit extends Mixins(LangMixin(['comments'])) {
    private show = true
 click(){
    //  console.log('clicked')
     this.$emit('click')
 }
}
export default CommentsEdit
