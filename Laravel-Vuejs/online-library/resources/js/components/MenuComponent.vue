<template>
    <header id="header">
        <b-container>
            <b-row>
                <b-col lg="4" md="6" sm="6" cols="6">
                    <h1><router-link :to="{ name: 'home' }">Online Library</router-link></h1>
                </b-col>
                <b-col lg="4" md="6" sm="6" cols="6" offset-lg="4">
                    <div v-if="loggedUserData && loggedUserData.id">
                        <div class="top-info">
                            <b-row class="no-gutters">
                                <b-col cols="3" class="text-center">
                                    <img :src="loggedUserData.avatar" class="avatar" alt="Avatar" />
                                </b-col>
                                <b-col cols="8">
                                    <p class="full-name">{{loggedUserData.name}}</p>
                                    <p class="top-email">{{loggedUserData.email}}</p>
                                </b-col>
                                <b-col cols="1" class="d-flex align-items-center">
                                    <router-link :to="{ name: 'auth.login' }" v-if="loggedUserData === null || loggedUserData.id === undefined">Login</router-link>
                                    <router-link :to="{ name: 'auth.logout' }" v-else><img src="../../img/icons/logout.png" class="logout-btn" alt=""></router-link>
                                </b-col>
                            </b-row>
                        </div>
                    </div>
                </b-col>
            </b-row>
        </b-container>
    </header>
</template>

<script>
    export default {
        name: 'MenuComponent',
        data() {
            return {

            };
        },
        computed: {
            loggedUserData () {
                return this.$store.getters['auth/getUserData'];
            }
        }
    }
</script>
<style>
    @import '../../css/styles.css';
</style>
