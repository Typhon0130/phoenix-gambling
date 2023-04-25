<template>
  <div class="vip animate" v-if="vip">
    <div class="param">
      <div class="name">Name</div>
      <div class="value">
        <input @input="edit('name', $event.target.value)" :value="currentLevel.name" type="text" placeholder="Name">
      </div>
    </div>
    <div class="param" v-if="level > 0">
      <div class="name">Deposit requirement (USD)</div>
      <div class="value">
        <input @input="edit('depositRequirement', $event.target.value)" :value="currentLevel.depositRequirement" type="text" placeholder="Deposit requirement (USD)">
      </div>
    </div>
    <div class="param" v-if="level > 0">
      <div class="name">Wager requirement (USD)</div>
      <div class="value">
        <input @input="edit('wagerRequirement', $event.target.value)" :value="currentLevel.wagerRequirement" type="text" placeholder="Wager requirement (USD)">
      </div>
    </div>
    <div class="param">
      <div class="name">Number of withdrawals per day</div>
      <div class="value">
        <input @input="edit('numberOfWithdrawals', $event.target.value)" :value="currentLevel.numberOfWithdrawals" type="text" placeholder="Number of withdrawals per day">
      </div>
    </div>
    <div class="param">
      <div class="name">Max. withdrawal (USD)</div>
      <div class="value">
        <input @input="edit('maxWithdrawal', $event.target.value)" :value="currentLevel.maxWithdrawal" type="text" placeholder="Max. withdraw (USD)">
      </div>
    </div>
    <div class="param">
      <div class="name">Withdrawal fee (%)</div>
      <div class="value">
        <input @input="edit('withdrawFee', $event.target.value)" :value="currentLevel.withdrawFee" type="text" placeholder="Withdraw fee (%)">
      </div>
    </div>
    <div class="param" v-if="level > 0">
      <div class="name">One-time bonus after level is reached (USD)</div>
      <div class="value">
        <input @input="edit('oneTimeBonus', $event.target.value)" :value="currentLevel.oneTimeBonus" type="text" placeholder="One-time bonus after level is reached (USD)">
      </div>
    </div>
    <div class="param">
      <div class="name">Invite bonus (USD)</div>
      <div class="value">
        <input @input="edit('inviteBonus', $event.target.value)" :value="currentLevel.inviteBonus" type="text" placeholder="Invite bonus (USD)">
      </div>
    </div>
    <div class="param">
      <div class="name">Referral deposit fee (%)</div>
      <div class="value">
        <input @input="edit('referralDepositFee', $event.target.value)" :value="currentLevel.referralDepositFee" type="text" placeholder="Referral deposit fee (%)">
      </div>
    </div>
    <div class="param" v-if="level > 0">
      <div class="name">Level protection (USD)</div>
      <div class="value">
        <input @input="edit('levelProtection', $event.target.value)" :value="currentLevel.levelProtection" type="text" placeholder="Level protection (USD)">
      </div>
    </div>
  </div>
</template>

<script>
  import { withSidebar } from "../../utils/pageSidebar.js";

  export default {
    data() {
      return {
        vip: null,
        level: null
      }
    },
    computed: {
      currentLevel() {
        return this.vip.filter(e => e.level === this.level)[0];
      }
    },
    created() {
      withSidebar(() => {
        window.axios.post('/api/vip').then(({ data }) => {
          this.vip = data;
          this.$bus.$emit('vipSidebar:select', 0);
          this.$bus.$emit('vipSidebar:setData', data);
          this.$bus.$emit('loading:done');
        });

        this.$bus.$on('vipSidebar:select', (level) => this.level = level);
      });
    },
    methods: {
      edit(key, value) {
        window.axios.post('/admin/editVIP', {
          level: this.level,
          key: key,
          value: value
        }).catch(() => this.$toast.error('Failed to save'));
      }
    }
  }
</script>

<style lang="scss" scoped>
  .vip {
    .param {
      margin-bottom: 25px;

      .name {
        font-weight: 400;
        margin-bottom: 10px;
        font-size: 1.1em;
      }
    }
  }
</style>
