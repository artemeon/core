import CommentsServices from "core/module_comments/scripts/services/CommentsServices"

const CommentsModule = {
    namespaced: true,
    state: {comments: []},
    mutations: {
        LIST_COMMENTS(state,payload): void{
            state.comments = payload
            console.log(state.comments)
        }
    },
    actions: {
        async listCommentsAction({commit},payload) : Promise<void> {
        
            const [err, res] = await CommentsServices.listComments(payload)
            commit('LIST_COMMENTS',res.data.comments)
        },
        async addCommentAction ({ commit,dispatch },payload) : Promise<void> {
            const [err, res] = await CommentsServices.addComment(payload)
            dispatch('listCommentsAction',{id:payload.systemId,field:payload.fieldId})
        },
        async getUsersList({commit},payload):Promise<void>{
            const [err, res] = await CommentsServices.listUsers(payload)
        }
    },
    getters: {
        getById: (state) => (id) => {
            return state.comments.find(comment => comment.fieldId === id)
          }
    }
}

export default CommentsModule
