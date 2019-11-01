import { Component, Mixins } from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import {namespace} from 'vuex-class'
import CommentsModule from "core/module_comments/scripts/modules/CommentsModule";

@Component
class CommentsMain extends Mixins(LangMixin(['comments'])) {
    @namespace('commentsModule').Action addCommentAction: any
    created(){
        const name = 'commentsModule'
        const store = this.$store
        if (!(store && store.state && store.state[name])) {
            store.registerModule(name, CommentsModule)
        }
    }
    mounted(){
        const parent = document.getElementById('content')
        parent.appendChild(this.$el)
        console.log('here')
        this.addCommentAction()
    }
}
export default CommentsMain
