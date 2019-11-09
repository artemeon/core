import { Component, Mixins ,Watch} from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import {namespace} from 'vuex-class'
import CommentsModule from "core/module_comments/scripts/modules/CommentsModule"


@Component
class CommentsMain extends Mixins(LangMixin(['comments'])) {
    @namespace('commentsModule').Action addCommentAction: any
    @namespace('commentsModule').Action listCommentsAction: any
    test = []
    created(){
        const name = 'commentsModule'
        const store = this.$store
        if (!(store && store.state && store.state[name])) {
            store.registerModule(name, CommentsModule)
        }
    }
    mounted(){
        console.log('mounted comments')
        const parent = document.getElementById('content')
        parent.appendChild(this.$el)

    //    setInterval(()=>{
    //         let datasets = []
    //         let elements = document.querySelectorAll('[data-field-id]')
    //         elements.forEach(element=>{
    //             let test= <HTMLVideoElement>element 
    //             if(test.dataset)
    //              this.workData(test.dataset)
    //         })
    //     },2000)
        

 
        // this.addCommentAction()
        // this.listCommentsAction()
        // document.addEventListener('DOMContentLoaded', () => {
        //     const test = document.querySelectorAll('[data-field-id]')
        //     console.log('test',test)})
        parent.addEventListener('DOMSubtreeModified', this.callback, false)

    }

    @Watch('test')
    Onchange(old){
        console.log('test watch changed',this.test,this.test[0].dataset)
    }
    callback(){
        let sets = document.querySelectorAll('[data-field-id]')
        if(sets && sets.length>0)
        {
            if(JSON.stringify(sets) !== JSON.stringify(this.test))
            {
                sets.forEach(nodeEl=>{
                    if(this.test.indexOf(nodeEl)===-1)
                    this.test.push(nodeEl)
                })
            }
            // console.log('test', this.test)
        }
        //  console.log('no Sets')
    }
    // workData(dataset){
    //     clearInterval(this.polling)
    //     console.log(dataset)
    // }
}
export default CommentsMain
