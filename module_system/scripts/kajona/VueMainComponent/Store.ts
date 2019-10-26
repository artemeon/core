import Vue from 'vue'
import Vuex from 'vuex'
import RatingDetailModule from 'core_customer/module_hsbcact/scripts/modules/RatingDetailModule'
import SearchModule from 'core/module_search/scripts/modules/SearchModule'
import ReportconfiguratorModule from 'core_agp/module_reportconfigurator/scripts/modules/ReportconfiguratorModule'
// import VueI18n from 'vue-i18n'
Vue.use(<any>Vuex)
// Vue.use(VueI18n)
export default new Vuex.Store({
    modules: {
        SearchModule: SearchModule,
        ReportconfiguratorModule: ReportconfiguratorModule,
        ratingModule: RatingDetailModule
    }
})
