<template>
  <div :class="'sidebar ' + (sidebar || mobileToggle ? 'visible' : 'hidden') + ' ' + (mobileToggle ? 'mobileToggle' : '')"
       @click="mobileToggle = false" v-click-outside="closeSidebar">
    <div class="fixed">
      <div class="sidebar-header">
        <div class="logo" @click="$router.push('/')"></div>
        <div class="toggle" @click="$store.dispatch('toggleSidebar')">
          <web-icon :icon="sidebar ? 'fal fa-chevron-left' : 'fal fa-chevron-right'"></web-icon>
        </div>
      </div>
      <overlay-scrollbars :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }"
                          :class="`games ${!isCasino ? 'sportSidebar' : ''}`">
        <template v-if="isCasino">
          <div class="sidebar-description">Axes</div>

          <router-link tag="div" to="/" class="game">
            <web-icon icon="casino"></web-icon>
            <div>{{ $t('general.sidebar.all_games') }}</div>
          </router-link>

          <div onclick="window.location.href = '/admin'" v-if="$checkPermission('dashboard')" class="game">
            <i class="fas fa-cog"></i>
            <div>{{ $t('general.sidebar.admin') }}</div>
          </div>

          <router-link tag="div" to="/vip" class="game">
            <web-icon icon="fas fa-stars"></web-icon>
            <div>VIP</div>
          </router-link>

          <template v-if="$isAvailable('phoenixSport')">
            <div class="divider"></div>
            <div class="sidebar-description">{{ $t('general.head.sport') }}</div>

            <router-link tag="div" to="/sport" class="game">
              <web-icon icon="sport"></web-icon>
              <div>{{ $t('general.head.sport') }}</div>
            </router-link>
          </template>

          <div class="divider"></div>
          <div class="sidebar-description">Promotions</div>

          <div class="game featured" @click="isGuest ? openAuthModal('auth') : openBonusModal()">
            <web-icon icon="stars"></web-icon>
            <div>Bonus</div>
          </div>

          <div class="game" @click="openLeaderboard()">
            <web-icon icon="trophy-diamond"></web-icon>
            <div>Competition</div>
          </div>

          <div class="divider"></div>
          <div class="sidebar-description">Originals</div>

          <router-link v-for="game in games" v-if="!game.isDisabled && !game.isHidden && game.type === 'Originals (Classic)'" :key="game.id" tag="div" :to="`/casino/game/` + game.id" class="game">
            <web-icon :icon="game.icon"></web-icon>
            <div>{{ game.name }}</div>
          </router-link>

          <content-placeholders class="game" v-for="n in 17" :key="n" v-if="games.length === 0">
            <content-placeholders-img/>
          </content-placeholders>

          <div class="divider"></div>

          <router-link tag="div" to="/partner" class="game">
            <web-icon icon="fas fa-user-secret"></web-icon>
            <div>{{ $t('footer.affiliates') }}</div>
          </router-link>
        </template>
        <template v-else>
          <div :class="`game ${betSlip ? 'router-link-exact-active router-link-active' : ''}`"
               @click="$store.dispatch('toggleBetSlip')">
            <web-icon icon="fas fa-ticket-alt"></web-icon>
            <div>{{ $t('sport.bet_slip') }}</div>
          </div>

          <content-placeholders class="game" v-for="n in 17" :key="n" v-if="sportGames.length === 0">
            <content-placeholders-img/>
          </content-placeholders>
          <template v-if="sportGames && sportGames.length > 0">
            <template v-if="!isGuest && $checkPermission('dashboard')">
              <div onclick="window.location.href = '/admin'" class="game">
                <i class="fas fa-cog"></i>
                <div>{{ $t('general.sidebar.admin') }}</div>
              </div>
              <div class="divider"></div>
            </template>

            <div v-for="game in sportGames.filter(e => e.sportType === sportType)" :key="game.id"
                 class="game" @click="navigateTo(game)">
              <web-icon :icon="game.icon"></web-icon>
              <div class="liveCount" v-if="game.liveCount > 0">{{ game.liveCount }}</div>
              <div>{{ game.name }}</div>
            </div>
          </template>
        </template>
      </overlay-scrollbars>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import AuthModal from "../modals/AuthModal.vue";
  import Bus from "../../bus";
  import FaucetModal from "../modals/FaucetModal.vue";
  import LeaderboardModal from "../modals/LeaderboardModal.vue";

  export default {
    data() {
      return {
        promo: false,
        language: null,
        mobileToggle: false,
        tempBlock: false
      }
    },
    created() {
      this.language = this.locale;
      Bus.$on('sidebar:toggleMobile', () => {
        this.tempBlock = true;
        setTimeout(() => this.tempBlock = false, 1);
        this.mobileToggle = !this.mobileToggle;
      });
    },
    computed: {
      ...mapGetters(['isGuest', 'user', 'theme', 'games', 'currencies', 'sidebar', 'sportGames', 'betSlip'])
    },
    methods: {
      openLeaderboard() {
        LeaderboardModal.methods.open();
      },
      openBonusModal() {
        FaucetModal.methods.open();
      },
      navigateTo(sport) {
        this.$store.dispatch('setSportFilter', sport.liveCount === 0 ? 'upcoming' : 'live');
        this.$router.push('/sport/category/' + sport.id);
      },
      closeSidebar() {
        if (!this.tempBlock) this.mobileToggle = false;
      },
      openAuthModal(type) {
        AuthModal.methods.open(type);
      },
      openSearch() {
        Bus.$emit('search:toggle');
      }
    }
  }
  </script>

  <style lang="scss">
  @import "resources/sass/variables";

  .sidebar.mobileToggle {
    display: block !important;
    width: 232px;
    opacity: 1;

    .fixed {
      padding: 18px 0;
      padding-bottom: 120px;
    }
  }

  @media(min-width: 1700px) {
    .sidebar.visible ~ .pageWrapper {
      padding-left: $sidebar-width-expand;
    }
  }

  @media(max-width: 991px) {
    .sidebar.visible ~ .pageWrapper {
      padding-left: 0 !important;;
    }
  }

  @media(min-width: 1700px), (max-width: 991px) {
    .sidebar .games {
      height: calc(100% - 95px) !important;
    }

    .sidebar .sidebar-header {
      display: flex !important;
    }

    .sidebar.visible {
      width: $sidebar-width-expand;

      .sidebar-description {
        opacity: 1;
        margin-left: 25px;
        height: auto;
      }

      .divider {
        margin-top: 35px !important;
      }

      .fixed {
        width: $sidebar-width-expand;

        .game {
          justify-content: unset;
          padding-left: 40px;
          padding-right: 40px;
          position: relative;
          white-space: nowrap;

          .liveCount {
            @include themed() {
              background: t('secondary');
              color: black;
              width: 15px;
              height: 15px;
              border-radius: 50%;
              display: flex;
              align-items: center;
              justify-content: center;
              margin-left: 10px;
              font-size: .6em;
              font-weight: 600;
              position: absolute;
              top: 8px;
            }
          }

          i {
            width: 25px;
          }

          svg {
            margin-right: 11px;
          }

          div {
            display: block;
            opacity: 1;
            margin-left: 10px;
            max-width: 60%;
            overflow: hidden;
            text-overflow: ellipsis;
          }
        }
      }

      .promotion {
        height: unset;

        .name, .description {
          opacity: 1;
          transition-delay: .4s;
          display: block;
        }

        .image {
          position: unset;
          top: unset;
          left: unset;
        }
      }
    }
  }

  .sidebar {
    width: $sidebar-width;
    flex-shrink: 0;
    z-index: 38002;
    transition: width 0.3s ease;

    .os-scrollbar-horizontal {
      display: none;
    }

    .sidebar-description {
      font-weight: 600;
      left: 0;
      height: 0;
      transition: height .3s ease, opacity .3s ease, margin-left .3s ease;
      margin-left: 5px;
      opacity: 0;
      text-transform: uppercase;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .promotion {
      margin-top: 20px;
      background: linear-gradient(274.28deg, rgba(255, 195, 76, 0) 0%, rgb(25 32 46) 100%), #20293c;
      padding: 20px 40px;
      display: flex;
      align-items: center;
      cursor: pointer;
      position: relative;
      height: 90px;
      transition: height .3s ease;

      .image {
        width: 40px;
        height: 40px;
        background: url('/img/misc/treasure.png') no-repeat center;
        background-size: cover;
        margin-right: 20px;

        position: absolute;
        top: 25px;
        left: 20px;
      }

      .name, .description {
        transition: opacity .3s ease;
        opacity: 0;
        transition-delay: 0s;
        display: none;
      }

      .name {
        color: #FFC34C;
        text-shadow: 0 0 8px rgba(255, 195, 76, 0.62);
        font-size: 16.5px;
      }

      .description {
        margin-top: 2px;
        font-weight: 500;
        font-size: 14.5px;
      }
    }

    .fixed {
      position: fixed;
      top: 0;
      width: $sidebar-width;
      height: 100%;

      @include themed() {
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.56);
        background: t('sidebar');
        transition: background 0.15s ease, width .3s ease;

        .sidebar-header {
          height: $header-height;
          display: none;

          .logo {
            background: url('/img/misc/logo.png') no-repeat center;
            width: 40px;
            height: 40px;
            background-size: contain;
            margin-top: auto;
            margin-bottom: auto;
            margin-left: 30px;
            display: none;
            cursor: pointer;
          }

          @media(max-width: 991px) {
            display: none !important;
          }

          .toggle {
            display: flex;
            padding: 10px 14px;
            background: t('chat-accent');
            margin: auto;
            cursor: pointer;
            transition: background .3s ease;
            border-radius: 4px;

            &:hover {
              background: t('header-block');
            }
          }
        }

        .games {
          height: 100%;

          &.sportSidebar {
            height: calc(100% - 35px);
          }

          border-radius: 3px;

          .divider {
            margin-top: 5px;
            transition: margin-top .3s ease;
          }

          .btn {
            width: calc(100% - 30px);
            margin-left: 15px;
            margin-right: 15px;
            margin-bottom: 15px;
            border-radius: 20px;

            &.btn-primary {
              border-bottom: 3px solid darken(t('secondary'), 5%);
            }

            &.btn-secondary {
              border-bottom: 3px solid darken($gray-600, 5%);
            }
          }
        }
      }

      .game {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 60px;
        font-size: 16px;
        cursor: pointer;
        position: relative;
        transition: color .3s ease;

        @include themed() {
          color: t('link');

          &.featured {
            color: #ffd92b;
          }
        }

        &.router-link-exact-active {
          @include themed() {
            color: t('text');
          }

          &:before {
            opacity: 1;
          }

          &:after {
            opacity: 1;
          }
        }

        @include themed() {
          &.highlight {
            color: t('secondary') !important;
          }
        }

        div {
          display: none;
          opacity: 0;
          transition: opacity 1s ease;
        }

        .vue-content-placeholders-img {
          display: block !important;
          opacity: 1 !important;
        }

        .vue-content-placeholders-img {
          height: 15px;
          width: 15px;
          border-radius: 3px;
        }

        img {
          width: 14px;
          height: 14px;
        }

        i {
          cursor: pointer;
        }

        &:hover {
          opacity: 1;
        }

        .online {
          position: absolute !important;
          top: 4px !important;
          left: 17px !important;
          border-radius: 50%;
          width: 15px;
          @include themed() {
            background: t('secondary');
          }
          color: white;
          height: 15px;
          font-size: 0.5em;
          display: flex;
          align-items: center;
          justify-content: center;
          text-align: center;
        }
      }

      .game.router-link-exact-active {
        opacity: 1;
      }
    }

    &.visible .fixed .sidebar-header {
      .logo {
        display: unset;
      }

      .toggle {
        margin-right: 20px !important;
      }
    }
  }

  @media(max-width: 991px) {
    .sidebar {
      display: none;
    }
  }
</style>
