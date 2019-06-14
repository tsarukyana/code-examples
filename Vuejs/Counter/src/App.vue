<template>
  <div id="app">
    <b-container>
      <b-row>
        <router-view/>
      </b-row>
    </b-container>
  </div>
</template>

<script>
export default {
  name: 'App'
}
</script>

<style>
#app {
  margin-top: 60px;
}
</style>
