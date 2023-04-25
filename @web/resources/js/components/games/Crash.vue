<template>
  <div class="crash-game-container">
    <custom-history></custom-history>
    <div class="crash-content">
      <div class="layer background">
        <div class="bg"></div>
        <div class="bg"></div>
      </div>
      <div class="layer landscape">
        <div class="bg"></div>
        <div class="bg"></div>
        <div class="bg"></div>
        <div class="bg"></div>
      </div>
      <div class="layer rocks">
        <div class="rock"></div>
        <div class="rock"></div>
      </div>
      <div class="layer ground">
        <div class="bg"></div>
        <div class="bg"></div>
        <div class="bg"></div>
      </div>
      <div class="dino">
        <div class="anim-wrapper">
          <div class="dino-anim"></div>
        </div>
        <p class="current-num">0.00x</p>
      </div>
      <div class="crash-anim-container">
        <div class="crash-anim"></div>
      </div>
    </div>
  </div>
</template>

<script>
import Bus from '../../bus.js';
import {mapGetters} from 'vuex';

export default {
  data() {
    return {
      debug: false,

      //cashoutTexts: [],
      crashed: false,
      placedBetThisRound: false,

      betValue: null,

      startTimestamp: 0,
      currentMultiplier: 1,
      autoCashout: 2,

      autoBetTake: null,

      betNextRoundMode: false,
      nextRoundBetData: null,

      hex: {
        0: ['#ffc000', '#997300'],
        1: ['#ffa808', '#a16800'],
        2: ['#ffa808', '#a95b00'],
        3: ['#ff9010', '#a95b00'],
        4: ['#ff7818', '#914209'],
        5: ['#ff6020', '#b93500'],
        6: ['#ff4827', '#c01d00'],
        7: ['#ff302f', '#c80100'],
        8: ['#ff1837', '#91071c'],
        9: ['#ff003f', '#990026']
      }
    }
  },
  computed: {
    ...mapGetters(['theme', 'currency'])
  },
  methods: {
    extendedAutoBetHandle(take) {
      this.autoBetTake = take;
    },
    multiplayerEvent(event, data) {
      let color = this.hex[0];
      if (parseFloat(this.currentMultiplier) > 1) color = this.hex[1];
      if (parseFloat(this.currentMultiplier) > 2) color = this.hex[2];
      if (parseFloat(this.currentMultiplier) > 3) color = this.hex[3];
      if (parseFloat(this.currentMultiplier) > 4) color = this.hex[4];
      if (parseFloat(this.currentMultiplier) > 5) color = this.hex[5];
      if (parseFloat(this.currentMultiplier) > 7) color = this.hex[6];
      if (parseFloat(this.currentMultiplier) > 10) color = this.hex[7];
      if (parseFloat(this.currentMultiplier) > 100) color = this.hex[8];
      if (parseFloat(this.currentMultiplier) > 250) color = this.hex[9];

      switch (event) {
        case 'MultiplayerBettingStateChange':
          if (!this.placedBetThisRound && this.gameInstance.bettingType === 'manual' && !this.nextRoundBetData) this.setNextRoundBetMode(data.state);
          break;
        case 'MultiplayerGameBet':
          Bus.$emit('sidebar:multiplayer:add', {user: data.user, game: data.game});
          break;
        case 'MultiplayerBetCancellation':
          /*this.cashoutTexts.push({
              name: data.user.name,
              alpha: 1,
              multiplier: parseFloat(this.currentMultiplier).toFixed(2),
              x: this.linePosX,
              y: this.linePosY
          });*/
          break;
        case 'MultiplayerGameFinished':
          this.finishExtended(false);
          this.crashed = true;
          this.setAnimated(['.crash-anim-container', '.crash-anim'], true);
          this.setAnimated(['.dino'], false);

          Bus.$emit('game:customHistory:add', {
            text: `<div class="color" style="background: ${color[0]};"></div> ${parseFloat(data.data.multiplier).toFixed(2)}x`,
            style: '',
            seed: {
              serverSeed: data.server_seed,
              clientSeed: data.client_seed,
              nonce: data.nonce,
              placement: 'bottom'
            }
          });

          if (this.gameInstance.bettingType === 'auto' && this.gameInstance.game.autoBetSettings.state) this.gameInstance.game.autoBetSettings.next();
          break;
        case 'MultiplayerTimerStart':
          Bus.$emit('sidebar:multiplayer:clear');

          this.placedBetThisRound = false;
          this.setRoundTimer(6, () => {
            this.startTimestamp = +new Date() / 1000;
            this.startGame();
          });

          if (this.nextRoundBetData) {
            this.updateGameInstance((i) => {
              i.bet = this.nextRoundBetData.value;
              this.$store.dispatch('setCurrency', this.nextRoundBetData.currency);
              this.nextRoundBetData = null;
            });
            $('.play-button').click();
          }

          this.setNextRoundBetMode(false);
          break;
      }
    },
    updateMultiplier() {
      if (this.gameInstance.bettingType === 'manual' && this.placedBetThisRound && !this.crashed)
        $('.play-button').html(this.$i18n.t('general.take', {value: (this.betValue * parseFloat(this.currentMultiplier)).toFixed(this.currency.startsWith('local_') ? 2 : 8)})).removeClass('disabled');

      if (document.querySelector('.current-num')) document.querySelector('.current-num').innerHTML = `x${parseFloat(this.currentMultiplier).toFixed(2)}`;
    },
    startGame() {
      if (this.placedBetThisRound) {
        $('.play-button').removeClass('disabled');
        this.updateMultiplier();
      } else setTimeout(() => {
        if (!this.crashed) this.setNextRoundBetMode(true)
      }, 100);

      this.nextRoundBetData = null;

      const nextMultiplier = () => {
        let timeInMilliseconds = 0, simulation = 1, suS = 0, diffS = (+new Date() / 1000) - this.startTimestamp;

        while (timeInMilliseconds / 1000 < diffS) {
          simulation += 0.05 / 15 + suS;
          timeInMilliseconds += 2000 / 15 / 3;
          if (simulation >= 5.5) {
            suS += 0.05 / 15;
            timeInMilliseconds += 4000 / 15 / 3;
          }
        }

        //console.log(`sim ${simulation}`, `tMS ${timeInMilliseconds}`, `diffS ${diffS}`, `suS ${suS}`);
        this.currentMultiplier = simulation.toFixed(2);
        if (this.currentMultiplier > 1000) {
          this.startTimestamp = +new Date();
          this.currentMultiplier = 1;
        }

        this.updateMultiplier();

        if (this.placedBetThisRound) {
          if (parseFloat(this.currentMultiplier) >= this.autoCashout && parseFloat(this.currentMultiplier) >= 1.1 && !this.crashed) {
            if (this.gameInstance.bettingType === 'manual') $('.play-button').click();
            else this.autoBetTake();
          }
        }
      }

      let interval = setInterval(function () {
        if (this.crashed) {
          clearInterval(interval);
          return;
        }

        nextMultiplier();
      }, 66);
    },
    setRoundTimer(seconds, callback) {
      seconds *= 1000;

      $('.history-crash').hide()
        .css({'width': '100%'})
        .fadeIn('fast')
        .animate({'width': '0%'}, {duration: seconds, easing: 'linear'});

      setTimeout(() => {
        this.crashed = false;
        this.currentMultiplier = 1.0;
        this.setAnimated(['.crash-anim-container', '.crash-anim'], false);
        this.setAnimated(['.dino'], true);
        this.setAnimated(['.layer.background', '.layer.rocks', '.layer.landscape', '.layer.ground'], true);
        callback();
      }, seconds);

      this.crashed = true;
      this.setAnimated(['.crash-anim-container', '.crash-anim'], true);
      this.setAnimated(['.dino'], false);
      this.setAnimated(['.layer.background', '.layer.rocks', '.layer.landscape', '.layer.ground'], false);
    },
    setAnimated(selectors, flag) {
      document.querySelectorAll(selectors).forEach(e => e.classList.toggle('animated', flag));
    },
    reset() {
      this.setAnimated(['.crash-anim-container', '.crash-anim'], false);
      this.setAnimated(['.dino',], true);
      this.setAnimated(['.layer.background', '.layer.rocks', '.layer.landscape', '.layer.ground'], true);
      document.querySelector('.current-num').innerHTML = `x1.00`;
    },
    gameDataRetrieved(data) {
      Bus.$emit('crash:history:addEntry', {html: ''});

      $('.play-button').on('click', this.onPlayButtonClick);

      this.reset();

      this.startTimestamp = data.timestamp;
      this.startGame();

      _.forEach(data.players, (player) => Bus.$emit('sidebar:multiplayer:add', {user: player.user, game: player.game}));

      setTimeout(() => this.setNextRoundBetMode(true), 100);

      _.forEach(data.history, (m) => {
        let color = this.hex[0];
        if (m.multiplier > 250) color = this.hex[9];
        else if (m.multiplier > 100) color = this.hex[8];
        else if (m.multiplier > 10) color = this.hex[7];
        else if (m.multiplier > 7) color = this.hex[6];
        else if (m.multiplier > 5) color = this.hex[5];
        else if (m.multiplier > 4) color = this.hex[4];
        else if (m.multiplier > 3) color = this.hex[3];
        else if (m.multiplier > 2) color = this.hex[2];
        else if (m.multiplier > 1) color = this.hex[1];

        Bus.$emit('game:customHistory:add', {
          text: `<div class="color" style="background: ${color[0]};"></div> ${parseFloat(m.multiplier).toFixed(2)}x`,
          style: '',
          seed: {
            serverSeed: m.server_seed,
            clientSeed: m.client_seed,
            nonce: m.nonce,
            placement: 'bottom'
          }
        });
      });
    },
    getSidebarComponents() {
      return [
        {name: 'label', data: {label: this.$i18n.t('general.wager')}},
        {name: 'wager-classic'},
        {name: 'label', data: {label: this.$i18n.t('general.autoStop')}},
        {
          name: 'input', data: {
            value: '2.00', callback: (v) => {
              v = parseFloat(v);
              if (!isNaN(v) && v >= 1.1 && v <= 1000) {
                this.autoCashout = v;
                return true;
              }
              return false;
            }
          }
        },
        {name: 'auto-bets'},
        {name: 'multiplayer-table'},
        {name: 'play'},
        {name: 'footer', data: {buttons: ['help', 'sound', 'stats']}},
        {name: 'history'}
      ];
    },
    getClientData() {
      return {};
    },
    callback(response) {
      if (!response || this.placedBetThisRound) {
        this.placedBetThisRound = false;
        if (this.gameInstance.bettingType === 'manual') this.setNextRoundBetMode(true);
        return;
      }

      this.placedBetThisRound = true;
      this.betValue = response.wager;
      if (this.gameInstance.bettingType === 'manual') $('.play-button').addClass('disabled');
    },
    setNextRoundBetMode(type) {
      if (this.gameInstance.bettingType !== 'manual') return;

      this.betNextRoundMode = type;

      if (type) $('.play-button').html(this.$i18n.t('general.bet_next_round'));
      else $('.play-button').html(this.$i18n.t('general.play'));
    },
    onPlayButtonClick() {
      if (this.placedBetThisRound || $('.play-button').hasClass('disabled')) return;
      if (this.betNextRoundMode) {
        if (this.nextRoundBetData) {
          this.setNextRoundBetMode(true);
          this.nextRoundBetData = null;
          return;
        }

        this.nextRoundBetData = {
          currency: this.currency,
          value: this.gameInstance.bet
        };

        $('.play-button').html(this.$i18n.t('general.cancel'));
      }
    }
  }
}
</script>

