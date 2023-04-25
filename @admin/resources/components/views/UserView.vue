<template>
  <div class="user">
    <div class="pageTitle">
      {{ user ? user.user.name : 'User' }}
    </div>
    <div class="animate" v-if="user && currencies">
      <div class="entries c">
        <div class="h">
          <div>Currency</div>
          <div>Games</div>
          <div>Wins</div>
          <div>Losses</div>
          <div>Wagered</div>
          <div>Balance</div>
        </div>
        <template v-for="currency in currencies">
          <div class="entry" v-if="userAdvanced.currencies[currency.id]" :key="currency">
            <div>
              {{ currency.name }}
            </div>
            <div>
              {{ userAdvanced ? userAdvanced.currencies[currency.id].games : '...' }}
            </div>
            <div>
              {{ userAdvanced ? userAdvanced.currencies[currency.id].wins : '...' }}
            </div>
            <div>
              {{ userAdvanced ? userAdvanced.currencies[currency.id].losses : '...' }}
            </div>
            <div>
              {{ userAdvanced ? (userAdvanced.currencies[currency.id].wagered.toFixed(currency.id.startsWith('local_') ? 2 : 8)) : '...' }} {{ currency.displayName }}
            </div>
            <div>
              <template v-if="$permission.checkPermission('users', 'edit')">
                <input type="text" :placeholder="currency.name" :value="user.currencies[currency.id].balance.toFixed(currency.id.startsWith('local_') ? 2 : 8)" @input="changeBalance(currency.walletId, $event.target.value)"
                       :class="$isDemo ? 'demoDisable' : ''">
              </template>
            </div>
          </div>
        </template>
      </div>
      <div class="search">
        <web-icon icon="fal fa-fw fa-search"></web-icon>
        <input type="text" placeholder="Transaction filter" v-model="search">
      </div>
      <div class="entries tx">
        <div class="h">
          <div>Transaction</div>
          <div>Data</div>
        </div>
        <template v-for="transaction in transactions">
          <div class="entry" :key="transaction" v-if="currencies[transaction.currency]">
            <div style="flex-direction: column; padding-left: 5px">
              <div>Message: {{ transaction.data.message ? transaction.data.message : '-' }}</div>
              <div>Game: {{ transaction.data.game ? transaction.data.game : '-' }}</div>
              <div>
                Amount: {{ transaction.amount.toFixed(transaction.currency.startsWith('local_') ? 2 : 8) }} {{ currencies[transaction.currency].name }}
                (Before: {{ transaction.old.toFixed(transaction.currency.startsWith('local_') ? 2 : 8) }}, Now: {{ transaction.new.toFixed(transaction.currency.startsWith('local_') ? 2 : 8) }})
              </div>
            </div>
            <div>
              {{ new Date(transaction.created_at).toLocaleString() }}
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
  import OverlayScrollbars from 'overlayscrollbars';
  import WebIcon from "../ui/WebIcon.vue";
  import { withSidebar } from "../../utils/pageSidebar.js";

  export default {
    data() {
      return {
        user: null,
        userAdvanced: null,
        transactions: [],
        page: 0,
        isLoadingNextPage: false,
        search: '',
        currencies: null
      }
    },
    methods: {
      changeBalance(id, balance) {
        window.axios.post('/admin/balance', {
          id: this.user.user._id,
          balance: balance,
          currency: id
        }).catch(() => this.$toast.error('Failed to save'));
      },
      isDone() {
        if(this.currencies && this.user) {
          this.$bus.$emit('loading:done');

          this.$nextTick(() => {
            OverlayScrollbars(document.querySelector('.user .entries.c'), {
              scrollbars: { autoHide: 'leave' },
              className: 'os-theme-thin-light'
            });
            OverlayScrollbars(document.querySelector('.user .entries.tx'), {
              scrollbars: { autoHide: 'leave' },
              className: 'os-theme-thin-light'
            })
          });
        }
      },
      load() {
        if(this.isLoadingNextPage) return;
        this.isLoadingNextPage = true;
        window.axios.post('/admin/transactions/' + this.user.user._id + '/' + this.page).then(({ data }) => {
          if(data.length === 0) {
            this.isLoadingNextPage = true;
            return;
          }

          this.isLoadingNextPage = false;
          this.transactions = this.transactions.concat(data);
          this.page++;
        }).catch(() => this.isLoadingNextPage = false);
      }
    },
    created() {
      withSidebar(() => {
        window.axios.post('/api/data/currencies').then(({data}) => {
          this.currencies = data;
          this.isDone();
        });

        window.axios.post('/admin/user', { id: this.$route.params.id }).then(({ data }) => {
          this.user = data;
          this.load();

          window.addEventListener('scroll', () => {
            const atEnd = () => {
              let c = [document.scrollingElement.scrollHeight, document.body.scrollHeight, document.body.offsetHeight].sort(function(a,b){return b-a})
              return (window.innerHeight + window.scrollY + 2 >= c[0]);
            };

            if (atEnd()) this.load();
          });

          this.$bus.$emit('userSidebar:setData', data);
        });

        window.axios.post('/admin/userAdvanced', { id: this.$route.params.id }).then(({ data }) => {
          this.userAdvanced = data;
          this.isDone();
        });
      });
    },
    watch: {
      search() {
        if(this.search.length >= 3) {
          window.axios.post('/admin/transactionsSearch', { user: this.user.user._id, search: this.search }).then(({ data }) => {
            this.page = 0;
            this.transactions = data;
            this.isLoadingNextPage = true;
          });
        } else {
          this.isLoadingNextPage = false;
          this.load();
        }
      }
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";

  .user {
    .search {
      position: relative;

      i {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        left: 20px;
      }

      input {
        padding-left: 55px;
      }
    }

    .entries {
      display: flex;
      flex-direction: column;
      margin-top: 15px;

      @include min(0, bp('md')) {
        width: calc(100vw - 140px);
      }

      width: calc(100vw - 620px);

      .h, .entry {
        min-width: 1000px;
      }

      .h div, .entry div {
        width: 100%;
        flex: 1;
        margin-right: 10px;
        white-space: nowrap;

        button {
          margin-right: 10px;

          &:last-child {
            margin-right: 0;
          }
        }
      }

      .h {
        display: flex;
        font-size: 1.1em;
        margin-bottom: 20px;
        margin-top: 10px;
      }

      .entry {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        cursor: pointer;

        div {
          display: flex;
          align-items: center;

          img {
            margin-right: 10px;
            width: 32px;
            height: 32px;
          }
        }
      }
    }
  }
</style>
