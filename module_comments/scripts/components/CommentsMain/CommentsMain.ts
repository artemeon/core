import { Component, Mixins } from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import {namespace} from 'vuex-class'
import CommentsModule from "core/module_comments/scripts/modules/CommentsModule";
import $ from 'jquery'
@Component
class CommentsMain extends Mixins(LangMixin(['comments'])) {
    @namespace('commentsModule').Action addCommentAction: any
    @namespace('commentsModule').Action listCommentsAction: any
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
        setTimeout(()=>{
            let elements = document.querySelectorAll('[data-fieldid]')
            elements.forEach(element=>{
                let test= <HTMLVideoElement>element 
                console.log(test.dataset.systemid, 'works with timeout')
            })
        },2000)
 
        // this.addCommentAction()
        // this.listCommentsAction()
    }
}
export default CommentsMain
