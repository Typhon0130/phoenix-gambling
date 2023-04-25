<template>
  <div class="sport">
    <template v-if="!hasFeature">
      <template v-if="$permission.checkPermission('*')">
        <dashboard-warning type="critical">
          Sportsbook is not available for your license. <a href="https://t.me/casino_sales" target="_blank" style="margin-left: 5px">Contact Phoenix Gambling</a>
        </dashboard-warning>
      </template>
      <template v-else>
        <dashboard-warning type="warning">
          Sportsbook is not available. Contact your system administrator for more information.
        </dashboard-warning>
      </template>
    </template>
    <template v-else>
      <div class="animate" v-if="bets">
        <div class="entries">
          <div class="h">
            <div>Game ID</div>
            <div>Game</div>
            <div>Market</div>
            <div>Runner</div>
            <div>User</div>
            <div>Date</div>
            <div>Category</div>
            <div>Payout</div>
            <div>Manage</div>
          </div>
          <template v-if="bets.bets.length === 0">
            No data to show.
          </template>
          <div v-for="(v, key) in bets.bets" :key="key">
            <div class="title">{{ key.startsWith('multi|') ? 'Multi' : 'Single' }}</div>
            <div v-for="(bet, i) in v" :key="bet.data._id">
              <div class="entry">
                <div>
                  {{ bet.data.game_id }}
                </div>
                <div>
                  {{ bet.data.game }}
                </div>
                <div>
                  {{ bet.data.market }}
                </div>
                <div>
                  {{ bet.data.runner }}
                </div>
                <div @click="$router.push('/admin/user/' + bet.user._id)">
                  <img alt :src="bet.user.avatar"> {{ bet.user.name }}
                </div>
                <div>
                  {{ new Date(bet.data.created_at).toLocaleString() }}
                </div>
                <div>
                  {{ bet.data.category }}
                </div>
                <div>
                  <template v-if="i === 0">
                    {{ bet.data.bet.toFixed(8) }}
                    {{ bet.data.currency }}
                    <template v-if="key.startsWith('multi|')">(Multi bet)</template>
                    <template v-else>x{{ bet.data.odds }}</template>
                  </template>
                </div>
                <div>
                  <template v-if="i === 0">
                    <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-primary mr-2" @click="sync(bet.data._id)"><web-icon icon="fal fa-fw fa-sync"></web-icon> Sync</button>
                    <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-danger mr-2" @click="set(bet.data._id, 'win')"><web-icon icon="fal fa-fw fa-check"></web-icon> Set: Won</button>
                    <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-secondary" @click="set(bet.data._id, 'lose')"><web-icon icon="fal fa-fw fa-times"></web-icon> Set: Lost</button>
                    <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-secondary" @click="set(bet.data._id, 'refund')"><web-icon icon="fal fa-fw fa-undo"></web-icon> Refund</button>
                  </template>
                </div>
              </div>
            </div>
          </div>
          <div class="pagination" v-if="maxPages">
            <div class="prev" @click="page -= 2" v-if="page - 2 >= 1">{{ page - 2 }}</div>
            <div class="prev" @click="page--" v-if="page - 1 >= 1">{{ page - 1 }}</div>
            <div class="current">{{ page }}</div>
            <div class="next" @click="page++" v-if="page + 1 <= maxPages">{{ page + 1 }}</div>
            <div class="next" @click="page += 2" v-if="page + 2 <= maxPages">{{ page + 2 }}</div>
            <div class="jump" @click="jump"><web-icon icon="fal fa-fw fa-ellipsis-h"></web-icon></div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
  import DashboardWarning from "../ui/DashboardWarning.vue";
  import WebIcon from "../ui/WebIcon.vue";
  import OverlayScrollbars from 'overlayscrollbars';

  export default {
    data() {
      return {
        hasFeature: false,

        bets: null,
        page: 1,
        maxPages: null
      }
    },
    watch: {
      page() {
        this.load();
      }
    },
    methods: {
      set(id, status) {
        window.axios.post('/admin/setSportManually', { id: id, status: status }).then(() => window.location.reload());
      },
      sync(id) {
        window.axios.post('/admin/sportVerify', { id: id }).then(({ data }) => {
          if(data.result) window.location.reload();
          else this.$toast.error('Game is not finished yet. Bet status was not changed.');
        });
      },
      load() {
        window.axios.post('/admin/sportBets').then(({ data }) => {
          this.bets = data;
          this.maxPages = data.maxPages;
          this.$bus.$emit('loading:done');

          this.$nextTick(() => {
            OverlayScrollbars(document.querySelector('.sport .entries'), {
              scrollbars: { autoHide: 'leave' },
              className: 'os-theme-thin-light'
            });
          });
        });
      },
      jump() {
        const page = parseInt(prompt(`Enter page (from 1 to ${this.maxPages}):`));
        if(page && !isNaN(page) && page >= 1 && page <= this.maxPages) {
          this.page = page;
        } else alert('Invalid page: ' + page);
      }
    },
    mounted() {
      this.hasFeature = window.$permission.isFeatureAvailable('phoenixSport');
      if(this.hasFeature) this.load();
    },
    components: {
      DashboardWarning,
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .entries {
    display: flex;
    flex-direction: column;
    margin-top: 15px;

    @include min(0, bp('md')) {
      width: calc(100vw - 140px);
    }

    width: calc(100vw - 350px);

    .title {
      font-weight: 600;
      font-size: 1.1em;
    }

    .h, .entry {
      min-width: 1000px;
    }

    .h div, .entry div {
      margin-right: 10px;
      white-space: nowrap;
      width: 250px;
      overflow: hidden;
      text-overflow: ellipsis;
      flex-shrink: 0;

      &:last-child {
        width: 500px;
      }

      button {
        margin-right: 10px;

        &:last-child {
          margin-right: 0;
        }
      }
    }

    .h {
      display: flex;
      font-size: 1.1em;
      margin-bottom: 20px;
      margin-top: 10px;
    }

    .entry {
      display: flex;
      align-items: center;
      margin-bottom: 25px;
      cursor: pointer;

      div {
        display: flex;
        align-items: center;

        img {
          margin-right: 10px;
          width: 32px;
          height: 32px;
        }
      }
    }
  }

  .pagination {
    font-size: 16px;
    margin-top: 25px;
    display: flex;

    .current {
      @include themed() {
        color: t('secondary');
        font-weight: 400;
      }
    }

    div {
      margin-right: 5px;
      cursor: pointer;
      font-weight: 100;

      &:last-child {
        margin-right: 0;
      }
    }
  }
</style>
