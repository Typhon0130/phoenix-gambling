import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/:pathMatch(.*)', component: () => import('./components/views/PageNotFoundView.vue') },

  {
    path: '/admin',
    component: () => import('./components/views/DashboardView.vue'),
    meta: {
      title: 'Dashboard', requiresPermission: [ { id: 'dashboard', type: 'active' } ],
      search: {
        title: 'Dashboard',
        keywords: [ 'statistics', 'stats', 'dashboard', 'overview', 'income', 'profit'  ]
      }
    }
  },
  {
    path: '/admin/ota',
    component: () => import('./components/views/UpdateView.vue'),
    meta: {
      title: 'Update', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Update',
        keywords: [ 'update', 'upgrade' ]
      }
    }
  },
  {
    path: '/admin/ssh',
    component: () => import('./components/views/WebSSHNotSupported.vue')
  },
  {
    path: '/admin/database',
    component: () => import('./components/views/DatabaseView.vue'),
    meta: {
      sidebar: () => import('./components/views/sidebar/DatabaseViewSidebar.vue'), noPadding: true, requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Database',
        keywords: [ 'db', 'database' ]
      }
    }
  },
  {
    path: '/admin/wallet',
    component: () => import('./components/views/WalletView.vue'),
    meta: {
      requiresPermission: [ { id: 'wallet', type: 'active' } ],
      search: {
        title: 'Wallet',
        keywords: [ 'money', 'wallet', 'currency', 'currencies', 'nodes' ]
      }
    }
  },
  {
    path: '/admin/files',
    component: () => import('./components/views/FileManagerView.vue'),
    meta: {
      sidebar: () => import('./components/views/sidebar/FileManagerViewSidebar.vue'), noPadding: true, requiresPermission: [ { id: '*', type: 'active ' } ],
      search: {
        title: 'Files',
        keywords: [ 'ftp', 'files' ]
      }
    }
  },
  {
    path: '/admin/roles',
    component: () => import('./components/views/RolesView.vue'),
    meta: {
      title: 'Roles', sidebar: () => import('./components/views/sidebar/RolesViewSidebar.vue'), requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Roles',
        keywords: [ 'permissions', 'role' ]
      }
    }
  },
  {
    path: '/admin/logs',
    component: () => import('./components/views/LogsView.vue'),
    meta: {
      title: 'Logs', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Logs',
        keywords: [ 'errors', 'logs', 'warnings', 'info' ]
      }
    }
  },
  {
    path: '/admin/banner',
    component: () => import('./components/views/BannerView.vue'),
    meta: {
      title: 'Banner', requiresPermission: [ { id: 'banner', type: 'active' } ],
      search: {
        title: 'Banner',
        keywords: [ 'banner', 'ad' ]
      }
    }
  },
  {
    path: '/admin/license',
    component: () => import('./components/views/LicenseView.vue'),
    meta: {
      title: 'License', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'License',
        keywords: [ 'license', 'phoenix' ]
      }
    }
  },
  {
    path: '/admin/activity',
    component: () => import('./components/views/ActivityView.vue'),
    meta: {
      title: 'Activity', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Activity',
        keywords: [ 'activity', 'admins', 'moderators', 'agents' ]
      }
    }
  },
  {
    path: '/admin/games',
    component: () => import('./components/views/GamesView.vue'),
    meta: {
      title: 'Games', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Games',
        keywords: [ 'game', 'twist', 'rng', 'random' ]
      }
    }
  },
  {
    path: '/admin/vip',
    component: () => import('./components/views/VIPView.vue'),
    meta: {
      title: 'VIP', sidebar: () => import('./components/views/sidebar/VIPViewSidebar.vue'), requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'VIP',
        keywords: [ 'vip' ]
      }
    }
  },
  {
    path: '/admin/game/:id',
    component: () => import('./components/views/GameView.vue'),
    meta: {
      requiresPermission: [ { id: '*', type: 'active' } ]
    }
  },
  {
    path: '/admin/users',
    component: () => import('./components/views/UsersView.vue'),
    meta: {
      title: 'Users', requiresPermission: [ { id: 'users', type: 'active' } ],
      search: {
        title: 'Users',
        keywords: [ 'users' ]
      }
    }
  },
  {
    path: '/admin/withdraws',
    component: () => import('./components/views/WithdrawsView.vue'),
    meta: {
      title: 'Withdraws', requiresPermission: [ { id: 'withdraws', type: 'active' } ],
      search: {
        title: 'Withdraws',
        keywords: [ 'withdraws' ]
      }
    }
  },
  {
    path: '/admin/deposits',
    component: () => import('./components/views/DepositsView.vue'),
    meta: {
      title: 'Deposits', requiresPermission: [ { id: 'withdraws', type: 'active' } ],
      search: {
        title: 'Deposits',
        keywords: [ 'deposits' ]
      }
    }
  },
  {
    path: '/admin/user/:id',
    component: () => import('./components/views/UserView.vue'),
    meta: {
      sidebar: () => import('./components/views/sidebar/UserViewSidebar.vue'), requiresPermission: [ { id: 'users', type: 'active' } ]
    }
  },
  {
    path: '/admin/createUser',
    component: () => import('./components/views/CreateUserView.vue'),
    meta: {
      title: 'Users', requiresPermission: [ { id: 'users', type: 'create' } ]
    }
  },
  {
    path: '/admin/notifications',
    component: () => import('./components/views/NotificationsView.vue'),
    meta: {
      title: 'Notifications', requiresPermission: [ { id: 'notifications', type: 'active' } ],
      search: {
        title: 'Notifications',
        keywords: [ 'notifications' ]
      }
    }
  },
  {
    path: '/admin/promocodes',
    component: () => import('./components/views/PromocodesView.vue'),
    meta: {
      title: 'Promocodes', requiresPermission: [ { id: 'promocodes', type: 'active' } ],
      search: {
        title: 'Promocodes',
        keywords: [ 'promo' ]
      }
    }
  },
  {
    path: '/admin/bots/chat',
    component: () => import('./components/views/ChatBotsView.vue'),
    meta: {
      title: 'Chat <i class="fas fa-fw fa-robot"></i>', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Bot (Chat)',
        keywords: [ 'bot' ]
      }
    }
  },
  {
    path: '/admin/bots/bets',
    component: () => import('./components/views/BetBotsView.vue'),
    meta: {
      title: 'Bets <i class="fas fa-fw fa-robot"></i>', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Bot (Bets)',
        keywords: [ 'bot' ]
      }
    }
  },
  {
    path: '/admin/themes',
    component: () => import('./components/views/ThemesView.vue'),
    meta: {
      sidebar: () => import('./components/views/sidebar/ThemesViewSidebar.vue'), requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Themes',
        keywords: [ 'theme', 'colors' ]
      }
    }
  },
  {
    path: '/admin/appearance',
    component: () => import('./components/views/AppearanceView.vue'),
    meta: {
      title: 'Appearance', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Appearance',
        keywords: [ 'appearance', 'logo', 'name', 'look' ]
      }
    }
  },
  {
    path: '/admin/ssl',
    component: () => import('./components/views/SSLView.vue'),
    meta: {
      title: 'SSL', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'SSL',
        keywords: [ 'certificate', 'ssl', 'https' ]
      }
    }
  },
  {
    path: '/admin/sport',
    component: () => import('./components/views/SportManagementView.vue'),
    meta: {
      title: 'Sport', requiresPermission: [ { id: 'sportManagement', type: 'active' } ],
      search: {
        title: 'Sport',
        keywords: [ 'sport' ]
      }
    }
  },
  {
    path: '/admin/plugins',
    component: () => import('./components/views/PluginsView.vue'),
    meta: {
      sidebar: () => import('./components/views/sidebar/PluginsSidebar.vue'),
      title: 'Plugins', requiresPermission: [ { id: '*', type: 'active' } ],
      search: {
        title: 'Plugins',
        keywords: [ 'plugins' ]
      }
    }
  },
  {
    path: '/admin/slots',
    component: () => import('./components/views/ExternalSlotsView.vue'),
    meta: {
      title: 'Slots', requiresPermission: [ { id: '*', type: 'active' } ]
    }
  },
  {
    path: '/admin/games/stats',
    component: () => import('./components/views/GameAnalytics.vue'),
    meta: {
      title: 'Analytics', requiresPermission: [ { id: 'game_stats', type: 'active' } ],
      search: {
        title: 'Game Analytics',
        keywords: [ 'game', 'analytics' ]
      }
    }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition;
    else return { top: 0 };
  }
});

router.beforeEach((to) => {
  if(window.$dashboardNotifications && window.$dashboardNotifications.canSwitch()) return false;

  if(to.matched.some(record => record.meta.requiresPermission)) {
    setTimeout(() => {
      let flag = true;

      to.meta.requiresPermission.forEach(permission => {
        if(flag) flag = window.$permission.checkPermission(permission.id, permission.type);
      });

      return flag;
    });
  }
});

export default router;
