import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export const store = new Vuex.Store({
  state: {
    counterResult: 0
  },
  getters: {
    showCounterResult: state => {
      return state.counterResult
    }
  },
  mutations: {
    raiseCounterResult (state, raiseNumber) {
      state.counterResult = (parseFloat(state.counterResult) * 10 + parseFloat(raiseNumber) * 10) / 10
    },
    decreaseCounterResult (state, decreaseNumber) {
      state.counterResult = (parseFloat(state.counterResult) * 10 - parseFloat(decreaseNumber) * 10) / 10
    }
  },
  actions: {
    raiseCounterResult ({commit, dispatch, state}, raiseNumber) {
      commit('raiseCounterResult', raiseNumber)
    },
    decreaseCounterResult ({commit, dispatch, state}, decreaseNumber) {
      commit('decreaseCounterResult', decreaseNumber)
    }
  }
})
