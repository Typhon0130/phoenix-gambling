<template>
  <dashboard-page-sidebar class="rolesSidebar">
    <button class="btn btn-primary" :class="$isDemo ? 'demoDisable' : ''" @click="createRole">Create</button>
    <template v-if="roles">
      <div class="role" v-for="role in roles.roles" :key="role"
           :class="selected === role.id ? 'active' : ''" @click="select(role.id)">
        {{ role.name }}
      </div>
    </template>
  </dashboard-page-sidebar>
</template>

<script>
  import DashboardPageSidebar from "./DashboardPageSidebar.vue";

  export default {
    data() {
      return {
        roles: null,
        selected: null
      }
    },
    created() {
      this.$bus.$on('rolesSidebar:setData', (data) => {
        this.roles = data;
        this.$bus.$emit('sidebarLoading:done');
      });
      this.$bus.$on('rolesSidebar:select', (data) => this.selected = data);
    },
    methods: {
      select(id) {
        this.$bus.$emit('rolesSidebar:select', id);
      },
      createRole() {
        const name = prompt('Enter role name:');
        if(!name) return;

        window.axios.post('/admin/roles/new', {
          name: name,
          id: name.toLowerCase().replaceAll(' ', '_')
        }).then(() => this.$bus.$emit('rolesSidebar:update'));
      }
    },
    components: {
      DashboardPageSidebar
    }
  }
</script>

<style lang="scss" scoped>
  .rolesSidebar {
    .btn {
      margin-bottom: 30px;
    }

    .role {
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
