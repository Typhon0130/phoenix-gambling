import Vue from 'vue';
import Vuex from 'vuex';

import i18n, {languages, selectedLocale, defaultLanguage} from './i18n';
import createPersistedState from 'vuex-persistedstate';
import Bus from './bus';
import InvalidTokenModal from "./components/modals/InvalidTokenModal.vue";

Vue.use(Vuex);

export default new Vuex.Store({
  state: {
    license: null,

    user: null,
    demo: false,
    locale: selectedLocale,
    theme: 'main',
    unit: 'btc',
    currency: null,
    liveFeedEntries: 10,
    sound: true,
    quick: false,
    chat: true,
    betSlip: false,
    channel: 'casino_' + selectedLocale,
    liveChannel: 'all',
    sidebar: true,
    fiatView: false,
    gameInstance: [],

    vip: null,
    games: [],
    currencies: [],
    notifications: [],

    sportFilter: 'live',
    sportGames: [],

    recentGameHistory: [],

    phoenixConsoleSettings: []
  },
  mutations: {
    setSportFilter(state, filter) {
      state.sportFilter = filter;
    },
    setFiatView(state, view) {
      state.fiatView = view;
    },
    setGameInstance(state, gameInstance) {
      state.gameInstance = {...state.gameInstance, gameInstance};
    },
    setUserData(state, userData) {
      state.user = userData;
      axios.defaults.headers.common['Authorization'] = `Bearer ${userData.token}`;
    },
    updateLocale(state, newLocale) {
      state.locale = newLocale;
    },
    logout(state) {
      state.user = null;
      axios.defaults.headers.common['Authorization'] = null;
    },
    switchTheme(state, newTheme) {
      newTheme = 'main';
      //state.theme = newTheme ?? (state.theme === 'main' ? '???' : 'main');
      document.querySelector('html').className = 'theme--' + state.theme;
    },
    setCurrencies(state, currencies) {
      state.currencies = currencies;
    },
    setDemo(state, status) {
      state.demo = status;
    },
    setUnit(state, unit) {
      state.unit = unit;
    },
    setCurrency(state, unit) {
      state.currency = unit;
    },
    setLiveFeedEntryCount(state, count) {
      state.liveFeedEntries = count;
    },
    setSoundState(state, soundState) {
      state.sound = soundState;
    },
    setQuickState(state, quickState) {
      if (state.gameInstance.playTimeout) return;
      state.quick = quickState;
    },
    toggleChat(state, toggle = null) {
      state.chat = toggle !== null ? toggle : !state.chat;
      Bus.$emit('layoutSizeChange');
    },
    toggleBetSlip(state, toggle = null) {
      state.betSlip = toggle !== null ? toggle : !state.betSlip;
    },
    toggleSidebar(state, toggle = null) {
      state.sidebar = toggle !== null ? toggle : !state.sidebar;
      Bus.$emit('layoutSizeChange');
    },
    updateData(state) {
      axios.post('/license').then(({ data }) => {
        state.license = data;

        if(window.$permission.$isAvailable('phoenixSport')) {
          const retrieveCategories = () => {
            axios.post('/api/sport/categories').then(({ data }) => {
              if(data.length === 0)
                Bus.$emit('sport:noCategories');

              state.sportGames = data;
              setTimeout(retrieveCategories, 5000);
            });
          }

          retrieveCategories();
        }
      });

      axios.post('/api/data/games').then(({data}) => state.games = data);
      axios.post('/api/data/currencies').then(({data}) => {
        state.currencies = data;
        if (!state.currency || data[state.currency] === undefined) state.currency = data[Object.keys(data)[0]].id;
      });
      axios.post('/api/data/notifications').then(({data}) => state.notifications = data);

      axios.post('/api/vip').then(({ data }) => state.vip = data);
    },
    setChatChannel(state, channel) {
      state.channel = channel;
    },
    setLiveChannel(state, channel) {
      state.liveChannel = channel;
    },
    pushRecentGame(state, id) {
      if (state.recentGameHistory.includes(id)) state.recentGameHistory = state.recentGameHistory.filter((e) => e !== id);
      state.recentGameHistory.push(id);
    },
    phoenixConsoleToggle(state, value) {
      if(state.phoenixConsoleSettings.includes(value)) state.phoenixConsoleSettings = state.phoenixConsoleSettings.filter(e => e !== value);
      else state.phoenixConsoleSettings.push(value);
    }
  },
  actions: {
    setSportFilter({commit}, filter) {
      commit('setSportFilter', filter);
    },
    login({commit}, credentials) {
      return axios.get('/sanctum/csrf-cookie').then(() => {
        axios.post('/auth/login', credentials, {
          withCredentials: true
        }).then(({data}) => {
          commit('setUserData', data);
          commit('updateData');
          Bus.$emit('login:success');
        }).catch((e) => Bus.$emit('login:fail', e));
      });
    },
    setUserData({commit}, data) {
      commit('setUserData', data);
    },
    update({commit}) {
      if (this.state.user) axios.post('/auth/update').then(({data}) => {
        commit('setUserData', {
          user: data,
          token: this.state.user.token
        });

        if (data.discord && !data.discord_bonus) {
          axios.post('/auth/discord_bonus');
        }
      }).catch(() => {
        const logOut = () => {
          InvalidTokenModal.methods.open();

          localStorage.clear();
          window.location.reload();
        };

        if(window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
          window.$fixAuthError = logOut;
          console.log('');
          console.log(' * If you\'ve changed database, you can clear cache and fix errors above by entering following command:');
          console.log('window.$fixAuthError();');
          return;
        }

        logOut();
      });
    },
    logout({commit}) {
      commit('logout');
    },
    setLiveFeedEntryCount({commit}, count) {
      commit('setLiveFeedEntryCount', count);
    },
    changeLocale({commit}, newLocale) {
      if(!languages.includes(newLocale))
        newLocale = defaultLanguage;

      i18n.locale = newLocale;
      commit('updateLocale', newLocale);
    },
    switchTheme({commit}, theme = null) {
      commit('switchTheme', theme);
    },
    setDemo({commit}, status) {
      commit('setDemo', status);
    },
    setUnit({commit}, unit) {
      commit('setUnit', unit);
    },
    setCurrency({commit}, currency) {
      commit('setCurrency', currency);
    },
    updateData({commit}) {
      commit('updateData');
    },
    setCurrencies({commit}, currencies) {
      commit('setCurrencies', currencies);
    },
    setGameInstance({commit}, gameInstance) {
      commit('setGameInstance', gameInstance);
    },
    setSoundState({commit}, state) {
      commit('setSoundState', state);
    },
    setQuickState({commit}, quickState) {
      commit('setQuickState', quickState);
    },
    toggleChat({commit}, toggle = null) {
      commit('toggleChat', toggle);
    },
    toggleBetSlip({commit}, toggle = null) {
      commit('toggleBetSlip', toggle);
    },
    toggleSidebar({commit}, toggle = null) {
      commit('toggleSidebar', toggle);
    },
    setLiveChannel({commit}, channel) {
      commit('setLiveChannel', channel);
    },
    setChatChannel({commit}, channel) {
      commit('setChatChannel', channel);
    },
    pushRecentGame({commit}, id) {
      commit('pushRecentGame', id);
    },
    setFiatView({commit}, value) {
      commit('setFiatView', value);
    },
    phoenixConsoleToggle({commit}, value) {
      commit('phoenixConsoleToggle', value);
    }
  },
  plugins: [createPersistedState()],
  getters: {
    license: state => state.license,

    isGuest: state => !state.user,
    user: state => state.user,
    locale: state => state.locale,
    theme: state => state.theme,
    demo: state => !state.user || state.demo,
    unit: state => state.unit,
    currency: state => state.currency,
    liveFeedEntries: state => state.liveFeedEntries,
    sound: state => state.sound,
    quick: state => state.quick,
    chat: state => state.chat,
    betSlip: state => state.betSlip,
    sidebar: state => state.sidebar,
    channel: state => state.channel,
    liveChannel: state => !state.user ? (state.liveChannel === 'mine' ? 'all' : state.liveChannel) : state.liveChannel,
    fiatView: state => state.fiatView,

    vip: state => state.vip,
    games: state => state.games,
    sportGames: state => state.sportGames,
    sportFilter: state => state.sportFilter,
    currencies: state => state.currencies,
    notifications: state => state.notifications,
    gameInstance: state => state.gameInstance,

    recentGameHistory: state => state.recentGameHistory,

    // Phoenix Console
    phoenixShowUnavailableRunners: state => state.phoenixConsoleSettings.includes('showUnavailableRunners'),
    phoenixShowEsportStats: state => state.phoenixConsoleSettings.includes('showEsportStats')
  }
});
