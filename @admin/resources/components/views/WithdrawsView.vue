<template>
  <div class="withdraws">
    <div class="animate">
      <div class="entries">
        <template v-if="withdraws">
          <div class="h">
            <div>ID</div>
            <div>User</div>
            <div>Date</div>
            <div>Payment Method</div>
            <div>Amount</div>
            <div>Status</div>
            <div>Manage</div>
          </div>
          <template v-if="withdraws.withdraws.length === 0">
            No data to show.
          </template>
          <div class="entry" v-for="withdraw in withdraws.withdraws" :key="withdraw.data._id">
            <div>
              {{ withdraw.data._id }}
            </div>
            <div @click="$router.push('/admin/user/' + withdraw.user._id)">
              <img alt :src="withdraw.user.avatar"> {{ withdraw.user.name }}
            </div>
            <div>
              {{ new Date(withdraw.data.created_at).toLocaleString() }}
            </div>
            <div>
              <div v-if="withdraw.data.address">{{ withdraw.data.address }}</div>
            </div>
            <div>
              {{ withdraw.data.sum }} {{ withdraw.data.currency }}
            </div>
            <div>
              <span style="color: indianred" v-if="withdraw.data.status === 0">Pending</span>
              <span style="color: forestgreen" v-else-if="withdraw.data.status === 1">Paid</span>
              <span style="color: orange" v-else-if="withdraw.data.status === 2">Declined (Reason: {{ withdraw.data.decline_reason }})</span>
              <span style="color: cornflowerblue" v-else-if="withdraw.data.status === 3">Ignored</span>
              <span v-else-if="withdraw.data.status === 4">Cancelled (by user)</span>
            </div>
            <div>
              <template v-if="withdraw.data.status === 0">
                <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-primary mr-2" @click="acceptWithdraw(withdraw.data._id)"><web-icon icon="fal fa-fw fa-check"></web-icon></button>
                <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-danger mr-2" @click="declineWithdraw(withdraw.data._id)"><web-icon icon="fal fa-fw fa-times"></web-icon></button>
                <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-secondary" @click="ignoreWithdraw(withdraw.data._id)"><web-icon icon="fal fa-fw fa-clock"></web-icon> Ignore</button>
              </template>
              <template v-else-if="withdraw.data.status === 3">
                <button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-secondary" @click="unignoreWithdraw(withdraw.data._id)"><web-icon icon="fal fa-fw fa-clock"></web-icon> Stop ignoring</button>
              </template>
            </div>
          </div>
        </template>
      </div>
      <div class="pagination" v-if="maxPages">
        <div class="prev" @click="page -= 2" v-if="page - 2 >= 1">{{ page - 2 }}</div>
        <div class="prev" @click="page--" v-if="page - 1 >= 1">{{ page - 1 }}</div>
        <div class="current">{{ page }}</div>
        <div class="next" @click="page++" v-if="page + 1 <= maxPages">{{ page + 1 }}</div>
        <div class="next" @click="page += 2" v-if="page + 2 <= maxPages">{{ page + 2 }}</div>
        <div class="jump" @click="jump"><web-icon icon="fal fa-fw fa-ellipsis-h"></web-icon></div>
      </div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import OverlayScrollbars from 'overlayscrollbars';

  export default {
    data() {
      return {
        withdraws: null,
        page: 1,
        maxPages: null
      }
    },
    mounted() {
      OverlayScrollbars(document.querySelector('.withdraws .entries'), {
        scrollbars: { autoHide: 'leave' },
        className: 'os-theme-thin-light'
      });
    },
    watch: {
      page() {
        this.load();
      }
    },
    methods: {
      load(animate = false) {
        this.withdraws = null;

        window.axios.post('/admin/wallet/withdraws', {
          page: this.page
        }).then(({ data }) => {
          this.withdraws = data;
          this.maxPages = data.maxPages;

          if(animate)
            this.$bus.$emit('loading:done');
        });
      },
      ignoreWithdraw(id) {
        window.axios.post('/admin/wallet/ignore', { id: id }).then(() => this.load());
      },
      acceptWithdraw(id) {
        window.axios.post('/admin/wallet/accept', { id: id }).then(() => this.load());
      },
      declineWithdraw(id) {
        window.axios.post('/admin/wallet/decline', { id: id, reason: prompt('Enter decline reason:') }).then(() => this.load());
      },
      unignoreWithdraw(id) {
        window.axios.post('/admin/wallet/unignore', { id: id }).then(() => this.load());
      },
      jump() {
        const page = parseInt(prompt(`Enter page (from 1 to ${this.maxPages}):`));
        if(page && !isNaN(page) && page >= 1 && page <= this.maxPages) {
          this.page = page;
        } else alert('Invalid page: ' + page);
      }
    },
    created() {
      this.load(true);
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .withdraws {
    .entries {
      display: flex;
      flex-direction: column;
      margin-top: 15px;

      @include min(0, bp('md')) {
        width: calc(100vw - 140px);
      }

      width: calc(100vw - 350px);

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

    .pagination {
      font-size: 16px;
      margin-top: 25px;
      display: flex;

      .current {
        @include themed() {
          color: t('secondary');
          font-weight: 400;
        }
      }

      div {
        margin-right: 5px;
        cursor: pointer;
        font-weight: 100;

        &:last-child {
          margin-right: 0;
        }
      }
    }
  }
</style>
