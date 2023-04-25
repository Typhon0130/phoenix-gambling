import Vuex from 'vuex';
import createPersistedState from 'vuex-persistedstate';

export default new Vuex.Store({
  state: {
    license: null,

    dashboardTheme: 'dark',
    websiteName: null,

    user: null,
    vip: null,
    games: null,
    currencies: null
  },
  mutations: {
    updateData(state) {
      window.axios.post('/api/websiteName').then(({ data }) => state.websiteName = data.name);
    },
    switchTheme(state, newTheme) {
      state.dashboardTheme = newTheme;
      document.querySelector('html').className = 'theme--' + newTheme;
    },
    setLicense(state, license) {
      state.license = license;
    }
  },
  actions: {
    updateData({commit}) {
      commit('updateData');
    },
    setLicense({commit}, license) {
      commit('setLicense', license);
    },
    switchTheme({commit}, theme = null) {
      commit('switchTheme', theme);
    }
  },
  getters: {
    license: state => state.license,
    dashboardTheme: state => state.dashboardTheme,

    user: state => state.user,
    vip: state => state.vip,
    games: state => state.games,
    currencies: state => state.currencies,

    websiteName: state => state.websiteName
  },
  plugins: [createPersistedState()]
});
