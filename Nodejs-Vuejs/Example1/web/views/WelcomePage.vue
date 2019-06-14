<template>
    <div>
        <Header
                :showRegistrationModal="showRegistrationModal"
                :showLoginModal="showLoginModal"
                :settings="settings"
                @openRegModal="openRegModal"
                @openLogModal="openLogModal"
        >
        </Header>
        <v-alert :value="confirmedEmailText" :type="confirmedEmailType">
            <p v-html="confirmedEmailText"></p>
        </v-alert>
        <main-page
                :showRegistrationModal="showRegistrationModal"
                :showLoginModal="showLoginModal"
                :settings="settings"
                @openRegModal="openRegModal"
                @openLogModal="openLogModal"
        >
        </main-page>
        <Footer>
        </Footer>
    </div>
</template>
<script>
    import Header from '../components/global/Header.vue'
    import Footer from '../components/global/Footer.vue'
    import mainPage from '../components/welcomePage/mainPage.vue'
    import {mapState} from 'vuex'
    import {EventBus} from '../event-bus'

    const getSettings = function (settings, lang) {
        let _settings = [];
        if (settings) {
            settings.forEach(setting => {
                setting.SettingTranslations.forEach(st => {
                    if (!setting.value) {
                        setting.value = st.value
                    }
                    if (st.languageId === lang.id) {
                        setting.value = st.value
                    }
                });
                _settings.push(setting)
            })
        }
        return _settings
    };

    export default {
        name: 'WelcomePage',
        components: {
            Header,
            mainPage,
            Footer
        },
        data: () => ({
            settings: [],
            confirmedEmailText: '',
            confirmedEmailType: 'error',
            showRegistrationModal: false,
            showLoginModal: false
        }),
        computed: {
            ...mapState({
                activeLanguage: state => state.common.activeLanguage
            })
        },
        methods: {
            languageChanged(lang) {
                this.settings = getSettings(this.settings, lang)
            },
            openRegModal() {
                this.showRegistrationModal = !this.showRegistrationModal
            },
            openLogModal() {
                this.showLoginModal = !this.showLoginModal
            }
        },
        created() {
            if (this.$route.name === 'confirmEmail') {
                this.confirmedEmailText = '';
                this.confirmedEmailType = 'error';
                this.$store.dispatch('common/confirmEmail', {confirmCode: this.$route.query.code})
                    .then((res) => {
                        if (res.data) {
                            if (res.data.message) {
                                this.confirmedEmailText = res.data.message
                            }
                            if (res.data.type) {
                                this.confirmedEmailType = res.data.type
                            }
                        }
                    })
                    .catch(console.error)
            }
            this.$store.dispatch('common/getSettings')
                .then((res) => {
                    if (res.data && res.data.settings) {
                        this.settings = getSettings(res.data.settings, this.activeLanguage)
                    }
                })
                .catch(console.error);
            EventBus.$on('language-changed', this.languageChanged)
        }
    }
</script>
<style lang="scss">
</style>
