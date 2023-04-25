<template>
  <div>
    <div class="thirdPartyContainer">
      <transition name="fade" mode="out-in">
        <div :key="1" v-if="loading" class="loading">
          <loader></loader>
        </div>
      </transition>

      <sidebar-play ref="tpPlay" class="d-none"></sidebar-play>

      <iframe id="frame" :src="url" v-if="url" frameborder="0"
          allow="accelerometer; autoplay; encrypted-media; gyroscope" allowfullscreen=""></iframe>

      <div class="thirdPartySidebar" v-if="currencies[currency].price < 0">
        <div class="conversion">{{ $t('third-party.unavailable_currency') }}</div>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import AuthModal from "../modals/AuthModal.vue";

  export default {
    data() {
      return {
        loading: false,

        url: null,
        expand: false
      };
    },
    mounted() {
      if (this.currencies[this.currency].price > 0) {
        setTimeout(() => this.$refs.tpPlay.onClick(), 100);
        this.loading = true;
      }
    },
    /*watch: {
      url() {
        setTimeout(() => {
          const frame = document.querySelector('#frame');
          let interval = setInterval(() => {
            if(!frame) clearInterval(interval);
            else {
              frame.height = frame.contentWindow.document.body.scrollHeight + 'px';
            }
          });
        });
      }
    },*/
    computed: {
      ...mapGetters(['gameInstance', 'games', 'currencies', 'currency', 'demo', 'isGuest'])
    },
    methods: {
      openAuthModal() {
        AuthModal.methods.open('auth');
      },
      callback(response) {
        this.url = response.link;
        this.loading = false;
      },
      getClientData() {
        return {};
      },
      getSidebarComponents() {
        return [];
      }
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../sass/variables";

  .thirdPartyContainer {
    width: 100%;
    margin: auto;
    height: calc(100vh - #{$header-height} - 15px * 2) !important;
    overflow: hidden;
    position: relative;

    .loading {
      @include themed() {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 100;

        .warning {
          font-weight: 600;
        }

        .btn {
          margin-top: 15px;
          width: 130px;
        }
      }
    }

    iframe {
      border: none;
      width: 100%;
      min-height: 100%;
      position: absolute;
      z-index: 200;
      top: 0;
      left: 0;
    }
  }
</style>
