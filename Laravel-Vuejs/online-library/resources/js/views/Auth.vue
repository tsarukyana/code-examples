<template>
    <div class="p-login" ref="page"  :style="{height: window.height-87 + 'px'}">
        <b-container>
            <b-row>
                <b-col lg="6" offset-lg="3">
                    <div class="login-block">

                        <!--Loader-->
                        <div class="lds-roller" v-if="loading">
                            <div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
                        </div>

                        <h1>Login or Sign up</h1>
                        <div v-if="error" class="error">
                            <p>{{ error }}</p>
                        </div>
                        <div style="background-color:#f1f1f1">
                            <a class="btn btn-block btn-social btn-google" href="/redirect">
                                <img src="../../img/icons/google-plus.png" alt="">Sign in with Google
                            </a>
                        </div>
                    </div>
                </b-col>
            </b-row>
        </b-container>
    </div>
</template>
<script>
export default {
    data() {
        return {
            loading: false,
            loginData: {},
            error: null,
            window: {
                width: 0,
                height: 0
            },
        };
    },
    created() {
        if (this.$route.name === 'auth.logout') {
            this.$store.dispatch('auth/signOut');
            this.$router.push('/auth/login');
            return false;
        }
        window.addEventListener('resize', this.handleResize);
    },
    destroyed () {
        window.removeEventListener('resize', this.handleResize)
    },
    mounted () {
        this.handleResize()
    },
    methods: {
        handleResize () {
            this.window.height = window.innerHeight
        }
    }
}
</script>
<style>
.error {
    color: red;
}
</style>
