import Vue from 'vue'
import Router from 'vue-router'
import Home from '@/components/Home'
import CounterWithProps from '@/components/CounterWithProps'
import CounterWithVuex from '@/components/CounterWithVuex'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'Home',
      component: Home
    },
    {
      path: '/counter-with-props',
      name: 'CounterWithProps',
      component: CounterWithProps
    },
    {
      path: '/counter-with-vuex',
      name: 'CounterWithVuex',
      component: CounterWithVuex
    }
  ]
})
