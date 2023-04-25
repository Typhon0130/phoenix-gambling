<template>
  <div class="pgManager" :class="disabled ? 'disable' : ''">
    <div class="pgHeader" @click="show = !show">
      <i :class="`fal fa-chevron-${show ? 'down' : 'up'}`"></i>
      Phoenix Console
    </div>
    <div class="pgContent" :class="show ? 'active' : ''">
      <overlay-scrollbars :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }">
        <div class="category">Sports</div>
        <div class="toggle">
          <div class="name">Show unsupported runners & markets</div>
          <div class="sw"><web-switch :value="phoenixShowUnavailableRunners" :onChange="() => { this.$store.dispatch('phoenixConsoleToggle', 'showUnavailableRunners') }"></web-switch></div>
        </div>
        <div class="toggle">
          <div class="name">Show esport game stats</div>
          <div class="sw"><web-switch :value="phoenixShowEsportStats" :onChange="() => { this.$store.dispatch('phoenixConsoleToggle', 'showEsportStats') }"></web-switch></div>
        </div>
        <div class="divider"></div>
        <div>
          <small>Press <b>P</b> to hide Phoenix Console</small>
        </div>
      </overlay-scrollbars>
    </div>
  </div>
</template>

<script>
  import { mapGetters } from 'vuex';

  export default {
    data() {
      return {
        show: false,
        disabled: false
      }
    },
    computed: {
      ...mapGetters(['phoenixShowUnavailableRunners', 'phoenixShowEsportStats'])
    },
    created() {
      window.addEventListener('keydown', (e) => {
        if(e.key.toLowerCase() === 'p') {
          this.show = false;
          this.disabled = !this.disabled;
        }
      })
    }
  }
</script>

<style lang="scss" scoped>

  .pgManager {
    position: fixed;
    z-index: 999999;
    bottom: 0;
    right: 50px;
    display: flex;
    flex-direction: column;
    background: #24262b;
    width: 250px;

    &.disable {
      opacity: .2;
      pointer-events: none;
    }

    .pgHeader {
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      cursor: pointer;
      padding: 10px 25px;
      background: #1c1e22;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
      border: 2px solid #ff4848;
      border-bottom: none;

      i {
        margin-right: 10px;
      }
    }

    .pgContent {
      display: none;
      border: 2px solid #ff4848;
      border-top: none;
      border-bottom: none;
      padding: 10px 15px;

      :deep(.os-content) {
        max-height: 300px;
      }

      .category {
        margin-bottom: 10px;
        text-transform: uppercase;
        font-weight: 600;
      }

      .toggle {
        display: flex;
        margin-bottom: 10px;

        .sw {
          margin-left: auto;
        }

        .name {
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: .9em;
          opacity: .8;
        }
      }

      .divider {
        margin-top: 15px;
      }

      &.active {
        display: unset;
      }
    }
  }
</style>
