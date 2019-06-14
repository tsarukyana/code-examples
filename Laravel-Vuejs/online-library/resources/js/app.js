
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
require('./bootstrap');

import Vue from 'vue';
import BootstrapVue from 'bootstrap-vue';
import VueRouter from 'vue-router';
import store from './store'
import VueScrollTo from 'vue-scrollto';

Vue.use(VueRouter);
Vue.use(BootstrapVue);
Vue.use(VueScrollTo, {
    container: "body",
    duration: 500,
    easing: "ease",
    offset: 0,
    force: true,
    cancelable: true,
    onStart: false,
    onDone: false,
    onCancel: false,
    x: false,
    y: true
})


import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'
import 'bootstrap-social/bootstrap-social.css';

import App from './views/App';
import Home from './views/Home';
import BookDetails from './views/BookDetails';
import Auth from './views/Auth';

const router = new VueRouter({
    mode: 'history',
    routes: [
        {
            path: '/',
            name: 'home',
            component: Home,
            meta: {
                auth: true // A protected route
            },
        },
        {
            path: '/book/details',
            name: 'book.details',
            component: BookDetails,
            meta: {
                auth: true // A protected route
            },
        },
        {
            path: '/auth/login',
            name: 'auth.login',
            component: Auth,
        },
        {
            path: '/auth/logout',
            name: 'auth.logout',
            component: Auth,
        }
    ],
});

router.beforeEach((to, from, next) => {
    if (to.meta && to.meta.auth) {
        if (to.name === 'home') {
            if (to.query && to.query.token) {
                localStorage.setItem('access_token', to.query.token);
                localStorage.setItem('token_type', 'Bearer');
                next();
            } else if (!localStorage.getItem('access_token'))  {
                next('/auth/login');
            } else {
                next();
            }
        }
        next();
    } else {
        next();
    }
});

const app = new Vue({
    el: '#app',
    components: { App },
    store,
    router,
});

window.Vue = app;
