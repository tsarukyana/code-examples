<template>
  <div class="home">
    <h1>{{ msg }}</h1>
    <router-link :to="'counter-with-props'">Counter With Props ($emit)</router-link>
    <router-link :to="'counter-with-vuex'">Counter With Vuex (Global State)</router-link>
  </div>
</template>

<script>
export default {
  name: 'Home',
  data () {
    return {
      msg: 'Welcome to Counter App'
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.home {
  margin: 0 auto;
}
  .home a {
    display: block;
    text-align: center;
  }
</style>
