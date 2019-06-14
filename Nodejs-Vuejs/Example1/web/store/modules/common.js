import app from '../../main'
import i18n from '../../lang/lang'
import axiosApi from '../../axiosApi'

// initial state
const state = {
    languages: [],
    roles: [],
    activeLanguage: {},
    ipLocation: {}
};

// getters
const getters = {
    getRoles: (state) => {
        return state.roles
    },
    getLanguages: (state) => {
        return state.languages
    },
    getActiveLanguage: (state) => {
        return state.activeLanguage
    }
};

// actions
const actions = {
    async setLang({commit, state}, payload) {
        if (!payload) {
            payload = 'en'
        }
        let _i18n = i18n;
        if (app) {
            _i18n = app.$i18n
        }
        const lang = localStorage.getItem('lang');
        state.languages.forEach(language => {
            if (language.code === payload) {
                commit('SET_ACTIVE_LANGUAGE', language)
            }
        });
        if (payload !== lang) {
            localStorage.setItem('lang', payload)
        }
        if (payload in _i18n.messages) {
            commit('SET_LANG', payload)
        } else {
            try {
                // you can use fetch or import which ever you want.
                // Just make sure your webpack support import syntax
                // const res = await axios.get(`./src/lang/${payload}.json`)
                const res = await import(`../../lang/locale/${payload}.json`);

                _i18n.setLocaleMessage(payload, res);
                commit('SET_LANG', payload)
            } catch (e) {
                console.log(e)
            }
        }
    },
    getRoles({commit}, payload) {
        return axiosApi.get('/roles')
            .then(response => {
                commit('SET_ROLES', response.data)
                return {roles: response.data}
            })
            .catch(err => console.log(err))
    },
    gatRegistrationRoles({commit, state}, payload) {
        return axiosApi.get('/registrationRoles')
            .then(response => {
                return {regRoles: response.data}
            })
            .catch(err => console.log(err))
    },
    getLanguages({commit}, payload) {
        let languages = localStorage.getItem('languages');
        if (languages) { // todo this part will work if no any changes in languages table
            languages = JSON.parse(languages);
            commit('SET_LANGUAGES', languages);

            const lang = localStorage.getItem('lang');
            state.languages.forEach(language => {
                if (language.code === lang) {
                    commit('SET_ACTIVE_LANGUAGE', language)
                }
            });
            return {languages: languages}
        } else {
            return axiosApi.get('/languages/list')
                .then(response => {
                    commit('SET_LANGUAGES', response.data.languages);

                    const lang = localStorage.getItem('lang');
                    state.languages.forEach(language => {
                        if (language.code === lang) {
                            commit('SET_ACTIVE_LANGUAGE', language)
                        }
                    });
                    localStorage.setItem('languages', JSON.stringify(response.data.languages))
                    return {languages: response.data.languages}
                })
                .catch(err => console.log(err))
        }
    },
    updateActiveLanguage({commit}, payload) {
        commit('SET_ACTIVE_LANGUAGE', payload)
    },
    sendEmailContactUs({commit}, payload) {
        return axiosApi.post('/notification/contactUs', payload.emailData)
            .then(response => {
                return response.data
            })
            .catch(err => {
                return err.response
            })
    },
    getSettings({commit}, payload) {
        return axiosApi.get('/settings/list')
            .then(response => {
                return response
            })
            .catch(err => {
                return err.response
            })
    },
    updateSettings({commit}, payload) {
        return axiosApi.post('/settings/save', {data: payload.data})
            .then(response => {
                return response
            })
            .catch(err => {
                return err.response
            })
    },
    confirmEmail({commit}, payload) {
        return axiosApi.post('/auth/confirmEmail', {confirmCode: payload.confirmCode})
            .then(response => {
                return response
            })
            .catch(err => {
                return err.response
            })
    },
    getUserIpLocation({commit}, payload) {
        return axiosApi.get('/auth/getUserIpLocation', {
            params: {
                ipAddress: payload.ipAddress
            }
        })
            .then(response => {
                commit('SET_IP_LOCATION', response.data)
                return response
            })
            .catch(err => {
                return err.response
            })
    }
};

// mutations
const mutations = {
    SET_LANG(state, payload) {
        let _i18n = i18n;
        if (app) {
            _i18n = app.$i18n
        }
        _i18n.locale = payload
    },
    SET_LANGUAGES(state, payload) {
        state.languages = payload
    },
    SET_ROLES(state, payload) {
        state.roles = payload
    },
    SET_ACTIVE_LANGUAGE: (state, payload) => {
        state.activeLanguage = payload
    },
    SET_IP_LOCATION: (state, payload) => {
        state.ipLocation = payload.ipLocation
    }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
