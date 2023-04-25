<template>
  <dashboard-page-sidebar class="themesSidebar">
    <div class="type" :class="selected === 'web' ? 'active' : ''" @click="select('web')">
      Web
    </div>
    <div class="type" :class="selected === 'dashboard' ? 'active' : ''" @click="select('dashboard')">
      Dashboard
    </div>
  </dashboard-page-sidebar>
</template>

<script>
  import DashboardPageSidebar from "./DashboardPageSidebar.vue";

  export default {
    data() {
      return {
        selected: 'web'
      }
    },
    watch: {
      selected() {
        document.body.scrollTo({ top: 0 });
      }
    },
    created() {
      this.$bus.$on('themesSidebar:select', (data) => this.selected = data);
    },
    methods: {
      select(id) {
        this.$bus.$emit('themesSidebar:select', id);
      }
    },
    components: {
      DashboardPageSidebar
    }
  }
</script>

<style lang="scss" scoped>
  .themesSidebar {
    .type {
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
    }
  }
</style>
