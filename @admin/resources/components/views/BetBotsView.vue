<template>
  <div class="bots animate" v-if="data && status !== null">
    <div class="type">
      <div class="edit">
        <div class="setting" v-for="setting in data" :key="setting">
          <div class="e">{{ setting.name }}</div>
          <div class="d">{{ setting.description }}</div>
          <template v-if="setting.type === 'textarea'">
            <textarea rows="5" @input="change(setting.name, $event.target.value)" v-model="setting.value"></textarea>
          </template>
          <template v-else>
            <input type="text" :value="setting.value" @input="change(setting.name, $event.target.value)">
          </template>
        </div>
      </div>
      <dashboard-warning type="info">
        Bot will play games from "Originals (Classic)" category only.
      </dashboard-warning>
      <button :class="$isDemo ? 'demoDisable' : ''" @click="start" class="btn btn-primary"><web-icon :icon="status ? 'fal fa-fw fa-times' : 'fal fa-fw fa-play'"></web-icon> {{ status ? 'Stop' : 'Start' }}</button>
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import DashboardWarning from "../ui/DashboardWarning.vue";

  export default {
    data() {
      return {
        status: null,
        data: null,

        animated: false
      }
    },
    watch: {
      status() {
        this.verifyLoad();
      },
      data() {
        this.verifyLoad();
      }
    },
    methods: {
      verifyLoad() {
        if(this.status !== null && this.data) {
          if(this.animated) return;
          this.animated = true;

          this.$bus.$emit('loading:done');
        }
      },
      change(key, value) {
        window.axios.post('/admin/settings/edit', { key: key, value: value.length === 0 ? '0' : value });
      },
      start() {
        this.status = !this.status;

        window.axios.post('/admin/bot/start').then(() => {
          this.$toast.success('Done');
        });
      }
    },
    created() {
      window.axios.post('/admin/bot/status').then(({ data }) => this.status = data.status);
      window.axios.post('/admin/bot/settings').then(({ data }) => this.data = data);
    },
    components: {
      WebIcon,
      DashboardWarning
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";

  .bots {
    .type {
      margin-top: 30px;

      .edit {
        display: flex;
        flex-direction: column;
        width: 20%;
        min-width: 200px;

        .setting {
          margin-bottom: 15px;
        }

        .e {
          margin-bottom: 5px;
          font-size: 1.1em;
        }

        .d {
          margin-bottom: 10px;
          opacity: .6;
        }

        @include min(0, bp('md')) {
          width: 100%;
          margin-right: 0;
        }

        input {
          margin-bottom: 15px;
        }
      }
    }
  }
</style>
