import axiosApi from '../../axiosApi'

// initial state
const state = {
    userData: {},
};

// getters
const getters = {
    getUserData: (state) => {
        return state.userData
    }
};

// actions
const actions = {
    userInfo({commit, state}) {
        const token = localStorage.getItem('access_token');
        if (!token) {
            return;
        }
        if (!state.userData.id) {
            return axiosApi.get(`/userInfo`)
                .then(response => {
                    if (response.data.user) {
                        commit('SET_USER_DATA', response.data.user);
                    }
                    return response.data;
                })
                .catch(e => {
                    return e.response
                })
        }
    },
    signOut({commit, state}, payload) {
        localStorage.clear();
        commit('SET_USER_DATA', {});
    }
};

// mutations
const mutations = {
    SET_USER_DATA(state, user) {
        state.userData = user;
    }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
