<template>
  <div class="dashboardConfig" :class="show ? 'show' : ''">
    <transition name="fade">
      <div class="backdrop" @click="show = false"></div>
    </transition>
    <div class="side" :class="show ? 'show' : ''">
      <div class="os">
        <div class="category">
          Theme
        </div>
        <div class="themes">
          <div class="theme" @click="setTheme('dark')">
            <div class="preview"></div>
            <div class="name">Dark</div>
          </div>
          <div class="theme" @click="setTheme('lightDark')">
            <div class="preview"></div>
            <div class="name">Gray</div>
          </div>
          <div class="theme" @click="setTheme('light')">
            <div class="preview"></div>
            <div class="name">Light</div>
          </div>
        </div>
        <a class="logout" href="javascript:void(0)" @click="logout">Logout</a>
      </div>
    </div>
  </div>
</template>

<script>
  import OverlayScrollbars from 'overlayscrollbars';

  export default {
    data() {
      return {
        show: false
      }
    },
    methods: {
      setTheme(id) {
        this.show = false;
        this.$store.dispatch('switchTheme', id);
      },
      logout() {
        window.axios.get('/auth/logout').then(() => {
          localStorage.clear();
          window.location.href = '/';
        });
      }
    },
    created() {
      this.$bus.$on('dashboardConfig:toggle', () => this.show = !this.show);
    },
    mounted() {
      OverlayScrollbars(document.querySelector('.dashboardConfig .os'), {
        scrollbars: { autoHide: 'leave' },
        className: 'os-theme-thin-light'
      });
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .dashboardConfig {
    position: fixed;
    z-index: 12;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    pointer-events: none;

    .backdrop {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      backdrop-filter: blur(15px);
      background: rgba(black, .6);
      opacity: 0;
      transition: opacity .3s ease;
    }

    &.show {
      pointer-events: unset;

      .backdrop {
        opacity: 1;
      }
    }

    .os {
      height: 100%;
    }

    .side {
      position: absolute;
      top: 0;
      width: 300px;
      height: 100%;
      padding: 40px 50px;
      right: -300px;
      transition: right .3s ease;

      &.show {
        right: 0;
      }

      .themes {
        width: 100%;
        margin-bottom: 30px;

        .theme {
          width: 100%;
          padding: 10px;
          border-radius: 10px;
          margin-bottom: 20px;
          cursor: pointer;
          position: relative;

          &:last-child {
            margin-bottom: 0;
          }

          @include themed() {
            background: t('block-2');
            transition: all .3s ease;

            &:hover {
              transform: scale(1.01);
            }
          }

          .preview {
            width: 100%;
            height: 140px;
            background-position: top left;
            background-repeat: no-repeat;
            background-size: 1140px 400px;
            border-radius: 10px;
          }

          .name {
            position: absolute;
            bottom: 25px;
            left: 25px;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: .9em;
            color: white;

            @include themed() {
              background: rgba(black, .5);
            }
          }

          &:nth-child(1) {
            .preview {
              background-image: url('/img/dashboard/themes/dark.png');
            }
          }

          &:nth-child(2) {
            .preview {
              background-image: url('/img/dashboard/themes/gray.png');
            }
          }

          &:nth-child(3) {
            .preview {
              background-image: url('/img/dashboard/themes/light.png');
            }
          }
        }
      }

      .category {
        font-size: 1.3em;
        margin-bottom: 25px;
      }

      @include themed() {
        background: t('sidebar');
      }
    }
  }
</style>
