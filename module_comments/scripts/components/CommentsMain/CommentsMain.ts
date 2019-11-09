import { Component, Mixins ,Watch} from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import {namespace} from 'vuex-class'
import CommentsModule from "core/module_comments/scripts/modules/CommentsModule"
import CommentsEdit from "../CommentsEdit/CommentsEdit.vue"
import CommentsBox from '../CommentsBox/CommentsBox.vue'
import Vue from 'vue'
@Component({components: {CommentsEdit}})
class CommentsMain extends Mixins(LangMixin(['comments'])) {
    @namespace('commentsModule').Action addCommentAction: any
    @namespace('commentsModule').Action listCommentsAction: any
    @namespace('commentsModule').State comments: any
    @namespace('commentsModule').Getter getById: any
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
    let currentSystem = this.test[0].dataset.systemId
    this.listCommentsAction(currentSystem)
        console.log('done')
        //test 
    this.test.map(domElement=>{
    let field = domElement.dataset.fieldId
    console.log(this.getById(field),this.comments)
    let componentClass = Vue.extend(CommentsEdit)
    let instance = new componentClass(
    )
    instance.$mount()
    let componentClassBox = Vue.extend(CommentsBox)
    let instanceBox = new componentClassBox(
    )
    instanceBox.$mount()
    const me = this
    instance.$on('click', ()=>{me.handleChild(instance,instanceBox)})
    instanceBox.$on('send', (e)=>{me.handleSend(e,instance,instanceBox,domElement)})
    let parentOfparent = domElement.parentNode.parentNode
    let parent = domElement.parentNode
    parentOfparent.insertBefore(instance.$el,domElement.parentNode.nextSibling)
    parent.insertBefore(instanceBox.$el,domElement.nextSibling)
})

        //end
    

        
    }
    handleSend(e,btn,box,source){
        console.log(e,btn,box)
        let now = new Date().getTime()
        let THIRTY_DAYS = 30 * 24 * 60 * 60 * 1000
        let thirtyDaysFromNow = now + THIRTY_DAYS
        let fieldId=source.dataset.fieldId
        let systemId=source.dataset.systemId
        console.log(source.dataset)
        let data={
            fieldId:fieldId,
            systemId:systemId,
            text:e,
            assignee: 'dha',
            date:thirtyDaysFromNow,
            pred:'0',
            done:'0',
           
        }
        console.log(data)
         this.addCommentAction(data)
    }
    handleChild(sourceBtn,Box){
        sourceBtn.show=false
        Box.show=true
        
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
}
export default CommentsMain
