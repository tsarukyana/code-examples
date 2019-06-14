<template>
    <div class="p-details">
        <b-container>
            <!--<h3>This books details page</h3>-->
            <b-row>
                <b-col lg="4">
                    <img src="../../img/book-placeholder.png" class="book-img" alt="">
                </b-col>
                <b-col lg="8">
                    <div class="pl-30-md">
                        <div v-if="bookData && bookData.key">
                            <h3 class="book-title">{{bookData.title}}</h3>
                            <h6 class="book-title-suggest">{{bookData.title_suggest}}</h6>
                            <h3 class="book-key">Book key: <strong>{{bookData.key}}</strong></h3>
                            <p class="book-info" v-if="bookData.subject"><strong>Subjects</strong>{{bookData.subject.join()}}</p>
                            <p class="book-info" v-if="bookData.first_publish_year"><strong>First Published</strong>{{bookData.first_publish_year}}</p>
                            <p class="book-info" v-if="bookData.edition_count"><strong>Edition Count</strong>{{bookData.edition_count}}</p>
                            <p class="book-info" v-if="bookData.isbn"><strong>ISBNs</strong>{{bookData.isbn.join()}}</p>
                            <p class="book-info" v-if="bookData.publisher"><strong>Publisher</strong>{{bookData.publisher.join()}}</p>
                            <p class="book-info" v-if="bookData.contributor"><strong>Contributor</strong>{{bookData.contributor.join()}}</p>
                            <a class="book-info-btn" :href="`https://openlibrary.org${bookData.key}`" target="_blank">More Info</a>
                        </div>
                        <div v-else>No any data</div>
                    </div>
                </b-col>
            </b-row>
        </b-container>
    </div>
</template>
<script>
    import {setAuthHeader} from '../axiosApi';

    export default {
        data() {
            return {};
        },
        computed: {
            bookData() {
                const bookDetails = localStorage.getItem('bookDetails') && JSON.parse(localStorage.getItem('bookDetails'));
                const stateBookDetails = this.$store.getters['books/getBookDetails'];
                if (stateBookDetails.key) {
                    return stateBookDetails;
                } else {
                    return bookDetails;
                }
            }
        },
        created() {
            setAuthHeader(localStorage.getItem('token_type'), localStorage.getItem('access_token'));
            this.$store.dispatch('auth/userInfo').then(() => {}).catch(console.error)
        }
    }
</script>
<style>

</style>
