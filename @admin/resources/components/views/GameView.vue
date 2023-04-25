<template>
  <div class="game">
    <div class="pageTitle">
      {{ games ? game.name : 'Game' }}
    </div>
    <div class="animate" v-if="games">
      <div class="switch a">
        <dashboard-switch :value="!game.isDisabled" :onChange="toggle"></dashboard-switch>
        Enabled
      </div>
      <div class="switch a">
        <dashboard-switch :value="!game.isHidden" :onChange="toggleVisibility"></dashboard-switch>
        Visible
      </div>
      <template v-if="isModernClassic">
        <template v-if="phoenixGamesData">
          <div class="module" v-for="module in phoenixGamesData.features[game.name]" :key="module">
            <div class="name">{{ module.name }}</div>
            <div class="description">{{ module.description }}</div>
            <div class="switch">
              <dashboard-switch :value="module.isEnabled" :onChange="() => togglePGModule(module.id)"></dashboard-switch>
              Enabled
            </div>
            <div class="settings">
              <div class="setting" v-for="(value, key) in module.settings" :key="key">
                <div class="description">{{ key }}</div>
                <input type="text" :value="value" @change="setPGModuleValue(module.id, key, $event.target.value)">
              </div>
            </div>
          </div>

          <div class="globalSetting">
            <div class="name">Callback URL</div>
            <div class="description">Phoenix Games will send game results to this server:</div>
            <input type="text" :value="phoenixGamesData.callbackUrl" @change="setPGCallbackUrl($event.target.value)">
          </div>
          <div class="globalSetting">
            <div class="name">Fake players</div>
            <div class="description">Multiplayer games will have fake players.</div>
            <dashboard-switch :value="phoenixGamesData.fakePresence" :onChange="() => togglePGBots()"></dashboard-switch>
          </div>
        </template>
        <template v-else>
          {{ $isDemo ? 'You can\'t edit game settings on the demo version.' : 'Contacting Phoenix Games server...' }}
        </template>
      </template>
      <template v-else-if="isClassic && modules">
        <dashboard-warning type="info">
          This game uses fair RNG by default. By enabling any of the twisting modules below, you can override RNG results.
        </dashboard-warning>
        <div class="module" v-for="module in modules.filter((e) => e.supports)" :key="module">
          <div class="name">{{ module.name }}</div>
          <div class="description" v-html="module.description"></div>
          <div class="switch">
            <dashboard-switch :value="module.isEnabled" :onChange="() => toggleModule(module.id)"></dashboard-switch>
            Enabled
          </div>
          <div class="settings">
            <div class="setting" v-for="setting in module.settings" :key="setting">
              <div class="name">{{ setting.name }}</div>
              <div class="description" v-html="setting.description"></div>
              <input :disabled="!module.isEnabled" type="text" :value="setting.value.length === 0 ? setting.defaultValue : setting.value" :placeholder="setting.defaultValue" @input="changeOption(module.id, setting.id, $event.target.value)">
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
  import DashboardSwitch from "../ui/interactive/DashboardSwitch.vue";
  import DashboardWarning from "../ui/DashboardWarning.vue";

  export default {
    data() {
      return {
        games: null,
        modules: null,
        modulesIsDemo: false,

        phoenixGamesData: null
      }
    },
    computed: {
      game() {
        return this.games.filter(e => e.id === this.$route.params.id)[0];
      },
      isClassic() {
        return this.game.type === 'Originals (Classic)';
      },
      isModernClassic() {
        return this.game.type === 'Originals';
      }
    },
    methods: {
      setPGModuleValue(module, key, value) {
        window.axios.post('/admin/phoenixGames/setFeatureValue/' + this.$route.params.id + '/' + module + '/' + key + '/' + value)
          .catch(() => this.$toast.error('Failed to change module value'));
      },
      togglePGModule(id) {
        window.axios.post('/admin/phoenixGames/toggleFeature/' + this.$route.params.id + '/' + id)
          .catch(() => this.$toast.error('Failed to toggle module'));
      },
      setPGCallbackUrl(url) {
        window.axios.post('/admin/phoenixGames/setCallbackUrl', {
          url: url
        }).catch(() => this.$toast.error('Failed to change callback URL'));
      },
      togglePGBots() {
        window.axios.post('/admin/phoenixGames/toggleFakePresence').catch(() => this.$toast.error('Failed to toggle bots'));
      },
      toggle() {
        window.axios.post('/admin/toggle', { name: this.game.id }).catch(() => this.$toast.error('Failed to save'));
      },
      toggleVisibility() {
        window.axios.post('/admin/toggleVisibility', { name: this.game.id }).catch(() => this.$toast.error('Failed to save'));
      },
      loadModules(demo) {
        window.axios.post('/admin/modules', { game: this.game.id, demo: demo }).then(({ data }) => {
          this.modulesIsDemo = demo;
          this.modules = data;
        });
      },
      changeOption(module, option, value) {
        window.axios.post('/admin/option_value', {
          api_id: this.game.id,
          module_id: module,
          option_id: option,
          demo: this.modulesIsDemo,
          value: value
        });
      },
      toggleModule(id) {
        const e = this.modules.filter(e => e.id === id)[0];
        e.isEnabled = !e.isEnabled;

        window.axios.post('/admin/toggle_module', {
          api_id: this.game.id,
          module_id: id,
          demo: this.isDemo
        });
      },
      loadPhoenixGamesData() {
        window.axios.post('/admin/phoenixGames/info').then(({ data }) => this.phoenixGamesData = data)
          .catch(() => {
            if(!this.$isDemo) this.$toast.error('Failed to contact Phoenix Games server');
          });
      }
    },
    created() {
      window.axios.post('/api/data/games').then(({ data }) => {
        this.games = data;
        this.$bus.$emit('loading:done');

        if(this.isClassic) {
          this.loadModules(false);
        } else if(this.isModernClassic) {
          this.loadPhoenixGamesData();
        }
      });
    },
    components: {
      DashboardSwitch,
      DashboardWarning
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .game {
    .globalSetting {
      margin-bottom: 25px;

      .name {
        font-size: 1.3em;
        margin-bottom: 10px;
      }

      .description {
        opacity: .6;
        margin-bottom: 15px;
      }
    }

    .module {
      margin-bottom: 25px;

      .name {
        font-size: 1.3em;
        margin-bottom: 10px;
      }

      .description {
        opacity: .6;
        margin-bottom: 15px;
      }

      .settings {
        margin-left: 25px;

        .setting {
          margin-bottom: 25px;

          &:last-child {
            margin-bottom: 0;
          }
        }

        @include themed() {
          border-left: 2px solid t('secondary');
        }

        padding-left: 25px;
        margin-top: 15px;
      }
    }

    .switch {
      display: flex;
      align-items: center;

      &.a {
        margin-bottom: 35px;
      }

      .switch {
        margin-right: 18px;
      }
    }
  }
</style>
