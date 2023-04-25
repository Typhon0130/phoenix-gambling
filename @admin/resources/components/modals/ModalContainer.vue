<template>
  <div class="modals" :class="modals.length === 0 ? 'empty' : ''" @click="closeModal(true)" @keydown.esc="closeModal(true)">
    <transition-group name="fade">
      <div class="modal" @click.stop :class="modal.name" :key="modal" v-for="modal in modals">
        <web-icon v-if="!modal.notDismissible" icon="fal fa-times" class="close" @click="closeModal"></web-icon>
        <div class="content">
          <component :is="modal.component"></component>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script>
  import Bus from "../../bus.js";
  import WebIcon from "../ui/WebIcon.vue";
  import OverlayScrollbars from 'overlayscrollbars';

  export default {
    data() {
      return {
        modals: []
      }
    },
    methods: {
      closeModal(userInput = false) {
        if(userInput && this.modals[0] && this.modals[0].notDismissible) return;
        this.modals.shift();
      }
    },
    created() {
      Bus.$on('modal:new', (modal) => {
        this.modals.push(modal);
        this.$nextTick(() => {
          OverlayScrollbars(document.querySelector('.modal.' + modal.name + ' .content'), {
            scrollbars: { autoHide: 'leave' },
            className: 'os-theme-thin-light'
          });
        });
      });
      Bus.$on('modal:close', () => this.closeModal());
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";
  @import "resources/sass/container";

  .modals {
    position: fixed;
    top: 0;
    z-index: 20;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(black, .5);
    backdrop-filter: blur(15px);
    opacity: 1;
    transition: opacity .3s ease;

    &.empty {
      pointer-events: none;
      opacity: 0;
    }

    .modal {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      z-index: 1;
      border-radius: 8px;
      max-width: 500px;
      min-width: 280px;

      .content {
        max-height: 570px;
      }

      @include min(0, bp('md')) {
        width: 100vw;
        min-width: unset;
        border-radius: 0;
      }

      .close {
        position: absolute;
        z-index: 5;
        opacity: .6;
        transition: opacity .3s ease;
        cursor: pointer;
        font-size: 16px;
        right: 15px;
        top: 15px;

        &:hover {
          opacity: 1;
        }
      }

      @include themed() {
        background: t('background');
      }
    }
  }
</style>
