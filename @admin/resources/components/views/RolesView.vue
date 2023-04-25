<template>
  <div class="roles animate" v-if="roles">
    <role-block v-if="role" :role="roles.roles.filter(e => e.id === role)[0]" :permissions="roles.allPermissions"
      :role-update="(e) => e"></role-block>
  </div>
</template>

<script>
  import RoleBlock from "../ui/RoleBlock.vue";
  import { withSidebar } from "../../utils/pageSidebar.js";

  export default {
    data() {
      return {
        roles: null,
        role: null
      }
    },
    created() {
      withSidebar(() => {
        this.loadRoles(true);

        this.$bus.$on('rolesSidebar:select', (id) => {
          this.role = id;
        });

        this.$bus.$on('rolesSidebar:update', this.loadRoles);
      });
    },
    watch: {
      roles() {
        this.$bus.$emit('rolesSidebar:setData', this.roles);
      }
    },
    methods: {
      loadRoles(select = false) {
        this.roles = null;
        window.axios.post('/admin/roles/all').then(({ data }) => {
          this.roles = data;
          this.$bus.$emit('loading:done');
          if(select) this.$bus.$emit('rolesSidebar:select', data.roles[0].id);
        });
      }
    },
    components: {
      RoleBlock
    }
  }
</script>
