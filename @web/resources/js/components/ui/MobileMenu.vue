<template>
  <div>
    <div class="sportWidgetMobile" v-if="$route.path.startsWith(`/${sportLink ?? 'sport'}/game`)">
      <div class="header" @click="activeLiveStream = !activeLiveStream">
        <i :class="`fal fa-chevron-${activeLiveStream ? 'down' : 'up'}`"></i>
        {{ $t('sport.liveStream') }}
        <div class="pulsating-circle"></div>
      </div>
      <div class="content" :class="activeLiveStream ? 'active' : ''">
        <div id="mobileWidget"></div>
      </div>
    </div>
    <div class="mobile-menu">
      <div class="control" @click="$router.push('/sport')" :class="$route.path === '/sport' ? 'active' : ''" v-if="$isAvailable('phoenixSport') && (isCasino || sportLink === 'esport')">
        <web-icon icon="sport"></web-icon>
        <div>Sport</div>
      </div>
      <div class="control" @click="$router.push('/')" :class="$route.path === '/' || $route.path === '/casino' ? 'active' : ''">
        <web-icon icon="casino"></web-icon>
        <div>Casino</div>
      </div>
      <div class="control" @click="$store.dispatch('toggleChat');">
        <web-icon icon="chat"></web-icon>
        <div>{{ $t('general.head.chat') }}</div>
      </div>
      <div class="control" @click="$store.dispatch('toggleChat', false); toggleSidebarMobile()">
        <web-icon icon="menu"></web-icon>
        <div>Menu</div>
      </div>
      <div class="control" @click="$store.dispatch('toggleBetSlip')" v-if="$isAvailable('phoenixSport') && !isCasino">
        <web-icon icon="fad fa-ticket"></web-icon>
        <div>Bet Slip</div>
        <div class="count" v-if="betSlipSize > 0">{{ betSlipSize > 9 ? '9+' : betSlipSize }}</div>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import Bus from "../../bus.js";

  export default {
    data() {
      return {
        betSlipSize: 0,
        activeLiveStream: false
      }
    },
    watch: {
      activeLiveStream() {
        if(this.activeLiveStream) Bus.$emit('sport:initializeMobileWidget');
      },
      $route() {
        this.activeLiveStream = false;
      }
    },
    created() {
      setInterval(() => {
        this.betSlipSize = Bus.$retrieve('betSlip:size');
      }, 100);
    },
    methods: {
      toggleSidebarMobile() {
        Bus.$emit('sidebar:toggleMobile');
      }
    },
    computed: {
      ...mapGetters(['theme', 'games', 'isGuest', 'user'])
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/themes";

  #mobileWidget {
    max-height: 320px;
    max-width: 300px;
    margin: auto;

    .notStarted {
      text-align: center;
      margin-bottom: 10px;
      opacity: .7;
    }

    .sr-lmt-plus__footer-wrapper {
      display: none;
    }
  }

  .sportWidgetMobile {
    position: fixed;
    bottom: 75px;
    left: 0;
    width: 100%;
    z-index: 5;
    display: flex;
    flex-direction: column;

    @include themed() {
      border-top: 1px solid t('border');
      background: t('body');
    }

    @media(min-width: 991px) {
      display: none;
    }

    .header {
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 10px;
      cursor: pointer;
      text-transform: uppercase;

      i {
        margin-right: 10px;
      }

      .pulsating-circle {
        margin-left: 10px;
      }
    }

    .content {
      display: none;
      @include themed() {
        background: t('sidebar');
      }

      &.active {
        display: block;
      }
    }
  }
</style>
