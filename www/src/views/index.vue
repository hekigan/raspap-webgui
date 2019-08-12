<template>
  <div class="home">
    <h1>WIFI list</h1>
    <main>
      <h2>Network List</h2>
      <form @submit.prevent="isSSIDSelected">
        <div>
          <button ref="load-list" class="large" @click.prevent="wifiList">refresh list</button>
        </div>
        <p>
          <select name="wifi-list" id="wifi-list" size="10" v-model="ssid">
            <option v-for="(wifi, key) in networks" :value="key" v-html="label(key, wifi)" :key="key"></option>
          </select>
        </p>
        <footer>
          <button class="large">select</button>
        </footer>
      </form>

      <!-- <hr>

      <div>
        <p class="next-page">
          <router-link :to="{ name: 'form', params: { manual: true } }" class="navigation">Manual input &gt;</router-link>
        </p>
      </div> -->
    </main>
  </div>
</template>

<script>
import isLoading from 'is-loading';

export default {
  data() {
    return {
      ssid: null,
      encryption: null,
      password: null,
      networks: null
    }
  },
  mounted() {
    this.wifiList();
  },
  components: {
  },
  computed: {
    selectedNetwork () {
      if (!this.ssid) {
        return null;
      }
      const result = {...this.networks[this.ssid], ssid: this.ssid};
      return result;
    }
  },
  methods: {
    async wifiList() {
      const btn = this.$refs['load-list'];
      isLoading(btn, { text: 'loading list...'}).loading();
      
      try {
        const response = await this.$http.get();
        this.networks = response.data;
        // console.log(this.networks);
      } catch (error) {
        console.error(error);
      }
      isLoading(btn).remove();
    },
    label(name, data) {
      let label = (data.connected) ? '&#10003; ' : '&nbsp;&nbsp;&nbsp; ';
      label += name;
      return label;
    },
    isSSIDSelected() {
      if (this.ssid !== null) {
        this.$router.push({ name: 'form', params: {
          network: this.selectedNetwork
        }});
      } else {
        window.alert('Please select a WIFI in the list');
      }
    }
  }
}
</script>