<template>
  <div class="counter">
    <div class="counter-header">
      <router-link :to="'/'">Home</router-link>
      <h4>{{title}}</h4>
    </div>
    <div class="row justify-content-center my-4">
      <div class="col-6">
        <plus></plus>
      </div>
      <div class="col-6">
        <minus></minus>
      </div>
    </div>
    <p class="counter-result text-center">Counter Result : {{counterResult}}</p>
  </div>
</template>

<script>
import Plus from './Vuex/Plus'
import Minus from './Vuex/Minus'
export default {
  name: 'CounterWithVuex',
  data () {
    return {
      title: 'Counter result by Vuex state (global)'
    }
  },
  components: {
    Plus,
    Minus
  },
  computed: {
    counterResult () {
      return this.$store.state.counterResult
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
  .counter {
    text-align: center;
    width: 100%;
  }
  .counter-header a{
    font-size: 26px;
  }
  .counter-header h4{
    padding-top: 20px;
  }
  .counter-result {
    font-size: 36px;

  }
</style>
