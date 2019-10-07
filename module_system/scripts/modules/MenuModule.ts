
import MenuServices from '../services/MenuService'

const MenuModule = {
    namespaced: true,
    state: { aspects: [] },
    mutations: {
        SET_MENU(state: any, payload: Array<any>): void {
            state.aspects = payload
        },
    },
    actions: {
        async getMenu({ commit }): Promise<void> {
            const [err, res] = await MenuServices.getMenu()
            if (res) {
                this.aspects = res.data.aspects
                commit('SET_MENU', res.data.aspects)
                console.log(res)
            }
        },
    },
    getters: {},
}

export default MenuModule
