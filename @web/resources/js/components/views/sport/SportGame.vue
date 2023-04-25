<template>
  <div class="sportGamePage container">
    <template v-if="!doesntExist">
      <loader v-if="!game"></loader>
      <template v-else>
        <div class="sportGameHeader">
          <i class="fal fa-chevron-left" @click="$router.push('/' + sportLink + '/category/' + $route.params.category)"></i>
          {{ game.name }}
        </div>

        <div class="sportGameInfo" :style="categoryBackground()">
          <div class="competitors">
            <div class="competitor">
              <div class="name">{{ game.competitors[0].name }}</div>
              <img v-if="game.competitors[0].logo" :src="game.competitors[0].logo" alt/>
            </div>
            <div class="competitor">
              <div class="name">{{ game.competitors[1].name }}</div>
              <img v-if="game.competitors[1].logo" :src="game.competitors[1].logo" alt/>
            </div>
          </div>
          <div class="sportLiveStatus">
            <div class="createdAt" v-if="game.liveStatus.createdAt > 0">
              {{ new Date(game.liveStatus.createdAt).toLocaleString() }}
            </div>
            <div class="score">{{ game.liveStatus.score.split(":")[0] }}
              <div class="divide"></div>
              {{ game.liveStatus.score.split(":")[1] }}
            </div>
            <div class="stage">{{ game.liveStatus.stage }}</div>
            <div class="setScore">{{ game.league.name }}</div>
          </div>
          <div class="live-markets" v-if="game.markets.length > 0">
            <bet-slip-button :category="$route.params.category" :market="game.markets[0]" :runner="runner" :game="game"
                             :class="'runner ' + (runner.open && game.open && game.markets[0].open ? '' : 'disabled')"
                             v-for="runner in game.markets[0].runners.slice(0, 2)"
                             :key="game.markets[0].name + runner.name">
              <div>{{ game.open && runner.open && game.markets[0].open ? $t(runner.translation.runner.key) : '--' }}</div>
              <div class="price">{{ game.open && runner.open && game.markets[0].open ? runner.price : '--' }}</div>
            </bet-slip-button>
          </div>
        </div>
        <div class="sportClosedBetting" v-if="!game.open">
          <i class="fal fa-pause-circle"></i> {{ $t('sport.game_is_closed') }}
        </div>
        <div class="sportGameMainData">
          <div class="market-columns">
            <div class="markets">
              <div class="phoenixEsportStats" v-if="phoenixShowEsportStats && game.eSport"><pre>{{ game.eSport }}</pre></div>

              <div class="empty" v-if="Object.values(sortMarkets(markets)).length === 0">
                <web-icon icon="time"></web-icon>
                <div>{{ $t('sport.noMarkets') }}</div>
              </div>
              <template v-for="market in sortMarkets(markets)">
                <div class="market" :key="market.name" v-if="market.categories[0] && market.categories[0][0]">
                  <div class="title"
                       @click="isExpanded(market) ? expanded.push(market.name) : expanded = expanded.filter((e) => e !== market.name); categoryToggledAtLeastOnce.push(market.name)">
                    {{ $t(market.categories[0][0].translation.market.key, market.categories[0][0].translation.market.data) }}
                    <i :class="isExpanded(market) ? 'fas fa-chevron-down' : 'fas fa-chevron-left'"></i>

                    <div v-if="!market.isAvailable && phoenixShowUnavailableRunners">
                      &nbsp; [Unsupported]
                    </div>
                  </div>
                  <template v-if="isExpanded(market)">
                    <div class="category" v-for="category in market.categories">
                      <bet-slip-button :category="$route.params.category"
                                       :class="'runner ' + (game.open && market.open && runner.open ? '' : 'disabled') + ' ' + (category.length < 3 ? 'runner-2' : 'runner-3')"
                                       v-for="(runner, i) in category" :game="game"
                                         v-if="(game.open && runner.open && market.open) || i < 3"
                                       :market="market" :runner="runner" :key="market.name + runner.name">
                          <div>{{ game.open && runner.open && market.open ? $t(runner.translation.runner.key) : '--' }}</div>
                          <div class="price">{{ game.open && runner.open && market.open ? runner.price : '--' }}</div>
                      </bet-slip-button>
                    </div>
                  </template>
                </div>
              </template>
            </div>
          </div>
          <div class="gameInfoDesktop">
            <div class="competitors">
              <div class="competitor">
                <div class="name">{{ game.competitors[0].name }}</div>
                <img v-if="game.competitors[0].logo" :src="game.competitors[0].logo" alt/>
              </div>
              <div class="competitor">
                <div class="name">{{ game.competitors[1].name }}</div>
                <img v-if="game.competitors[1].logo" :src="game.competitors[1].logo" alt/>
              </div>
            </div>
            <div class="sportLiveStatus">
              <div class="createdAt" v-if="game.liveStatus.createdAt > 0">
                <web-icon icon="time"></web-icon>
                {{ new Date(game.liveStatus.createdAt).toLocaleString() }}
              </div>
              <div class="score">{{ game.liveStatus.score.split(":")[0] }}
                <div class="divide"></div>
                {{ game.liveStatus.score.split(":")[1] }}
              </div>
              <div class="stage">{{ game.liveStatus.stage }}</div>
              <div class="setScore">{{ game.league.name }}</div>

              <div id="widget"></div>
            </div>
          </div>
        </div>
      </template>
    </template>
    <div class="error" v-else>
      <web-icon icon="time"></web-icon>
      <div class="title">{{ $t('sport.game_not_found') }}</div>
    </div>
  </div>
