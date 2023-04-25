<template>
  <dashboard-page-sidebar class="vipSidebar">
    <template v-if="vip">
      <div class="level" v-for="level in vip.filter(e => e.type !== 'data')" :key="level"
           :class="selected === level.level ? 'active' : ''" @click="select(level.level)">
        Level {{ level.level }}
      </div>
    </template>
  </dashboard-page-sidebar>
</template>

<script>
  import DashboardPageSidebar from "./DashboardPageSidebar.vue";

  export default {
    data() {
      return {
        vip: null,
        selected: null
      }
    },
    created() {
      this.$bus.$on('vipSidebar:setData', (data) => {
        this.vip = data;
        this.$bus.$emit('sidebarLoading:done');
      });
      this.$bus.$on('vipSidebar:select', (data) => this.selected = data);
    },
    methods: {
      select(id) {
        this.$bus.$emit('vipSidebar:select', id);
      }
    },
    components: {
      DashboardPageSidebar
    }
  }
</script>

<style lang="scss" scoped>
  .vipSidebar {
    .level {
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
