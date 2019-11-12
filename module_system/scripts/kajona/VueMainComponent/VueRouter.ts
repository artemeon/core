import Vue from 'vue'
import VueRouter from 'vue-router'
// import RatingDetail from 'core_agp/module_hsbcact/scripts/components/RatingDetail/RatingDetail.vue'
// import Reportgenerator from 'core_agp/module_reportconfigurator/scripts/components/Reportgenerator/Reportgenerator.vue'
import Router from '../Router'

Vue.use(<any>VueRouter)

const router = new VueRouter({
    routes: [
        // {
        //     path: '/vm/reportconfigurator/:reportId/:page?',
        //     name: 'reportconfigurator',
        //     component: Reportgenerator,
        //     beforeEnter: resetContainer,
        // },
        // {
        //     path: '/vm/hsbcact/rating/:systemId',
        //     component: RatingDetail,
        //     beforeEnter: resetContainer,
        //     props: (route) => ({
        //         query: {
        //             startDate: route.query.startDate,
        //             endDate: route.query.endDate,
        //         },
        //     }),
        // },
    ],
})
function resetContainer(to, from, next): void {
    Router.cleanPage(true)
    next()
}

export default router
