<template>
  <div class="licenseError">
    <template v-if="$checkPermission('*')">
      <div class="title">Invalid license</div>
      <div class="description"><a href="/admin">Visit dashboard</a> to fix this problem.</div>
    </template>
    <template v-else>
      <div class="title">Maintenance</div>
      <div class="description">
        Website is temporarily unavailable.
      </div>
    </template>
  </div>
</template>

<script>
  import { mapGetters } from 'vuex';

  export default {
    computed: {
      ...mapGetters(['user', 'isGuest', 'license'])
    },
    watch: {
      license() {
        this.verifyLicense();
      }
    },
    methods: {
      verifyLicense() {
        if(this.license && this.license.isValid)
          window.location.href = '/';
      }
    },
    created() {
      this.verifyLicense();
    }
  }
</script>

<style lang="scss" scoped>
  .licenseError {
    width: 100vw;
    height: 100vh;
    display: flex;
    flex-direction: column;
    text-align: center;
    justify-content: center;
    align-items: center;

    .title {
      font-size: 1.3em;
      margin-bottom: 10px;
    }

    .description {

    }
  }
</style>
