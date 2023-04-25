<template>
  <div class="sidebar">
    <div class="fixed" :class="mobileShow ? 'show' : ''" v-click-outside="() => mobileShow = false">
      <div class="logo" @click="$router.push('/admin')">
        <img src="/img/misc/logo.png" alt> <span>{{ websiteName ? websiteName.toLowerCase() : '' }}</span>
      </div>
      <div class="entries">
        <template v-for="entry in entries">
          <div class="entry" @click="entry.entries ? entry._expand = !entry._expand : entryClick(entry)" :key="entry"
             v-if="(!entry.feature || $permission.isFeatureAvailable(entry.feature)) && (!entry.permission || check(entry.permission)) && (license && (entry.visibleIfLicenseIsInvalid || license.isValid))"
             :class="[entry.disabled ? 'disabled' : '', entry.type, entry._expand ? 'expand' : '', (entry.link && entry.link === $route.path) || (entry.entries && entry.entries.some(e => e.link === $route.path)) ? 'active' : '']">
            <web-icon :icon="entry.icon" v-if="entry.icon"></web-icon>
            {{ entry.text }}
            <div class="attention" v-if="entry.attention"></div>
            <web-icon class="expand" :icon="entry._expand ? 'fal fa-fw fa-angle-down' : 'fal fa-fw fa-angle-right'" v-if="entry.entries"></web-icon>
            <div class="expand-space"></div>

            <template v-if="entry.entries && entry._expand">
              <template v-for="e in entry.entries">
                <div class="entry" v-if="!e.permission || check(e.permission)" :key="e" @click="entryClick(e)"
                    :class="e.link === $route.path ? 'active' : ''">
                  <web-icon :icon="e.icon" v-if="e.icon"></web-icon>
                  {{ e.text }}
                </div>
              </template>
            </template>
          </div>
        </template>
        <div class="entry" disabled=""></div>
      </div>
      <div class="phoenixGambling" onclick="window.open('https://phoenix-gambling.com', '_blank')">
        <img src="/img/phoenix.png" alt>
        Phoenix
        <web-icon icon="fal fa-chevron-right"></web-icon>
      </div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "@/components/ui/WebIcon.vue";
  import OverlayScrollbars from 'overlayscrollbars';
  import { mapGetters } from 'vuex';

  export default {
    computed: {
      ...mapGetters(['license', 'websiteName'])
    },
    data() {
      return {
        entries: [
          {
            text: 'Dashboard',
            icon: 'fas fa-fw fa-claw-marks',
            link: '/admin',
            permission: [ { id: 'dashboard', type: 'active' } ],
            visibleIfLicenseIsInvalid: true
          },
          {
            text: 'Promocodes',
            icon: 'fal fa-fw fa-percent',
            link: '/admin/promocodes',
            permission: [ { id: 'promocodes', type: 'active' } ]
          },
          {
            text: 'Notifications',
            icon: 'fal fa-fw fa-bells',
            link: '/admin/notifications',
            permission: [ { id: 'notifications', type: 'active' } ]
          },
          {
            text: 'Users',
            icon: 'fal fa-fw fa-users',
            link: '/admin/users',
            permission: [ { id: 'users', type: 'active' } ]
          },
          {
            text: 'Analytics',
            icon: 'fal fa-fw fa-stars',
            link: '/admin/games/stats',
            permission: [ { id: 'game_stats', type: 'active' } ]
          },
          {
            text: 'Activity',
            icon: 'fal fa-fw fa-wave-sine',
            link: '/admin/activity',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Sportsbook',
            icon: 'fas fa-fw fa-volleyball-ball',
            link: '/admin/sport',
            permission: [ { id: 'sportManagement', type: 'active' } ],
            feature: 'phoenixSport'
          },
          {
            type: 'separator',
            text: 'Wallet',
            permission: [ { id: 'withdraws', type: 'active' } ]
          },
          {
            text: 'Deposits',
            icon: 'fal fa-fw fa-arrow-down',
            link: '/admin/deposits',
            permission: [ { id: 'withdraws', type: 'active' } ]
          },
          {
            text: 'Withdraws',
            icon: 'fal fa-fw fa-arrow-up',
            link: '/admin/withdraws',
            permission: [ { id: 'withdraws', type: 'active' } ]
          },
          {
            type: 'separator',
            text: 'Settings',
            permission: [ { id: 'banner', type: 'active' }, { id: 'vip', type: 'active' } ]
          },
          {
            text: 'Plugins',
            link: '/admin/plugins',
            icon: 'fal fa-fw fa-box-open',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Themes',
            link: '/admin/themes',
            icon: 'fal fa-fw fa-palette',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Appearance',
            link: '/admin/appearance',
            icon: 'fal fa-fw fa-fill-drip',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Roles',
            link: '/admin/roles',
            icon: 'fal fa-fw fa-cog',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Wallet',
            link: '/admin/wallet',
            icon: 'fal fa-fw fa-wallet',
            permission: [ { id: 'wallet', type: 'active' } ]
          },
          {
            text: 'Banner',
            icon: 'fal fa-fw fa-ad',
            link: '/admin/banner',
            permission: [ { id: 'banner', type: 'active' } ]
          },
          {
            text: 'VIP',
            icon: 'fal fa-fw fa-star-christmas',
            link: '/admin/vip',
            permission: [ { id: 'vip', type: 'active' } ]
          },
          {
            text: 'Bots',
            icon: 'fas fa-fw fa-robot',
            permission: [ { id: '*', type: 'active' } ],
            entries: [
              {
                text: 'Bets (Classic)',
                icon: 'fal fa-fw fa-coins',
                link: '/admin/bots/bets',
                permission: [ { id: '*', type: 'active' } ]
              },
              {
                text: 'Chat',
                icon: 'fal fa-fw fa-comments',
                link: '/admin/bots/chat',
                permission: [ { id: '*', type: 'active' } ]
              }
            ]
          },
          {
            text: 'Games',
            icon: 'fas fa-fw fa-spade',
            link: '/admin/games',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Slots',
            icon: 'fal fa-fw fa-lemon',
            feature: 'externalSlots',
            link: '/admin/slots',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            type: 'separator',
            text: 'System',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'License',
            icon: 'fal fa-fw fa-badge-check',
            link: '/admin/license',
            visibleIfLicenseIsInvalid: true,
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'SSH',
            icon: 'fal fa-fw fa-terminal',
            link: '/admin/ssh',
            newWindow: true,
            permission: [ { id: '*', type: 'active' } ],
            disabled: window.$isDemo
          },
          {
            text: 'SSL',
            icon: 'fal fa-fw fa-lock',
            link: '/admin/ssl',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Database',
            icon: 'fal fa-fw fa-database',
            link: '/admin/database',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'File Manager',
            icon: 'fal fa-fw fa-folders',
            link: '/admin/files',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Update',
            icon: 'fal fa-fw fa-sync',
            link: '/admin/ota',
            permission: [ { id: '*', type: 'active' } ]
          },
          {
            text: 'Logs',
            icon: 'fal fa-fw fa-scroll-old',
            link: '/admin/logs',
            permission: [ { id: '*', type: 'active' } ]
          }
        ],

        mobileShow: false
      }
    },
    methods: {
      check(permissions) {
        let flag = false;

        permissions.forEach(permission => {
          if(this.$permission.checkPermission(permission.id, permission.type)) flag = true;
        });

        return flag;
      },
      entryClick(entry) {
        entry.attention = false;

        if(entry.link) {
          if(entry.newWindow) window.open(entry.link, 'SSH', "height=640,width=960,toolbar=no,menubar=no,scrollbars=no,location=no,status=no");
          else if(entry.newTab) window.open(entry.link, '_blank');
          else this.$router.push(entry.link);
        }
        this.mobileShow = false;
      }
    },
    mounted() {
      OverlayScrollbars(document.querySelector('.sidebar .entries'), {
        scrollbars: { autoHide: 'leave' },
        className: 'os-theme-thin-light'
      });

      this.$bus.$on('mobileMenu:toggle', () => this.mobileShow = !this.mobileShow);

      window.axios.post('/admin/notify').then(({ data }) => {
        const findByLink = (link) => this.entries.filter(e => e.link === link)[0];

        findByLink('/admin/withdraws').attention = data.withdraws;
        findByLink('/admin/ota').attention = data.update;
      });
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/variables";
  @import "resources/sass/container";
  @import "resources/sass/themes";

  $width: 250px;

  .sidebar {
    width: $width;
    height: 100vh;
    flex-shrink: 0;

    @include min(0, bp('md')) {
      width: 0;
    }

    .fixed {
      width: $width;
      height: 100%;
      position: fixed;
      top: 0;
      left: 0;
      padding: 25px;
      z-index: 10;
      transition: left .3s ease;
      white-space: nowrap;

      @include themed() {
        background: t('sidebar');
      }

      @include min(0, bp('md')) {
        left: -$width;
        pointer-events: none;

        &.show {
          left: 0;
          pointer-events: unset;
        }
      }

      $phoenixHeight: 70px;

      .logo {
        cursor: pointer;
        font-size: 1.5em;
        padding: 25px 35px;
        display: flex;
        align-items: center;

        span {
          width: 150px;
          overflow: hidden;
          position: relative;

          &:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;

            @include themed() {
              background-image: linear-gradient(to right, rgba(t('text'), 0) 75%, t('sidebar'));
            }
          }
        }

        img {
          width: 26px;
          height: 26px;
          margin-right: 10px;
        }
      }

      .entries {
        margin-top: 30px;
        height: calc(100% - #{$phoenixHeight} - 85px);

        .entry {
          border-radius: 10px;
          padding: 15px 30px;
          background: transparent;
          transition: background .3s ease, color .3s ease;
          cursor: pointer;
          margin-bottom: 5px;
          position: relative;

          @include themed() {
            color: t('link');
          }

          &.expand {
            .expand-space {
              margin-bottom: 25px;
            }
          }

          .entry {
            padding: 10px 20px;
          }

          .expand {
            position: absolute;
            right: 10px;
            top: 18px;
          }

          .attention {
            position: absolute;
            top: 28px;
            left: 40px;
            width: 8px;
            height: 8px;
            background: #ff3535;
            border-radius: 50%;
          }

          svg {
            width: 1.25em;
          }

          svg, i {
            margin-right: 15px;
          }

          &.separator {
            opacity: .6;
            margin-top: 40px;
            margin-bottom: 20px;
            padding: 0 30px;
          }

          &.active {
            font-weight: 400;

            @include themed() {
              color: t('secondary');
              background: t('block-2');
            }
          }
        }
      }

      .phoenixGambling {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: $phoenixHeight;
        padding: 0 35px;
        display: flex;
        align-items: center;
        white-space: nowrap;
        font-size: 1.1em;
        cursor: pointer;
        transition: background .3s ease, color .3s ease;

        @include themed() {
          background: t('block-2');
          color: t('link');
        }

        &:hover {
          @include themed() {
            background: t('block-2');
            color: t('text');
          }

          img {
            opacity: 1;
          }
        }

        img {
          width: 22px;
          height: 22px;
          margin-right: 10px;
          //filter: brightness(0) invert(1);
          opacity: .7;
          transition: opacity .3s ease;
        }

        i {
          margin-left: auto;
          font-size: .9em;
        }
      }
    }
  }
</style>
