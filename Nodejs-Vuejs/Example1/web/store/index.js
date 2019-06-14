import Vue from 'vue'
import Vuex from 'vuex'
import common from './modules/common'
import auth from './modules/auth'
import profile from './modules/profile'

Vue.use(Vuex);
const debug = process.env.NODE_ENV !== 'production';

export default new Vuex.Store({
  modules: {
    common,
    auth,
    profile
  },
  strict: debug
})
