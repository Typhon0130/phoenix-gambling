<template>
  <div class="role">
    <div class="roleTitle">{{ role.name }}</div>

    <template v-if="role.readOnly">
      <dashboard-warning type="info">
        This role can't be edited or removed.
      </dashboard-warning>
      <dashboard-warning type="info" v-if="role.id === 'chat_moderator'">
        This role enables blue border for user's chat messages and ability to moderate chat messages.
      </dashboard-warning>
    </template>
    <template v-else v-for="permission in permissions">
      <div class="permission" v-if="permission.id !== '*'" :key="role.id + permission">
        <div class="name">{{ permission.name }}</div>
        <div class="description">{{ permission.description }}</div>

        <div class="type">
          <div><dashboard-switch :value="hasPermission(role, permission.id, 'active')" :onChange="() => togglePermission(permission.id, 'active')"></dashboard-switch></div>
          <div>Allow</div>
        </div>
        <div class="type" v-if="permission.isEditable">
          <div><dashboard-switch :value="hasPermission(role, permission.id, 'edit')" :onChange="() => togglePermission(permission.id, 'edit')"></dashboard-switch></div>
          <div>Edit</div>
        </div>
        <div class="type" v-if="permission.isCreatable">
          <div><dashboard-switch :value="hasPermission(role, permission.id, 'create')" :onChange="() => togglePermission(permission.id, 'create')"></dashboard-switch></div>
          <div>Create</div>
        </div>
        <div class="type" v-if="permission.isDeletable">
          <div><dashboard-switch :value="hasPermission(role, permission.id, 'delete')" :onChange="() => togglePermission(permission.id, 'delete')"></dashboard-switch></div>
          <div>Delete</div>
        </div>
      </div>
    </template>

    <button class="btn btn-primary removeRole" v-if="!role.readOnly" @click="removeRole(role.id)" :class="$isDemo ? 'demoDisable' : ''">Remove</button>
  </div>
</template>

<script>
  import DashboardSwitch from "./interactive/DashboardSwitch.vue";
  import DashboardWarning from "./DashboardWarning.vue";

  export default {
    data() {
      return {
        edit: false
      }
    },
    methods: {
      hasPermission(role, id, type) {
        let flag = false;

        this.role.permissions.forEach(permission => {
          if(permission.id === id) flag = permission.permissions[type];
        });

        return flag;
      },
      togglePermission(id, type) {
        const permission = this.permissions.filter(e => e.id === id)[0];
        let permissions = this.role.permissions;

        const updateServerSide = (roleId, permissionId, type, state) => {
          window.axios.post('/admin/roles/update', {
            roleId: roleId,
            permissionId: permissionId,
            type: type,
            state: state
          }).catch(() => this.$toast.error('Failed to update role permissions'));
        };

        if(!permissions.filter(e => e.id === id)[0]) {
          let copy = JSON.parse(JSON.stringify(permission));
          copy.permissions[type] = !copy.permissions[type];
          permissions.push(copy);

          updateServerSide(this.role.id, copy.id, type, copy.permissions[type]);
        } else {
          const e = permissions.filter(e => e.id === id)[0];
          e.permissions[type] = !e.permissions[type];

          updateServerSide(this.role.id, e.id, type, e.permissions[type]);
        }

        this.roleUpdate(permissions);
      },
      removeRole(id) {
        if(confirm('Are you sure?'))
          window.axios.post('/admin/roles/remove', {
            id: id
          }).then(() => this.$router.go());
      },
      save() {
        this.edit = false;
      }
    },
    components: {
      DashboardWarning,
      DashboardSwitch
    },
    props: ['role', 'permissions', 'roleUpdate']
  }
</script>

<style lang="scss" scoped>
  .role {
    .roleTitle {
      font-size: 1.4em;
      margin-bottom: 20px;
    }

    .permission {
      margin-bottom: 25px;

      .name {
        font-weight: 400;
        margin-bottom: 10px;
        font-size: 1.1em;
      }

      .description {
        margin-bottom: 15px;
      }

      .type {
        display: flex;
        align-items: center;
        margin-bottom: 15px;

        &:last-child {
          margin-bottom: 0;
        }

        div:first-child {
          margin-right: 7px;
        }
      }
    }
  }
</style>
