import Vue from 'vue';
import VueRouter from 'vue-router';
import WelcomePage from '../views/WelcomePage';
import Products from '../views/Products';
import Product from '../views/Product';
import ProfilePage from '../views/ProfilePage';
import VueJwtDecode from 'vue-jwt-decode';
import AccountsPage from '../views/AccountsPage';
import MyAccount from '../components/profilePage/MyAccount';
import MyProducts from '../components/profilePage/MyProducts';
import MyFriends from '../components/profilePage/MyFriends';
import Notifications from '../components/profilePage/Notifications';
import Settings from '../components/profilePage/Settings';

Vue.use(VueRouter);

const Router = new VueRouter({
    mode: 'history',
    fallback: false,
    routes: [
        {
            path: '/',
            name: 'WelcomePage',
            component: WelcomePage
        },
        {
            path: '/forgotPassword',
            name: 'ForgotPassword',
            component: WelcomePage
        },
        {
            path: '/confirmEmail',
            name: 'confirmEmail',
            component: WelcomePage
        },
        {
            path: '/products',
            name: 'products',
            component: Products
        },
        {
            path: '/products/:productId',
            name: 'product',
            component: Product
        },
        {
            path: '/profile',
            name: 'ProfilePage',
            component: ProfilePage,
            meta: {
                isAuthenticated: true,
                rolePermissions: ['user', 'admin']
            },
            children: [
                {
                    path: 'my-account',
                    name: 'ProfileMyAccount',
                    component: MyAccount,
                    meta: {
                        isAuthenticated: true,
                        rolePermissions: ['user', 'admin']
                    }
                },
                {
                    path: 'my-products',
                    name: 'ProfileMyProducts',
                    component: MyProducts,
                    meta: {
                        isAuthenticated: true,
                        rolePermissions: ['user', 'admin']
                    }
                },
                {
                    path: 'my-friends',
                    name: 'ProfileMyFriends',
                    component: MyFriends,
                    meta: {
                        isAuthenticated: true,
                        rolePermissions: ['user', 'admin']
                    }
                },
                {
                    path: 'notifications',
                    name: 'ProfileNotifications',
                    component: Notifications,
                    meta: {
                        isAuthenticated: true,
                        rolePermissions: ['user', 'admin']
                    }
                },
                {
                    path: 'settings',
                    name: 'ProfileSettings',
                    component: Settings,
                    meta: {
                        isAuthenticated: true,
                        rolePermissions: ['user', 'admin']
                    }
                }
            ]
        },
        {
            path: '/accounts/:userId',
            name: 'AccountsPage',
            component: AccountsPage,
            meta: {
                isAuthenticated: true,
                rolePermissions: ['user', 'admin']
            }
        }
    ]
});

Router.beforeEach((to, from, next) => {
    if (to.meta.isAuthenticated) {
        let userPermissions = decodeJwtToken();
        if (userPermissions) {
            let accepted = false;
            to.meta.rolePermissions.forEach(rolePermission => {
                if (userPermissions.includes(rolePermission)) {
                    accepted = true;
                }
            });
            if (accepted) {
                next()
            } else {
                next('/')
            }
        } else {
            next('/')
        }
    } else {
        next()
    }
});
function decodeJwtToken() {
    let token = localStorage.getItem('token');
    if (token) {
        return VueJwtDecode.decode(token).permissions;
    }
}

export default Router
