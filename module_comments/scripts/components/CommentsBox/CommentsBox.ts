import { Component, Mixins ,Prop, Watch} from 'vue-property-decorator'
import { LangMixin } from 'core/module_system/scripts/kajona/VueMixings'
import ClickOutside from 'vue-click-outside'


@Component({  directives: {
    ClickOutside
  }})
class CommentsBox extends Mixins(LangMixin(['comments'])) {
    @Prop () id 
    @Prop () systemId
    @Prop () comments
    private isOpen = false
    // private show = false
    private message = ''
    mounted(){
        
    }
    @Watch('message')
    OnMessageChange(old){
         //TODO query users
         console.log(this.message)
    }
    hasAt(e){
        e = (e) ? e : window.event;
        let charCode = (e.which) ? e.which : e.keyCode;
        let count = this.message.indexOf('@')
        if (charCode===64) {  
          if(count > -1)
          {
        
          e.preventDefault()
            }
        } 
    }
    close(){
        this.$emit('close')
    }
    sendComment(){
        console.log('send comment')
        this.$emit('send',{text:this.message,id:this.id,systemId:this.systemId})
    }

}
export default CommentsBox
