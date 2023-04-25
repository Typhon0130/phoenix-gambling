<template>
  <div class="pluginMarketplace">
    <div class="marketContainer animate">
      <div class="content">
        <plugin-search-result :data="data" class="animate-sm" v-if="tab === 'all' || tab === 'recommended' && data" :marketplace="true"></plugin-search-result>
        <plugin-search-result :data="data" class="animate-sm" v-else-if="tab === 'installed' && data"></plugin-search-result>
      </div>
    </div>
  </div>
</template>

<script>
  import PluginSearchResult from "../ui/PluginSearchResult.vue";
  import { withSidebar } from "../../utils/pageSidebar.js";

  export default {
    data() {
      return {
        tab: 'all',
        data: null
      }
    },
    watch: {
      tab() {
        this.data = null;

        this.loadTab();
      },
      data() {
        this.$bus.$emit('loading:done');
      }
    },
    methods: {
      loadTab() {
        switch (this.tab) {
          case 'installed':
            window.axios.post('/admin/plugins/installed').then(({ data }) => this.data = data);
            break;
          case 'all':
            window.axios.post('/admin/plugins/all').then(({ data }) => this.data = data);
            break;
        }
      }
    },
    mounted() {
      this.$bus.$on('pluginsSidebar:select', (e) => {
        this.tab = e;
      });

      withSidebar(() => {
        this.loadTab();
      });
    },
    components: {
      PluginSearchResult
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .pluginMarketplace {
    .marketContainer {
      display: flex;

      .content {
        flex: 1;
        width: 0;
        margin-left: -10px;
        margin-top: -10px;

        .customContent {
          .btn {
            margin-top: 15px;
            margin-left: 15px;
            margin-bottom: 10px;
          }
        }
      }
    }
  }

  @include min(0, bp('md')) {
    .pluginMarketplace {
      .marketContainer {
        flex-direction: column;

        .sidebar {
          width: 100%;
          margin-right: 0;
          margin-bottom: 25px;
        }

        .content {
          width: unset;
          flex: unset;
        }
      }
    }
  }
</style>
