<template>
    <div class="g-about">
        <v-container grid-list-xl>
            <v-layout row justify-center>
                <v-flex lg7>
                    <h2 class="g-about-title mb-5">{{ getLanguageName('site_info')}}</h2>
                    <p>
                        {{ getLanguageName('site_info_description')}}
                    </p>
                </v-flex>
                <v-flex lg3>
                    <i class="g-about-icon"></i>
                </v-flex>
            </v-layout>
            <v-layout row justify-center class="mt-60">
                <v-flex lg3>
                    <i class="g-about-icon2"></i>
                </v-flex>
                <v-flex lg7>
                    <h2 class="g-about-title text-xs-right mb-5">{{ getLanguageName('site_info')}}</h2>
                    <p class="text-sm-right">
                        {{ getLanguageName('site_info_description')}}
                    </p>
                </v-flex>
            </v-layout>
        </v-container>
        <v-layout>
            <v-flex>
                <video-player class="video-player-box vjs-big-play-centered"
                              ref="videoPlayer"
                              :options="playerOptions"
                              :playsinline="true">
                </video-player>
            </v-flex>
        </v-layout>
        <div class="g-login-register">
            <v-container grid-list-xl class="">
                <v-layout row justify-center>
                    <v-flex md7>
                        <h2 class="g-title text-xs-center g-white">{{ getLanguageName('about_us')}}</h2>
                        <p class="g-subtitle text-xs-center g-white">
                            {{ getLanguageName("general_description") }}</p>
                    </v-flex>
                </v-layout>
                <v-form class="mt-3 mb-3">
                    <v-layout row justify-center>
                        <v-flex xs5>
                            <v-btn class="g-btn-default g-middle-btn g-up-letter m-0-15" @click="openRegModal">
                                {{$t('registration')}}
                            </v-btn>
                            <v-btn class="g-btn-main g-middle-btn g-up-letter m-0-15" @click="openLogModal">
                                {{$t('sign_in')}}
                            </v-btn>
                        </v-flex>
                    </v-layout>
                </v-form>
                <v-layout row justify-center mt-60>
                    <v-flex md4>
                        <p class="g-bold text-xs-center">186</p>
                        <p class="g-subtitle text-xs-center g-white">
                            Goods</p>
                    </v-flex>
                    <v-flex md4>
                        <p class="g-bold text-xs-center">17K</p>
                        <p class="g-subtitle text-xs-center g-white">
                            Goods</p>
                    </v-flex>
                    <v-flex md4>
                        <p class="g-bold text-xs-center">53</p>
                        <p class="g-subtitle text-xs-center g-white">
                            Goods</p>
                    </v-flex>
                </v-layout>
            </v-container>
        </div>
    </div>
</template>
<script>
    import 'video.js/dist/video-js.css'
    import {videoPlayer} from 'vue-video-player'

    export default {
        name: 'About',
        props: ['settings'],
        components: {
            videoPlayer
        },
        data: () => ({
            playerOptions: {
                fluid: true,
                muted: false,
                controls: true,
                aspectRatio: '16:9',
                language: 'en',
                playbackRates: [0.5, 1.0, 1.5, 2.0],
                sources: [{
                    type: 'video/mp4',
                    src: './static/OneHumanFamily.mp4'
                }],
                poster: './static/oneHumanCover.jpg'
            }
        }),
        methods: {
            openRegModal() {
                this.$emit('openRegModal')
            },
            openLogModal() {
                this.$emit('openLogModal')
            },
            getLanguageName(name) {
                let value = '';
                if (this.settings.length > 0) {
                    this.settings.forEach(setting => {
                        if (setting.name === name) {
                            value = setting.value
                        }
                    })
                }
                return value
            }
        }
    }
</script>
<style lang="scss" scoped>
    @import "../../assets/sass/desktop/mainPage.scss";
</style>
