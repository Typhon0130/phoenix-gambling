<template>
  <div></div>
</template>

<script>
  import { mapGetters } from 'vuex';

  export default {
    created() {
      window.$permission = this;
      window.$app.config.globalProperties.$permission = this;
    },
    computed: {
      ...mapGetters(['user', 'license'])
    },
    methods: {
      /**
       * @param permissionId
       * @param checkType active / create / delete / view
       */
      checkPermission(permissionId, checkType = 'active') {
        if(window.$isDemo) return true;

        if(!this.user) return false;
        if(this.user.user.roles.filter(e => e.id === '*').length > 0) return true;

        let flag = false;

        this.user.user.permissions.forEach(role => {
          role.permissions.forEach(permission => {
            if(!flag && permission.id === permissionId) {
              flag = permission.permissions[checkType];
            }
          });
        });

        return flag;
      },
      isFeatureAvailable(feature) {
        if(!this.license || !this.license.enabledFeatures) return false;
        return !!this.license.enabledFeatures.filter(e => e.id === feature)[0];
      }
    }
  }
</script>
