<template>
  <div class="wallet">
    <div class="pageTitle">
      <template v-if="!nodes">Wallet</template>
      <template v-else>${{ totalUsdBalance.toFixed(2) }} <i class="far fa-fw fa-sync-alt" @click="load()"></i></template>
    </div>
    <div class="animate" v-if="nodes">
      <div class="guide" v-if="nodes.filter(e => e.enabled && e.id.startsWith('commerce_')).length > 0">
        <div class="block" @click="expandCommerceSettings = !expandCommerceSettings">
          <div class="header" :class="expandCommerceSettings ? 'active' : ''">
            <web-icon icon="cog"></web-icon>
            <div class="title">
              <div class="type">Configuration</div> Coinbase Commerce
            </div>
          </div>
          <div class="content" v-if="expandCommerceSettings" @click.stop>
            <div class="info">
              <div class="key">
                <div>Coinbase Commerce API key</div>
                <div class="description">
                  Register <b>self-managed</b> account on <a href="https://commerce.coinbase.com/signup" target="_blank">Coinbase Commerce</a>.
                  <br>
                  Generate your API key on <a href="https://beta.commerce.coinbase.com/settings/security" target="_blank">this page</a>.
                </div>
              </div>
              <div class="value">
                <input type="text" placeholder="API key" :value="commerceSettings.commerceApiKey" v-if="commerceSettings" @input="setCCApiKey($event.target.value)">
                <dashboard-spinner v-else></dashboard-spinner>
              </div>
            </div>
            <div class="info">
              <div class="key">
                <div>Webhook</div>
                <div class="description" v-if="license">
                  Create webhook on <a href="https://beta.commerce.coinbase.com/settings/notifications" target="_blank">this page</a> with URL:
                  <br>
                  <b>https://phoenix-gambling.com/license/commerce/callback/{{ license.info.key }}</b>
                </div>
                <dashboard-spinner class="description" v-else></dashboard-spinner>
              </div>
            </div>
            <div class="atLeastOne" v-if="!isHttps">
              Your server must be configured to use HTTPS.
            </div>
          </div>
        </div>
        <div style="height: 25px"></div>
      </div>

      <template v-for="(node, i) in nodes" :key="node.id">
        <div class="block" @click="node.expand = !node.expand">
          <div class="header" :class="node.expand ? 'active' : ''">
            <web-icon :style="{ color: node.style }" :icon="node.icon"></web-icon>
            <div class="title">
              <div class="type" v-if="node.isToken">Token</div>
              <div class="type" v-else :style="{ background: types[node.id.split('_')[0]].badge }">{{ types[node.id.split('_')[0]].name }}</div>
              {{ node.shortName }}
            </div>
            <div class="balance" v-if="node.wallet && node.type === 'coin' && node.wallet.balance !== -1">{{ node.wallet.balance.toFixed(node.zeros) }}</div>
          </div>
          <div class="content" v-if="node.expand" @click.stop>
            <template v-if="node.wallet">
              <div class="info" v-if="node.wallet.address && ((node.id.startsWith('native_') || node.id.startsWith('tron_')) && !node.id.startsWith('native_eth'))">
                <div class="key">Wallet</div>
                <div class="value">
                  <template v-if="node.wallet.address === '1'">
                    <template v-if="$permission.checkPermission('*')">
                      <div>Click this button to create wallet address.</div>
                      <div class="attention" v-if="node.settings.filter(e => e.id === 'rpc')[0] && node.settings.filter(e => e.id === 'rpc')[0].value === '1'">
                        Configure RPC url!
                      </div>
                      <div><button class="btn btn-primary" @click="setupNode(node.id)" :disabled="$isDemo || node.settings.filter(e => e.id === 'rpc')[0] && node.settings.filter(e => e.id === 'rpc')[0].value === '1'">Create</button></div>
                    </template>
                    <template v-else>
                      Contact your system administrator to create wallet address for this currency.
                    </template>
                  </template>
                  <template v-else>
                    <div class="address" :style="{ userSelect: node.wallet.url ? '' : 'all' }" :onclick="node.wallet.url ? `window.open('${node.wallet.url.replace('%s', node.wallet.address)}', '_blank')` : ''">{{ node.wallet.address }}</div>
                    <div class="balance" @click="send(node)">{{ node.wallet.balance.toFixed(node.zeros) }} {{ node.shortName }}</div>
                  </template>
                </div>
              </div>
            </template>
            <div class="info">
              <div class="key">ID</div>
              <div class="value">{{ node.id }}</div>
            </div>
            <div class="info">
              <div class="key">Wallet ID</div>
              <div class="value">{{ node.walletId }}</div>
            </div>
            <div class="info" v-if="node.price">
              <div class="key">USD Price</div>
              <div class="value">${{ node.price }}</div>
            </div>
            <div class="info" v-if="node.chains.length > 0">
              <div class="key">Chains</div>
              <div class="value">{{ node.chains.map(e => e.name).join(', ') }}</div>
            </div>
            <template v-if="$permission.checkPermission('wallet', 'edit')">
              <div class="info" v-for="parameter in node.settings" :key="parameter.name">
                <div class="key">
                  <div>{{ parameter.name }}</div>
                  <div class="description">
                    <div v-for="line in parameter.description.split('\n')" :key="line">{{ line }}</div>
                  </div>
                </div>
                <div class="value"><input :readonly="parameter.readOnly" type="text" :value="parameter.value" @change="changeParameter(node.id, parameter.id, $event.target.value)"></div>
              </div>
            </template>
            <button class="btn btn-secondary" @click="send(node)" v-if="(node.id.startsWith('tron_') || node.id.startsWith('native_')) && node.wallet">Send</button>
            <button class="btn btn-primary" v-if="$permission.checkPermission('wallet', 'edit')" @click="toggle(node)"
                    :disabled="$isDemo || node.disabled || (nodes.filter(e => e.enabled).length <= 1 && node.enabled) || (nodes.filter(e => e.enabled && e.walletId === node.walletId && e.id !== node.id).length > 0 && !node.enabled)">
              {{ node.enabled ? 'Disable' : 'Enable' }}
            </button>
            <div class="atLeastOne" v-if="nodes.filter(e => e.enabled).length <= 1 && node.enabled">
              You can't disable this currency now. Website should have at least 1 enabled currency.
            </div>
            <div class="atLeastOne" v-if="nodes.filter(e => e.enabled && e.walletId === node.walletId && e.id !== node.id).length > 0 && !node.enabled">
              You can only use one type at once. Disable another type of {{ node.name }} before enabling this.
            </div>
          </div>
        </div>
        <div v-if="node.enabled && nodes[i + 1] && !nodes[i + 1].enabled" class="separator"></div>
      </template>
    </div>
  </div>
