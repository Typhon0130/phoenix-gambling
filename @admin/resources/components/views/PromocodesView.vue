<template>
  <div class="promocodes animate" v-if="promocodes">
    <div class="entries">
      <div class="h">
        <div>Code</div>
        <div>Amount</div>
        <div>Usages</div>
        <div>Expiration</div>
        <div>Created</div>
        <div v-if="$permission.checkPermission('promocodes', 'delete')">Actions</div>
      </div>
      <template v-if="promocodes.length === 0">
        No data to show.
      </template>
      <div class="entry" v-for="promocode in promocodes" :key="promocode">
        <div>
          {{ promocode.code }}
        </div>
        <div>
          {{ promocode.sum.toFixed(promocode.currency.startsWith('local_') ? 2 : 8) }} <template v-if="currencies">{{ currencies[currency].name }}</template>
        </div>
        <div>
          {{ promocode.times_used }}<template v-if="promocode.usages >= 0">/{{ promocode.usages }}</template>
        </div>
        <div>
          {{ +new Date(promocode.expires) === -62135596800 ? 'Never' : new Date(promocode.expires).toLocaleString() }}
        </div>
        <div>
          {{ new Date(promocode.created_at).toLocaleString() }}
        </div>
        <div v-if="$permission.checkPermission('promocodes', 'delete')">
          <button class="btn btn-primary" @click="remove(promocode._id)"
              :class="$isDemo ? 'demoDisable' : ''">
            <web-icon icon="fal fa-fw fa-times"></web-icon> Delete
          </button>
        </div>
      </div>
    </div>
    <div class="buttons">
      <dashboard-dropdown-config v-if="$permission.checkPermission('promocodes', 'create') && currencies"
         :values="[ { type: 'switch', title: 'Unlimited', event: (v, d) => { this.isUnlimited = v; v ? d.disabled.push('Uses') : d.disabled = d.disabled.filter(e => e !== 'Uses') } },
                    { type: 'switch', title: 'Random code', event: (v, d) => { this.isRandom = v; v ? d.disabled.push('Code') : d.disabled = d.disabled.filter(e => e !== 'Code') } },
                    { type: 'input', title: 'Code', value: this.code, event: (v) => { this.code = v; } },
                    { type: 'input', title: 'Uses', value: this.uses, event: (v) => { this.uses = v; } },
                    { type: 'input', title: 'Sum', value: this.sum, event: (v) => { this.sum = v; } },
                    { type: 'select', title: 'Currency', value: this.currency, values: Object.keys(currencies).map(e => [ currencies[e].id, currencies[e].name ]), event: (v) => { this.currency = v; } },
                    { type: 'datepicker', value: expires, title: 'Expires', event: (v) => { this.expires = new Date(v) } },
                    { type: 'button', disabled: $isDemo, title: 'Create', event: (d) => { d.show = false; create() } } ]">
        <web-icon icon="fal fa-fw fa-plus"></web-icon> Create
      </dashboard-dropdown-config>
      <button class="btn" @click="cleanup" v-if="$permission.checkPermission('promocodes', 'delete')"
        :class="$isDemo ? 'demoDisable' : ''"><web-icon icon="fal fa-fw fa-times"></web-icon> Delete expired</button>
    </div>
  </div>
</template>

<script>
  import OverlayScrollbars from 'overlayscrollbars';
  import WebIcon from "../ui/WebIcon.vue";
  import DashboardDropdownConfig from "../ui/interactive/DashboardDropdownConfig.vue";

  export default {
    data() {
      return {
        promocodes: null,
        currencies: null,

        isUnlimited: false,
        isRandom: false,
        code: 'Code',
        uses: '50',
        sum: '0',
        currency: null,
        expires: new Date(Date.now() + (3600 * 1000 * 24))
      }
    },
    components: {
      WebIcon,
      DashboardDropdownConfig
    },
    methods: {
      remove(id) {
        this.promocodes = this.promocodes.filter((e) => e._id !== id);
        window.axios.post('/admin/promocode/remove', { id: id });
      },
      cleanup() {
        window.axios.post('/admin/promocode/remove_inactive').then(() => {
          this.$toast.success('Done');
          this.load();
        })
      },
      create() {
        window.axios.post('/admin/promocode/create', {
          code: this.isRandom ? '%random%' : this.code,
          usages: this.isUnlimited ? '%infinite%' : parseInt(this.uses),
          expires: new Date(this.expires).getTime() / 1000,
          sum: parseFloat(this.sum),
          currency: this.currency
        }).then(() => this.load()).catch(() => this.$toast.error('Error'));
      },
      load(animate = false) {
        window.axios.post('/admin/promocode/get').then(({ data }) => {
          this.promocodes = data;

          if(animate) {
            this.$bus.$emit('loading:done');
            this.$nextTick(() => {
              OverlayScrollbars(document.querySelector('.promocodes .entries'), {
                scrollbars: {autoHide: 'leave'},
                className: 'os-theme-thin-light'
              });
            });
          }
        });
      }
    },
    created() {
      this.load(true);

      window.axios.post('/api/data/currencies').then(({ data }) => {
        this.currencies = data;
        this.currency = Object.keys(this.currencies)[0];
      });
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";

  .promocodes {
    .buttons {
      margin-top: 15px;
      display: flex;

      .btn, :deep(.dropdownConfig) {
        margin-right: 15px;

        &:last-child {
          margin-right: 0;
        }
      }
    }

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
  }
</style>
