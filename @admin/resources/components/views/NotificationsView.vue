<template>
  <div class="notifications animate" v-if="notifications">
    <div class="entries">
      <div class="h">
        <div>Text</div>
        <div v-if="$permission.checkPermission('notifications', 'delete')">Actions</div>
      </div>
      <template v-if="notifications.global.length === 0">
        No data to show.
      </template>
      <div class="entry" v-for="notification in notifications.global" :key="notification">
        <div>
          <web-icon :icon="notification.icon" style="margin-right: 10px" v-tooltip="notification.icon"></web-icon>
          <div v-html="notification.text"></div>
        </div>
        <div v-if="$permission.checkPermission('notifications', 'delete')">
          <button class="btn btn-primary" @click="remove(notification._id)"
            :class="$isDemo ? 'demoDisable' : ''">
            <web-icon icon="fal fa-fw fa-times"></web-icon> Delete
          </button>
        </div>
      </div>
    </div>
    <button class="btn btn-primary createBtn" :class="$isDemo ? 'demoDisable' : ''" @click="sendGlobal" v-if="$permission.checkPermission('notifications', 'create')"><web-icon icon="fal fa-fw fa-plus"></web-icon> Create</button>
  </div>
</template>

<script>
  import OverlayScrollbars from 'overlayscrollbars';
  import WebIcon from "../ui/WebIcon.vue";

  export default {
    data() {
      return {
        notifications: null
      }
    },
    methods: {
      remove(id) {
        window.axios.post('/admin/notifications/global_remove', { id: id }).then(() => this.load());
      },
      sendGlobal() {
        const text = prompt('Notification text:');
        if(!text) return;
        const icon = prompt('Notification icon:', 'fal fa-fw fa-exclamation-triangle');
        if(!icon) return;

        window.axios.post('/admin/notifications/global', {
          icon: icon,
          text: text
        }).then(() => this.load());
      },
      load(animate = false) {
        window.axios.post('/admin/notifications/data').then(({ data }) => {
          this.notifications = data;

          if(animate) {
            this.$bus.$emit('loading:done');
            this.$nextTick(() => {
              OverlayScrollbars(document.querySelector('.notifications .entries'), {
                scrollbars: {autoHide: 'leave'},
                className: 'os-theme-thin-light'
              });
            });
          }
        });
      }
    },
    components: {
      WebIcon
    },
    created() {
      this.load(true);
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";

  .notifications {
    .createBtn {
      margin-top: 15px;
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

        button {
          margin-right: 10px;

          &:last-child {
            margin-right: 0;
          }
        }
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
        }
      }
    }
  }
</style>
