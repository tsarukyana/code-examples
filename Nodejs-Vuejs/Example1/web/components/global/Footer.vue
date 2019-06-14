<template>
    <div class="g-footer">
        <v-container grid-list-xl>
            <v-layout row justify-space-between>
                <v-flex xs12 lg2 d-flex align-center>
                    <span>Find us:</span>
                    <a href="" class="d-flex"><i class="g-social-icon m-0-15 g-fb"></i></a>
                    <a href="" class="d-flex"><i class="g-social-icon g-instagram"></i></a>
                </v-flex>
                <v-flex lg4 d-flex justify-center>
                    <p class="ex-copyright-text">Copyright &copy; 2018.All rights reserved</p>
                </v-flex>
                <v-flex lg4 d-flex justify-end>
                    <a href="" class="d-flex"><i class="g-download-app-apple"></i></a>
                    <a href="" class="d-flex"><i class="g-download-app-googleplay"></i></a>
                </v-flex>
            </v-layout>
        </v-container>
    </div>
</template>
<script>
    export default {
        name: 'Footer',
        data: () => ({})
    }
</script>
<style lang="scss" scoped>
    @import "../../assets/sass/desktop/footer.scss";
</style>
