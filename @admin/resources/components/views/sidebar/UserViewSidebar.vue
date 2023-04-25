<template>
  <dashboard-page-sidebar class="userSidebar">
    <template v-if="user">
      <div class="data">
        <img :src="user.user.avatar" alt>
        {{ user.user.name }}
      </div>
      <div class="category">
        <div class="title">Name History</div>
        <div class="entry" v-for="history in user.user.name_history" :key="history">
          <div>{{ history['name'] }}</div>
          <div>{{ new Date(history['time']).toLocaleString() }}</div>
        </div>
      </div>
      <div class="title">Info</div>

      <div class="category">
        <div class="entry">
          <div>{{ user.user.register_ip }}</div>
          <div>IP (Registered)</div>
        </div>
        <div class="entry">
          <div>{{ user.user.register_ip }}</div>
          <div>IP (Authorized)</div>
        </div>
        <div class="entry">
          <div>{{ new Date(user.user.created_at).toLocaleString() }}</div>
          <div>Registered at</div>
        </div>
        <div class="entry">
          <div>{{ new Date(user.user.login_date).toLocaleString() }}</div>
          <div>Authorized at</div>
        </div>
      </div>

      <div class="title">Fraud Risk</div>

      <multi-accounts :id="user.user._id"></multi-accounts>

      <template v-if="$permission.checkPermission('*')">
        <div class="title">Roles</div>
        <select @change="$event.target.value === '-' ? false : toggleRole($event.target.value)">
          <option value="-" disabled selected>...</option>
          <option :value="role.id" v-for="role in roles" :key="role">{{ user.user.roles.filter(e => e.id === role.id).length === 0 ? 'Add' : 'Remove' }} - {{ role.name }}</option>
        </select>
      </template>

      <div class="title" v-if="$permission.checkPermission('users', 'delete') || $permission.checkPermission('users', 'edit')">Manage</div>
      <template v-if="$permission.checkPermission('users', 'delete')">
        <div><button :class="$isDemo ? 'demoDisable' : ''" class="btn btn-primary" @click="ban(user.user._id)">{{ user.user.ban ? 'Unban' : 'Ban' }}</button></div>
        <div style="margin-top: 10px"><button class="btn btn-primary" :class="$isDemo ? 'demoDisable' : ''" @click="setVIPLevel" v-if="$permission.checkPermission('users', 'edit')">Set VIP level</button></div>
      </template>
    </template>
  </dashboard-page-sidebar>
</template>

<script>
  import DashboardPageSidebar from "./DashboardPageSidebar.vue";
  import MultiAccounts from "../../ui/MultiAccounts.vue";

  export default {
    data() {
      return {
        user: null,
        roles: null
      }
    },
    created() {
      this.$bus.$on('userSidebar:setData', (data) => {
        this.user = data;
        this.$bus.$emit('sidebarLoading:done');
      });

      if(this.$permission.checkPermission('*')) {
        window.axios.post('/admin/roles/all').then(({ data }) => this.roles = data.roles);
      }
    },
    methods: {
      setVIPLevel() {
        const vipLevel = prompt('User won\'t be able to upgrade his VIP level naturally. One time bonus will be available.\nEnter VIP level from 0 to 10 (-1 to revert the changes):');
        if(vipLevel) {
          let level = parseInt(vipLevel);
          if(level < -1 || level > 10) return alert('Invalid VIP level. Accepted: 0 - 10 or -1');
          window.axios.post('/admin/forceVip', {
            level: level,
            id: this.info.user._id
          }).then(() => this.$toast.success('Success')).catch(() => alert('Failed to set VIP level'));
        }
      },
      ban(id) {
        this.user.user.ban = !this.user.user.ban;
        window.axios.post('/admin/ban', { id: id });
      },
      toggleRole(id) {
        if(id === '*' && this.user.user._id === id)
          if(!confirm('Are you sure? You will remove super admin role (*) from yourself!')) return;

        window.axios.post('/admin/roles/toggleRole', {
          userId: this.user.user._id,
          roleId: id
        }).then(() => {
          window.location.reload();
        });
      }
    },
    components: {
      MultiAccounts,
      DashboardPageSidebar
    }
  }
</script>

<style lang="scss" scoped>
  .userSidebar {
    .title {
      font-size: 1.2em;
      margin-bottom: 15px;
      margin-top: 25px;
    }

    .category {
      .entry {
        margin-bottom: 10px;

        div:last-child {
          opacity: .6;
          font-size: .8em;
          margin-top: 5px;
        }

        &:last-child {
          margin-bottom: 0;
        }
      }
    }

    .data {
      display: flex;
      align-items: center;
      margin-bottom: 15px;

      img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 15px;
      }
    }
  }
</style>
