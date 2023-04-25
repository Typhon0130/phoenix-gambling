<template>
  <div :class="'betSlip ' + (betSlip ? 'visible' : 'hidden')">
    <div class="betSlipHeader">
      <div :class="'tab ' + (tab === 'betSlip' ? 'active' : '')" @click="tab = 'betSlip'">{{
          $t('sport.bet_slip')
        }}
      </div>
      <div :class="'tab ' + (tab === 'myBets' ? 'active' : '')" @click="tab = 'myBets'" v-if="!isGuest">
        {{ $t('sport.my_bets') }}
      </div>
      <i class="fal fa-times" @click="$store.dispatch('toggleBetSlip', false)"></i>
    </div>
    <template v-if="tab === 'betSlip'">
      <div class="betSlipSubHeader">
        <div class="betSlipTabs">
          <div :class="'betSlipTab ' + (isSingle ? 'active' : '')" @click="isSingle = true">
            {{ $t('sport.single') }}
          </div>
          <div :class="'betSlipTab ' + (!isSingle ? 'active' : '')" @click="isSingle = false">
            {{ $t('sport.multi') }}
          </div>
        </div>
        <div class="betSlipControls">
          <div class="custom-control custom-checkbox mb-2">
            <label>
              <input type="checkbox" class="custom-control-input" v-model="noOddsChanges">
              <div class="custom-control-label" v-html="$t('sport.no_odds_changes')"></div>
            </label>
          </div>
          <i class="fal fa-times" @click="clearBetSlip()" v-if="bets.length > 0"></i>
        </div>
      </div>
      <div v-if="bets.length === 0" class="bets empty">
        <web-icon icon="sport"></web-icon>
        <div class="title">{{ $t('sport.empty_bet_slip_title') }}</div>
        <div class="subtitle">{{ $t('sport.empty_bet_slip_subtitle') }}</div>
      </div>

      <button class="btn btn-primary acceptAll" v-if="!isSingle && bets.filter(e => e.verifyOddsChange).length > 0" @click="acceptAllChanges">{{ $t('sport.accept_all_odds_changes') }}</button>

      <overlay-scrollbars ref="betSlipScrollbar"
                          :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }"
                          class="bets" v-if="bets.length > 0">
        <div
          :class="'bet ' + (bet.disabled || !bet.runner.open || !bet.game.open || !bet.market.open ? 'disabled' : '')"
          v-for="bet in bets">
          <div class="betHeader">
            <div class="game">{{ bet.game.name }}</div>
            <i class="fal fa-times" @click="removeBet(bet)"></i>
          </div>
          <div class="betContainer">
            <div class="oddsChange"
                 v-if="bet.game.open && bet.market.open && bet.runner.open && !bet.disabled && bet.verifyOddsChange">
              <div>{{ $t('sport.odds_change', {odds: bet.runner.price}) }}</div>
              <div>
                <button class="btn btn-sm btn-primary" @click="bet.verifyOddsChange = false">{{ $t('sport.accept') }}</button>
                <button class="btn btn-sm btn-primary" @click="removeBet(bet)">{{ $t('sport.decline') }}</button>
              </div>
            </div>

            <div class="controls">
              <div class="market">
                {{ $t(bet.runner.translation.market.key, bet.runner.translation.market.data) }}
              </div>
              <div class="runner">
                {{ $t(bet.runner.translation.runner.key, bet.runner.translation.runner.data) }}
              </div>
              <div class="control" v-if="isSingle && currencies">
                <input v-model="bet.value" type="number" min="0">
                <web-icon :icon="currencies[currency].icon" :style="{ color: currencies[currency].style }"></web-icon>
              </div>
            </div>
            <div class="info">
              <div class="payout">{{ bet.runner.price }}</div>
              <template v-if="isSingle && currencies">
                <div class="estPayout">{{ $t('sport.estimate_payout') }}</div>
                <div class="estPayoutValue">
                  <unit :to="currency" :value="bet.value * bet.runner.price"></unit>
                  <web-icon :icon="currencies[currency].icon" :style="{ color: currencies[currency].style }"></web-icon>
                </div>
              </template>
            </div>
          </div>
        </div>
      </overlay-scrollbars>
      <div class="betSlipManagement">
        <div class="multiBet" v-if="!isSingle && currencies">
          <input v-model="multiBet" type="number" min="0">
          <web-icon :icon="currencies[currency].icon" :style="{ color: currencies[currency].style }"></web-icon>
        </div>
        <div class="value">
          <div class="name">
            {{ $t(isSingle ? 'sport.total_bet' : 'sport.total_odds') }}
          </div>
          <div class="val">
            <unit v-if="isSingle" :to="currency" :value="total()"></unit>
            <span v-else>{{ total().toFixed(2) }}</span>
            <web-icon v-if="isSingle && currencies" :icon="currencies[currency].icon"
                      :style="{ color: currencies[currency].style }"></web-icon>
          </div>
        </div>
        <div class="value">
          <div class="name">
            {{ $t('sport.estimate_payout') }}
          </div>
          <div class="val" v-if="currencies">
            <unit :to="currency" :value="estPayout()"></unit>
            <web-icon :icon="currencies[currency].icon" :style="{ color: currencies[currency].style }"></web-icon>
          </div>
        </div>
        <div
          :class="'btn btn-primary btn-block ' + (hasUnverifiedOddsChanges() || betting || bets.length === 0 ? 'disabled' : '')"
          :disabled="hasUnverifiedOddsChanges() || betting || bets.length === 0"
          @click="isGuest ? openAuthModal('auth') : bet()">{{ $t(isGuest ? 'sport.register_to_bet' : 'sport.bet') }}
        </div>
      </div>
    </template>
    <template v-else-if="!isGuest">
      <div class="betSlipSubHeader noFooter">
        <div class="betSlipTabs">
          <div :class="'betSlipTab ' + (betsTab === 'ongoing' ? 'active' : '')" @click="betsTab = 'ongoing'">
            {{ $t('sport.ongoing') }}
          </div>
          <div :class="'betSlipTab ' + (betsTab === 'finished' ? 'active' : '')" @click="betsTab = 'finished'">
            {{ $t('sport.finished') }}
          </div>
        </div>
      </div>
      <div class="bets noFooter empty" v-if="userGames == null">
        <loader></loader>
      </div>
      <div v-else-if="userGames.length === 0" class="bets noFooter empty">
        <web-icon icon="scroll"></web-icon>
        <div class="title">{{ $t('sport.no_bets_title') }}</div>
      </div>
      <overlay-scrollbars ref="betSlipScrollbar"
                          :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }"
                          class="bets noFooter" v-if="userGames && Object.keys(userGames).length > 0">
        <div :class="key.startsWith('multi') ? 'multiBetGroup' : 'singleBet'" v-for="(value, key) in userGames">
          <div class="title" v-if="key.startsWith('multi')">{{ $t('sport.multi') }}</div>
          <div class="bet" v-for="bet in value">
            <div class="betHeader">
              <div class="game">{{ bet.game }}</div>
            </div>
            <div class="betContainer">
              <div class="controls">
                <div class="market">
                  {{ bet.market }}
                </div>
                <div class="runner">
                  {{ bet.runner }}
                </div>
                <unit :to="bet.currency" :value="bet.bet"></unit>
                <web-icon :icon="currencies[bet.currency].icon" v-if="currencies"
                          :style="{ color: currencies[bet.currency].style }"></web-icon>
              </div>
              <div class="info">
                <div class="payout">{{ bet.odds }}</div>
              </div>
            </div>
            <div class="gameOutcome" :class="bet.status" v-if="betsTab === 'finished'">
              {{ $t('sport.outcome.' + bet.status) }}
            </div>
          </div>
        </div>
      </overlay-scrollbars>
    </template>
  </div>
