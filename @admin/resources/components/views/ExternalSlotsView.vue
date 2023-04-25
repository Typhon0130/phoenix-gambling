<template>
  <div class="slots animate" v-if="data">
    <div class="ogTitle">Slotegrator</div>
    <div class="switch">
      <dashboard-switch :value="data.enabled === 'true'" :onChange="e => set('[Slotegrator] Enabled', e + '')"></dashboard-switch>
      Enabled
    </div>
    <div class="type">
      <div class="smTitle">API key type</div>
      <select @change="set('[Slotegrator] Key Type', $event.target.value); data.type = $event.target.value;">
        <option value="staging" :selected="data.type === 'staging'">Staging</option>
        <option value="production" :selected="data.type === 'production'">Production</option>
      </select>
      <div class="editContent">
        <div class="smTitle">
          Credentials
        </div>
        <div class="edit">
          <input type="text" placeholder="Merchant ID" :value="data.id" @input="set('[Slotegrator] Merchant ID', $event.target.value)">
          <input type="text" placeholder="Merchant Key" :value="data.key" @input="set('[Slotegrator] Merchant Key', $event.target.value)">
          <input type="text" placeholder="API URL" :value="data.url" @input="set('[Slotegrator] API URL', $event.target.value)">
          <button v-if="data.type === 'staging'" class="btn btn-primary fit" v-tooltip="'Play any slot game before clicking this button.'"
            onclick="window.open('/admin/slots/slotegratorValidate', '_blank')"><web-icon icon="fal fa-sync fa-fw"></web-icon> Self-validate</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import DashboardSwitch from "../ui/interactive/DashboardSwitch.vue";
  import WebIcon from "../ui/WebIcon.vue";

  export default {
    data() {
      return {
        data: null
      }
    },
    methods: {
      set(key, value) {
        window.axios.post('/admin/slots/setValue', {
          key: key,
          value: value
        });
      }
    },
    created() {
      window.axios.post('/admin/slots/settings').then(({ data }) => {
        this.data = data;
        this.$bus.$emit('loading:done');
      });
    },
    components: {
      WebIcon,
      DashboardSwitch
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";

  .slots {
    .smTitle {
      font-size: 1.1em;
      font-weight: 400;
      margin-bottom: 15px;
    }

    .switch {
      display: flex;
      align-items: center;

      .switch {
        margin-right: 18px;
      }
    }

    .ogTitle {
      font-size: 1.5em;
      font-weight: 400;
      margin-bottom: 25px;

      &.withMargin {
        margin-top: 15px;
      }
    }

    .type {
      margin-top: 30px;

      select {
        margin-bottom: 15px;
      }

      .editContent {
        margin-bottom: 25px;
        width: 20%;
        min-width: 200px;

        @include min(0, bp('md')) {
          width: 100%;
          margin-right: 0;
        }

        .edit {
          display: flex;
          flex-direction: column;

          input {
            margin-bottom: 15px;
          }
        }
      }
    }
  }
</style>
