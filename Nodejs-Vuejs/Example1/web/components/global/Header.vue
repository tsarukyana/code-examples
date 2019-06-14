<template>
    <div>
        <v-container
                fluid
                class="p-0 h-100"
        >
            <v-toolbar>
                <v-layout row wrap class="align-center">
                    <v-flex xs12 md2>
                        <v-toolbar-title class="d-flex justify-center">
                            <a @click="$router.push('/')"><i class="g-brand"></i></a>
                            <div class="d-flex  g-full-logo">
                                <a @click="$router.push('/')"><i class="g-brand-giver"></i></a>
                                <span>{{getLanguageName ('goal')}}</span>
                            </div>
                        </v-toolbar-title>
                    </v-flex>
                    <v-flex md3 lg4>
                        <v-toolbar-items>
                            <v-flex
                                    class="g-menu-btn"
                            >
                                <v-icon>menu</v-icon>
                            </v-flex>
                            <v-text-field
                                    class="g-searchBar"
                                    v-model="search"
                                    :placeholder="$t('search_for')"
                                    append-icon="search"
                                    @click:append="() => (openSearch = !openSearch)"
                                    hide-details
                                    dark
                            >
                            </v-text-field>
                        </v-toolbar-items>
                    </v-flex>
                    <v-flex md4 lg3 d-flex justify-space-around class=" hidden-sm-and-down g-header-links ">
                        <v-btn flat v-if="false">{{$t('headers.givers')}}</v-btn>
                        <v-btn flat v-if="false">{{$t('headers.receivers')}}</v-btn>
                        <v-btn flat @click="$router.push('/products')">{{$t('headers.product')}}</v-btn>
                        <v-btn flat>{{$t('headers.about_us')}}</v-btn>
                        <v-btn flat>{{$t('headers.get_in_touch')}}</v-btn>
                    </v-flex>
                    <v-flex md2>
                        <v-toolbar-items v-if="!isLogged" class="hidden-sm-and-down d-flex justify-space-around">
                            <v-btn class="g-btn-default g-btn-135" @click.prevent="openRegistrationModal()">{{
                                $t('registration') }}
                            </v-btn>
                            <v-btn class="g-btn-main g-btn-135" @click.prevent="openLoginModal()">{{ $t('sign_in') }}
                            </v-btn>
                        </v-toolbar-items>
                        <v-toolbar-items v-else-if="isLogged" class="d-flex justify-space-around">
                            <v-menu open-on-hover bottom offset-y>
                                <v-avatar v-if="isLogged && !signUpData.image" slot="activator"
                                          size="55px">
                                    <img src="../../../src/assets/images/NoImage.png"
                                         alt="Avatar">
                                </v-avatar>
                                <v-avatar v-else-if="isLogged && signUpData.image" slot="activator"
                                          size="55px">
                                    <img :src=" apiUrl+ signUpData.image"/>
                                </v-avatar>
                                <v-list>
                                    <v-list-tile
                                            v-for="(item, index) in profileItemList"
                                            :key="index"
                                            @click="profileStaticFunction($event, item.path)">
                                        <v-list-tile-title>{{ item.title }}</v-list-tile-title>
                                    </v-list-tile>
                                </v-list>
                            </v-menu>
                            <v-menu open-on-click bottom offset-y>
                                <v-btn slot="activator" class="notificationButton" @click="hideCount">
                                    <v-badge color="red">
                                        <span v-if="newNotificationsCount" slot="badge">{{newNotificationsCount}}</span>
                                        <v-icon large color="grey">notifications</v-icon>
                                    </v-badge>
                                </v-btn>
                                <v-list>
                                    <v-subheader>{{$t('notification_list')}}</v-subheader>
                                    <v-divider></v-divider>
                                    <template v-for="(item, index) in notifications.slice(0, 5)">
                                        <v-list-tile :key="index">
                                            <v-list-tile-content>
                                                <v-list-tile-title @click="changeActiveTab">{{
                                                    item.Notification.description }}
                                                </v-list-tile-title>
                                            </v-list-tile-content>
                                            <v-list-tile-action>
                                                <v-btn icon class="mx-0" @click="deleteNotification(item.id)">
                                                    <v-icon color="pink">clear</v-icon>
                                                </v-btn>
                                            </v-list-tile-action>
                                        </v-list-tile>
                                        <v-divider v-if="index < notifications.length"
                                                   :key="`divider-${index}`"></v-divider>
                                    </template>
                                    <v-list-tile @click="changeActiveTab">{{$t('see_all_notifications')}}</v-list-tile>
                                </v-list>
                            </v-menu>
                        </v-toolbar-items>
                    </v-flex>
                    <v-flex md1>
                        <v-toolbar-items class="justify-center">
                            <v-menu class="g-language-select">
                                <v-flex slot="activator">
                                    <i :class="langIcon"></i>
                                    <i class="g-grey-icon ml-2"></i>
                                </v-flex>
                                <v-list>
                                    <v-list-tile
                                            v-for="(language, index) in languages"
                                            :key="index"
                                            @click="setLang(language)">
                                        <v-list-tile-title>{{ language.name }}</v-list-tile-title>
                                    </v-list-tile>
                                </v-list>
                            </v-menu>
                        </v-toolbar-items>
                    </v-flex>
                </v-layout>
            </v-toolbar>
        </v-container>
        <!--Registration-->
        <v-dialog v-model="dialog" persistent max-width="500px">
            <v-card>
                <v-layout class="g-cancel-btn">
                    <v-btn
                            class="ex-v-btn ex-close-btn"
                            @click.native="dialog = false">
                        <img src="../../assets/images/cancel.svg" alt="">
                    </v-btn>
                </v-layout>

                <v-card-title class="justify-center">
                    <h1 class="g-registration">{{ $t('create_account') }}</h1>
                    <p class="g-info-title text-xs-center">{{ $t('there_are_many_variations') }}</p>
                </v-card-title>
                <v-alert :value="regModalAlert.value" :type="regModalAlert.type">
                    <p v-html="regModalAlert.text"></p>
                </v-alert>
                <v-card-text v-if="!regModalAlert.isConfirmEmail">
                    <v-container grid-list-md>
                        <v-layout class="justify-center g-social-icons">
                            <v-flex class="g-icon-style">
                                <a :href="`${apiUrl}/api/auth/facebook`"><i class="fa fa-facebook"
                                                                            aria-hidden="true"></i></a>
                            </v-flex>
                            <v-flex class="g-icon-style">
                                <a :href="`${apiUrl}/api/auth/google`"><i class="fa fa-google-plus"
                                                                          aria-hidden="true"></i></a>
                            </v-flex>
                        </v-layout>
                        <v-layout wrap>
                            <v-form class="w-100">
                                <v-flex xs12>
                                    <v-text-field
                                            :class="{'invalid-field': this.regModalAlert.value && !this.regData.firstName}"
                                            class="ex-input-group"
                                            @keypress="enterKeyPressRegistration"
                                            :placeholder="$t('first_name')"
                                            v-model="regData.firstName"
                                            :rules="nameRules"
                                    >
                                    </v-text-field>
                                    <span></span>
                                </v-flex>
                                <v-flex xs12>
                                    <v-text-field
                                            class="ex-input-group"
                                            @keypress="enterKeyPressRegistration"
                                            :placeholder="$t('last_name')"
                                            v-model="regData.lastName"
                                            :rules="lastNameRules"
                                    >
                                    </v-text-field>
                                </v-flex>
                                <v-flex xs12>
                                    <v-text-field
                                            class="ex-input-group"
                                            @keypress="enterKeyPressRegistration"
                                            :placeholder="$t('email')"
                                            v-model="regData.email"
                                            :rules="exEmailRules"
                                    >
                                    </v-text-field>
                                </v-flex>
                                <v-flex xs12>
                                    <v-text-field
                                            class="ex-input-group"
                                            @keypress="enterKeyPressRegistration"
                                            :placeholder="$t('password')"
                                            type="password"
                                            v-model="regData.password"
                                            :rules="accoundPassword"
                                    >
                                    </v-text-field>
                                </v-flex>
                                <v-flex xs12>
                                    <v-text-field ref="confirmConfirm"
                                                  class="ex-input-group"
                                                  @keypress="enterKeyPressRegistration"
                                                  :placeholder="$t('confirm_password')"
                                                  type="password"
                                                  v-model="regData.password_confirmation"
                                                  :rules="confirmPassword"
                                    >
                                    </v-text-field>
                                </v-flex>
                                <!--need to allow choose user or company roles in the future-->
                                <v-flex xs12 v-if="false">
                                    <v-select
                                            :items="roles"
                                            name="name"
                                            item-text="name"
                                            :label="$t('role')"
                                            v-model="regData.role"
                                    ></v-select>
                                </v-flex>
                                <v-card-actions>
                                    <v-layout class="justify-center align-center" column>
                                        <v-btn class="g-btn-main g-big-btn" flat @click="signUp()"
                                               v-if="!regModalAlert.isConfirmEmail">{{ $t('registration') }}
                                        </v-btn>
                                        <p class="m25-0 ex-info-text">{{ $t('already_have_an_account') }}<span
                                                @click.prevent="openLoginModal()">{{$t('sign_up')}}</span></p>
                                    </v-layout>
                                </v-card-actions>
                            </v-form>
                        </v-layout>
                    </v-container>
                    <!--<small>*indicates required field</small>-->
                </v-card-text>
            </v-card>
        </v-dialog>
        <!--Login-->
        <v-dialog v-model="loginModal" persistent max-width="500px">
            <v-card>
                <v-layout class="g-cancel-btn">
                    <v-btn
                            class="ex-v-btn ex-close-btn"
                            @click.native="loginModal = false">
                        <img src="../../assets/images/cancel.svg" alt="">
                    </v-btn>
                </v-layout>
                <v-card-title
                        class="justify-center">
                    <v-layout column class="justify-center">
                        <h1 class="g-registration">{{ $t('sign_in') }}</h1>
                        <p class="g-info-title text-xs-center">{{ $t('there_are_many_variations_2') }}</p>
                    </v-layout>
                </v-card-title>
                <v-alert v-if="showRequestAlert" :value="true" type="info">
                    <p>{{ $t('header.product_request_alert')}}
                        <a href="" @click.prevent="openRegistrationModal(); loginModal = false">{{
                            $t('header.register_simply')}}</a>
                    </p>
                </v-alert>
                <v-card-text>
                    <v-container grid-list-md>
                        <v-layout class="justify-center g-social-icons">
                            <v-flex class="g-icon-style">
                                <a :href="`${apiUrl}/api/auth/google`"><i class="fa fa-google-plus"
                                                                          aria-hidden="true"></i></a>
                            </v-flex>
                            <v-flex class="g-icon-style">
                                <a :href="`${apiUrl}/api/auth/facebook`"><i class="fa fa-facebook"
                                                                            aria-hidden="true"></i></a>
                            </v-flex>
                        </v-layout>
                        <v-layout wrap>
                            <v-flex xs12>
                                <v-text-field
                                        class="ex-input-group"
                                        @keypress="enterKeyPressLogin"
                                        v-model="logEmail"
                                        :placeholder="$t('email')"
                                        :rules="exEmailRules"
                                        required>
                                </v-text-field>
                            </v-flex>
                            <v-flex xs12>
                                <v-text-field
                                        class="ex-input-group"
                                        @keypress="enterKeyPressLogin"
                                        v-model="logPass"
                                        :rules="accoundPassword"
                                        :placeholder="$t('password')"
                                        ref="logPass" type="password"
                                        required>
                                </v-text-field>
                            </v-flex>
                            <v-flex xs12>
                                <v-layout class="row justify-space-between align-center">
                                    <v-checkbox
                                            :label=" $t('remember_me')"
                                            value="rememberMe"
                                    ></v-checkbox>
                                    <p class="forgot-password-p" @click.prevent="forgotPasswordModal()">
                                        {{$t('forgot_password')}}</p>
                                </v-layout>
                            </v-flex>
                        </v-layout>

                    </v-container>
                    <!--<small>*indicates required field</small>-->
                </v-card-text>
                <v-card-actions>
                    <v-layout class="justify-center align-center" column>
                        <v-spacer></v-spacer>
                        <v-btn class="g-btn-main-light g-big-btn" flat @click.native="signIn()">{{ $t('login') }}
                        </v-btn>
                        <p class="m25-0 ex-info-text">{{ $t('already_have_an_account') }}<span
                                @click.prevent="openLoginModal()">{{$t('sign_up')}}</span></p>
                    </v-layout>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <!--forgot password-->
        <v-dialog v-model="forgotModal" max-width="500px">
            <v-card>
                <v-card-title class="justify-center">
                    <p class="g-registration">{{ $t('forgot_password') }}</p>
                </v-card-title>
                <v-card-text>
                    <v-alert :value="loginModalAlert.value" :type="loginModalAlert.type">
                        {{loginModalAlert.text}}
                    </v-alert>
                    <v-container grid-list-md>
                        <v-text-field
                                @keypress="enterKeyPressForgotPassword"
                                v-model="forgotPasswordInputText"
                                :label="$t('email')"
                                :placeholder="$t('write_email')"
                                required></v-text-field>
                    </v-container>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <div
                            class="loading-parent"
                            v-if="loading"
                    >
                        <v-icon v-show="loading">fa fa-spinner fa-spin</v-icon>
                    </div>
                    <div v-if="!loading">
                        <v-btn class="g-btn-default" flat @click="backLoginModal">{{ $t('back') }}</v-btn>
                        <v-btn class="g-btn-default" flat @click="forgotPasswordEmailCheck">{{ $t('send') }}</v-btn>
                    </div>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <!--Modal where they type in a new password-->
        <v-dialog v-model="modalNewPassword" persistent max-width="600px">
            <v-card>
                <v-card-title class="justify-center">
                    <p class="g-registration">{{ $t('modal_forgot_new_password') }}</p>
                </v-card-title>
                <v-card-text>
                    <v-alert :value="loginModalAlert.value" :type="loginModalAlert.type">
                        {{loginModalAlert.text}}
                    </v-alert>
                    <v-container grid-list-md>
                        <v-text-field
                                @keypress="enterSendModalNewPasswordInfo"
                                v-model="newPassword"
                                :label="$t('password')"
                                type="password"
                                :placeholder="$t('new_password')"
                                required></v-text-field>
                        <v-text-field
                                @keypress="enterSendModalNewPasswordInfo"
                                v-model="confirmNewPassword"
                                :label="$t('password')"
                                type="password"
                                :placeholder="$t('confirm_password')"
                                required></v-text-field>
                    </v-container>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="g-btn-default" flat @click.native="closeModalNewPasswordInfo">{{ $t('close') }}
                    </v-btn>
                    <v-btn class="g-btn-default" flat @click="sendModalNewPasswordInfo">{{ $t('send') }}</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
    import {mapState} from 'vuex'
    import VueJwtDecode from 'vue-jwt-decode'
    import {EventBus} from '../../event-bus'
    import {setAuthHeader} from '../../axiosApi'
    import {validateEmail} from '../../helpers/main'

    export default {
        name: 'Header',
        props: ['showRegistrationModal', 'showLoginModal', 'settings', 'productRequestAlert'],
        data: () => ({
            openSearch: false,
            fullscreen: true,
            search: '',
            loading: false,
            changePasswordUserId: '',
            newPassword: '',
            confirmNewPassword: '',
            apiUrl: process.env.ROOT_API,
            regData: {},
            forgotPasswordInputText: '',
            show: false,
            langIcon: 'g-language-' + localStorage.getItem('lang'),
            isLogged: false,
            dialog: false,
            loginModal: false,
            forgotModal: false,
            modalNewPassword: false,
            logEmail: '',
            logPass: '',
            roles: [],
            showRequestAlert: false,
            loginModalAlert: {
                value: false,
                type: 'error',
                text: '',
                inactiveUser: false
            },
            regModalAlert: {
                value: false,
                type: 'error',
                text: '',
                isConfirmEmail: false
            }
        }),
        mounted() {
            const forgotPasswordCode = this.$route.query.code;
            if (forgotPasswordCode) {
                this.$store.dispatch('profile/forgotPasswordBoolean', forgotPasswordCode)
                    .then(response => {
                        if (response.type === 'success') {
                            this.modalNewPassword = true;
                            this.changePasswordUserId = response.userId
                        } else {
                            this.$router.push('/')
                        }
                    })
                    .catch(error => console.log(error))
            }
        },
        computed: {
            ...mapState({
                signUpData: state => state.auth.userData,
                languages: state => state.common.languages,
                notifications: state => state.profile.notifications,
                newNotificationsCount: state => state.profile.newNotificationsCount
            }),
            profileItemList() {
                return [
                    {title: this.$t('my_account'), path: '/profile/my-account'},
                    {title: this.$t('logout'), path: '/logout'}
                ]
            },
            nameRules() {
                return [v => !!v || this.$t('first_name_required')]
            },
            lastNameRules() {
                return [v => !!v || this.$t('last_name_required')]
            },
            exEmailRules() {
                return [
                    v => !!v || this.$t('email_required'),
                    v => /.+@.+/.test(v) || this.$t('email_not_valid')
                ]
            },
            accoundPassword() {
                return [v => !!v || this.$t('account_password_required')]
            },
            confirmPassword() {
                return [v => v === this.regData.password || this.$t('confirm_password')]
            }
        },
        watch: {
            showRegistrationModal: function () {
                this.openRegistrationModal()
            },
            showLoginModal: function () {
                this.openLoginModal()
            },
            productRequestAlert: function () {
                this.openLoginModal();
                this.showRequestAlert = true;
            }
        },
        methods: {
            backLoginModal() {
                this.dataAllClearFunction();
                this.openLoginModal()
            },
            dataAllClearFunction() {
                this.changePasswordUserId = '';
                this.newPassword = '';
                this.confirmNewPassword = '';
                this.forgotPasswordInputText = '';
                this.show = false;
                this.isLogged = false;
                this.dialog = false;
                this.loginModal = false;
                this.forgotModal = false;
                this.modalNewPassword = false;
                this.logEmail = '';
                this.logPass = '';
            },
            closeModalNewPasswordInfo() {
                this.modalNewPassword = false;
                this.$router.push('/');
            },
            sendModalNewPasswordInfo() {
                const playload = {
                    newPassword: this.newPassword,
                    confirmNewPassword: this.confirmNewPassword,
                    userId: this.changePasswordUserId,
                    code: this.$route.query.code
                };
                const _newPassword = this.newPassword;
                console.log('_newPassword', _newPassword);
                this.$store.dispatch('profile/changeUserPassword', playload)
                    .then((res) => {
                        if (res.type === 'success') {
                            this.dataAllClearFunction();
                            this.$toastr('success', res.message, this.$t('success'));
                            // login the user
                            this.logPass = _newPassword;
                            this.logEmail = res.email;
                            console.log('this.logPass, this.logEmail', this.logPass, this.logEmail);
                            this.signIn()
                                .then(() => {
                                    this.$router.push('/profile/my-account')
                                })
                                .catch(console.error)
                        } else {
                            this.loginModalAlert.value = true;
                            this.loginModalAlert.text = res.message;
                            this.loginModalAlert.type = res.type;
                        }
                    }).catch(console.error)
            },
            forgotPasswordEmailCheck() {
                this.loading = true;
                this.$store.dispatch('profile/checkUserForgotPassword', this.forgotPasswordInputText)
                    .then((res) => {
                        if (res.type === 'success') {
                            this.dataAllClearFunction();
                            this.$toastr('success', res.message, this.$t('success'))
                        } else {
                            this.loginModalAlert.value = true;
                            this.loginModalAlert.text = res.message;
                            this.loginModalAlert.type = res.type
                        }
                        this.loading = false
                    }).catch(console.error)
            },
            forgotPasswordModal() {
                this.loginModalAlert.value = false;
                this.loginModalAlert.text = '';
                this.loginModalAlert.type = 'error';

                this.dialog = false;
                this.loginModal = false;
                this.forgotModal = true
            },
            enterSendModalNewPasswordInfo(ele) {
                if (ele.which === 13) {
                    this.sendModalNewPasswordInfo()
                }
            },
            enterKeyPressRegistration(ele) {
                if (ele.which === 13) {
                    this.signUp()
                }
            },
            enterKeyPressLogin(ele) {
                if (ele.which === 13) {
                    this.signIn()
                }
            },
            enterKeyPressForgotPassword(ele) {
                if (ele.which === 13) {
                    this.forgotPasswordEmailCheck()
                }
            },
            setLang(lang) {
                this.$store.dispatch('common/setLang', lang.code).then(() => {
                    this.langIcon = 'g-language-' + lang.code;
                    EventBus.$emit('language-changed', lang)
                }).catch(console.error)
            },
            openRegistrationModal() {
                this.regModalAlert.value = false;
                this.regModalAlert.isConfirmEmail = false;
                this.dialog = true;
                this.checkRouteName()
            },
            openLoginModal() {
                this.checkRouteName();
                this.loginModalAlert.value = false; // this need for hide already existed alert messages
                this.loginModalAlert.inactiveUser = false; // this need for hide already existed alert messages
                this.showRequestAlert = false;
                this.loginModal = true
            },
            signIn() {
                this.loginModalAlert.value = false; // this need for hide already existed alert messages
                this.loginModalAlert.inactiveUser = false;// this need for hide already existed alert messages
                let data = {};
                data.email = this.logEmail;
                data.password = this.logPass;

                return this.$store.dispatch('auth/signIn', data).then((res) => {
                    if (res.token) {
                        localStorage.setItem('token', res.token);
                        this.loginModal = false;
                        setAuthHeader(res.token);
                        this.getUserNotifications();
                        let pathBeforeLogin = '/';
                        if (localStorage.getItem('routePath')) {
                            pathBeforeLogin = localStorage.getItem('routePath');
                            localStorage.removeItem('routePath')
                        }
                        this.$router.push(pathBeforeLogin)
                    } else if (res.message) { // something went wrong
                        this.loginModalAlert.value = true;
                        this.loginModalAlert.text = res.message;
                        this.loginModalAlert.type = res.type;
                        if (res.inactiveUser) {
                            this.loginModalAlert.inactiveUser = res.inactiveUser;
                        }
                    }
                    // here we have changed this.signUpData from auth state
                }).catch((err) => {
                    console.log('err', err);
                    return err.response;
                })
            },
            signUp() {
                this.regModalAlert.value = false; // this need for hide already existed alert messages
                this.regModalAlert.isConfirmEmail = false;
                let _err = '';
                if (!this.regData.firstName) {
                    _err += this.$t('first_name_required') + '<br />';
                }
                if (!this.regData.lastName) {
                    _err += this.$t('last_name_required') + '<br />';
                }
                if (!validateEmail(this.regData.email)) {
                    _err += this.$t('invalid_email') + '<br />';
                }
                if (this.regData.password !== this.regData.password_confirmation) {
                    _err += this.$t('password_doesnt_match') + '<br />';
                }
                if (_err !== '') {
                    this.regModalAlert.value = true;
                    this.regModalAlert.text = _err;
                    this.regModalAlert.type = 'error';
                    return false;
                }
                this.$store.dispatch('auth/signUp', this.regData).then((res) => {
                    if (res.token) {
                        localStorage.setItem('token', res.token);
                        this.dialog = false;
                        setAuthHeader(res.token); // important for add token into header
                        this.$router.push('/profile');
                    } else if (res.message) { // something went wrong
                        this.regModalAlert.value = true;
                        this.regModalAlert.text = res.message;
                        this.regModalAlert.type = res.type;
                        if (res.isConfirmEmail) {
                            this.regModalAlert.isConfirmEmail = res.isConfirmEmail;
                        }
                    } else if (res.data && res.data.errors && res.data.errors.length > 0) {
                        let _errors = '';
                        res.data.errors.forEach(err => {
                            if (err.message_code) {
                                _errors += this.$t(err.message_code) + '<br>';
                            } else {
                                _errors += err.message + '<br>';
                            }
                        });
                        this.regModalAlert.value = true;
                        this.regModalAlert.text = _errors;
                        this.regModalAlert.type = 'error';
                    }
                })
                    .catch(console.error)
            },
            profileStaticFunction(event, path) {
                if (path !== '/logout') {
                    setAuthHeader(localStorage.getItem('token')); // important for add token into header
                    this.$router.push(path);
                } else {
                    this.logOut();
                }
            },
            changeActiveTab() {
                this.$emit('changeActive', '3');
                this.$router.push('/profile/notifications');
            },
            getUserNotifications() {
                this.$store.dispatch('profile/getUserNotifications').then((res) => {
                }).catch(console.error)
            },
            deleteNotification(item) {
                this.$store.dispatch('profile/deleteNotification', item).then((res) => {
                    this.$toastr(res.type, res.message, this.$t(res.type));
                }).catch(console.error)
            },
            hideCount() {
                this.$store.dispatch('profile/hideNotificationCount').then((res) => {
                }).catch(console.error)
            },
            logOut() {
                this.$store.dispatch('auth/signOut');
                this.$store.dispatch('profile/clearUserData');
                this.$store.dispatch('profile/clearCategories');
                this.$store.dispatch('categories/clearList');
                this.dataAllClearFunction();
                this.$router.push('/');
            },
            resendConfirmationCode() {
                if (validateEmail(this.logEmail)) {
                    this.$store.dispatch('auth/resendConfirmationCode', {email: this.logEmail})
                        .then((res) => {
                            if (res.data.resendConfirmEmail) {
                                this.$toastr(res.data.type, res.data.message, this.$t(res.data.type))
                            }
                        })
                        .catch(console.error)
                }
            },
            checkRouteName() {
                if (this.$route.name === 'product') {
                    localStorage.setItem('routePath', this.$route.path)
                }
            },
            getLanguageName(name) {
                let value = '';
                if (this.settings && this.settings.length > 0) {
                    this.settings.forEach(setting => {
                        if (setting.name === name) {
                            value = setting.value
                        }
                    })
                }
                return value
            }
        },
        beforeUpdate() {
            if (localStorage && localStorage.getItem('token')) {
                this.isLogged = true
            }
        },
        beforeMount() {
            if (this.$router.currentRoute.query['signed_token_fb_or_google']) {
                localStorage.setItem('token', this.$router.currentRoute.query['signed_token_fb_or_google'])
                if (localStorage.getItem('token')) {
                    let pathBeforeLogin = '/';
                    if (localStorage.getItem('routePath')) {
                        pathBeforeLogin = localStorage.getItem('routePath');
                        localStorage.removeItem('routePath');
                    }
                    setTimeout(() => {
                        window.location.reload();
                        this.isLogged = true;
                    }, 0);
                    this.$router.push(pathBeforeLogin)
                }
            } else if (this.$router.currentRoute.query['confirmationPage']) {
                let token = this.$router.currentRoute.query['confirmationPage'];
                if (token) {
                    try {
                        let decodedUser = VueJwtDecode.decode(token);
                        setTimeout(() => {
                            this.regData.realInviteEmail = decodedUser.userEmail;
                            this.regData.email = decodedUser.userEmail;
                            let userIsGuest = decodedUser.isGuest;
                            this.regData.inviterMail = decodedUser.inviterMail;
                            this.regData.inviterId = decodedUser.inviterId;
                            if (userIsGuest) {
                                this.openRegistrationModal();
                                this.regModalAlert.text = this.$t('pleaseRegForConfirm');
                                this.regModalAlert.type = 'warning';
                                this.regModalAlert.value = true;
                            } else {
                                this.openLoginModal();
                                this.loginModalAlert.text = this.$t('pleaseLogForConfirm');
                                this.loginModalAlert.type = 'warning';
                                this.loginModalAlert.value = true;
                            }
                        }, 1000)
                    } catch (e) {
                        console.log('e', e.message);
                    }
                }
            }
            if (localStorage && localStorage.getItem('token')) {
                this.isLogged = true;
                this.getUserNotifications();
            }
            this.$store.dispatch('auth/userInfo')
                .then((res) => {

                })
                .catch(console.error)
        }
    }
</script>

<style lang="scss">
    @import "../../assets/sass/desktop/header.scss";
</style>
