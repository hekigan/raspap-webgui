<template>
  <div class="wifi-form">
    <h1>
        <router-link :to="{ name: 'index' }" class="back">&lt;</router-link>
        WIFI settings
    </h1>

    <main class="details">
      <h2>Wifi details</h2>
      <form>
        <fieldset ref="form">
          <p class="label">
            <span>SSID</span>
          </p>
          <p class="value">
            <input v-if="manual" type="text" v-model="ssid">
            <span v-else v-text="ssid"></span>
          </p>
          <p class="label">
            <span>Encryption</span>
          </p>
          <p class="value">
            <input v-if="manual" type="text" v-model="encryption">
            <ul v-else-if="currentProtocols.length > 1" class="compact">
              <li v-for="prop in currentProtocols" v-text="prop" :key="prop"></li>
            </ul>
            <span v-else v-text="currentProtocols[0]"></span>
          </p>
          <p class="label">
            <span>Password</span>
          </p>
          <p class="value">
            <input type="text" v-model="password">
          </p>
          <footer>
            <template v-if="network && network.configured">
              <button ref="update" class="large" @click.prevent="update">update</button>
              <button ref="connect" class="large" @click.prevent="connect">connect</button>
            </template>
            <template v-if="network && !network.configured">
              <button ref="add" class="large" @click.prevent="add">add</button>
            </template>
            <button v-else ref="delete" class="large danger" @click.prevent="remove">delete</button>
          </footer>
        </fieldset>
      </form>
    </main>
  </div>
</template>

<script>
import isLoading from 'is-loading';
// let defaults = {
//   client_settings: true
// };

export default {
  data() {
    return {
      manual: null,
      ssid: null,
      encryption: null,
      password: null
    }
  },
  created() {
    // console.log(this.$route.params);
    this.manual = this.$route.params.manual;
    const n = this.$route.params.network;
    if (n) {
      this.ssid = n.ssid;
      this.password = n.passphrase;
    }
  },
  components: {
  },
  computed: {
    currentProtocols() {
      if (this.ssid !== null) {
        return this.protocols();
      } else {
        return [];
      }
    },
    network () {
        return this.$route.params.network || {};
    }
  },
  methods: {
    async saveWifi() {
      try {
        let values = {
          ssid0: this.ssid,
          passphrase0: this.password,
          protocol0: this.encryption
        };

        // const network = this.selectedNetwork;
        // if (network.configured) {
        //   values.
        // }

        const response = await this.$http.post(null, values);
        console.log(response);
      } catch (error) {
        console.error(error);
      }
    },
    label(name, data) {
      let label = (data.connected) ? '&#10003; ' : '&nbsp;&nbsp;&nbsp; ';
      label += name;
      return label;
    },
    isSSIDSelected() {
      if (this.ssid !== null) {
        this.step = 2;
      } else {
        window.alert('Please select a WIFI in the list');
      }
    },
    protocols() {
      const data = this.network.protocol;
      if (Array.isArray(data)) {
        if (data.length == 1) {
          this.encryption = data[0];
        }
        return data;
      }
      this.encryption = data;
      return [data];
    },
    getForm(index = 0) {
      let data = new FormData();

      data.append('client_settings', true);
      data.append(`ssid${index}`, this.ssid);
      data.append(`passphrase${index}`, this.password);
      data.append(`protocol${index}`, this.currentProtocols.join('<br />'));

      return data;
    },
    async add () {
      const form = this.$refs.form;
      isLoading(this.$refs.add, { disableList: [form]}).loading();

      let params = this.getForm(this.network.index);
      params.append(`update${this.network.index}`, 'Add');

      try {
        const response = await this.$http.post(null, params);

        // console.log(params);
        console.log(response);
        if (!response.data[this.ssid].configured) {
          alert('could not save, please try again');
        } else {
          alert('connection saved');
          this.$router.go(-1); 
        }
      } catch (error) {
        console.error(error);
      }
      isLoading(this.$refs.add, { disableList: [form]}).remove();
    },
    async connect() {
      const form = this.$refs.form;
      isLoading(this.$refs.connect, { disableList: [form]}).loading();

      console.log('connect to', this.network);
      let params = this.getForm(this.network.index);
      params.append(`connect`, this.network.index);

      try {
        const response = await this.$http.post(null, params);

        console.log(params);
        console.log(response);
      } catch (error) {
        console.error(error);
      }
      isLoading(this.$refs.connect, { disableList: [form]}).remove();
    },
    async update() {
      const form = this.$refs.form;
      isLoading(this.$refs.update, { disableList: [form]}).loading();

      console.log('update', this.network);
      let params = this.getForm(this.network.index);
      params.append(`update${this.network.index}`, 'Update');

      try {
        const response = await this.$http.post(null, params);

        console.log(params);
        console.log(response);
      } catch (error) {
        console.error(error);
      }

      isLoading(this.$refs.update, { disableList: [form]}).remove();
    },
    async remove() {
      console.log('delete', this.network);
      const nw = response.data[this.ssid]; // current network
      const form = this.$refs.form;
      isLoading(this.$refs.delete, { disableList: [form]}).loading();

      let params = this.getForm(this.network.index);
      params.append(`delete${this.network.index}`, 'Delete');

      if (nw.configured && nw.connected) {
        this.$http.post(null, params);
        isLoading(this.$refs.delete, { disableList: [form]}).remove();
        this.$router.go(-1);
        return;
      }

      try {
        const response = await this.$http.post(null, params);
        console.log(params);
        console.log(response);
        
        if (!response.data[this.ssid].configured) {
          alert('wifi removed successfully');
          this.$router.go(-1); 
        } else {
          alert('failed to remove connection');
        }
      } catch (error) {
        console.error(error);
      }
      
      isLoading(this.$refs.delete, { disableList: [form]}).remove();
    }
  }
}
</script>
