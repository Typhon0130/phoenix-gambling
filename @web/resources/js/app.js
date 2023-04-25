import '../sass/app.scss';

import $ from "jquery";
import './bootstrap.js';

import Vue from 'vue';

import router from './routes';
import store from './store';
import i18n from './i18n';

import Bus from './bus.js';

import Vuex from 'vuex';
import VueRouter from 'vue-router';
import VueI18n from 'vue-i18n';
import VueIziToast from 'vue-izitoast';
import VueFeather from 'vue-feather';
import VueMask from 'v-mask';
import vClickOutside from 'v-click-outside';
import infiniteScroll from 'vue-infinite-scroll';
import VueCountdown from '@chenfengyuan/vue-countdown';
import VTooltip from 'v-tooltip';
import VueContext from 'vue-context';

import VueContentPlaceholders from 'vue-content-placeholders';
import { OverlayScrollbarsPlugin } from 'overlayscrollbars-vue';

import WebsiteLayout from "./components/views/WebsiteLayout.vue";

window.$ = $;
window.jQuery = $;

import('./utils/superwheel.js');

Vue.use(VueRouter);
Vue.use(Vuex);
Vue.use(VueI18n);
Vue.use(OverlayScrollbarsPlugin);
Vue.use(VueContentPlaceholders);
Vue.use(VueMask);
Vue.use(VueIziToast, { position: 'topRight' });
Vue.use(vClickOutside);
Vue.use(VueFeather);
Vue.use(infiniteScroll);
Vue.use(VTooltip);

Vue.component("layout", WebsiteLayout);
Vue.component(VueCountdown.name, VueCountdown);
Vue.component('vue-context', VueContext);

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

import './mixins/whisper.js';
import './mixins/game.js';
import './mixins/permission.js';

const massImport = (files) => {
  Object.entries(files).forEach(([path, m]) => {
    const componentName = window._.upperFirst(
      window._.camelCase(path.split('/').pop().replace(/\.\w+$/, ''))
    );

    Vue.component(
      `${componentName}`, m.default
    );
  });
}

massImport(import.meta.globEager('./components/ui/**/*.vue'));
massImport(import.meta.globEager('./components/games/sidebar/*.vue'));

window.$bus = Bus;

const isIgnoringDemo = () => {
  try {
    return localStorage.vuex && (JSON.parse(localStorage.vuex).user.user.isDemoLimitationsIgnored === true);
  } catch (e) {
    return false;
  }
};

window.$isDemo = import.meta.env.VITE_IS_DEMO === 'true' && !isIgnoringDemo();
Vue.prototype.$isDemo = window.$isDemo;

new Vue({
  el: '#app',
  router,
  store,
  i18n
});
