<template>
  <div class="fraud" v-if="info">
    <template v-if="!info.user.register_multiaccount_hash || !info.user.login_multiaccount_hash">
      <div class="f text-danger" v-if="!info.user.register_multiaccount_hash">Registered with clean cookies</div>
      <div class="f text-danger" v-if="!info.user.login_multiaccount_hash">Authorized with clean cookies</div>
    </template>
    <div class="f" v-if="info.same_register_hash.length <= 1 && info.same_login_hash.length <= 1 && info.same_register_ip.length <= 1 && info.same_login_ip.length <= 1">
      -
    </div>
    <template v-else>
      <div class="f" v-if="info.same_register_hash.length > 1 && info.user.register_multiaccount_hash">
        <div class="text-danger">Same registration hash:</div>
        <router-link :to="`/admin/user/${user._id}`" v-for="user in info.same_register_hash" :key="user">
          {{ user.name }}</router-link>
      </div>
      <div class="f" v-if="info.same_login_hash.length > 1 && info.user.login_multiaccount_hash">
        <div class="text-danger">Same auth hash:</div>
        <router-link :to="`/admin/user/${user._id}`" v-for="user in info.same_login_hash" :key="user">
          {{ user.name }}</router-link>
      </div>
      <div class="f" v-if="info.same_register_ip.length > 1">
        <div class="text-danger">Same registration IP:</div>
        <div v-for="user in info.same_register_ip" :key="user">
          <router-link :to="`/admin/user/${user._id}`">{{ user.name }}</router-link>
        </div>
      </div>
      <div class="f" v-if="info.same_register_ip.length > 1">
        <div class="text-danger">Same auth IP:</div>
        <div v-for="user in info.same_login_ip" :key="user">
          <router-link :to="`/admin/user/${user._id}`">{{ user.name }}</router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
  export default {
    props: ['id'],
    data() {
      return {
        info: null
      }
    },
    created() {
      window.axios.post('/admin/checkDuplicates', {id: this.id}).then(({data}) => this.info = data);
    }
  }
</script>

<style lang="scss" scoped>
  .fraud {
    font-size: .9em;
    display: flex;
    flex-direction: column;

    .text-danger {
      color: #ff7a7a;
    }

    .f {
      margin-bottom: 10px;

      div {
        margin-bottom: 5px;

        &:last-child {
          margin-bottom: 0;
        }
      }
    }
  }
</style>
