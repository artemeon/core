
import MenuServices from '../services/MenuService'

const MenuModule = {
    namespaced: true,
    state: {
        aspects: [],
        isLoaded: false,
    },
    mutations: {
        SET_MENU(state: any, payload: Array<any>): void {
            state.aspects = payload
        },
        SET_IS_LOADED(state: any): void {
            state.isLoaded = true
        },
    },
    actions: {
        async getMenu({ commit }): Promise<void> {
            const [err, res] = await MenuServices.getMenu()
            if (res) {
                commit('SET_MENU', res.data.aspects)
                commit('SET_IS_LOADED')
            }
        },
    },
    getters: {},
}

export default MenuModule
