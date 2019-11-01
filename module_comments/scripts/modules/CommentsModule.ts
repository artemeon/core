import CommentsServices from "core/module_comments/scripts/services/CommentsServices"

const CommentsModule = {
    namespaced: true,
    state: {},
    mutations: {},
    actions: {
        async addCommentAction ({ commit }) : Promise<void> {
            const [err, res] = await CommentsServices.addComment('test')
        }
    },
    getters: {}
}
export default CommentsModule
