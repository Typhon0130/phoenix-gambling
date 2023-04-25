<template>
  <dashboard-page-sidebar class="pluginsSidebar">
    <div class="category" :class="tab === 'all' ? 'active' : ''" @click="select('all')">
      <web-icon icon="fal fa-fw fa-box-open"></web-icon>
      Marketplace
    </div>
    <div class="category" :class="tab === 'installed' ? 'active' : ''" @click="select('installed')">
      <web-icon icon="fal fa-fw fa-download"></web-icon>
      Installed
    </div>
  </dashboard-page-sidebar>
</template>

<script>
  import DashboardPageSidebar from "./DashboardPageSidebar.vue";
  import WebIcon from "../../ui/WebIcon.vue";

  export default {
    data() {
      return {
        tab: 'all'
      }
    },
    created() {
      this.$bus.$emit('sidebarLoading:done');
    },
    methods: {
      select(id) {
        this.tab = id;
        this.$bus.$emit('pluginsSidebar:select', id);
      }
    },
    components: {
      WebIcon,
      DashboardPageSidebar
    }
  }
</script>

<style lang="scss" scoped>
  .pluginsSidebar {
    .category {
      opacity: .6;
      cursor: pointer;
      margin-bottom: 25px;
      transition: opacity .3s ease;

      &:hover {
        opacity: 1;
      }

      &.active {
        opacity: 1;
      }

      svg, i {
        margin-right: 10px;
      }
    }
  }
</style>
