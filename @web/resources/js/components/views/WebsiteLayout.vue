<template>
  <router-view :key="$route.fullPath" v-if="$route.meta.hideLayout"></router-view>
  <div class="d-flex" v-else>
    <modal></modal>
    <website-notifications></website-notifications>
    <div class="wrapper">
      <layout-sidebar></layout-sidebar>
      <div class="pageWrapper">
        <layout-header></layout-header>
        <global-notifications></global-notifications>
        <div v-if="!isCasino" class="container">
          <div class="sportHeader">
            <div class="sportHeaderButton" @click="$store.dispatch('setSportFilter', 'live'); $route.path !== '/' + sportLink ? $router.push('/' + sportLink) : false;"
                 :class="($route.path === '/' + sportLink || $route.path.startsWith('/' + sportLink + '/category')) && sportFilter === 'live' ? 'active' : ''">
              <web-icon icon="fas fa-video"></web-icon>
              Live
            </div>
            <div class="sportHeaderButton" @click="$store.dispatch('setSportFilter', 'upcoming'); $route.path !== '/' + sportLink ? $router.push('/' + sportLink) : false;"
                 :class="($route.path === '/' + sportLink || $route.path.startsWith('/' + sportLink + '/category')) && sportFilter === 'upcoming' ? 'active' : ''">
              <web-icon icon="fas fa-clock"></web-icon>
              Upcoming
            </div>
          </div>
        </div>
        <router-view :key="$route.fullPath" class="pageContent"></router-view>
        <live-feed></live-feed>
        <layout-footer></layout-footer>
      </div>
    </div>
    <chat></chat>
    <bet-slip></bet-slip>
    <floating-buttons></floating-buttons>
    <mobile-menu></mobile-menu>
    <profit-monitoring></profit-monitoring>
    <sport-movable-widget></sport-movable-widget>
    <support-chat v-if="$hasPlugin('phoenix:supportChat')"></support-chat>
    <phoenix-gambling-manager-view v-if="!isGuest && user && user.user.isPhoenixGamblingManager"></phoenix-gambling-manager-view>
    <search></search>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import BannerModal from "../modals/BannerModal.vue";
  import Modal from "../modals/Modal.vue";
  import Bus from "../../bus.js";
  import { polyfillCountryFlagEmojis } from "country-flag-emoji-polyfill";
  import { pickers, startPicking } from "../../utils/depositPicker/DepositPickerManager.js";
  import PhoenixGamblingManagerView from "../ui/PhoenixGamblingManagerView.vue";
  import SportsUnavailableModal from "../modals/SportsUnavailableModal.vue";

  export default {
    computed: {
      ...mapGetters(['user', 'isGuest', 'currencies', 'currency', 'locale', 'license', 'sportFilter', 'sportGames'])
    },
    created() {
      this.$store.dispatch('changeLocale', this.$store.state.locale);
      this.$store.dispatch('switchTheme', this.$store.state.theme);
      this.$store.dispatch('update');
      this.$store.dispatch('updateData');

      this.reconnectToWS();

      if(typeof URLSearchParams === 'function') {
        const params = new URLSearchParams(window.location.search);
        if(params.has('c')) this.setCookie('c', params.get('c'));
      }

      this.$store.dispatch('setChatChannel', (this.isCasino ? 'casino_' : 'sport_') + this.locale);

      this.verifyLicense();

      Bus.$on('sport:noCategories', () => {
        if(!this.isCasino) {
          this.$router.push('/');
          SportsUnavailableModal.methods.open();
        }
      });
    },
    watch: {
      user() {
        if(!this.isGuest) {
          pickers.forEach(picker => {
            if(this.user.user['wallet_' + picker.id()])
              startPicking(picker.id(), this.user.user['wallet_' + picker.id()]);
          });
        }
      },
      sportLink() {
        this.onWebModeChanged();
      },
      isCasino() {
        this.onWebModeChanged();
      },
      $route() {
        this.verifyLicense();
      },
      license() {
        this.verifyLicense()
      },
      isGuest() {
        this.reconnectToWS();

        if(this.$route.meta.requiresAuth) this.$router.push('/');
        else if(this.$route.meta.requiresPermission) {
          let flag = true;

          this.$route.meta.requiresPermission.forEach(permission => {
            if(flag) flag = window.$permission.$checkPermission(permission.id, permission.type);
          });

          if(flag) this.$router.push('/');
        }
      }
    },
    mounted() {
      BannerModal.methods.open();
      polyfillCountryFlagEmojis();
    },
    methods: {
      onWebModeChanged() {
        if(!this.isCasino && this.sportGames.length === 0)
          Bus.$emit('sport:noCategories');

        this.$store.dispatch('setChatChannel', (this.isCasino ? 'casino_' : (this.sportLink === 'esport' ? 'esport_' : 'sport_')) + this.locale);
      },
      verifyLicense() {
        if(this.license && !this.license.isValid)
          this.$router.push('/error/1');
      },
      reconnectToWS() {
        window.Echo.connector.disconnect();

        window.Echo = new window.LaravelEcho({
          broadcaster: 'socket.io',
          host: `${window.location.hostname}:2087`,
          auth: {
            headers: {
              Authorization: `Bearer ${this.isGuest ? null : this.user.token}`
            }
          }
        });

        window.Echo.connector.socket.on('connect', () => Bus.$emit('ws:connect'));
        window.Echo.connector.socket.on('disconnect', () => Bus.$emit('ws:disconnect'));

        window.Echo.private(`App.Models.User.${this.isGuest ? 'Guest' : this.user.user._id}`)
          .listen('WhisperResponse', (e) => Bus.$emit('event:whisperResponse', e))
          .listen('BalanceModification', (e) => Bus.$emit('event:balanceModification', e))
          .listen('BonusBalanceTransferred', (e) => Bus.$emit('event:bonusBalanceTransferred', e));

        if(!this.isGuest && this.$hasPlugin('phoenix:supportChat')) {
          if (this.$checkPermission('manageTickets'))
            Echo.channel('Support').listen('SupportMessage', (e) => Bus.$emit('event:supportMessageAdmin', e));

          Echo.channel(`Support.${this.user.user._id}`).listen('SupportMessage', (e) => Bus.$emit('event:supportMessage', e));
        }

        window.Echo.channel('Everyone').listen('ChatMessage', (e) => Bus.$emit('event:chatMessage', e))
          .listen('ChatRemoveMessages', (e) => Bus.$emit('event:chatRemoveMessages', e))
          .listen('ChatMessageLike', (e) => Bus.$emit('event:chatMessageLike', e))
          .listen('NewQuiz', (e) => Bus.$emit('event:chatNewQuiz', e))
          .listen('QuizAnswered', (e) => Bus.$emit('event:chatQuizAnswered', e))
          .listen('LiveFeedGame', (e) => Bus.$emit('event:liveGame', e))
          .listen('LiveFeedSportGame', (e) => Bus.$emit('event:liveSportGame', e))

          .listen('MultiplayerBettingStateChange', (e) => Bus.$emit('event:multiplayerBettingStateChange', e))
          .listen('MultiplayerBetCancellation', (e) => Bus.$emit('event:multiplayerBetCancellation', e))
          .listen('MultiplayerGameFinished', (e) => Bus.$emit('event:multiplayerGameFinished', e))
          .listen('MultiplayerGameBet', (e) => Bus.$emit('event:multiplayerGameBet', e))
          .listen('MultiplayerTimerStart', (e) => Bus.$emit('event:multiplayerTimerStart', e))
          .listen('MultiplayerDataUpdate', (e) => Bus.$emit('event:multiplayerDataUpdate', e));

        if(!this.isGuest) window.Echo.channel(`App.Models.User.${this.user.user._id}`).notification((notification) => Bus.$emit('event:notification', notification));
      }
    },
    components: {
      PhoenixGamblingManagerView,
      Modal
    }
  }
</script>