</template>

<script>
  import Bus from "../../../bus.js";
  import { mapGetters } from 'vuex';
  import WebIcon from "../../ui/WebIcon.vue";

  export default {
    components: {WebIcon},
    data() {
      return {
        game: null,
        markets: null,

        doesntExist: false,
        updateInterval: null,
        loadingGame: false,

        expanded: [],
        categoryToggledAtLeastOnce: [],

        embedded: false,
        mobileEmbedded: false
      }
    },
    computed: {
      ...mapGetters(['phoenixShowUnavailableRunners', 'phoenixShowEsportStats']),
      isVisible() {
        return !this.doesntExist && this.game;
      }
    },
    watch: {
      isVisible() {
        if(this.isVisible && !this.embedded) this.$nextTick(() => {
          this.initializeWidget('widget', () => this.embedded = true);
        });
      },
      doesntExist() {
        if(!this.doesntExist) this.embedded = false;
      },
      game() {
        let markets = {};

        this.game.markets.forEach((market) => {
          if(!market.isAvailable && !this.phoenixShowUnavailableRunners) return;

          let runners = [];

          market.runners.forEach(runner => {
            if(!runner.supported.status && !this.phoenixShowUnavailableRunners) return;
            runners.push(runner);
          });

          if (markets[market.name]) markets[market.name].categories.push(runners);
          else markets[market.name] = {
            name: market.name,
            open: market.open,
            isAvailable: market.isAvailable,
            categories: [
              runners
            ]
          };
        });

        this.markets = markets;
      }
    },
    beforeDestroy() {
      clearInterval(this.updateInterval);
    },
    methods: {
      initializeWidget(elementId, callback) {
        if(this.game.sportType === 'SPORTS') {
          if (window.SIR) {
            window.SIR('addWidget', '#' + elementId, 'match.lmtPlus', {matchId: this.game.id});
            callback();
          }
        } else if(this.game.sportType === 'ESPORTS') {
          if(!this.game.twitch)
            document.querySelector('#' + elementId).innerHTML = '<div class="notStarted">' + this.$i18n.t('sport.widgetUnavailable') + '</div>';
          else if(window.Twitch) {
            new window.Twitch.Embed(elementId, {
              width: '100%',
              height: 190,
              channel: this.game.twitch.substring(this.game.twitch.lastIndexOf('/') + 1),
              layout: "video"
            });

            callback();
          }
        }
      },
      sortMarkets(markets) {
        return Object.values(markets).sort((a, b) => b.open - a.open).sort((a, b) => b.isAvailable - a.isAvailable);
      },
      isExpanded(market) {
        return (!this.categoryToggledAtLeastOnce.includes(market.name) && market.categories.filter((e) => e.filter((e) => e.main).length > 0).length > 0) || !this.expanded.includes(market.name);
      },
      categoryBackground() {
        switch (this.$route.params.category) {
          case 'soccer':
          case 'football':
            return {background: '#2d7837'};
          case 'tennis':
            return {background: '#9d5535'};
          case 'table tennis':
            return {background: '#1b4d78'};
          case 'basketball':
            return {background: '#7e5f3a'};
          case 'hockey':
            return {background: '#38536c'};
          default:
            return {background: '#08090a'};
        }
      },
      update() {
        if (this.loadingGame) return;
        this.loadingGame = true;

        axios.post('/api/sport/game', {id: this.$route.params.id}).then(({data}) => {
          this.game = data;
          this.doesntExist = false;
          this.loadingGame = false;
        }).catch(() => {
          this.doesntExist = true;
          this.loadingGame = false;
        });
      }
    },
    created() {
      this.update();

      this.updateInterval = setInterval(() => this.update(), 1500);

      Bus.$on('sport:initializeMobileWidget', () => {
        if(!this.mobileEmbedded) this.initializeWidget('mobileWidget', () => this.mobileEmbedded = true);
      });
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .sportGamePage {
    @media(min-width: 991px) {
      margin-top: -15px;
    }

    .error {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding-top: 20px;

      svg, i {
        @include themed() {
          font-size: 7em;
          margin-bottom: 25px;
          color: t('secondary');
        }
      }

      .title {
        font-size: 1.5em;
      }
    }

    .loaderContainer {
      margin-top: 55px;
      margin-bottom: 55px;

      .loader {
        margin: auto;
      }
    }

    @include themed() {
      $desktop-data-size: 336px;

      .market-columns {
        display: flex;
      }

      .sportGameMainData {
        display: flex;

        .empty {
          margin: auto;
          text-align: center;
          display: flex;
          flex-direction: column;
          align-items: center;

          i, svg {
            font-size: 3em;
            margin-bottom: 15px;
          }
        }

        @media(max-width: 991px) {
          width: 100%;

          .market-columns {
            width: 100%;
          }
        }

        .market-columns {
          @media(min-width: 991px) {
            width: calc(100% - #{$desktop-data-size});
            padding-right: 15px;
          }
        }

        .gameInfoDesktop {
          width: $desktop-data-size;
          margin-top: 20px;
          border-radius: 10px;
          background: t('sidebar');
          padding: 20px 25px;
          height: fit-content;
          position: sticky;
          top: $header-height + 15px;

          @media(max-width: 991px) {
            display: none;
          }

          .sportLiveStatus {
            position: relative;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;

            .createdAt {
              margin-top: 15px;
              opacity: 0.6;
              font-size: .9em;
              display: flex;
              align-items: center;
              justify-content: center;

              svg, i {
                margin-right: 5px;
                margin-top: -1px;
              }
            }

            .score {
              font-size: 2.5em;
              margin-top: 15px;
              margin-bottom: 15px;
              font-weight: 600;
              display: flex;
              align-items: center;
              justify-content: center;

              .divide {
                border-left: 1px solid rgba(white, .5);
                height: 30px;
                margin: 0 10px;
              }
            }

            .stage, .setScore {
              font-size: .9em;
            }
          }

          .competitors {
            display: flex;

            .competitor {
              .name {
                font-size: 1em;
                margin-bottom: 10px;
              }

              img {
                width: 72px;
                height: 72px;
                border: 3px solid white;
                background: white;
                border-radius: 50%;
              }

              &:last-child {
                margin-left: auto;
                text-align: right;
              }
            }
          }
        }
      }

      .markets {
        display: flex;
        width: 100%;
        flex-direction: column;
        padding: 15px;
        margin-top: 5px;

        &:first-child {
          padding-right: 0;
        }

        .market {
          border-radius: 3px;
          background: t('sidebar');
          margin-bottom: 15px;
          color: t('text');
          transition: background .3s ease, color .3s ease;

          &.active {
            background: t('secondary') !important;
            color: black;
          }

          &:last-child {
            margin-bottom: 0;
          }

          .title {
            padding: 10px 20px;
            background: darken(t('sidebar'), 1.5%);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;

            svg, i {
              margin-left: auto;
            }
          }

          .category {
            display: flex;
            flex-wrap: wrap;

            .runner {
              width: 100%;
              display: flex;
              user-select: none;
              padding: 10px 15px;
              background: lighten(t('sidebar'), 3%);
              cursor: pointer;
              transition: background .3s ease, color .3s ease;
              flex-wrap: wrap;
              color: t('text');

              &.unavailable {
                background: rgb(222, 70, 70) !important;

                .price {
                  color: white !important;
                }
              }

              &.active {
                background: t('secondary') !important;
                color: black !important;

                .price {
                  color: black !important;
                }
              }

              &.runner-2 {
                flex-basis: 50%;
                max-width: 50%;
              }

              &.runner-3 {
                flex-basis: 33.33%;
                max-width: 33.33%;
              }

              .price {
                color: t('secondary');
              }

              &.disabled {
                cursor: default;
                background: lighten(t('sidebar'), 2%) !important;
              }

              &:hover {
                background: lighten(t('sidebar'), 4%);
              }

              div:last-child {
                margin-left: auto;
              }
            }
          }
        }
      }

      .sportGameHeader {
        text-transform: uppercase;
        font-weight: 600;
        font-size: 1.1em;
        padding: 25px;
        width: 100%;
        background: t('sidebar');
        text-align: center;
        position: relative;
        border-radius: 10px;

        i {
          position: absolute;
          left: 25px;
          top: 50%;
          transform: translateY(-50%);
          cursor: pointer;
          opacity: .8;
          transition: opacity 3s ease;

          &:hover {
            opacity: 1;
          }
        }
      }

      .sportClosedBetting {
        padding: 15px;
        width: 100%;
        background: t('sidebar');
        text-align: center;

        i {
          margin-right: 5px;
        }
      }

      .sportGameInfo {
        padding: 30px;
        color: white;
        position: relative;

        @media(min-width: 991px) {
          display: none;
        }

        .sportLiveStatus {
          position: relative;
          left: 50%;
          transform: translateX(-50%);
          text-align: center;

          .score {
            font-size: 2.5em;
            margin-top: 5px;
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;

            .divide {
              border-left: 1px solid rgba(white, .5);
              height: 30px;
              margin: 0 10px;
            }
          }

          .stage, .setScore {
            font-size: .9em;
          }
        }

        .live-markets {
          display: flex;
          align-items: center;
          justify-content: center;
          margin-top: 15px;

          .runner {
            margin-right: 10px;
            cursor: pointer;
            user-select: none;
            background: t('body');
            display: flex;
            min-width: 120px;
            padding: 10px 15px;
            border-radius: 3px;
            color: t('text');
            transition: background .3s ease, color .3s ease;

            &.active {
              background: t('secondary') !important;
              color: black !important;

              .price {
                color: black !important;
              }
            }

            div:last-child {
              margin-left: auto;
            }

            div:first-child {
              margin-right: 5px;
            }

            &:hover {
              background: t('sidebar');
            }

            &.disabled {
              cursor: default;
              background: t('body') !important;
            }

            &:last-child {
              margin-right: 0;
            }
          }
        }

        .competitors {
          display: flex;
          position: absolute;
          width: calc(100% - 60px);

          .competitor {
            .name {
              font-weight: 600;
              margin-bottom: 10px;
              font-size: 1.1em;
            }

            img {
              width: 72px;
              height: 72px;
              border: 3px solid white;
              background: white;
              border-radius: 50%;
            }

            &:last-child {
              margin-left: auto;
              text-align: right;
            }
          }
        }
      }
    }
  }

  @media(max-width: 991px) {
    .sportGamePage {
      .market-columns {
        flex-direction: column;

        .markets {
          padding-right: 15px !important;
        }
      }
    }
  }

  @media(max-width: 991px) {
    .sportGamePage {
      .markets {
        .runner-3 {
          flex-basis: 50% !important;
          max-width: 50% !important;
        }

        .runner {
          font-size: 0.8em;
        }
      }
    }

    .sportGameHeader {
      font-size: 0.8em !important;
      padding: 15px !important;

      i {
        display: none !important;
      }
    }

    .sportGameInfo {
      padding-bottom: 70px !important;

      .live-markets {
        display: none !important;
      }

      .competitors {
        bottom: 10px;

        .competitor {
          img {
            display: none !important;
          }
        }
      }
    }
  }
</style>
