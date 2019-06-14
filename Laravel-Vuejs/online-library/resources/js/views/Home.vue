<template>
    <div>
        <div class="top-section"></div>
        <b-container class="w-800">
            <div class="search-area">
                <b-tabs>
                    <b-tab title="Search" active @click="tabChanged">
                        <div class="form-group inp-group d-flex mt-3">
                            <input type="text" placeholder="Search for Books ..." class="form-control" id="q" v-model="searchData.q">
                            <button type="button" @click="searchBook(0)" :disabled="loading"><img src="../../img/icons/search.png" class="search-icon" alt=""></button>
                        </div>
                    </b-tab>
                    <b-tab title="Advanced Search" @click="tabChanged">
                        <div class="form-group inp-group d-flex mt-3">
                            <input type="text" placeholder="Author" class="form-control mr-3" id="title" v-model="searchData.title">
                            <input type="text" placeholder="Title" class="form-control" id="author" v-model="searchData.author">
                            <button type="button" @click="searchBook(1)" :disabled="loading" class="w-118"><img src="../../img/icons/search.png" class="search-icon" alt=""></button>
                        </div>
                    </b-tab>
                    <b-tab title="ISBN Search" @click="tabChanged">
                        <div class="form-group inp-group d-flex mt-3">
                            <input type="text" placeholder="ISBN" class="form-control" id="isbn" v-model="searchData.isbn">
                            <button type="button" @click="searchBook(2)" :disabled="loading"><img src="../../img/icons/search.png" class="search-icon" alt=""></button>
                        </div>
                    </b-tab>
                </b-tabs>

                <!--Loader-->
                <transition name="slide-fade">
                    <div class="bookshelf_wrapper" v-if="loading">
                        <ul class="books_list">
                            <li class="book_item first"></li>
                            <li class="book_item second"></li>
                            <li class="book_item third"></li>
                            <li class="book_item fourth"></li>
                            <li class="book_item fifth"></li>
                            <li class="book_item sixth"></li>
                        </ul>
                        <div class="shelf"></div>
                    </div>
                </transition>

                <div v-if="isbnData && isbnData.bib_key">
                    <h3>{{isbnData.bib_key}}</h3>
                    <p><a :href="isbnData.info_url" target="_blank"><img :src="isbnData.thumbnail_url" alt="avatar"></a></p>
                </div>
            </div>
        </b-container>
        <b-container>
            <div class="main-block" id="books" v-if="booksData.docs">
                <div class="books">
                    <h3 class="total-items">Total Items: <strong>{{booksData.numFound}}</strong></h3>
                    <div class="row">
                        <div v-for="book in booksData.docs" :key="book.key" class="col-lg-12-5 custom-col-md-4 custom-col-sm-3 custom-col-6">
                            <div class="book-item book-one">
                                <img src="../../img/book-placeholder.png" alt="">
                                <div class="need-pointer book-title" @click="selectBook(book)"><!--Title:--> {{book.title}}</div>
                                <div v-if="book.subtitle">
                                    <p><!--SubTitle:--> <strong>{{book.subtitle}}</strong></p>
                                </div>
                                <div v-if="book.author_name">
                                    <p class="book-author"><!--Authors:--> {{book.author_name.join()}}</p>
                                </div>
                                <div v-if="book.edition_count">
                                    <p><strong>{{book.edition_count}}</strong> editions </p>
                                </div>
                                <div v-if="book.first_publish_year">
                                    <p>first published in: <strong>{{book.first_publish_year}}</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <b-pagination align="center" size="md" :total-rows="numFound" v-model="currentPage" :per-page="100"  v-scroll-to="'#header'">
                    </b-pagination>
                </div>
            </div>
        </b-container>
    </div>
</template>
<script>
import {setAuthHeader} from '../axiosApi';
export default {
    data() {
        return {
            loading: false,
            searchType: 0,
            booksData: {},
            currentPage: 1,
            numFound: 0,
            isbnData: {},
            searchData: {
                q: '',
                title: '',
                isbn: '',
                author: ''
            }
        };
    },
    created() {
        if (this.$route.query && this.$route.query.token) {
            setAuthHeader(localStorage.getItem('token_type'), localStorage.getItem('access_token'));
        }
        this.$store.dispatch('auth/userInfo').then(() => this.$router.push('/')).catch(console.error)
    },
    methods: {
        searchBook (search_type) {
            if (search_type === undefined) {
                return false;
            }
            this.searchType = search_type;
            this.loading = true;
            this.searchData.searchType = search_type; // search = 0, advanced search = 1, isbn = 2
            this.searchData.page = this.currentPage;
            this.$store.dispatch('books/search', {q: this.searchData})
                .then((res) => {
                    if (this.searchType === 2) { // isbn
                        if (res.books) {
                            let _isbnData = {};
                            Object.keys(res.books).map((key, index) => {
                                this.isbnData = res.books[key]
                            });
                        }
                    } else { // search / advanced search
                        if (res.books) {
                            this.booksData = res.books;
                            this.numFound = res.books.numFound;
                        }
                    }
                    this.loading = false;
                })
                .catch(console.error)
        },
        tabChanged () {
            this.isbnData = {};
            this.booksData = {};
            this.numFound = 0;
            this.currentPage = 1;
            this.searchType = undefined;
        },
        selectBook (book) {
            this.$store.dispatch('books/details', {book: book})
                .then((res) => {
                    localStorage.setItem('bookDetails', JSON.stringify(res));
                    this.$router.push({name: 'book.details'});
                })
                .catch(console.error)
        }
    },
    watch: {
        currentPage: function (val) {
            this.searchBook(this.searchType);
        }
    }
}
</script>
<style>
    .book-item{
        margin-bottom: 10px;
    }
    .need-pointer{
        cursor: pointer;
    }
    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s;
    }
    .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
        opacity: 0;
    }

    .slide-fade-enter-active {
        transition: all .3s ease;
    }
    .slide-fade-leave-active {
        transition: all .2s cubic-bezier(1.0, 0.5, 0.8, 1.0);
    }
    .slide-fade-enter, .slide-fade-leave-to
        /* .slide-fade-leave-active below version 2.1.8 */ {
        transform: translateX(10px);
        opacity: 0;
    }
</style>
