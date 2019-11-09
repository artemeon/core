import { Component, Mixins ,Watch} from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'


@Component
class CommentsBox extends Mixins(LangMixin(['comments'])) {
    private show = false

}
export default CommentsBox