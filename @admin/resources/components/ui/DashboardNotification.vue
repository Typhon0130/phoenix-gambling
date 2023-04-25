<template>
  <div class="dashboardNotification">
    <div class="notification" v-for="notification in notifications" :key="notification">
      <template v-if="notification.type === 'save'">
        <web-icon :icon="notification.icon" v-if="notification.icon"></web-icon>
        {{ notification.text }}
        <div class="buttons">
          <web-icon icon="fal fa-fw fa-check" @click="notification.yes ? notification.yes() : false; notifications = notifications.filter(e => e !== notification)"></web-icon>
          <web-icon icon="fal fa-fw fa-times" @click="notification.no ? notification.no() : false; notifications = notifications.filter(e => e !== notification)"></web-icon>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
  import WebIcon from "./WebIcon.vue";

  export default {
    data() {
      return {
        notifications: []
      }
    },
    methods: {
      canSwitch() {
        const canSwitch = this.notifications.length > 0;
        if(canSwitch) this.shake();
        return canSwitch;
      },
      shake() {
        const body = document.querySelector('body');
        if(body.classList.contains("shake")) return;

        document.body.scrollTo({ top: 0 });
        document.body.style.overflowY = 'hidden';
        body.classList.toggle('shake', true);

        setTimeout(() => {
          document.body.style.overflowY = 'auto';
          body.classList.toggle('shake', false);
        }, 400);
      }
    },
    mounted() {
      window.$dashboardNotifications = this;

      this.$bus.$on('notifications:add', (data) => {
        this.notifications.push(data);
      });
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .dashboardNotification {
    z-index: 11;
    position: fixed;
    bottom: 30px;
    right: 60px;

    .notification {
      display: flex;
      margin-bottom: 15px;
      padding: 15px 25px;
      border-radius: 100px;
      align-items: center;
      font-weight: 600;
      position: relative;

      .buttons {
        margin-left: 10px;

        i {
          opacity: .6;
          cursor: pointer;
          transition: opacity .3s ease;

          &:hover {
            opacity: 1;
          }

          @include themed() {
            color: t('text');
          }
        }
      }

      i {
        margin-right: 8px;
        margin-top: 2px;
      }

      @include themed() {
        border: 2px solid t('criticalBorder');
        color: t('criticalColor');
        background: t('background');
      }

      &:last-child {
        margin-bottom: 0;
      }
    }
  }
</style>
