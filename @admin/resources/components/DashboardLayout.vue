<template>
  <div class="layout">
    <permission-store></permission-store>

    <dashboard-configuration></dashboard-configuration>
    <dashboard-notification></dashboard-notification>
    <modal-container></modal-container>

    <dashboard-demo-notification></dashboard-demo-notification>

    <div class="pageContainer">
      <dashboard-sidebar></dashboard-sidebar>
      <div class="page">
        <dashboard-header></dashboard-header>
        <div class="pageContent" :class="$route.meta.sidebar ? 'noPadding' : ''">
          <template v-if="$route.meta.sidebar">
            <component :is="defineAsyncComponent($route.meta.sidebar)"></component>
            <div class="pageContentWithSidebar" :class="$route.meta.noPadding ? 'noPadding' : ''">
              <div class="pageTitle" v-if="$route.meta.title" v-html="$route.meta.title"></div>
              <router-view :key="$route.fullPath"></router-view>
            </div>
          </template>
          <template v-else>
            <div class="pageTitle" v-if="$route.meta.title" v-html="$route.meta.title"></div>
            <router-view :key="$route.fullPath"></router-view>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import PermissionStore from "./views/utils/PermissionStore.vue";
  import DashboardHeader from "./ui/DashboardHeader.vue";
  import DashboardSidebar from "./ui/DashboardSidebar.vue";
  import { gsap, Power3 } from 'gsap';
  import { defineAsyncComponent } from 'vue';
  import DashboardConfiguration from "./ui/DashboardConfiguration.vue";
  import DashboardNotification from "./ui/DashboardNotification.vue";
  import ModalContainer from "./modals/ModalContainer.vue";
  import DashboardDemoNotification from "./ui/DashboardDemoNotification.vue";

  export default {
    components: {
      DashboardDemoNotification,
      DashboardNotification,
      DashboardConfiguration,
      PermissionStore,
      DashboardSidebar,
      DashboardHeader,
      ModalContainer
    },
    setup() {
      return {
        defineAsyncComponent
      }
    },
    created() {
      this.$store.dispatch('updateData');
      this.$store.dispatch('switchTheme', this.$store.state.dashboardTheme);

      window.setTheme = (id) => {
        this.$store.dispatch('switchTheme', id);
      }

      this.$bus.$on('loading:done', () => {
        this.$nextTick(() => {
          const e = '.animate';

          if(/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
            gsap.set(e, { opacity: 1, top: 0 });
            return;
          }

          if (document.querySelector(e)) {
            gsap.set(e, {position: 'relative', opacity: 0, top: -100});
            gsap.to(e, {opacity: 1, top: 0, duration: 1, ease: Power3.easeOut});
          }
        });
      });

      this.$bus.$on('sidebarLoading:done', () => {
        this.$nextTick(() => {
          const e = '.pageSidebar';

          if(document.querySelector(e)) {
            setTimeout(() => {
              gsap.to(e, {opacity: 1, duration: 1, ease: Power3.easeOut});
            }, 150);
          }
        })
      });
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/variables";
  @import "resources/sass/container";

  .layout {
    height: calc(100vh - 30px);

    .pageContainer {
      display: flex;

      .page {
        display: flex;
        flex-direction: column;
        flex: 1;

        .pageContentWithSidebar {
          width: 100%;

          &:not(.noPadding) {
            padding: 40px 50px;
          }
        }

        .pageContent {
          padding: 40px 50px;
          height: 100%;
          display: flex;
          flex-direction: column;

          &.noPadding {
            padding: 0;

            @include min(676px) {
              flex-direction: unset;
            }
          }

          :deep(.pageTitle) {
            font-size: 3em;
            margin-bottom: 35px;
            display: flex;
            align-items: center;

            i {
              margin-left: 20px;
            }
          }
        }
      }
    }
  }
</style>