</template>

<script>
import {mapGetters} from 'vuex';
import Bus from '../../bus';
import AuthModal from "../modals/AuthModal.vue";

export default {
  data() {
    return {
      bets: [],
      isSingle: true,
      noOddsChanges: true,
      multiBet: 0,
      betting: false,
      tab: 'betSlip',
      betsTab: 'ongoing',

      userGames: null,
      userGamesPage: 0,

      money: {
        decimal: '.',
        thousands: '',
        prefix: '',
        suffix: '',
        precision: 8,
        masked: false
      }
    }
  },
  created() {
    Bus.$on('betSlip:add', (data) => {
      for (let i = 0; i < this.bets.length; i++) {
        let bet = this.bets[i];
        if (bet.game.id === data.game.id && bet.market.name === data.market.name && bet.runner.name === data.runner.name) {
          this.removeBet(bet);
          return;
        }
      }

      data.value = 0;
      this.bets.push(data);

      setTimeout(() => this.$refs.betSlipScrollbar._osInstace.scroll({y: '100%'}), 50);
    });

    Bus.$retrieve('betSlip:includes', (data) => {
      for (let i = 0; i < this.bets.length; i++) {
        let bet = this.bets[i];
        if (bet.game.id === data.game.id && bet.market.name === data.market.name && bet.runner.name === data.runner.name) {
          return true;
        }
      }

      return false;
    });

    Bus.$retrieve('betSlip:size', () => {
      return this.bets.length;
    });

    setInterval(() => {
      if (this.isCasino) return;

      this.bets.forEach((bet) => {
        axios.post('/api/sport/game', {id: bet.game.id}).then(({data}) => {
          let disabled = true;

          data.markets.forEach((market) => {
            market.runners.forEach((runner) => {
              if (bet.market.name === market.name && bet.runner.name === runner.name) {
                if (this.noOddsChanges && bet.runner.price !== runner.price)
                  bet.verifyOddsChange = true;

                bet.game = data;
                bet.market = market;
                bet.runner = runner;

                disabled = false;
              }
            });
          });

          if (disabled) bet.disabled = true;
        });
      });
    }, 1500);
  },
  methods: {
    acceptAllChanges() {
      this.bets.forEach(bet => bet.verifyOddsChange = false);
    },
    openAuthModal(type) {
      AuthModal.methods.open(type);
    },
    loadNextBetsPage() {
      axios.get(`/api/user/sportGames/${this.user.user._id}/${this.userGamesPage}/${this.betsTab}`).then(({data}) => {
        if (!this.userGames) this.userGames = [];

        this.gamesPage += 1;
        this.userGames = data;
      });
    },
    bet() {
      if (this.hasUnverifiedOddsChanges() || this.betting) return;
      this.betting = true;

      const balance = this.currencies[this.currency].balance.real;

      if ((this.isSingle && balance < this.total()) || (!this.isSingle && balance < this.multiBet)) {
        this.$toast.error(this.$i18n.t('sport.not_enough_balance'));
        this.betting = false;
        return;
      }

      const bets = [];
      this.bets.forEach((bet) => {
        bets.push({
          value: bet.value,
          game: {
            id: bet.game.id
          },
          runner: bet.runner,
          market: bet.market,
          category: bet.category
        });
      });

      axios.post('/api/sport/bet', {
        type: this.isSingle ? 'single' : 'multi',
        bets: bets,
        multiBetValue: this.multiBet
      }).then(({data}) => {
        this.bets.forEach(bet => {
          Bus.$emit('betSlip:remove:' + bet.hash);
        });

        this.betting = false;
        this.bets = [];

        this.$toast.success(this.$i18n.t('sport.created'));
      }).catch((e) => {
        if (e.response.data.code === 1) this.$toast.error('This game is finished');
        else if (e.response.data.code === 3) this.$toast.error('Insufficient balance');
        else if (e.response.data.code === 8) this.$toast.error('You can\'t place multibet on multiple options on the same match.');
        else if (e.response.data.code === 9) this.$toast.error('Max. bet amount per option: $100');
        else this.$toast.error(this.$i18n.t('sport.error'));

        this.betting = false;
        console.log(e);
      });
    },
    clearBetSlip() {
      this.bets = [];
      Bus.$emit('betSlip:clear');
    },
    hasUnverifiedOddsChanges() {
      let has = false;
      this.bets.forEach((bet) => {
        if (!bet.disabled && bet.runner.open && bet.game.open && bet.market.open && bet.verifyOddsChange) has = true;

        if ((this.isSingle && bet.value === 0) || (!this.isSingle && this.multiBet === 0)) has = true;
      });
      return has;
    },
    removeBet(bet) {
      Bus.$emit('betSlip:remove:' + bet.hash);
      this.bets = this.bets.filter((v) => !(bet.market.name === v.market.name && bet.game.id === v.game.id && bet.runner.name === v.runner.name));
    },
    total() {
      if(this.isSingle) {
        let total = 0;
        this.bets.forEach((bet) => {
          total += parseFloat(bet.value);
        });
        return total;
      } else {
        let total = 0;
        this.bets.forEach((bet) => {
          if(total === 0) total = parseFloat(bet.runner.price);
          else total *= parseFloat(bet.runner.price);
        });
        return total;
      }
    },
    estPayout() {
      if (!this.isSingle) return this.multiBet * this.total();
      let total = 0;
      this.bets.forEach((bet) => {
        total += parseFloat(bet.value) * bet.runner.price;
      });
      return total;
    }
  },
  computed: {
    ...mapGetters(['betSlip', 'isGuest', 'currencies', 'currency', 'user'])
  },
  watch: {
    tab() {
      if (this.tab !== 'betSlip' && this.userGames === null) this.loadNextBetsPage();
    },
    betsTab() {
      this.userGames = null;
      this.userGamesPage = 0;
      this.loadNextBetsPage();
    }
  }
}
</script>

