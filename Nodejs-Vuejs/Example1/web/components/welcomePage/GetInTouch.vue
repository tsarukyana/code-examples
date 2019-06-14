<template>
    <div class="g-get-touch">
        <v-container grid-list-xl>
            <v-layout row justify-center>
                <v-flex md7>
                    <v-progress-linear :indeterminate="sendMessageProgress"
                                       v-if="sendMessageProgress"></v-progress-linear>
                    <h2 class="g-title text-xs-center">{{ $t('get_in_touch')}}</h2>
                    <p class="g-subtitle text-xs-center">
                        {{ getLanguageName("contact_us_description") }}</p>
                </v-flex>
            </v-layout>
            <v-form v-model="valid" ref="contactUsForm">
                <v-layout row justify-center>
                    <v-flex md5>
                        <v-text-field
                                v-model="emailData.name"
                                :rules="nameRules"
                                :label="$t('full_name')"
                                required
                        ></v-text-field>

                    </v-flex>
                    <v-flex md5>
                        <v-text-field
                                v-model="emailData.email"
                                :rules="emailRules"
                                :label="$t('email')"
                                required
                        ></v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout row justify-center>
                    <v-flex xs5>
                        <v-text-field
                                v-model="emailData.mobile"
                                :label="$t('mobile')"
                        ></v-text-field>
                    </v-flex>
                    <v-flex xs5>
                        <v-text-field
                                v-model="emailData.subject"
                                :label="$t('subject')"
                                :rules="subjectRules"
                                required
                        ></v-text-field>
                    </v-flex>
                </v-layout>
                <v-layout row justify-center>
                    <v-flex xs10>
                        <v-textarea
                                v-model="emailData.message"
                                :label="$t('enter_your_message') + '...'"
                                :rules="messageRules"
                                textarea
                                auto-grow
                                required
                        >
                        </v-textarea>
                    </v-flex>
                </v-layout>
                <v-layout row justify-center class="mb-3 mb-60">
                    <v-flex xs12 lg5 text-xs-center>
                        <v-btn class="g-btn-default g-middle-btn  g-up-letter" @click="clearMessage">{{ $t('clear') }}
                        </v-btn>
                        <v-btn class="g-btn-main-light g-middle-btn g-up-letter" @click="sendMessage"
                               :disabled="disableSend">{{ $t('send') }}
                        </v-btn>
                    </v-flex>
                </v-layout>
            </v-form>
        </v-container>
    </div>
</template>

<script>
    import {validateEmail} from '../../helpers/main'

    export default {
        name: 'get-in-touch',
        props: ['settings'],
        data: () => ({
            valid: false,
            disableSend: false,
            sendMessageProgress: false,
            emailData: {
                name: '',
                email: '',
                mobile: '',
                subject: '',
                message: ''
            }
        }),
        computed: {
            nameRules() {
                return [
                    v => !!v || this.$t('field_is_required')
                ]
            },
            emailRules() {
                return [
                    v => !!v || this.$t('field_is_required'),
                    v => validateEmail(v) || this.$t('email_must_be_valid')
                ]
            },
            subjectRules() {
                return [
                    v => !!v || this.$t('field_is_required')
                ]
            },
            messageRules() {
                return [
                    v => !!v || this.$t('field_is_required')
                ]
            }
        },
        methods: {
            clearMessage() {
                this.emailData = {
                    name: '',
                    email: '',
                    mobile: '',
                    subject: '',
                    message: ''
                }
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
            },
            sendMessage() {
                if (!this.valid) {
                    this.$toastr('warning', '', this.$t('fill_required_fields'));
                    return false
                }

                this.disableSend = true;
                this.sendMessageProgress = true;
                this.$store.dispatch('common/sendEmailContactUs', {emailData: this.emailData})
                    .then((res) => {
                        if (res.success) {
                            this.$toastr('success', this.$t('success'), this.$t('email_sent'));
                            this.clearMessage();
                            this.$refs.contactUsForm.reset()
                        } else if (res.error) {
                            this.$toastr('error', this.$t('something_went_wrong'), '')
                        }
                        this.disableSend = false;
                        this.sendMessageProgress = false
                    })
                    .catch(console.error)
            }
        }
    }
</script>
<style lang="scss" scoped>
    @import "../../assets/sass/desktop/mainPage.scss";
</style>
