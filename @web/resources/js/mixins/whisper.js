import {mapGetters} from 'vuex';
import Vue from 'vue';

Vue.mixin({
  computed: {
    ...mapGetters(['user', 'isGuest'])
  },
  created() {
    window.whisperTest = this.whisper;
  },
  methods: {
    async whisper(event, request = {}) {
      return new Promise((resolve, reject) => {
        const time = +new Date();

        const handleApiResponse = (url, json) => {
          if (json.message != null && json.errors != null) {
            reject(0);
            return;
          }

          if (json.code != null) {
            console.error(url, json.code + ' > ' + json.message);
            reject(json.code);
            return;
          }

          console.log(url, +new Date() - time + 'ms', json);
          resolve(json);
        }

        window.axios.post('/api/whisper', {
          event: event,
          data: request
        }).then(({data}) => {
          handleApiResponse(event, data);
        }).catch((e) => {
          reject(e)
        });
      });
    }
  }
});
