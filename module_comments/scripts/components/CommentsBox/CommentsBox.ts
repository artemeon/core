import { Component, Mixins ,Watch} from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'


@Component
class CommentsBox extends Mixins(LangMixin(['comments'])) {
    private show = false
    sendComment(){
        console.log('send comment')
        this.$emit('send')
    }
    cancel(){
        this.$emit('cancel')
    }

}
export default CommentsBox