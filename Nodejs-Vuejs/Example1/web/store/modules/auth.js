import axiosApi from '../../axiosApi'

// initial state
const state = {
    userData: {}
};

// getters
const getters = {
    getUserData: (state) => {
        return state.userData
    }
};

// actions
const actions = {
    signIn({commit, state}, payload) {
        return axiosApi.post(`/auth/signIn`, {
            data: JSON.stringify(payload)
        })
            .then(response => {
                if (response.data.token) {
                    commit('setUserData', response.data.user)
                }
                return response.data
            })
            .catch(e => {
                return e.response
            })
    },
    signUp({commit, state}, payload) {
        return axiosApi.post(`/auth/signUp`, {
            data: JSON.stringify(payload)
        })
            .then(response => {
                if (response.data.token) {
                    commit('setUserData', response.data.user)
                }
                return response.data
            })
            .catch(e => {
                return e.response
            })
    },
    userInfo({commit, state}) {
        const token = localStorage.getItem('token');
        if (!token) {
            return
        }
        if (!state.userData.id) {
            return axiosApi.get(`/profile/userInfo`)
                .then(response => {
                    if (response.data.user) {
                        commit('setUserData', response.data.user);
                        return {user: response.data.user}
                    }
                })
                .catch(e => {
                    return e.response
                })
        }
    },
    signOut({commit, state}, payload) {
        localStorage.clear();
        commit('setUserData', {})
    },
    updateUserData({commit, state}, payload) {
        commit('setUserData', payload.userData)
    },
    resendConfirmationCode({commit, state}, payload) {
        return axiosApi.post(`/auth/resendConfirmationCode`, {email: payload.email})
            .then(response => {
                return response
            })
            .catch(e => {
                return e.response
            })
    }
};

// mutations
const mutations = {
    setUserData(state, user) {
        state.userData = user
    }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
