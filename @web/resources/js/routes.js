import VueRouter from 'vue-router';
import AuthModal from "./components/modals/AuthModal.vue";
import Bus from "./bus.js";

import { defineAsyncComponent } from 'vue';

const routes = [
  { path: '*', component: defineAsyncComponent(() => import('./components/views/PageNotFound.vue')) },

  { path: '/', component: defineAsyncComponent(() => import('./components/views/CasinoIndex.vue')) },
  { path: '/casino', component: defineAsyncComponent(() => import('./components/views/CasinoIndex.vue')) },

  { path: '/casino/game/provider/:provider', component: defineAsyncComponent(() => import('./components/views/GameProvider.vue')) },
  { path: '/casino/game/tag/:tag', component: defineAsyncComponent(() => import('./components/views/GameTag.vue')) },
  { path: '/casino/game/:id', component: defineAsyncComponent(() => import('./components/views/Game.vue')) },

  { path: '/:sportMode(sport)', component: defineAsyncComponent(() => import('./components/views/sport/SportIndex.vue')) },
  { path: '/:sportMode(sport)/category/:id', component: defineAsyncComponent(() => import('./components/views/sport/SportIndex.vue')) },
  { path: '/:sportMode(sport)/league/:id/:league', component: defineAsyncComponent(() => import('./components/views/sport/SportIndex.vue')) },
  { path: '/:sportMode(sport)/game/:category/:id', component: defineAsyncComponent(() => import('./components/views/sport/SportGame.vue')) },

  { path: '/privacyPolicy', component: defineAsyncComponent(() => import('./components/views/PrivacyPolicyView.vue')) },
  { path: '/terms', component: defineAsyncComponent(() => import('./components/views/TermsView.vue')) },

  { path: '/vip', component: defineAsyncComponent(() => import('./components/views/VIP.vue')), meta: { requiresAuth: true } },

  { path: '/password/reset/:user/:token', component: defineAsyncComponent(() => import('./components/views/CasinoIndex.vue')) },
  { path: '/fairness', component: defineAsyncComponent(() => import('./components/views/Fairness.vue')) },
  { path: '/partner', component: defineAsyncComponent(() => import('./components/views/Affiliate.vue')) },
  { path: '/profile/:tag', component: defineAsyncComponent(() => import('./components/views/Profile.vue')) },

  { path: '/error/noLayout/1', component: defineAsyncComponent(() => import('./components/views/error/InvalidLicenseView.vue')), meta: { hideLayout: true } },
  { path: '/error/1', component: defineAsyncComponent(() => import('./components/views/error/InvalidLicenseUIView.vue')) }
];

const router = new VueRouter({
  routes,
  mode: 'history',
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    else return {x: 0, y: 0};
  }
});


router.beforeEach((to, from, next) => {
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!(JSON.parse(localStorage.getItem('vuex')) ?? []).user) {
      AuthModal.methods.open('auth');
      return false;
    }
  }

  Bus.$emit('route:pathChange', {
    to: to.fullPath
  })
  next();
});

const originalPush = VueRouter.prototype.push;
VueRouter.prototype.push = function push(location, onResolve, onReject) {
  if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject);
  return originalPush.call(this, location).catch(err => err);
}

export default router;