</template>

<script>
  import { tokenToUsd } from "../../utils/conversion.js";
  import WebIcon from "../ui/WebIcon.vue";
  import DashboardSpinner from "../ui/DashboardSpinner.vue";

  export default {
    data() {
      return {
        types: {
          'local': {
            name: 'Local',
            badge: '#2b892e'
          },
          'native': {
            name: 'Self-hosted node',
            badge: '#d73737'
          },
          'commerce': {
            name: 'Coinbase Commerce',
            badge: '#0775c3'
          },
          'tron': {
            name: 'Tron',
            badge: '#d73737'
          },
          'infura': {
            name: 'Infura',
            badge: '#d73737'
          }
        },

        nodes: null,
        totalUsdBalance: 0,

        license: null,

        isHttps: true,
        commerceSettings: null,

        expandCommerceSettings: false
      }
    },
    watch: {
      nodes() {
        this.totalUsdBalance = 0;

        this.nodes.forEach((node) => {
          if(node.wallet && node.wallet.balance !== -1) this.totalUsdBalance += tokenToUsd(node.wallet.balance, node.price);
        });
      }
    },
    methods: {
      setCCApiKey(apiKey) {
        window.axios.post('/admin/nodes/commerce/setApiKey', { key: apiKey });
      },
      load(callback = null) {
        window.axios.post('/admin/license/info').then(({ data }) => this.license = data);
        window.axios.post('/admin/nodes/commerce/settings').then(({ data }) => this.commerceSettings = data);

        window.axios.post('/admin/nodes/list').then(({ data }) => {
          this.nodes = this.resort(data);
          this.$bus.$emit('progress:done');

          if(callback) callback();
        });
      },
      changeParameter(nodeId, key, value) {
        window.axios.post('/admin/currencyOption', {
          currency: nodeId,
          option: key,
          value
        });

        this.nodes.filter(e => e.id === nodeId)[0].settings.filter(e => e.id === key)[0].value = value;
      },
      setupNode(id) {
        window.axios.post('/admin/wallet/setup', {
          id: id
        }).then(() => {
          this.load(() => {
            if(this.nodes.filter(e => e.id === id)[0].settings.filter(e => e.id === 'transfer_address')[0].value === '1')
              this.$toast.error('Failed to create wallet address. Make sure that node is running.');
          });
        })
      },
      toggle(node) {
        node.disabled = true;

        window.axios.post('/admin/toggleCurrency', {
          walletId: node.id,
          type: !node.enabled ? 'enabled' : 'disabled'
        }).then(() => {
          node.disabled = false;
          this.load();
        });
      },
      resort(data) {
        return data.sort((a, b) => a.enabled === b.enabled ? 0 : a.enabled ? -1 : 1);
      },
      send(node) {
        const address = prompt(`Enter destination ${node.shortName} address: `);
        if(!address) return;

        if(node.id.startsWith('native_eth')) {
          if(!confirm(`You are going to send all ${node.shortName} deposits to ${address}.\nAre you sure?`)) return;

          window.axios.post('/admin/ethereumNativeSendDeposits', { node: node.id, toAddr: address }).then(() => this.$toast.success('Success')).catch(() => this.$toast.error('Failed to send'));
        } else {
          let amount = prompt('Enter amount:');
          if(!amount || isNaN(parseFloat(amount))) {
            if(!amount) return;
            alert('Invalid amount (not a number).');
            return;
          }

          amount = parseFloat(amount);
          const confirmed = confirm(`You are going to send ${amount.toFixed(node.zeros)} to ${address} (${node.shortName}).\nAre you sure?`);
          if(!confirmed) return;

          window.axios.post('/admin/wallet/transfer', {
            currency: node.id,
            amount: amount,
            address: address
          }).then(() => this.$toast.success('Success'))
            .catch(() => this.$toast.error('Error'));
        }
      }
    },
    created() {
      this.load(() => this.$bus.$emit('loading:done'));

      this.isHttps = window.location.protocol === 'https:';
    },
    components: {
      DashboardSpinner,
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .wallet {
    .guide {
      .block {
        @include themed() {
          box-shadow: 0 0 1px 2px t('secondary');

          .header {
            svg, i {
              color: t('secondary');
            }
          }
        }
      }
    }

    .separator {
      margin-top: 15px;
      margin-bottom: 25px;
      height: 2px;
      width: 100%;

      @include themed() {
        background: rgba(t('text'), .05);
      }
    }

    .atLeastOne {
      margin-top: 25px;
      opacity: .6;
    }

    .pageTitle i {
      cursor: pointer;
      font-size: 22px;
      transition: all .3s ease;
      margin-left: 20px;

      &:hover {
        transform: rotate(180deg);
      }
    }

    .header {
      display: flex;
      align-items: center;
      cursor: pointer;
      padding: 20px 25px;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;

      .title {
        display: flex;
        align-items: center;

        .type {
          margin-right: 10px;
          background: #6c51d7;
          border-radius: 6px;
          padding: 5px 8px;
          color: white;
        }

        @media (max-width: 991px) {
          flex-direction: column;
          align-items: unset;

          .type {
            margin-bottom: 10px;
          }
        }
      }

      i, svg {
        width: 26px;
        height: 26px;
        margin-right: 20px;
      }

      .balance {
        margin-left: auto;

        @media(max-width: 991px) {
          display: none;
        }
      }

      @include themed() {
        &.active {
          background: t('block-2') !important;
        }

        .status {
          &.bad {
            color: red;
            margin-left: auto;
          }
        }
      }
    }

    .block {
      margin-bottom: 25px;
      border-radius: 10px;

      .content {
        padding: 0 25px 20px;

        .btn {
          margin-right: 10px;
        }
      }

      @include themed() {
        background: t('block');
      }
    }

    .info {
      margin-top: 25px;
      margin-bottom: 25px;
      display: flex;

      .key {
        .description {
          margin-top: 10px;
          margin-bottom: 10px;
          font-size: .8em;
          opacity: .6;
          line-height: 1.8em;
        }
      }

      .value {
        margin-left: auto;
        text-align: right;
        padding-left: 30px;

        div {
          margin-bottom: 10px;

          .btn {
            margin-right: 0;
          }

          &:last-child {
            margin-bottom: 0;
          }

          &.attention {
            @include themed() {
              color: t('criticalColor');
            }
          }
        }

        .address, .balance {
          cursor: pointer;
          opacity: .5;
          transition: opacity .3s ease;

          &:hover {
            opacity: 1;
          }
        }

        .balance {
          margin-top: 5px;
        }
      }

      @include min(0, bp('md')) {
        flex-direction: column;

        .value {
          text-align: left;
          padding-left: 0;
          margin-left: unset;
        }
      }
    }
  }
</style>
