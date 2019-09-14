import Vue from 'vue'
import Vuex from 'vuex'
import SearchModule from 'core/module_search/scripts/modules/SearchModule'
// import VueI18n from 'vue-i18n'
Vue.use(<any>Vuex)
// Vue.use(VueI18n)
export default new Vuex.Store({
    modules: {
        SearchModule: SearchModule
    }
})
