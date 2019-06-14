import axiosApi from '../../axiosApi'

// initial state
const state = {
    userData: {
        friends: []
    },
    categories: [],
    notifications: [],
    newNotificationsCount: 0
};

// getters
const getters = {
    getUserData: (state) => {
        return state.userData
    },
    getCategories: (state) => {
        return state.categories
    },
    getNotifications: (state) => {
        return state.notifications
    }
};

// actions
const actions = {
    updateUser({commit, state}, payload) {
        return axiosApi.put(`/users/${payload.id}`, payload)
            .then(response => {
                if (response.data.user) {
                    commit('setUserData', response.data.user)
                }
                return response.data
            })
            .catch(e => {
                console.log(e)
            })
    },
    uploadProfilePicture({commit, state}, payload) {
        return axiosApi.post(`/profile/upload`, payload)
            .then(response => {
                commit('setUserData', response.data)
                return {user: response.data}
            })
            .catch(err => console.log(err))
    },
    saveProduct({commit, state}, payload) {
        return axiosApi.post(`/product/save`, payload)
            .then(response => {
                if (payload.uploadImages && payload.uploadImages.length > 0) {
                    const makeRequestsFromArray = (arr) => {
                        let index = 0;
                        const request = () => {
                            return axiosApi.post(`/product/uploadImage/${response.data.product.id}`, arr[index]).then((res) => {
                                index++;
                                if (index >= arr.length) {
                                    return res.data
                                }
                                return request()
                            })
                        };
                        return request()
                    };

                    let arrImageBlob = [];
                    payload.uploadImages.map((imageBlob, i) => {
                        const formData = new FormData();
                        formData.append('file', imageBlob);
                        arrImageBlob[i] = formData
                    });
                    return makeRequestsFromArray(arrImageBlob)
                } else {
                    return response.data
                }
            })
            .catch(e => {
                console.log(e)
            })
    },
    sendInvitationToFriend({commit, state}, payload) {
        return axiosApi.post('/email/sendInvitation', payload)
            .then(response => {
                return response.data
            })
            .catch(err => {
                console.log(err)
            })
    },
    getUserFriends({commit, state}, payload) {
        return axiosApi.get('/user/friends').then(response => {
            commit('setUserFriends', response.data);
            return {friends: response.data}
        }).catch(err => {
            console.log(err)
        })
    },
    deleteProduct({commit, state}, payload) {
        return axiosApi.delete(`/product/delete/${payload}`)
            .then(response => {
                return response.data
            })
            .catch(e => {
                console.log(e)
            })
    },
    getUserProducts({commit, state}, payload) {
        return axiosApi.get(`/product/getAll`)
            .then(response => {
                commit('setUserProducts', response.data.products);
                return {products: response.data.products}
            })
            .catch(e => {
                console.log(e)
            })
    },
    getAllCategories({commit, state}, payload) {
        return axiosApi.get(`/product/getAllCategories`)
            .then(response => {
                commit('setCategories', response.data.categories);
                return {categories: response.data.categories}
            })
            .catch(e => {
                console.log(e)
            })
    },
    clearUserData({commit, state}, payload) {
        commit('setUserData', {})
    },
    clearCategories({commit, state}, payload) {
        commit('setCategories', {})
    },
    getUserNotifications({commit, state}, payload) {
        return axiosApi.get(`/notification/userNotifications`)
            .then(response => {
                if (response.data.notifications) {
                    commit('setUserNotifications', response.data.notifications);
                    return response.data
                }
            })
            .catch(e => {
                console.log(e)
            })
    },
    deleteNotification({commit}, payload) {
        return axiosApi.delete(`/notification/delete/${payload}`)
            .then(response => {
                if (response.data.notifications) {
                    commit('setUserNotifications', response.data.notifications)
                }
                return response.data
            })
            .catch(e => {
                console.log(e)
            })
    },
    hideNotificationCount({commit}, payload) {
        return axiosApi.get(`/notification/hideUserNotificationsCount`)
            .then(response => {
                if (response.data.notifications) {
                    commit('setUserNotifications', response.data.notifications)
                }
                return response.data
            })
            .catch(e => {
                console.log(e)
            })
    },
    acceptInvitation({commit, state}, payload) {
        return axiosApi.post(`/user/acceptInvitation`, {
            data: JSON.stringify(payload)
        }).then(response => {
            if (response.data.notifications) {
                commit('setUserNotifications', response.data.notifications)
            }
            if (response.data.friends) {
                commit('setUserFriends', response.data.friends)
            }
            return response.data
        }).catch(e => {
            console.log(e)
        })
    },
    checkUserForgotPassword({commit, state}, payload) {
        return axiosApi.post(`/user/forgotPassword`, {
            data: payload
        }).then(response => {
            return response.data
        }).catch(e => {
            console.log(e)
        })
    },
    forgotPasswordBoolean({commit}, payload) {
        return axiosApi.post(`/user/forgotPasswordBoolean`, {forgotPassword: payload})
            .then(response => {
                return response.data
            })
            .catch(e => {
                return e.response
            })
    },
    changeUserPassword({commit}, payload) {
        return axiosApi.post(`/user/changeUserPassword`, payload)
            .then(response => {
                return response.data
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
    },
    setUserProducts(state, products) {
        state.products = products
    },
    setCategories(state, categories) {
        state.categories = categories
    },
    setUserFriends(state, friends) {
        state.userData.friends = friends
    },
    setUserNotifications(state, notifications) {
        let count = 0;
        notifications.forEach((a) => {
            if (a.show === true) {
                count++
            }
        });
        state.newNotificationsCount = count;
        state.notifications = notifications
    }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
