<template>
  <div class="counter">
    <div class="counter-header">
      <router-link :to="'/'">Home</router-link>
      <h4>{{title}}</h4>
    </div>
    <div class="row justify-content-center my-4">
      <div class="col-4">
        <plus @on-raise-click="raiseClick"></plus>
      </div>
      <div class="col-4">
        <minus @on-decrease-click="decreaseClick"></minus>
      </div>
    </div>
    <p class="counter-result text-center">Counter Result : {{counterResult}}</p>
  </div>
</template>

<script>
import Plus from './Props/Plus'
import Minus from './Props/Minus'
export default {
  name: 'CounterWithProps',
  data () {
    return {
      title: 'Counter result by props ($emit)',
      counterResult: 0
    }
  },
  methods: {
    raiseClick (raiseNumber) {
      this.counterResult = (parseFloat(this.counterResult) * 10 + parseFloat(raiseNumber) * 10) / 10
    },
    decreaseClick (decreaseNumber) {
      this.counterResult = (parseFloat(this.counterResult) * 10 - parseFloat(decreaseNumber) * 10) / 10
    }
  },
  components: {
    Plus,
    Minus
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
