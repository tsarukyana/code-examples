import axiosApi from '../../axiosApi'

// initial state
const state = {
    bookDetails: {}
};

// getters
const getters = {
    getBookDetails: (state) => {
        return state.bookDetails
    }
};

// actions
const actions = {
    search({commit, state}, payload) {
        return axiosApi.post(`/books/search`, {q: payload.q})
        .then(response => {
            return response.data
        })
        .catch(e => {
            return e.response
        });
    },
    details({commit, state}, payload) {

        commit('SET_BOOK_DETAILS', payload.book);
        return payload.book;

        // todo correct version need to get details from api
        /*
        return axiosApi.post(`/books/search`, {q: payload.q})
        .then(response => {
            return response.data
        })
        .catch(e => {
            return e.response
        });
        */
    }
};

// mutations
const mutations = {
    SET_BOOK_DETAILS(state, book) {
        state.bookDetails = book;
    }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
}
