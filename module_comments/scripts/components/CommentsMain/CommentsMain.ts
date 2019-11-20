import { Component, Mixins ,Watch} from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import {namespace} from 'vuex-class'
import CommentsModule from "core/module_comments/scripts/modules/CommentsModule"
import CommentsEdit from "../CommentsEdit/CommentsEdit.vue"
import CommentsBox from '../CommentsBox/CommentsBox.vue'
import Vue from 'vue'
@Component({components: {CommentsEdit,CommentsBox}})
class CommentsMain extends Mixins(LangMixin(['comments'])) {
    @namespace('commentsModule').Action addCommentAction: any
    @namespace('commentsModule').Action listCommentsAction: any
    @namespace('commentsModule').Action getUsersList: any
    @namespace('commentsModule').State comments: any
    @namespace('commentsModule').Getter getById: any
    test = []
    openedComment = null
    sourceBtnClicked = null
    boxId = ''
    heightClass = ''
    systemId = ''
    created(){
        const name = 'commentsModule'
        const store = this.$store
        if (!(store && store.state && store.state[name])) {
            store.registerModule(name, CommentsModule)
        }
    }
    openCommentBox(id: string): void{
        this.boxId = id
        this.openedComment=id
        this.heightClass = 'core-component-main-comment'
        this.listCommentsAction({id:this.systemId,field:this.boxId})
    }
    closeCommentBox(){
        this.openedComment=null
        this.heightClass=''
        this.sourceBtnClicked.show=true
    }
    mounted(){
        const parent = document.getElementById('content')
        parent.appendChild(this.$el)
        parent.addEventListener('DOMSubtreeModified', this.callback, true)
         this.getUsersList()
    }

    @Watch('test')
     Onchange(old){
        // console.log('change')
if(this.test.length >0){
    
    // console.log('change')
    this.openedComment = null
let currentSystem = this.test[0].dataset.systemId
this.systemId=currentSystem
// this.listCommentsAction(currentSystem)
//    console.log('done')
   //test 
this.test.map(domElement=>{
let field = domElement.dataset.fieldId
// console.log(this.getById(field),this.comments)
let componentClass = Vue.extend(CommentsEdit)
let instance = new componentClass(
)
instance.$mount()
// let componentClassBox = Vue.extend(CommentsBox)
// let instanceBox = new componentClassBox(
// )
// instanceBox.$mount()
const me = this
instance.$on('click', ()=>{me.handleChild(instance,field)})
// instanceBox.$on('send', (e)=>{me.handleSend(e,instance,instanceBox,domElement)})
let parentOfparent = domElement.parentNode.parentNode
let parent = domElement.parentNode
parentOfparent.insertBefore(instance.$el,domElement.parentNode.nextSibling)
// parent.insertBefore(instanceBox.$el,domElement.nextSibling)

})
}

    

        
    }
    handleSend(params){
        
        let now = new Date().getTime()
        let THIRTY_DAYS = 30 * 24 * 60 * 60 * 1000
        let thirtyDaysFromNow = now + THIRTY_DAYS
        let fieldId=params.id
        let systemId=params.systemId
        
        let data={
            fieldId:fieldId,
            systemId:systemId,
            text:params.text,
            assignee: 'sir',
            date:thirtyDaysFromNow,
            pred:'0',
            done:'0',
           
        }
        console.log(data)
      this.addCommentAction(data)
    }
    handleChild(sourceBtn,id){
        if(this.sourceBtnClicked){
            this.sourceBtnClicked.show=true
        }
        sourceBtn.show=false
        this.openCommentBox(id)
        this.sourceBtnClicked = sourceBtn
        
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
        }else{
            this.test = []
            this.openedComment=null 
            this.sourceBtnClicked=null
            this.heightClass = ''
        }
          
    }
}
export default CommentsMain
