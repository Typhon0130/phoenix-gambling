import TwoFactorAuthModal from "./components/modals/TwoFactorAuthModal.vue";
import InvalidTokenModal from "./components/modals/InvalidTokenModal.vue";

import lodash from 'lodash';
import axios from 'axios';
import Echo from 'laravel-echo';
import io from 'socket.io-client';

window._ = lodash;

window.axios = axios;

window.axios.defaults.withCredentials = true;
window.axios.defaults.headers.common = {
  'X-Requested-With': 'XMLHttpRequest',
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
};

window.axios.interceptors.response.use((response) => response, (error) => {
  if(error.response) {
    if(error.response.status === 419) { // Invalid CSRF Token
      if(!window.$reloading) {
        InvalidTokenModal.methods.open();
        window.location.reload();
      }

      window.$reloading = true;
    }

    if(error.response.data && error.response.data.code === -1024) return new Promise((resolve, reject) => TwoFactorAuthModal.methods.open(error, resolve, reject));
  }

  return Promise.reject(error);
});

let vuex = null;
if(localStorage.getItem('vuex')) {
  vuex = JSON.parse(localStorage.getItem('vuex'));
  if(vuex.user) window.axios.defaults.headers.common['Authorization'] = `Bearer ${vuex.user.token}`;
}

window.io = io;
window.LaravelEcho = Echo;

window.Echo = new Echo({
  broadcaster: 'socket.io',
  host: `${window.location.hostname}:2087`,
  auth: {
    headers: {
      Authorization: `Bearer ${vuex && vuex.user ? vuex.user.token : null}`
    }
  }
});
