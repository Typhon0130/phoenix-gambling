import { createApp } from 'vue/dist/vue.esm-bundler.js';
import DashboardLayout from './components/DashboardLayout.vue';
import router from './routes.js';
import store from "./store.js";
import toast from "./toast.js";
import bus from "./bus.js";

import vClickOutside from "click-outside-vue3";
import FloatingVue from 'floating-vue';

import axios from 'axios';

import './sass/app.scss';
import '@vuepic/vue-datepicker/dist/main.css';
import 'floating-vue/dist/style.css';

FloatingVue.options.themes.tooltip.disabled = window.innerWidth <= 768;

const app = createApp(DashboardLayout)
  .use(vClickOutside)
  .use(FloatingVue)
  .use(router)
  .use(store);

window.$app = app;
window.$bus = bus;
app.config.globalProperties.$toast = toast;
app.config.globalProperties.$bus = bus;

window.axios = axios;
window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common = {
  'X-Requested-With': 'XMLHttpRequest',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
};

window.axios.interceptors.response.use((response) => response, (error) => {
  if(error.response) {
    if(error.response.status === 419) {
      if (!window.$reloading) {
        document.write('Loading...');
        window.location.reload();
      }
    }

    if(error.response.data && error.response.data.code === -1024) {
      window.location.href = '/';
      return Promise.reject(error);
    }
  }

  return Promise.reject(error);
});

let vuex = null;
if(localStorage.getItem('vuex')) {
  vuex = JSON.parse(localStorage.getItem('vuex'));
  if(vuex.user) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${vuex.user.token}`;
  }
}

const isIgnoringDemo = () => {
  try {
    return localStorage.vuex && (JSON.parse(localStorage.vuex).user.user.isDemoLimitationsIgnored === true);
  } catch (e) {
    return false;
  }
};

window.$isDemo = import.meta.env.VITE_IS_DEMO === 'true' && !isIgnoringDemo();
app.config.globalProperties.$isDemo = window.$isDemo;

app.mount('#app');
