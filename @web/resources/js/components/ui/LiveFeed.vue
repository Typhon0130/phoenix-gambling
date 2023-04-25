<template>
  <div class="container">
    <div class="live">
      <div class="header">
        <template v-if="isCasino">
          <div class="live_tabs">
            <div class="tabs">
              <div v-if="!isGuest" @click="$store.dispatch('setLiveChannel', 'mine')"
                   :class="`tab ${liveChannel === 'mine' ? 'active' : ''}`">{{ $t('general.bets.mine') }}
              </div>
              <div @click="$store.dispatch('setLiveChannel', 'all')"
                   :class="`tab ${liveChannel === 'all' ? 'active' : ''}`">{{ $t('general.bets.all') }}
              </div>
              <div @click="$store.dispatch('setLiveChannel', 'high_rollers')"
                   :class="`tab ${liveChannel === 'high_rollers' ? 'active' : ''}`">{{
                  $t('general.bets.high_rollers')
                }}
              </div>
              <div @click="$store.dispatch('setLiveChannel', 'lucky_wins')"
                   :class="`tab ${liveChannel === 'lucky_wins' ? 'active' : ''}`">{{ $t('general.bets.lucky_wins') }}
              </div>
            </div>
            <select @change="$store.dispatch('setLiveFeedEntryCount', parseFloat(liveFeedEntriesWrap))"
                    v-model="liveFeedEntriesWrap">
              <option value="10" :selected="liveFeedEntries === 10">10</option>
              <option value="25" :selected="liveFeedEntries === 25">25</option>
              <option value="50" :selected="liveFeedEntries === 50">50</option>
            </select>
          </div>
        </template>
        <template v-else>
          <div class="live_tabs">
            <div class="tabs">
              <div v-if="!isGuest" @click="$store.dispatch('setLiveChannel', 'mine')"
                   :class="`tab ${liveChannel === 'mine' ? 'active' : ''}`">{{ $t('general.bets.mine') }}
              </div>
              <div @click="$store.dispatch('setLiveChannel', 'all')"
                   :class="`tab ${liveChannel !== 'mine' ? 'active' : ''}`">{{ $t('general.bets.all') }}
              </div>
            </div>
            <select @change="$store.dispatch('setLiveFeedEntryCount', parseFloat(liveFeedEntriesWrap))"
                    v-model="liveFeedEntriesWrap">
              <option value="10" :selected="liveFeedEntries === 10">10</option>
              <option value="25" :selected="liveFeedEntries === 25">25</option>
              <option value="50" :selected="liveFeedEntries === 50">50</option>
            </select>
          </div>
        </template>
      </div>
      <template v-if="isCasino">
        <div class="live_table_container">
          <loader v-if="!lastGames"></loader>
          <table class="live-table" v-else>
            <thead>
            <tr>
              <th>{{ $t('general.bets.game') }}</th>
              <th>{{ $t('general.bets.player') }}</th>
              <th class="d-none d-xl-table-cell">{{ $t('general.bets.time') }}</th>
              <th class="d-none d-xl-table-cell">{{ $t('general.bets.bet') }}</th>
              <th class="d-none d-xl-table-cell">{{ $t('general.bets.mul') }}</th>
              <th>{{ $t('general.bets.win') }}</th>
            </tr>
            </thead>
            <tbody class="live_games">
            <tr v-for="game in lastGames">
              <th>
                <div class="gameIcon">
                  <router-link :to="`/casino/game/${game.metadata.id}`" tag="div" class="icon d-none d-md-inline-block">
                    <web-icon :icon="game.metadata.icon"></web-icon>
                  </router-link>
                  <div class="name">
                    <div>
                      <router-link :to="`/casino/game/${game.metadata.id}`">{{ game.metadata.name }}</router-link>
                    </div>
                    <a href="javascript:void(0)"
                       @click="openOverviewModal(game.game._id, game.game.game)">{{ $t('general.overview') }}</a>
                  </div>
                </div>
              </th>
              <th>
                <div>
                  <a :href="game.user.private_bets !== true || (isGuest ? false : !$checkPermission('ignore_privacy')) ? 'javascript:void(0)' : $route.path"
                     @click="game.user.private_bets !== true || (isGuest ? false : !$checkPermission('ignore_privacy')) ? openUserModal(game.user._id) : false">
                    <span v-if="game.user.private_bets && (isGuest ? true : user.user.access === 'user')"><web-icon
                      icon="fad fa-user-secret mr-1"></web-icon> {{ $t('general.bets.hidden_name') }}</span>
                    <span v-else>{{ game.user.name }}</span>
                  </a>
                </div>
              </th>
              <th class="d-none d-xl-table-cell">
                <div><span>{{ new Date(game.game.created_at).toLocaleTimeString() }}</span></div>
              </th>
              <th data-highlight class="d-none d-xl-table-cell">
                <div>
                  <unit :to="game.game.currency" :value="game.game.wager"></unit>
                  <web-icon :icon="currencies[game.game.currency].icon"
                            :style="{ color: currencies[game.game.currency].style }"></web-icon>
                </div>
              </th>
              <th data-highlight class="d-none d-xl-table-cell">
                <div>{{
                    (game.game.status === 'win' || game.game.multiplier < 1 ? game.game.multiplier : 0).toFixed(2)
                  }}x
                </div>
              </th>
              <th>
                <div :class="game.game.status === 'win' ? 'live-win' : ''">
                  <unit :to="game.game.currency" :value="game.game.profit"></unit>
                  <web-icon :icon="currencies[game.game.currency].icon"
                            :style="{ color: currencies[game.game.currency].style }"></web-icon>
                </div>
              </th>
            </tr>
            </tbody>
          </table>
        </div>
      </template>
      <template v-else>
        <div class="live_table_container">
          <loader v-if="!lastGames"></loader>
          <table class="live-table" v-else>
            <thead>
            <tr>
              <th>{{ $t('general.bets.event') }}</th>
              <th>{{ $t('general.bets.player') }}</th>
              <th class="d-none d-md-table-cell">{{ $t('general.bets.time') }}</th>
              <th class="d-none d-md-table-cell">{{ $t('general.bets.odds') }}</th>
              <th class="d-none d-md-table-cell">{{ $t('general.bets.bet') }}</th>
            </tr>
            </thead>
            <tbody class="live_games">
            <tr v-for="game in lastGames">
              <th>
                <div class="gameIcon">
                  <router-link :to="`/sport/game/`" tag="div" class="icon d-none d-md-inline-block">
                    <web-icon :icon="game.game.icon"></web-icon>
                  </router-link>
                  <div class="name">
                    <div>
                      <router-link :to="`/sport/category/` + game.game.category">{{ game.game.game }}</router-link>
                    </div>
                    <router-link :to="`/sport/category/` + game.game.category">{{
                        game.game.categoryName
                      }}
                    </router-link>
                  </div>
                </div>
              </th>
              <th>
                <div>
                  <a @click="game.user.private_bets !== true || (isGuest ? false : !$checkPermission('ignore_privacy')) ? openUserModal(game.user._id) : false"
                    :href="game.user.private_bets !== true || (isGuest ? false : !$checkPermission('ignore_privacy')) ? `javascript:void(0)` : $route.path">
                    <span v-if="game.user.private_bets && (isGuest ? true : !$checkPermission('ignore_privacy'))"><web-icon
                      icon="fad fa-user-secret mr-1"></web-icon> {{ $t('general.bets.hidden_name') }}</span>
                    <span v-else>{{ game.user.name }}</span>
                  </a>
                </div>
              </th>
              <th class="d-none d-md-table-cell">
                <div><span>{{ new Date(game.game.created_at).toLocaleTimeString() }}</span></div>
              </th>
              <th data-highlight class="d-none d-md-table-cell">
                <div>
                  x{{ game.game.odds.toFixed(2) }}
                </div>
              </th>
              <th data-highlight class="d-none d-md-table-cell">
                <div>
                  <unit :to="game.game.currency" :value="game.game.bet"></unit>
                  <web-icon :icon="currencies[game.game.currency].icon"
                            :style="{ color: currencies[game.game.currency].style }"></web-icon>
                </div>
              </th>
            </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import Bus from '../../bus';
  import OverviewModal from '../modals/OverviewModal.vue';
  import UserModal from "../modals/UserModal.vue";

  export default {
    data() {
      return {
        lastGames: null,
        liveFeedEntriesWrap: 10
      }
    },
    watch: {
      liveChannel() {
        this.getGames();
      },
      liveFeedEntries() {
        this.getGames();
      },
      lastGames() {
        if (this.lastGames && this.lastGames.length >= this.liveFeedEntries) this.lastGames.pop();
      },
      $route(to, from) {
        if ((to.path.startsWith("/sport") && !from.path.startsWith("/sport")) || (from.path.startsWith("/sport") && !to.path.startsWith("/sport")))
          this.getGames();
      },
    },
    computed: {
      ...mapGetters(['liveFeedEntries', 'isGuest', 'liveChannel', 'user', 'currencies'])
    },
    created() {
      this.getGames();
      this.liveFeedEntriesWrap = this.liveFeedEntries;

      Bus.$on('event:liveGame', (e) => {
        if (!this.isCasino) return;

        if (this.liveChannel === 'mine' && e.user._id !== this.user.user._id) return;
        if (this.liveChannel === 'lucky_wins' && (e.game.multiplier < 10 || e.game.status !== 'win')) return;
        if (this.liveChannel === 'high_rollers' && e.game.wager < this.currencies[e.game.currency].highRollerRequirement) return;
        setTimeout(() => this.lastGames.unshift(e), e.delay);
      });

      Bus.$on('event:liveSportGame', (e) => {
        if (this.isCasino) return;

        if (this.liveChannel === 'mine' && e.user._id !== this.user.user._id) return;
        this.lastGames.unshift(e);
      });
    },
    methods: {
      openUserModal(id) {
        UserModal.methods.open(id);
      },
      getGames() {
        this.lastGames = null;

        axios.post('/api/data/latestGames', {
          mode: this.isCasino ? 'casino' : 'sport',
          type: this.liveChannel,
          count: this.liveFeedEntries
        }).then(({data}) => this.lastGames = data.reverse());
      },
      openOverviewModal(id, game) {
        OverviewModal.methods.open(id, game);
      }
    }
  }
</script>
