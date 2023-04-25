<template>
  <div class="ssl animate">
    <div class="description">
      You can issue Let's Encrypt certificate or renew it here.
    </div>
    <div class="domain">
      <div class="title">Domain</div>
      <input placeholder="Domain" type="text" v-model="domain">
    </div>

    <dashboard-warning type="critical" v-if="isCompletelyInvalidDomain || isInvalidDomain">
      {{ isCompletelyInvalidDomain ? 'You can\'t issue certificates for IPs or local domains.' : 'This domain is not valid. It should not contain \"http(s)://\" prefix and trailing slashes.' }}
    </dashboard-warning>

    <button :class="[isCompletelyInvalidDomain || isInvalidDomain ? 'disabled' : '', $isDemo ? 'demoDisable' : '']" class="btn btn-primary" @click="install"><web-icon icon="fal fa-tools fa-fw"></web-icon> Install</button>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import LoadingModal from "../modals/LoadingModal.vue";
  import CompilerModalOutput from "../modals/CompilerModalOutput.vue";
  import DashboardWarning from "../ui/DashboardWarning.vue";

  export default {
    data() {
      return {
        domain: ''
      }
    },
    computed: {
      isInvalidDomain() {
        return !this.domain.includes('.') || this.domain.includes('/') || this.domain.startsWith("http") || this.domain.endsWith(".");
      },
      isCompletelyInvalidDomain() {
        return (!isNaN(parseInt(this.domain.replaceAll('.', ''))) && this.domain.includes('.'))
          || this.domain === 'localhost';
      }
    },
    mounted() {
      this.domain = window.location.hostname;

      this.$bus.$emit('loading:done');
    },
    methods: {
      install() {
        LoadingModal.methods.open().then((done) => {
          window.axios.post('/admin/ssl/install', {
            domain: this.domain
          }).then(() => {
            done();
            setTimeout(() => {
              window.$bus.$emit('modal:close');
              CompilerModalOutput.methods.open('Certificate is installed successfully.\nChanges will take place after next server restart: "service apache2 restart"', 'Success');

              // apache2 runs as user, it can't restart itself. We'll fix it in later versions
            }, 1000);
          }).catch(e => {
            window.$bus.$emit('modal:close');

            if(e.response.data.code === 1) CompilerModalOutput.methods.open('Windows OS is not supported.\nYou can install certificate manually using certbot if you are using Windows Server (which is not supported officially)', 'Failed to install certificate');
            else CompilerModalOutput.methods.open('Failed to obtain certificate.\nYou can check server logs for more information.', 'Failed to install certificate');
          });
        });
      }
    },
    components: {
      WebIcon,
      DashboardWarning
    }
  }
</script>

<style lang="scss" scoped>
  .ssl {
    .description {
      margin-bottom: 25px;
    }

    .domain {
      margin-bottom: 25px;

      .title {
        margin-bottom: 15px;
        font-weight: 600;
      }
    }
  }
</style>
