<template>
  <div class="gameAnalytics animate" v-if="data">
    <div v-if="data.length === 0" class="noData">
      <web-icon icon="time"></web-icon>
      <div>No data yet. Play any game to make analytics appear.</div>
    </div>
    <div class="banner" v-else v-for="statId in [...new Set(data.map(e => e.statId))]" :key="statId">
      <div class="head">
        <div class="title">{{ statId }}</div>
      </div>
      <div class="stats">
        <div class="stat">
          <div class="title">Wagered today</div>
          <div class="text">
            <div class="currency" v-for="currency in findCurrencies(statId)" :key="currency">
              {{ currencies[currency].name }} {{ find(statId, 'daily', currency).wagered.toFixed(currencies[currency].decimals) }}
            </div>
          </div>
        </div>
        <div class="stat">
          <div class="title">Payout today</div>
          <div class="text">
            <div class="currency" v-for="currency in findCurrencies(statId)" :key="currency">
              {{ currencies[currency].name }} {{ find(statId, 'daily', currency).payout.toFixed(currencies[currency].decimals) }}
            </div>
          </div>
        </div>
        <div class="stat">
          <div class="title">Profit today</div>
          <div class="text">
            <div class="currency" v-for="currency in findCurrencies(statId)" :key="currency">
              {{ currencies[currency].name }} {{ find(statId, 'daily', currency).profit.toFixed(currencies[currency].decimals) }}
            </div>
          </div>
        </div>
        <div class="stat">
          <div class="title">Wagered total</div>
          <div class="text">
            <div class="currency" v-for="currency in findCurrencies(statId)" :key="currency">
              {{ currencies[currency].name }} {{ find(statId, 'total', currency).wagered.toFixed(currencies[currency].decimals) }}
            </div>
          </div>
        </div>
        <div class="stat">
          <div class="title">Payout total</div>
          <div class="text">
            <div class="currency" v-for="currency in findCurrencies(statId)" :key="currency">
              {{ currencies[currency].name }} {{ find(statId, 'total', currency).payout.toFixed(currencies[currency].decimals) }}
            </div>
          </div>
        </div>
        <div class="stat">
          <div class="title">Profit total</div>
          <div class="text">
            <div class="currency" v-for="currency in findCurrencies(statId)" :key="currency">
              {{ currencies[currency].name }} {{ find(statId, 'daily', currency).profit.toFixed(currencies[currency].decimals) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";

  export default {
    data() {
      return {
        data: null,
        currencies: null
      }
    },
    methods: {
      find(id, type, currency) {
        const result = this.data.filter(e => e.currency === currency && e.statId === id && e.type === type)[0];
        return result ? result : {
          payout: 0,
          profit: 0,
          wagered: 0
        };
      },
      findCurrencies(id) {
        return [ ...new Set(this.data.filter(e => e.statId === id).map(e => e.currency)) ];
      }
    },
    mounted() {
      window.axios.post('/admin/gameAnalytics').then(({ data }) => {
        let analyticsData = data;

        window.axios.post('/api/data/currencies').then(({ data }) => {
          this.data = analyticsData;
          this.currencies = data;
          this.$bus.$emit('loading:done');
        });
      });
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .gameAnalytics {
    .noData {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;

      svg, i {
        width: 60px;
        margin-bottom: 25px;
      }

      div {
        font-size: 1.1em;
      }
    }

    @include themed() {
      .banner {
        background: t('block');
        display: flex;
        margin-bottom: 15px;

        &:last-child {
          margin-bottom: 0;
        }

        .head {
          padding: 20px;
          background: t('block-2');
        }

        .stats {
          display: flex;
          flex-wrap: wrap;
          padding: 20px 20px 5px;

          .stat {
            margin-right: 35px;
            margin-bottom: 15px;

            &:last-child {
              margin-right: 0;
            }
          }
        }

        .title {
          font-weight: 600;
          text-transform: uppercase;
          opacity: .7;
        }

        .text {
          margin-top: 5px;
        }
      }
    }
  }
</style>
