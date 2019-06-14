<template>
  <div>
    <b-input-group prepend="Decrement">
      <b-form-input type="number" v-model="decreaseNumber" placeholder="Please type number"></b-form-input>
    </b-input-group>
    <button class="btn btn-danger my-3" @click="decrease()"><i class="fa fa-minus"></i></button>
  </div>
</template>

<script>
export default {
  name: 'Minus',
  data () {
    return {
      decreaseNumber: 1
    }
  },
  methods: {
    decrease () {
      this.$store.dispatch('decreaseCounterResult', this.decreaseNumber)
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
  .fa-minus {
    font-size: 26px;
  }
</style>
