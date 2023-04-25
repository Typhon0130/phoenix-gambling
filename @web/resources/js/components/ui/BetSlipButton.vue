<template>
  <div :class="[active ? 'active' : '', showUnavailableTooltip ? 'unavailable' : '']" @click="add" v-tooltip="phoenixShowUnavailableRunners ?
      `Handler: ${runner.supported.class}<br>Name: ${runner.name}<br>Price: ${runner.price}` : null">
    <slot></slot>
  </div>
</template>

<script>
import {mapGetters} from 'vuex';
import Bus from '../../bus';
import Vue from 'vue';

export default {
  computed: {
    ...mapGetters(['betSlip', 'phoenixShowUnavailableRunners']),
    showUnavailableTooltip() {
      return this.phoenixShowUnavailableRunners && !this.runner.supported.status;
    }
  },
  created() {
    this.hash = this.game.id + this.market.name + this.runner.name;

    Bus.$on('betSlip:clear', () => this.active = false);
    /*Bus.$on('betSlip:add', (data) => {
        if(data.hash === this.hash) this.active = true;
    });*/
    Bus.$on('betSlip:remove:' + this.hash, () => this.active = false);

    this.active = Bus.$retrieve('betSlip:includes', {
      game: {
        id: this.game.id
      },
      market: {
        name: this.market.name
      },
      runner: {
        name: this.runner.name
      }
    });
  },
  props: {
    game: {
      type: Object,
      required: true
    },
    runner: {
      type: Object,
      required: true
    },
    market: {
      type: Object,
      required: true
    },
    category: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      hash: null,
      active: false
    }
  },
  methods: {
    add() {
      if (!this.game.open || !this.market.open || !this.runner.open) return;

      if (!this.betSlip && window.innerWidth > 991) this.$store.dispatch('toggleBetSlip', true);

      this.active = true;

      let object = new Vue({
        data: {
          game: this.game,
          market: this.market,
          runner: this.runner,
          hash: this.hash,
          category: this.category
        }
      });

      Bus.$emit('betSlip:add', object);
    }
  }
}
</script>
