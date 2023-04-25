<script>
  import Bus from '../../bus.js';

  export default {
    methods: {
      open(user) {
        Bus.$emit('modal:new', {
          name: 'user',
          component: {
            data() {
              return {
                userId: user,
                info: null
              }
            },
            created() {
              window.axios.post('/api/profile/getUser', {id: this.userId}).then(({data}) => {
                this.info = data;
              });
            },
            methods: {
              visitProfile() {
                Bus.$emit('modal:close');
                this.$router.push('/profile/' + this.info.user._id);
              },
              visitProfileAdmin() {
                window.open('/admin/user/' + this.info.user._id, '_blank');
              }
            },
            template: `
              <loader v-if="!info"></loader>
              <div class="profile" v-else>
                <div class="profile-header">
                  <div class="avatar" :style="{ backgroundImage: 'url(' + info.user.avatar + ')' }"></div>
                  <div class="name">
                    {{ info.user.name }}
                    <i class="fal fa-external-link" @click="visitProfile"></i>
                    <i class="fal fa-cog" v-if="$checkPermission('users')" @click="visitProfileAdmin"></i>
                  </div>
                  <div class="date">{{ $t('userModal.joined', {date: new Date(info.user.created_at).toLocaleString()}) }}
                </div>
              </div>
              <div class="profile-stats">
                <div class="stat">
                  <div>{{ $t('userModal.wagered') }}</div>
                  <div>$ {{ info.wagered.toFixed(2) }}</div>
                </div>
                <div class="stat">
                  <div>{{ $t('userModal.totalGames') }}</div>
                  <div>{{ info.games }}</div>
                </div>
              </div>
              </div>`
          }
        });
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";
  @import "resources/sass/themes";

  .xmodal.user {
    max-width: 350px;

    .modal_content {
      padding: 0 !important;

      .profile-stats {
        display: flex;

        .stat {
          margin: 10px;
          width: calc(100% - 20px - 1px);

          @include themed() {
            border: 1px solid t('body');
          }

          padding: 20px;
          text-align: center;

          div:first-child {
            opacity: .6;
          }

          div:last-child {
            font-weight: 600;
          }
        }
      }

      .profile-header {
        @include themed() {
          background: t('body');
        }
        display: flex;
        flex-direction: column;

        .avatar {
          background-size: cover;
          background-position: center;
          background-repeat: no-repeat;
          width: 50px;
          height: 50px;
          margin: auto;
          margin-top: 30px;
          margin-bottom: 15px;
          border-radius: 50%;
        }

        .name {
          font-weight: 600;
          text-align: center;
          font-size: 1.1em;

          i {
            margin-left: 5px;
            cursor: pointer;
            opacity: .7;
            transition: opacity .3s ease;

            &:hover {
              opacity: 1;
            }
          }
        }

        .date {
          text-align: center;
          font-size: .9em;
          margin-bottom: 15px;
        }
      }
    }

    .loader {
      margin: auto;
    }
  }
</style>
