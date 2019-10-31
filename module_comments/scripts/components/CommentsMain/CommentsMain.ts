import { Component, Mixins } from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'

@Component
class CommentsMain extends Mixins(LangMixin(['comments'])) {
}
export default CommentsMain
