<template>
  <div class="ota animate" v-if="version">
    <div class="type">
      <div class="c1">
        <div class="smTitle">
          Update
        </div>
        <div class="updaterMeta">
          <dashboard-spinner v-if="!data"></dashboard-spinner>
          <template v-else>
            <template v-if="data.code === 0">
              Your server is up to date.
            </template>
            <template v-else-if="data.code === 1">
              <div class="title">{{ data.version }}</div>
              <div class="description">New update is ready to be installed.</div>
              <div class="changelog" v-html="marked(data.changelog)"></div>
              <button class="btn btn-primary" :class="$isDemo ? 'demoDisable' : ''" @click="install"><web-icon icon="fal fa-sync fa-fw"></web-icon> Install</button>
            </template>
          </template>
        </div>
      </div>
      <div class="c2">
        <div class="smTitle">
          Current
        </div>
        <div class="versions">
          <div class="version">
            <div>Phoenix</div>
            <div>{{ version.phoenix }}</div>
          </div>
          <div class="separated"></div>
          <div class="version">
            <div>PHP</div>
            <div>{{ version.php }}</div>
          </div>
          <div class="version">
            <div>Laravel</div>
            <div>{{ version.laravel }}</div>
          </div>
          <div class="version">
            <div>Vue.js | Dashboard</div>
            <div>{{ version.vueAdmin }}</div>
          </div>
          <div class="version">
            <div>Vue.js | Web</div>
            <div>{{ version.vue }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import DashboardSpinner from "../ui/DashboardSpinner.vue";
  import { marked } from 'marked';
  import WebIcon from "../ui/WebIcon.vue";
  import LoadingModal from "../modals/LoadingModal.vue";
  import CompilerModalOutput from "../modals/CompilerModalOutput.vue";

  export default {
    data() {
      return {
        version: null,
        data: null
      }
    },
    watch: {
      version() {
        this.$bus.$emit('loading:done');
      }
    },
    setup() {
      return {
        marked
      }
    },
    created() {
      window.axios.get('/api/version').then(({ data }) => this.version = data.version);
      window.axios.post('/admin/update/check').then(({ data }) => {
        this.data = data;
      });
    },
    methods: {
      install() {
        LoadingModal.methods.open().then((done) => {
          window.axios.post('/admin/update/install').then(() => {


            done();
            setTimeout(() => window.location.reload(), 1500);
          }).catch((e) => {
            window.$bus.$emit('modal:close');

            const code = e.response.data.code;
            if(code === 1 || code === 2)
              CompilerModalOutput.methods.open(e.response.data.message.replaceAll(/[\u001b\u009b][[()#;?]*(?:[0-9]{1,4}(?:;[0-9]{0,4})*)?[0-9A-ORZcf-nqry=><]/g, ''), code === 1 ? 'Failed to build @web module.' : 'Failed to build @admin module.');
            else if(code === 0)
              CompilerModalOutput.methods.open('Current version matches latest version from update manifest.', 'Nothing to update');
            else if(code === 3)
              CompilerModalOutput.methods.open('Current version is missing "next" parameter in manifest.', 'Invalid update manifest');
            else if(code === 4)
              CompilerModalOutput.methods.open(e.response.data.message, 'Failed to apply patch');
          });
        });
      }
    },
    components: {
      WebIcon,
      DashboardSpinner
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .updaterMeta {
    .title {
      font-size: 1.4em;
    }

    .description {
      margin-top: 10px;
      margin-bottom: 25px;
    }

    .btn {
      margin-top: 25px;
    }
  }

  .smTitle {
    font-size: 1.1em;
    font-weight: 400;
    margin-bottom: 15px;
  }

  .type {
    display: flex;
    margin-top: 30px;

    @include min(0, bp('md')) {
      flex-direction: column;
    }

    .c1 {
      margin-right: 30px;
      width: 20%;
      min-width: 200px;
      margin-bottom: 25px;

      @include min(0, bp('md')) {
        width: 100%;
        margin-right: 0;
      }

      .edit {
        display: flex;
        flex-direction: column;


      }
    }

    .c2 {
      .versions {
        .separated {
          width: 100%;
          height: 2px;
          margin-top: 10px;
          margin-bottom: 10px;

          @include themed() {
            background: t('block-2');
          }
        }

        .version {
          display: flex;
          margin-bottom: 10px;

          div {
            width: 50%;
          }

          div:first-child {
            margin-right: 50px;
            font-weight: 600;
            white-space: nowrap;
          }

          div:last-child {
            text-align: right;
          }
        }
      }
    }
  }
</style>
