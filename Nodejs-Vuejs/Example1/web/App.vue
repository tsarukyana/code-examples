<template>
  <v-app>
        <router-view></router-view>
  </v-app>
</template>

<script>

export default {
  name: 'App',
  beforeMount () {
    const localLang = localStorage.getItem('lang')
    this.$store.dispatch('common/getLanguages')
      .then(() => {
        if (!localLang) { // need to get language by user ip and save that data into localStorage
          let ipAddress = localStorage.getItem('ipAddress')
          if (!ipAddress) {
            // Here we are adding core js ajax request because no need to send x-lang header via axiosApi
            const xhr = new XMLHttpRequest()
            xhr.open('GET', process.env.IP_DETECT_URL, false) // synchrony
            xhr.send()
            if (xhr.status === 200 && xhr.responseText) {
              ipAddress = xhr.responseText.trim()
              localStorage.setItem('ipAddress', ipAddress)
            }
          }
          let storageIpLocation = localStorage.getItem('ipLocation')
          if (storageIpLocation) {
            storageIpLocation = JSON.parse(storageIpLocation)
          }
          if (!storageIpLocation) {
            if (ipAddress) {
              return this.$store.dispatch('common/getUserIpLocation', {ipAddress: ipAddress})
                .then((res) => {
                  if (res.data && res.data.ipLocation) {
                    localStorage.setItem('ipAddress', res.data.ipLocation.ip)
                    localStorage.setItem('ipLocation', JSON.stringify(res.data.ipLocation))
                    if (res.data.ipLocation.countryCode === 'AM') { // Armenia -> Language need to put hy
                      localStorage.setItem('lang', 'hy')
                    } else { // other country -> Language need to put en
                      localStorage.setItem('lang', 'en')
                    }
                  }
                  return true
                })
                .catch(console.error)
            } else {
              // default value
              localStorage.setItem('lang', 'en')
            }
          } else {
            this.$store.commit('common/SET_IP_LOCATION', {ipLocation: storageIpLocation})
            if (storageIpLocation.countryCode && storageIpLocation.countryCode === 'AM') { // Armenia -> Language need to put hy
              localStorage.setItem('lang', 'hy')
            } else { // other country -> Language need to put en
              localStorage.setItem('lang', 'en')
            }
            return true
          }
        }
        return true
      })
      .then(() => {
        this.$store.dispatch('common/setLang', localStorage.getItem('lang'))
      })
      .catch(console.error)
  }
}
</script>

<style lang="scss">
@import '../node_modules/font-awesome/css/font-awesome.min.css'; // Ensure you are using css-loader
@import '../node_modules/material-design-icons-iconfont/dist/material-design-icons.css'; // Ensure you are using css-loader
@import '../node_modules/vuetify/dist/vuetify.min.css'; // Ensure you are using css-loader
@import '../node_modules/swiper/dist/css/swiper.css';
@import url('https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700,800,900');
@import './assets/sass/main.scss';
#app {
  font-family: 'Montserrat', 'Mardoto-Regular',  sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
}
</style>
