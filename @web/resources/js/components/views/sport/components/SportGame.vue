<template>
  <div class="sportGame" v-if="category">
    <router-link tag="div" :to="'/' + sportLink + '/game/' + category.id + '/' + game.id" class="info">
      <div class="header">
        <div v-if="game.live" class="liveIcon"></div>
        <web-icon :icon="category.icon"></web-icon>
        {{ game.name }}
      </div>
      <div class="stage" @click.stop>
        <web-icon icon="stats"></web-icon>
        <span class="export" @click="toggleWidget(game)"><web-icon icon="export"></web-icon></span>
        {{ game.liveStatus.stage }} | {{ game.liveStatus.score }}
      </div>
      <div class="date">
        <web-icon icon="time"></web-icon>
        {{ new Date(game.liveStatus.createdAt).toLocaleString() }}
      </div>
      <div class="competitors">
        <div class="competitor">{{ game.competitors[0].name }}</div>
        <div class="competitor">{{ game.competitors[1].name }}</div>
      </div>
    </router-link>
    <div class="markets" v-if="suitableMarket">
      <div class="title">
        {{ $t(suitableRunner.translation.market.key) }}
      </div>
      <div class="runners">
        <bet-slip-button :category="category.id" :market="suitableMarket" :runner="runner" :game="game"
                         :key="runner.name"
                         :class="['market ' + (suitableMarket.open && game.open && runner.open ? '' : 'disabled'), runner.supported.status ? '' : 'unavailable']"
                         v-for="runner in suitableMarket.runners.slice(0, 3)">
          <div class="runner">
            <div class="name">
              {{
                runner.open && game.open && suitableMarket.open ? $t(runner.translation.runner.key) : '--'
              }}
            </div>
            <div class="price">{{ runner.open && game.open && suitableMarket.open ? runner.price : '--' }}</div>
          </div>
        </bet-slip-button>
        <div class="moreOdds" v-if="game.totalOddsMT > 0">+{{ game.totalOddsMT }}</div>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import Bus from "../../../../bus.js";

  export default {
    props: ['game'],
    data() {
      return {
        category: null,

        suitableMarket: null,
        suitableRunner: null
      }
    },
    computed: {
      ...mapGetters(['sportGames', 'phoenixShowUnavailableRunners'])
    },
    watch: {
      sportGames() {
        this.findCategory();
      },
      game() {
        this.findSuitableMarket();
      },
      phoenixShowUnavailableRunners() {
        this.findSuitableMarket();
      }
    },
    methods: {
      toggleWidget(game) {
        Bus.$emit('sportMovableWidget:toggle', game);
      },
      findCategory() {
        if (this.sportGames) this.category = this.sportGames.filter((e) => e.id === this.game.category.id)[0];
      },
      findSuitableMarket() {
        let market = null;
        let runner = null;

        for(let i = 0; i < this.game.markets.length; i++) {
          const m = this.game.markets[i];
          if(m.open && m.runners.length > 0 && (m.isAvailable || this.phoenixShowUnavailableRunners)) {
            let okay = false;

            m.runners.forEach(r => {
              if(r.supported.status || this.phoenixShowUnavailableRunners) {
                runner = r;
                okay = true;
              }
            });

            if(okay) {
              market = m;
              break;
            }
          }
        }

        if(market) {
          this.suitableMarket = market;
          this.suitableRunner = runner;
        }

        return market;
      }
    },
    mounted() {
      this.findSuitableMarket();
      this.findCategory();
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .sportGame {
    position: relative;

    @include themed() {
      background: t('body');
      display: flex;
      padding: 20px 30px;
      font-family: 'Open Sans', sans-serif;

      &:nth-child(even) {
        background: t('sidebar');

        .market {
          background: t('body') !important;

          &:hover {
            background: lighten(t('body'), 2.5%) !important;
          }
        }
      }

      .info {
        width: 50%;
        cursor: pointer;

        @media(max-width: 991px) {
          margin-bottom: 0;
        }

        .date, .stage {
          margin-bottom: 5px;
          font-size: .9em;
          opacity: .6;
          display: flex;
          align-items: center;
          margin-top: 6px;

          svg, i {
            margin-right: 5px;
          }

          .export {
            margin-left: 2px;
            opacity: .5;
            transition: opacity .3s ease;

            &:hover {
              opacity: 1;
            }

            @media(max-width: 991px) {
              display: none;
            }
          }
        }

        .stage {
          opacity: 1;
        }

        .header {
          font-weight: 600;
          font-size: 1.15em;
          display: flex;
          align-items: center;

          i, svg {
            margin-right: 10px;
          }

          .liveIcon {
            position: relative;
            width: 16px;
            height: 16px;
            margin-right: 15px;

            &:before {
              content: '';
              position: relative;
              display: block;
              width: 300%;
              height: 300%;
              box-sizing: border-box;
              margin-left: -100%;
              margin-top: -100%;
              border-radius: 45px;
              background-color: rgba(black, .5);
              animation: pulse-ring 1.25s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
            }

            &:after {
              content: '';
              position: absolute;
              left: 0;
              top: 0;
              display: block;
              width: 100%;
              height: 100%;
              background-color: t('secondary');
              border-radius: 15px;
              box-shadow: 0 0 8px rgba(0, 0, 0, .3);
              animation: pulse-dot 1.25s cubic-bezier(0.455, 0.03, 0.515, 0.955) -.4s infinite;
            }

            @keyframes pulse-ring {
              0% {
                transform: scale(.33);
              }
              80%, 100% {
                opacity: 0;
              }
            }

            @keyframes pulse-dot {
              0% {
                transform: scale(.8);
              }
              50% {
                transform: scale(1);
              }
              100% {
                transform: scale(.8);
              }
            }
          }

          @media(max-width: 991px) {
            font-size: .8em;

            .liveIcon {
              width: 12px;
              height: 12px;
              margin-top: -3px;
              margin-right: 10px;
            }

            i, svg {
              display: none;
            }
          }
        }

        .league {
          font-size: 0.9em;
          margin-top: 10px;
          opacity: .6;
        }

        .competitors {
          margin-top: 15px;

          @media(max-width: 991px) {
            display: none;
          }

          .competitor {
            font-size: .9em;
          }

          .competitor:last-child {
            margin-top: 10px;
          }
        }
      }

      .markets {
        width: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;

        .title {
          font-weight: 600;
          margin-bottom: 15px;

          @include media-breakpoint-down('md') {
            margin-top: 5px;
            font-size: .8em;
            margin-bottom: 5px;
          }
        }

        .runners {
          display: flex;

          .moreOdds {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            color: t('secondary');

              position: absolute;
              right: 35px;
              margin-top: 5px;
              top: 25px;
          }

          .market {
            min-width: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: t('sidebar');
            padding: 5px 15px;
            transition: background .3s ease, opacity .3s ease, color .3s ease;
            user-select: none;
            opacity: 1;
            cursor: pointer;

            &.unavailable {
              background: rgb(222, 70, 70) !important;

              .price {
                color: white !important;
              }
            }

            @media(max-width: 991px) {
              font-size: .7em;
              min-width: unset;
              flex: 1;
              text-align: left;
              padding: 3px 5px;

              .runner {
                display: flex;
                width: 100%;

                .name {
                  width: 40px;
                  margin-right: 0;
                  text-overflow: ellipsis;
                  overflow: hidden;
                  white-space: nowrap;
                }

                .price {
                  width: 20%;
                }
              }
            }

            &.active {
              background: t('secondary') !important;
              color: black;

              .price {
                color: black !important;
              }
            }

            &.disabled {
              opacity: 0.5;
              cursor: default;
            }

            &:not(.disabled):hover {
              background: lighten(t('sidebar'), 2.5%);
            }

            &:first-child {
              border-top-left-radius: 3px;
              border-bottom-left-radius: 3px;
            }

            &:last-child {
              border-top-right-radius: 3px;
              border-bottom-right-radius: 3px;
            }

            .runner {
              display: flex;

              .name {
                font-weight: 600;
                margin-right: 5px;
              }

              .price {
                font-size: .9em;
                margin-top: 2px;
                color: t('secondary');
              }
            }
          }
        }
      }
    }
  }

  @include media-breakpoint-down('md') {
    .sportGame {
      flex-direction: column;

      .info, .markets, .runners {
        width: calc(100vw - 84px) !important;
        overflow: hidden;
      }

      .info {
        margin-bottom: 10px;
      }
    }
  }
</style>
