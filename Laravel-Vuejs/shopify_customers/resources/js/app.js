
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

import Vue from 'vue'
import vSelect from 'vue-select'
import ShopifyComponent from './components/ShopifyComponent.vue'
import Notify from 'vue2-notify'
// Use Notify
Vue.use(Notify, {
    position: 'top-right'
});

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('shopify-component', ShopifyComponent);
Vue.component('v-select', vSelect);

const app = new Vue({
    el: '#app'
});
