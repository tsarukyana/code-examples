<template>
  <div>
    <b-input-group prepend="Increment">
      <b-form-input type="number" v-model="raiseNumber" placeholder="Please type number"></b-form-input>
    </b-input-group>
    <button class="btn btn-primary my-3" @click="raise()"><i class="fa fa-plus"></i></button>
  </div>
</template>

<script>
export default {
  name: 'Plus',
  data () {
    return {
      raiseNumber: 1
    }
  },
  methods: {
    raise () {
      this.$store.dispatch('raiseCounterResult', this.raiseNumber)
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
  .fa-plus {
    font-size:26px
  }
</style>
