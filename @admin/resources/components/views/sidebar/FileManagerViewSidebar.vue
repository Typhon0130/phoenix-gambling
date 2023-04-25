<template>
  <dashboard-page-sidebar class="fmSidebar">
    <template v-if="data">
      <div class="dir" @click="select({ isDir: true, path: pathLower, isPrevious: true })" v-if="path !== ''">
        <web-icon icon="fas fa-fw fa-folder"></web-icon>
        ...
      </div>
      <template v-for="file in data" :key="file">
        <div v-if="file.isDir" class="dir" :class="file.warn ? 'warn' : ''" @click="select(file)">
          <web-icon icon="fas fa-fw fa-folder"></web-icon>
          {{ file.name }}
        </div>
      </template>
    </template>
  </dashboard-page-sidebar>
</template>

<script>
  import DashboardPageSidebar from "./DashboardPageSidebar.vue";
  import WebIcon from "../../ui/WebIcon.vue";

  export default {
    data() {
      return {
        path: null,
        data: null
      }
    },
    computed: {
      pathLower() {
        return this.path.substring(0, this.path.lastIndexOf('/'));
      }
    },
    created() {
      this.$bus.$on('filesSidebar:setData', (data) => {
        this.path = data.path;
        this.data = data.data;
      });
    },
    methods: {
      select(file) {
        this.$bus.$emit('files:select', file);
      }
    },
    components: {
      WebIcon,
      DashboardPageSidebar
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .fmSidebar {
    .dir {
      margin-bottom: 15px;
      cursor: pointer;
      opacity: .6;
      transition: opacity .3s ease;

      &:hover {
        opacity: 1;
      }

      &.warn {
        @include themed() {
          color: t('criticalColor');
        }
      }
    }
  }
</style>
