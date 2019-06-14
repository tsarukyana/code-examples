<template>
    <div>
        <div class="g-main-header">
            <!-- Slider main container -->
            <swiper :options="swiperOption">
                <swiper-slide>
                    <div class="g-slider-wrapper">
                        <img src="../../assets/images/slideimage.png" alt="">
                    </div>
                </swiper-slide>
                <swiper-slide>
                    <div class="g-slider-wrapper">
                        <img src="../../assets/images/Banner2.png" alt="">
                    </div>
                </swiper-slide>
                <swiper-slide>
                    <div class="g-slider-wrapper">
                        <img src="../../assets/images/slideimage.png" alt="">
                    </div>
                </swiper-slide>
                <swiper-slide>
                    <div class="g-slider-wrapper">
                        <img src="../../assets/images/slideimage.png" alt="">
                    </div>
                </swiper-slide>
                <div class="swiper-pagination" slot="pagination"></div>
            </swiper>
            <v-container grid-list-xl>
                <div class="g-main-header-content">
                    <v-layout rown>
                        <v-flex lg9>
                            <div class="g-bold mb-3">{{getLanguageName("site_info")}}</div>
                            <v-form class="mb-3 d-flex">
                                <v-btn class="g-btn-main-light g-middle-btn g-up-letter" @click="openRegModal">
                                    {{$t('givers')}}
                                </v-btn>
                                <v-btn class="g-btn-main g-middle-btn g-up-letter" @click="openLogModal">
                                    {{$t('receivers')}}
                                </v-btn>
                            </v-form>
                        </v-flex>
                    </v-layout>
                </div>
            </v-container>
        </div>
        <about
                :showRegistrationModal="showRegistrationModal"
                :showLoginModal="showLoginModal"
                :settings="settings"
                @openRegModal="openRegModal"
                @openLogModal="openLogModal"
        >

        </about>
        <get-in-touch
                :settings="settings"
        >
        </get-in-touch>

    </div>
</template>
<script>
    import About from '../welcomePage/About.vue'
    import GetInTouch from '../welcomePage/GetInTouch.vue'

    export default {
        name: 'mainPage',
        props: ['showRegistrationModal', 'showLoginModal', 'settings'],
        components: {
            About,
            'get-in-touch': GetInTouch
        },
        data() {
            return {
                swiperOption: {
                    direction: 'vertical',
                    autoplay: true,
                    delay: 7000,
                    effect: 'fade',
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true
                    }
                }
            }
        },
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
    @import "../../assets/sass/desktop/customSlider.scss";
</style>
