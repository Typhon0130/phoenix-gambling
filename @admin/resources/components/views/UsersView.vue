<template>
  <div class="users">
    <div class="animate">
      <div class="search" v-if="loadedOnce">
        <web-icon icon="fal fa-fw fa-search"></web-icon>
        <input type="text" placeholder="Filter" v-model="search">
        <button class="btn btn-primary" @click="exportXLSX">Export as .xlsx</button>
        <button class="btn btn-primary" @click="$router.push('/admin/createUser')" v-if="$permission.checkPermission('users', 'create')">Create</button>
      </div>
      <div class="entries">
        <template v-if="users">
          <div class="h">
            <div>Name</div>
            <div>Email</div>
            <div>VIP Level</div>
            <div>Deposited</div>
            <div>Withdrawn</div>
            <div>Registered</div>
          </div>
          <div class="entry" v-for="user in users.users" :key="user._id" @click="$router.push('/admin/user/' + user._id)">
            <div>
              <img alt :src="user.avatar"> {{ user.name }}
            </div>
            <div>
              {{ user.email }}
            </div>
            <div>
              {{ user.vipLevel }}
            </div>
            <div>
              ${{ user.depositedTotal.toFixed(2) }}
            </div>
            <div>
              ${{ user.withdrawnUsdTotal.toFixed(2) }}
            </div>
            <div>
              {{ new Date(user.created_at).toLocaleString() }}
            </div>
          </div>
        </template>
      </div>
      <div class="pagination" v-if="maxPages">
        <div class="prev" @click="page -= 2" v-if="page - 2 >= 1">{{ page - 2 }}</div>
        <div class="prev" @click="page--" v-if="page - 1 >= 1">{{ page - 1 }}</div>
        <div class="current">{{ page }}</div>
        <div class="next" @click="page++" v-if="page + 1 <= maxPages">{{ page + 1 }}</div>
        <div class="next" @click="page += 2" v-if="page + 2 <= maxPages">{{ page + 2 }}</div>
        <div class="jump" @click="jump"><web-icon icon="fal fa-fw fa-ellipsis-h"></web-icon></div>
      </div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import zipcelx from 'zipcelx';
  import OverlayScrollbars from 'overlayscrollbars';

  export default {
    data() {
      return {
        users: null,
        page: 1,
        search: '',
        loadedOnce: false,
        maxPages: null
      }
    },
    watch: {
      page() {
        this.load();
      },
      search() {
        let r = Math.random();
        window.$usersR = r;

        if(this.search.length === 0) {
          this.users = null;
          this.page = 1;
          this.load();
          return;
        }

        this.page = 1;
        this.users = null;

        window.axios.post('/admin/searchUsers', { search: this.search }).then(({ data }) => {
          if(window.$usersR !== r) return;
          this.users = { users: data };
        });
      }
    },
    mounted() {
      OverlayScrollbars(document.querySelector('.users .entries'), {
        scrollbars: { autoHide: 'leave' },
        className: 'os-theme-thin-light'
      });
    },
    methods: {
      jump() {
        const page = parseInt(prompt(`Enter page (from 1 to ${this.maxPages}):`));
        if(page && !isNaN(page) && page >= 1 && page <= this.maxPages) {
          this.page = page;
        } else alert('Invalid page: ' + page);
      },
      load() {
        window.axios.post('/admin/users/' + this.page).then(({ data }) => {
          if(!this.loadedOnce) {
            this.$bus.$emit('loading:done');
            this.loadedOnce = true;
          }

          if(this.users === null) this.users = [];

          this.users = data;
          this.maxPages = data.maxPages;
        });
      },
      exportXLSX() {
        try {
          let config = {
            filename: 'user-list-exported' + (this.search.length === 0 ? '' : '-' + this.search.replaceAll(" ", '-')),
            sheet: {
              data: [
                [
                  {
                    value: 'Username',
                    type: 'string'
                  },
                  {
                    value: 'Email',
                    type: 'string'
                  },
                  {
                    value: 'VIP level',
                    type: 'string'
                  },
                  {
                    value: 'Deposited ($)',
                    type: 'string'
                  },
                  {
                    value: 'Withdrawn ($)',
                    type: 'string'
                  },
                  {
                    value: 'Registered',
                    type: 'string'
                  }
                ]
              ]
            }
          };
          this.users.forEach(user => {
            let row = [];
            row.push({
              value: user.name,
              type: 'string'
            });
            row.push({
              value: user.email,
              type: 'string'
            });
            row.push({
              value: user.vipLevel,
              type: 'number'
            });
            row.push({
              value: user.depositedTotal.toFixed(2),
              type: 'string'
            });
            row.push({
              value: user.withdrawnUsdTotal.toFixed(2),
              type: 'string'
            });
            row.push({
              value: new Date(user.created_at).toLocaleString(),
              type: 'string'
            });
            config.sheet.data.push(row);
          });
          zipcelx(config);
        } catch (e) {
          this.$toast.error('Failed to export');
        }
      }
    },
    created() {
      this.load();
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .users {
    .pagination {
      font-size: 16px;
      margin-top: 25px;
      display: flex;

      .current {
        @include themed() {
          color: t('secondary');
          font-weight: 400;
        }
      }

      div {
        margin-right: 5px;
        cursor: pointer;
        font-weight: 100;

        &:last-child {
          margin-right: 0;
        }
      }
    }

    .entries {
      display: flex;
      flex-direction: column;
      margin-top: 15px;

      @include min(0, bp('md')) {
        width: calc(100vw - 140px);
      }

      width: calc(100vw - 350px);

      .h, .entry {
        min-width: 1000px;
      }

      .h div, .entry div {
        width: 100%;
        flex: 1;
        margin-right: 10px;
        white-space: nowrap;
      }

      .h {
        display: flex;
        font-size: 1.1em;
        margin-bottom: 20px;
        margin-top: 10px;
      }

      .entry {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        cursor: pointer;

        div {
          display: flex;
          align-items: center;

          img {
            margin-right: 10px;
            width: 32px;
            height: 32px;
          }
        }
      }
    }

    .search {
      position: relative;

      i {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        left: 20px;
      }

      input {
        padding-left: 55px;
        margin-right: 15px;
      }

      .btn {
        margin-right: 15px;
      }

      @include min(0, bp('md')) {
        i {
          display: none;
        }

        input {
          margin-bottom: 15px;
          padding: 15px 20px;
        }

        .btn {
          margin-bottom: 15px;
        }
      }
    }
  }
</style>
