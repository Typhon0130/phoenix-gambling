<template>
  <dashboard-page-sidebar class="dbSidebar">
    <template v-if="data">
      <template v-for="collection in data" :key="collection">
        <div class="collection" @click="select(collection)" :class="selected.name === collection.name ? 'active' : ''">
          <web-icon icon="fas fa-fw fa-folder"></web-icon>
          {{ collection.name }}
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
        selected: null,
        data: null
      }
    },
    computed: {
      pathLower() {
        return this.path.substring(0, this.path.lastIndexOf('/'));
      }
    },
    created() {
      this.$bus.$on('dbSidebar:setData', (data) => {
        this.data = data;
        this.$bus.$emit('db:select', data[0]);
      });
      this.$bus.$on('db:select', (data) => this.selected = data);
    },
    methods: {
      select(file) {
        this.$bus.$emit('db:select', file);
      }
    },
    components: {
      WebIcon,
      DashboardPageSidebar
    }
  }
</script>

<style lang="scss" scoped>
  .dbSidebar {
    .collection {
      margin-bottom: 15px;
      cursor: pointer;
      opacity: .6;
      transition: opacity .3s ease;

      &:hover, &.active {
        opacity: 1;
      }
    }
  }
</style>
