<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col vertical-tabs-column">
        <div class="vertical-tabs">
          <div :class="`option ${tab === 4 ? 'active' : ''}`" @click="tab = 4">
            {{ $t('fairness.tabs.calculator') }}
          </div>
        </div>
      </div>
      <div class="col vertical-tabs-content-column">
        <div class="vertical-tabs-content">
          <div class="tab-content" :style="{ display: tab === 4 ? 'block' : 'none' }">
            <div><strong>{{ $t('fairness.calculator.game') }}</strong></div>
            <div class="fairness-games">
              <div v-for="game in games" v-if="!game.isDisabled && game.type === 'Originals (Classic)'"
                   :class="`game ${game.id === selectedGame ? 'active' : '' }`" @click="selectedGame = game.id">
                <web-icon :icon="game.icon"></web-icon>
              </div>
            </div>
            <div class="mb-1 mt-1"><strong>{{ $t('fairness.calculator.client_seed') }}</strong></div>
            <input v-model="client_seed" type="text" :placeholder="$t('fairness.calculator.client_seed')">
            <div class="mb-1 mt-1"><strong>{{ $t('fairness.calculator.nonce') }}</strong></div>
            <input v-model="nonce" type="number" :placeholder="$t('fairness.calculator.nonce')">
            <div class="mb-1 mt-1"><strong>{{ $t('fairness.calculator.server_seed') }}</strong></div>
            <input v-model="server_seed" type="text" :placeholder="$t('fairness.calculator.server_seed')">
            <div class="mb-1 mt-1"><strong>{{ $t('fairness.calculator.result') }}</strong></div>
            <span v-html="result"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';

  import Dice from '../../fairness/games/Dice';
  import Keno from '../../fairness/games/Keno';
  import Limbo from '../../fairness/games/Limbo';
  import Coinflip from '../../fairness/games/Coinflip';
  import Crash from '../../fairness/games/Crash';
  import Slide from '../../fairness/games/Slide';
  import Mines from '../../fairness/games/Mines';
  import Plinko from '../../fairness/games/Plinko';
  import Roulette from '../../fairness/games/Roulette';
  import WheelDouble from '../../fairness/games/WheelDouble';
  import WheelX50 from "../../fairness/games/WheelX50";
  import Stairs from '../../fairness/games/Stairs';
  import Tower from '../../fairness/games/Tower';
  import Slots from '../../fairness/games/Slots';
  import Cards from '../../fairness/games/Cards';

  const bundle = {
    'dice': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Dice().verify({serverSeed, clientSeed, nonce});
      }
    },
    'keno': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Keno().verify({serverSeed, clientSeed, nonce});
      }
    },
    'limbo': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Limbo().verify({serverSeed, clientSeed, nonce});
      }
    },
    'coinflip': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Coinflip().verify({serverSeed, clientSeed, nonce});
      },
      isExtended: true
    },
    'crash': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Crash().verify({serverSeed, clientSeed, nonce});
      }
    },
    'slide': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Slide().verify({serverSeed, clientSeed, nonce})
      }
    },
    'mines': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        let result = [];
        for (let bombs = 2; bombs <= 24; bombs++) result.push(`${bombs} bombs: ${new Mines().verify({
          serverSeed,
          clientSeed,
          nonce
        }).slice(0, bombs).reverse().join(', ')}`);
        return result;
      },
      isExtended: true
    },
    'plinko': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Plinko().verify({serverSeed, clientSeed, nonce});
      }
    },
    'roulette': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Roulette().verify({serverSeed, clientSeed, nonce});
      }
    },
    'wheel': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new WheelDouble().verify({serverSeed, clientSeed, nonce});
      }
    },
    "x50": {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new WheelX50().verify({serverSeed, clientSeed, nonce});
      }
    },
    'blackjack': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        let result = [];
        _.forEach(new Cards().verifyBlackjack({serverSeed, clientSeed, nonce}), function (index) {
          result.push(deck.toString(deck[index + 1]));
        });
        return result;
      },
      isExtended: true
    },
    'hilo': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        let result = [];
        _.forEach(new Cards().verifyHilo({serverSeed, clientSeed, nonce}), function (index) {
          result.push(deck.toString(deck[index + 1]));
        });
        return result;
      },
      isExtended: true
    },
    'videopoker': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        let result = [];
        _.forEach(new Cards().verifyVideoPoker({serverSeed, clientSeed, nonce}), function (index) {
          result.push(deck.toString(deck[index + 1]));
        });
        return result;
      },
      isExtended: true
    },
    'baccarat': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        let result = [];
        _.forEach(new Cards().verifyBaccarat({serverSeed, clientSeed, nonce}), function (index) {
          result.push(deck.toString(deck[index + 1]));
        });
        return result;
      }
    },
    'diamonds': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Cards().verifyDiamondPoker({serverSeed, clientSeed, nonce});
      }
    },
    'stairs': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        let result = [];
        for (let i = 1; i <= 7; i++) {
          result.push(i + ' mines: ' + new Stairs().verify({serverSeed, clientSeed, nonce}, i).join(' / '));
        }
        return result;
      },
      isExtended: true
    },
    'tower': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        let result = [];
        for (let i = 1; i <= 4; i++) {
          result.push(i + ' mines: ' + new Tower().verify({serverSeed, clientSeed, nonce}, i).join(' / '));
        }
        return result;
      },
      isExtended: true
    },
    'slots': {
      verify: function ({serverSeed, clientSeed, nonce}) {
        return new Slots().verify({serverSeed, clientSeed, nonce});
      }
    }
  };

  const deck = {
    1: {type: 'spades', value: 'A'},
    2: {type: 'spades', value: '2'},
    3: {type: 'spades', value: '3'},
    4: {type: 'spades', value: '4'},
    5: {type: 'spades', value: '5'},
    6: {type: 'spades', value: '6'},
    7: {type: 'spades', value: '7'},
    8: {type: 'spades', value: '8'},
    9: {type: 'spades', value: '9'},
    10: {type: 'spades', value: '10'},
    11: {type: 'spades', value: 'J'},
    12: {type: 'spades', value: 'Q'},
    13: {type: 'spades', value: 'K'},
    14: {type: 'hearts', value: 'A'},
    15: {type: 'hearts', value: '2'},
    16: {type: 'hearts', value: '3'},
    17: {type: 'hearts', value: '4'},
    18: {type: 'hearts', value: '5'},
    19: {type: 'hearts', value: '6'},
    20: {type: 'hearts', value: '7'},
    21: {type: 'hearts', value: '8'},
    22: {type: 'hearts', value: '9'},
    23: {type: 'hearts', value: '10'},
    24: {type: 'hearts', value: 'J'},
    25: {type: 'hearts', value: 'Q'},
    26: {type: 'hearts', value: 'K'},
    27: {type: 'clubs', value: 'A'},
    28: {type: 'clubs', value: '2'},
    29: {type: 'clubs', value: '3'},
    30: {type: 'clubs', value: '4'},
    31: {type: 'clubs', value: '5'},
    32: {type: 'clubs', value: '6'},
    33: {type: 'clubs', value: '7'},
    34: {type: 'clubs', value: '8'},
    35: {type: 'clubs', value: '9'},
    36: {type: 'clubs', value: '10'},
    37: {type: 'clubs', value: 'J'},
    38: {type: 'clubs', value: 'Q'},
    39: {type: 'clubs', value: 'K'},
    40: {type: 'diamonds', value: 'A'},
    41: {type: 'diamonds', value: '2'},
    42: {type: 'diamonds', value: '3'},
    43: {type: 'diamonds', value: '4'},
    44: {type: 'diamonds', value: '5'},
    45: {type: 'diamonds', value: '6'},
    46: {type: 'diamonds', value: '7'},
    47: {type: 'diamonds', value: '8'},
    48: {type: 'diamonds', value: '9'},
    49: {type: 'diamonds', value: '10'},
    50: {type: 'diamonds', value: 'J'},
    51: {type: 'diamonds', value: 'Q'},
    52: {type: 'diamonds', value: 'K'},
    toIcon: function (card) {
      let icons = {
        'spades': 'fas fa-spade',
        'hearts': 'fas fa-heart',
        'clubs': 'fas fa-club',
        'diamonds': 'fas fa-diamond'
      };
      return icons[card.type];
    },
    toString: function (card) {
      return `<span class="${card.type === 'hearts' || card.type === 'diamonds' ? 'text-danger' : ''}">${card.value} <i class="${deck.toIcon(card)}"></i></span>`;
    }
  };

  export default {
    data() {
      return {
        client_seed: '',
        server_seed: '',
        nonce: 0,
        result: '-',

        tab: 4,
        selectedGame: 'dice'
      }
    },
    watch: {
      client_seed: {handler: 'calculateResult'},
      server_seed: {handler: 'calculateResult'},
      nonce: {handler: 'calculateResult'},
      selectedGame: {handler: 'calculateResult'}
    },
    methods: {
      calculateResult() {
        if (this.client_seed.length < 1 || isNaN(this.nonce) || this.server_seed.length < 1) return;

        if (bundle[this.selectedGame] === undefined) {
          this.$toast.error(`Unknown game ${this.selectedGame}, contact support for more information`);
          return;
        }

        const result = bundle[this.selectedGame].verify({
          serverSeed: this.server_seed,
          clientSeed: this.client_seed,
          nonce: bundle[this.selectedGame].isExtended ? this.nonce + 1 : this.nonce
        });

        this.result = '';
        if (typeof result === "object" || Array.isArray(result)) _.forEach(result, (e) => this.result += `<div>${e}</div>`);
        else this.result = result;
      }
    },
    created() {
      if (!this.isGuest) this.client_seed = this.user.user.client_seed;
      this.calculateResult();
    },
    computed: {
      ...mapGetters(['games', 'isGuest', 'user'])
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .fairness-games {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    border-radius: 3px;
    padding: 15px;
    @include themed() {
      background: t('body');
    }
    margin-top: 15px;
    margin-bottom: 15px;

    .game {
      display: inline-flex;
      padding: 10px;
      margin-right: 5px;
      opacity: 0.8;
      transition: opacity 0.3s ease;
      cursor: pointer;

      &:hover {
        opacity: 1;
      }
    }

    .game.active {
      opacity: 1;
    }
  }
</style>
