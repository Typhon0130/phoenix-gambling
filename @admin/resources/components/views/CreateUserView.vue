<template>
  <div class="createUser animate" v-if="roles">
    <div class="type">
      <div class="c1">
        <div class="smTitle">
          Account
        </div>
        <div class="edit">
          <input type="text" v-model="username" placeholder="Login">
          <input type="text" v-model="email" placeholder="Email">
          <input type="text" v-model="password" placeholder="Password">
          <button class="btn btn-primary" @click="createUser" :disabled="password.length === 0 || email.length === 0 || username.length === 0 || $isDemo">Create</button>
        </div>
      </div>
      <div class="c2">
        <div class="smTitle">
          Roles
        </div>

        <div class="preview">
          <div v-for="role in roles" :key="role.id">
            <button class="btn" :class="pickedRoles.includes(role.id) ? 'btn-primary' : ''" @click="pickedRoles.includes(role.id) ? pickedRoles = pickedRoles.filter(e => e !== role.id) : pickedRoles.push(role.id)">
              <web-icon :icon="pickedRoles.includes(role.id) ? 'fal fa-fw fa-times' : 'fal fa-fw fa-plus'"></web-icon> {{ role.name }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";

  export default {
    data() {
      return {
        roles: null,

        username: '',
        email: '',
        password: '',

        pickedRoles: []
      }
    },
    methods: {
      createUser() {
        window.axios.post('/admin/createUser', {
          email: this.email,
          name: this.username,
          password: this.password,
          roles: this.pickedRoles
        }).then(() => {
          this.$toast.success('Created successfully');
          this.username = '';
          this.email = '';
          this.password = '';
        }).catch((e) => {
          const errors = e.response.data.errors;
          Object.keys(errors).forEach(key => {
            const values = errors[key];
            switch (key) {
              case 'email': {
                values.forEach(value => {
                  if (value === 'validation.email')
                    this.$toast.error('Invalid email');
                  else if (value === 'validation.unique')
                    this.$toast.error('This email is already registered');
                });
                break;
              }
              case 'name': {
                values.forEach(value => {
                  if (value === 'validation.regex')
                    this.$toast.error('Login has less than 4 characters or contains invalid symbols');
                  else if (value === 'validation.unique')
                    this.$toast.error('This login is already registered, pick something else');
                });
                break;
              }
              case 'password': {
                values.forEach(value => {
                  if (value === 'validation.min.string')
                    this.$toast.error('Password should have at least 5 characters');
                });
                break;
              }
            }
          });
        });
      }
    },
    created() {
      window.axios.post('/admin/roles/all').then(({ data }) => {
        this.roles = data.roles;
        this.$bus.$emit('loading:done');
      });
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .createUser {
    .smTitle {
      font-size: 1.1em;
      font-weight: 400;
      margin-bottom: 15px;
    }

    .type {
      display: flex;
      margin-top: 30px;

      @include min(0, bp('md')) {
        flex-direction: column;
      }

      .c1 {
        margin-right: 30px;
        width: 20%;
        min-width: 200px;
        margin-bottom: 25px;

        @include min(0, bp('md')) {
          width: 100%;
          margin-right: 0;
        }

        .edit {
          display: flex;
          flex-direction: column;

          input {
            margin-bottom: 15px;
          }

          .btn {
            margin-top: 15px;
            width: 90px;
          }
        }
      }

      .c2 {
        .preview {
          div {
            margin-bottom: 15px;

            &:last-child {
              margin-bottom: 0;
            }
          }
        }
      }
    }
  }
</style>
