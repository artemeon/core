import Vue from 'vue'
import VueRouter from 'vue-router'
import Router from '../Router'
Vue.use(<any>VueRouter)

const router = new VueRouter({
    routes: []
})
function resetContainer (to, from, next) : void {
    Router.cleanPage(true)
    next()
}

export default router
