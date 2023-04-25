<template>
  <div class="container-fluid">
    <div :class="`game-container game-${$route.params.id}`">
      <div class="row" v-if="!game.id.startsWith('external:')">
        <div class="col">
          <game-sidebar></game-sidebar>
        </div>
        <div class="col">
          <div :class="`game-content game-content-${$route.params.id} game-type-local`">
            <component ref="gameComponent"
             :is="!game.id.startsWith('external') ? $route.params.id : 'third-party'"></component>
          </div>
        </div>
      </div>
      <div v-else :class="`game-content game-content-${$route.params.id} game-type-third-party`">
        <third-party ref="gameComponent"></third-party>
      </div>
    </div>
    <div class="game-footer"></div>
  </div>
</template>

<script>
  import ThirdParty from "../games/ThirdParty.vue";
  import Keno from "../games/Keno.vue";
  import Limbo from "../games/Limbo.vue";
  import Mines from "../games/Mines.vue";
  import Roulette from "../games/Roulette.vue";
  import Coinflip from "../games/Coinflip.vue";
  import Diamonds from "../games/Diamonds.vue";
  import Hilo from "../games/Hilo.vue";
  import Stairs from "../games/Stairs.vue";
  import Slide from "../games/Slide.vue";
  import Tower from "../games/Tower.vue";
  import Baccarat from "../games/Baccarat.vue";
  import Videopoker from "../games/Videopoker.vue";
  import Dice from "../games/Dice.vue";
  import Wheel from "../games/Wheel.vue";
  import Plinko from "../games/Plinko.vue";
  import Blackjack from "../games/Blackjack.vue";

  import {mapGetters} from 'vuex';

  export default {
    data() {
      return {
        game: null
      }
    },
    computed: {
      ...mapGetters(['games', 'gameInstance', 'user'])
    },
    created() {
      _.forEach(this.games, (e) => {
        if (e.id === this.$route.params.id) this.game = e;
      });

      if (!this.game) this.$router.push('/');
    },
    mounted() {
      this.createGameInstance(this.$route.params.id, this.$refs.gameComponent);
      window.$gameRef = this.$refs.gameComponent;

      this.updateGameInstance((i) => {
        i.bettingType = 'manual';
        i.playTimeout = false;
      });
    },
    components: {
      ThirdParty,
      Keno,
      Limbo,
      Mines,
      Roulette,
      Coinflip,
      Diamonds,
      Hilo,
      Stairs,
      Slide,
      Tower,
      Baccarat,
      Videopoker,
      Dice,
      Wheel,
      Plinko,
      Blackjack
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .game-padding {
    padding: 0 50px;
  }

  .tpBackdrop {
    height: 250px;
    position: relative;
    pointer-events: none;

    .game-name-big {
      position: absolute;
      font-size: 3em;
      top: 50%;
      transform: translateY(-50%);
      left: 4vmax;
    }

    .backdropImage {
      position: absolute;
      height: 400%;
      z-index: -1;
      margin-top: -110px;
      margin-left: -65px;
      width: calc(100% + 130px);
      filter: blur(33px);
      background-size: 100% 100%;

      &:after {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        z-index: 1;
        width: 100%;
        height: 100%;

        @include themed() {
          background: radial-gradient(circle at 50% -90%, transparent, t('body'));
        }
      }
    }
  }

  .game-type-local {
    padding: 15px;
  }

  .game-type-third-party {
    display: flex;
    flex-direction: column;
    position: relative;
    min-height: 350px;
  }

  .game-header {
    @include themed() {
      display: flex;
      background: darken(t('sidebar'), 3.5%);
      padding: 20px 35px;
      font-size: 1.1em;
      user-select: none;
      border-top-left-radius: 6px;
      border-top-right-radius: 6px;
      align-items: center;

      .game-category {
        cursor: pointer;
      }

      .game-name {
        cursor: default;
        font-weight: 600;
      }

      .game-slash {
        cursor: default;
        margin: 0 10px;
      }
    }
  }

  @media(max-width: 991px) {
    .game-padding {
      padding: unset !important;
    }

    .tpBackdrop {
      height: 90px;

      .game-name-big {
        font-size: 2.2em;
        left: 10px;
      }

      .backdropImage {
        margin-left: -15px !important;
        width: calc(100% + 30px) !important;
      }
    }
  }
</style>