<style lang="scss">
@import "../../../sass/variables";

:root {
  --landscapeFrameWidth: 566px;
  --landscapeFrameHeight: 120px;
  --groundFrameWidth: 821px;
  --groundFrameHeight: 65px;
  --dinoAnimFrameWidth: 360px;
  --dinoAnimFrameHeight: 240px;
  --crashAnimFrameWidth: 1000px;
  --crashAnimFrameHeight: 563px;
  --resultAnimFontSize: 30px;
}

@media (min-width: 320px) and (max-width: 480px) {
  :root {
    --landscapeFrameWidth: 283px;
    --landscapeFrameHeight: 60px;
    --groundFrameWidth: 404px;
    --groundFrameHeight: 32px;
    --dinoAnimFrameWidth: 180px;
    --dinoAnimFrameHeight: 120px;
    --crashAnimFrameWidth: 400px;
    --crashAnimFrameHeight: 225px;
    --resultAnimFontSize: 16px;
  }
}

.game-crash {
  .customHistory {
    top: 20px;
    transform: unset;
    flex-direction: unset;
    height: 45px;
    width: 70%;
    left: 15%;
    align-items: center;
    background: transparent;

    .element {
      padding: 0.955em 10px;

      @include themed() {
        color: t('text');
      }

      .color {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        margin-right: 6px;
        margin-top: 3px;
      }
    }
  }

  .crash-game-container {
    width: 100%;
    height: 100%;
  }

  .crash-content {
    $dinoAnimFrameRate: 40ms;
    $dinoAnimFrameCount: 17;

    $crashAnimFrameRate: 30ms;

    $crashAnimFrameCount: 58;
    $crashAnimLoopFrameCount: 9;

    overflow: hidden;

    position: relative;
    width: 100%;
    height: 100%;
    z-index: -1;

    .crash-anim-container {
      position: absolute;
      // left: 0;
      // bottom: calc(100% - var(--crashAnimFrameHeight));
      left: calc(50% - var(--crashAnimFrameWidth) / 2);
      bottom: 0;

      width: var(--crashAnimFrameWidth);
      height: var(--crashAnimFrameHeight);
      overflow: hidden;
      z-index: 6;
      // transition: all 0.3s ease-in;
      // animation-delay: 0.2s;

      &.animated {
        // left: calc(50% - var(--crashAnimFrameWidth) / 2);
        // bottom: 0;
      }

      .crash-anim {
        // margin-left: calc(var(--crashAnimFrameWidth) * -1 / 2);
        width: calc(var(--crashAnimFrameWidth) * #{$crashAnimFrameCount});
        height: var(--crashAnimFrameHeight);
        background-image: url("/img/crash/crash-anim.png");
        background-size: 100% 100%;
        transform: translateX(var(--crashAnimFrameWidth));

        &.animated {
          $noloopCount: $crashAnimFrameCount - $crashAnimLoopFrameCount;
          $noloopDuration: $noloopCount * $crashAnimFrameRate;
          $loopDuration: $crashAnimLoopFrameCount * $crashAnimFrameRate;

          transform: translateX(0);
          animation: crashAnim $noloopDuration steps($noloopCount) forwards,
          crashAnimLoop $loopDuration steps($crashAnimLoopFrameCount) $noloopDuration infinite;

          @keyframes crashAnim {
            to {
              transform: translateX(calc(var(--crashAnimFrameWidth) * #{$noloopCount} * -1));
            }
          }

          @keyframes crashAnimLoop {
            from {
              transform: translateX(calc(var(--crashAnimFrameWidth) * #{$noloopCount} * -1));
            }

            to {
              transform: translateX(-100%);
            }
          }
        }
      }
    }

    .dino {
      position: absolute;
      left: 50%;
      margin-left: -220px;
      bottom: 10px;
      width: var(--dinoAnimFrameWidth);
      height: var(--dinoAnimFrameHeight);
      z-index: 5;
      display: none;

      @media(max-width: 480px) {
        margin-left: 0;
        transform: translateX(-50%);
      }

      &.animated {
        display: unset;
      }

      .anim-wrapper {
        width: 100%;
        height: 100%;
        overflow: hidden;

        .dino-anim {
          width: calc(var(--dinoAnimFrameWidth) * #{$dinoAnimFrameCount});
          height: 100%;
          background-size: 100% 100%;
          background-image: url("/img/crash/dino-anim.png");
          animation: dinoAnim ($dinoAnimFrameCount * $dinoAnimFrameRate) steps($dinoAnimFrameCount);
          animation-iteration-count: infinite;
          animation-fill-mode: forwards;

          @keyframes dinoAnim {
            to {
              transform: translateX(-100%);
            }
          }
        }
      }

      p.current-num {
        position: absolute;
        left: 0;
        top: -80px;
        width: 100%;
        text-align: center;
        padding-left: 20px;
        box-sizing: border-box;
        color: #fff;
        font-size: 55px;
        font-family: 'Roboto', sans-serif;
        font-weight: 700;
        transform: translateY(0) rotate(1deg);
        animation: numAnim ($dinoAnimFrameCount * $dinoAnimFrameRate) linear;
        animation-delay: $dinoAnimFrameRate;
        animation-iteration-count: infinite;

        @keyframes numAnim {
          20% {
            transform: translateY(17px) rotate(2deg);
          }

          25% {
            transform: translateY(17px) rotate(2deg);
          }

          47% {
            transform: translateY(-5px) rotate(1deg);
          }

          74% {
            transform: translateY(17px) rotate(2deg);
          }

          100% {
            transform: translateY(0) rotate(1deg);
          }
        }
      }
    }

    .layer {
      position: absolute;
      left: 0;
      display: flex;
      align-items: center;

      &.background {
        width: 100%;
        height: 100%;

        &.animated {
          animation: bgAnim 15s linear;
          animation-iteration-count: infinite;

          @keyframes bgAnim {
            to {
              transform: translateX(-100%);
            }
          }
        }

        .bg {
          width: 100%;
          height: 100%;
          background-image: url('/img/crash/background.jpg');
          background-size: 100% 100%;
          flex-shrink: 0;

          & + .bg {
            margin-left: -1px;
          }
        }
      }

      &.landscape {
        bottom: 57px;
        width: calc(var(--landscapeFrameWidth) * 4);
        height: var(--landscapeFrameHeight);
        opacity: 0.7;
        z-index: 2;

        .bg {
          width: var(--landscapeFrameWidth);
          height: 100%;
          background-image: url("/img/crash/landscape.png");
          background-size: 100% 100%;
          flex-shrink: 0;

          & + .bg {
            margin-left: -1px;
          }
        }

        &.animated {
          animation: landscapeAnim 3s linear;
          animation-iteration-count: infinite;

          @keyframes landscapeAnim {
            to {
              transform: translateX(calc(var(--landscapeFrameWidth) * -1));
            }
          }
        }
      }

      &.rocks {
        bottom: 55px;
        width: calc(var(--groundFrameWidth) * 4);
        z-index: 3;

        .rock {
          background-size: 100% 100%;
          background-image: url("/img/crash/rock.png");
          width: 71px;
          height: 100px;

          &:first-child {
            margin-left: calc(var(--groundFrameWidth) * 2);
          }

          &:last-child {
            margin-left: calc(var(--groundFrameWidth) * 2 - 142px);
          }
        }

        &.animated {
          animation: rocksAnim 3.8s linear;
          animation-fill-mode: forwards;
          animation-iteration-count: infinite;
          filter: blur(1px);

          @keyframes rocksAnim {
            to {
              transform: translateX(-100%);
            }
          }
        }
      }

      &.ground {
        bottom: 0;
        width: calc(var(--groundFrameWidth) * 3);
        height: var(--groundFrameHeight);
        z-index: 4;

        .bg {
          width: var(--groundFrameWidth);
          height: 100%;
          background-image: url("/img/crash/ground.png");
          background-size: 100% 100%;
          flex-shrink: 0;

          & + .bg {
            margin-left: -1px;
          }
        }

        &.animated {
          // animation: groundAnim 1.3s linear;
          animation: groundAnim 1s linear;
          animation-fill-mode: forwards;
          animation-iteration-count: infinite;

          @keyframes groundAnim {
            to {
              transform: translateX(calc(var(--groundFrameWidth) * -1));
            }
          }
        }
      }
    }
  }

  .game-content-crash {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;

    @include themed() {
      .crashMultiplayerTable {
        width: 100%;
        border-top: 5px solid t('secondary');
        margin-top: 5px;

        display: flex;
        flex-direction: column;

        .user {
          display: flex;
          flex-direction: row;
          align-items: center;
          padding: 15px;
          background: t('sidebar');
          transition: background 0.3s ease;
          cursor: pointer;

          &:hover {
            background: darken(t('sidebar'), 2%);
          }

          &:first-child {
            margin-top: 5px;
          }

          &:nth-child(even) {
            background: t('input') !important;

            &:hover {
              background: darken(t('input'), 2%) !important;
            }
          }

          .avatar {
            img {
              border-radius: 50%;
              width: 32px;
              height: 32px;
            }
          }

          .name {
            margin-left: 5px;
          }

          .bet {
            margin-left: auto;
          }

          .crash {
            margin-left: 5px;
          }
        }
      }
    }

    .game-history {
      height: 10px;
      padding: 0 !important;
      position: relative;
      top: 0 !important;
      bottom: unset !important;

      .history-crash {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 0;
        will-change: width;

        span {
          position: absolute;
          right: 5px;
          top: 50%;
          transform: translateY(-50%);
          color: white;
          font-size: 0.65em;
        }

        @include themed() {
          background: t('secondary');
        }
      }
    }
  }
}
</style>