<style lang="scss">
@import "resources/sass/variables";

$management-height: 160px;

.betSlip.hidden {
  opacity: 0;
  pointer-events: none;
}

.betSlip {
  z-index: 38500;
  opacity: 1;
  transition: opacity 0.3s ease;
  position: fixed;
  height: 100%;
  width: $chat-width;
  top: 0;
  right: 0;

  .custom-control label {
    display: flex;
    white-space: nowrap;

    div {
      margin-left: 5px;
    }
  }

  @include themed() {
    background: t('sidebar');
    border-left: 2px solid t('border');

    @media(max-width: 991px) {
      border: none;
    }

    .betSlipHeader {
      background: t('header');
      height: 73px;
      padding: 15px 20px;
      display: flex;
      align-items: center;
      font-weight: 600;

      @media(max-width: 991px) {
        height: 40px;
        padding: 5px 10px;
      }

      i {
        @media(max-width: 991px) {
          font-size: 1.5em;
        }
      }

      .tab {
        margin-right: 15px;
        transition: opacity 0.3s ease;
        opacity: 1;
        cursor: pointer;

        &:not(.active) {
          font-weight: 100;
          opacity: 0.6;
        }

        &:last-child {
          margin-right: 0;
        }
      }

      i {
        margin-left: auto;
        cursor: pointer;
      }
    }

    .betSlipSubHeader {
      height: 100px;

      @media(max-width: 991px) {
        padding: 7px;
        font-size: 0.9em;
        height: unset;
      }

      &.noFooter {
        height: 65px;
      }

      border-bottom: 2px solid t('border');
      padding: 14px 18px 15px 15px;

      .betSlipTabs {
        display: flex;

        .betSlipTab {
          margin-right: 5px;
          position: relative;
          font-weight: 600;
          cursor: pointer;
          padding: 5px 10px;
          background: transparent;
          transition: background .3s ease;
          border-radius: 3px;
          box-shadow: 0 0 0 1px t('body');

          &.active {
            background: t('body');
            box-shadow: none;
          }

          &:last-child {
            margin-right: 0;
          }
        }
      }

      .betSlipControls {
        display: flex;
        margin-top: 14px;

        @media(max-width: 991px) {
          margin-top: 7px;
          margin-bottom: -8px;
        }

        i {
          cursor: pointer;
          margin-left: auto;
          margin-top: 5px;
        }
      }
    }

    .acceptAll {
      position: absolute;
      z-index: 5;
      left: 50%;
      transform: translateX(-50%);
      bottom: 200px;
    }

    .bets {
      height: calc(100% - #{$management-height} - 73px - 102px);

      .singleBet {
        margin-top: 15px;
      }

      .multiBetGroup {
        padding: 10px;
        background: t('body');
        margin-top: 15px;

        .title {
          text-align: center;
          margin-bottom: 10px;
        }

        .bet {
          margin-bottom: 0;
        }
      }

      &.noFooter {
        height: calc(100% - 73px - 67px);
      }

      padding: 15px;
      position: relative;

      .bet {
        border-radius: 3px;
        margin-bottom: 10px;
        background: lighten(t('sidebar'), 3%);

        &.disabled {
          opacity: 0.6;

          .betContainer {
            pointer-events: none;
          }
        }

        .betHeader {
          background: lighten(t('sidebar'), 6%);
          font-size: 0.9em;
          padding: 10px;
          display: flex;
          align-items: center;

          .game {
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
            width: 90%;
          }

          i {
            margin-left: auto;
            margin-right: 3px;
            cursor: pointer;
          }
        }

        .gameOutcome {
          padding: 10px;
          border-top: 1px solid rgba(white, .25);
          font-weight: 600;
          font-size: 0.9em;

          &.refund, &.win {
            color: #28c228;
          }

          &.lose {
            color: #ef4b4b;
          }
        }

        .betContainer {
          padding: 10px;
          display: flex;
          position: relative;

          .oddsChange {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(lighten(t('sidebar'), 3%), .8);
            backdrop-filter: blur(5px);
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;

            div:first-child {
              margin-bottom: 8px;
            }

            div:last-child {
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 0.9em;

              .btn {
                font-size: 1em;
              }

              .btn:first-child {
                margin-right: 5px;
              }
            }
          }

          .controls {
            width: 50%;

            .market, .runner {
              width: 90%;
              text-overflow: ellipsis;
              overflow: hidden;
              white-space: nowrap;
              font-size: 0.9em;
            }

            .runner {
              font-weight: 600;
              font-size: 0.95em;
              margin-top: 2px;
            }

            .control {
              margin-top: 10px;
              position: relative;

              input {
                font-size: 0.9em;
                padding: 5px 33px 5px 10px;
              }

              svg, i {
                position: absolute;
                top: 9px;
                right: 10px;
              }
            }
          }

          .info {
            width: 50%;
            text-align: right;
            display: flex;
            margin-top: auto;
            flex-direction: column;
            font-size: 0.9em;

            .payout {
              font-weight: 600;
              font-size: 1.1em;
            }

            .estPayout, .estPayoutValue {
              opacity: 0.8;
              font-size: 0.9em;
            }

            .estPayoutValue {
              white-space: nowrap;
              text-overflow: ellipsis;
              overflow: hidden;
              width: 90%;
              margin-left: auto;
            }
          }
        }

        &:last-child {
          margin-bottom: 0;
        }
      }

      &.empty {
        display: flex;
        align-items: center;
        flex-direction: column;
        justify-content: center;

        svg, i {
          font-size: 5em;
          margin-bottom: 15px;
        }

        .subtitle {
          opacity: 0.7;
          font-size: 0.95em;
          margin-top: 5px;
          text-align: center;
        }
      }
    }

    .betSlipManagement {
      height: $management-height;
      padding: 15px;
      display: flex;
      flex-direction: column;
      position: relative;
      background: rgba(lighten(t('border'), 2%), .8);
      margin-top: auto;

      .multiBet {
        margin-bottom: 5px;
        position: relative;

        input {
          font-size: 0.9em;
          padding: 5px 33px 5px 10px;
        }

        svg, i {
          position: absolute;
          top: 9px;
          right: 10px;
        }
      }

      .value {
        display: flex;
        margin-bottom: 5px;
        font-size: 0.9em;

        .name {
          opacity: 0.8;
        }

        .val {
          margin-left: auto;
        }
      }

      .btn {
        margin-top: auto;
      }
    }
  }
}

@media(max-width: 991px) {
  .betSlip {
    left: 0;
    top: $header-height;
    width: 100vw !important;
    height: calc(100% - 55px - #{$header-height});

    .bets {
      height: calc(100% - 294px) !important;
    }
  }
}
</style>
