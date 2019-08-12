import Vue from 'vue'
import Router from 'vue-router'
import Index from './views/index.vue'
import WifiForm from './views/wifi-form.vue'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'index',
      component: Index
    },
    {
      path: '/form',
      name: 'form',
      component: WifiForm
    }
  ]
})
