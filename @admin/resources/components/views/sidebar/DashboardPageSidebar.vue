<template>
  <div class="pageSidebar">
    <div class="os">
      <slot></slot>
      <div class="size"></div>
    </div>
  </div>
</template>

<script>
  import OverlayScrollbars from 'overlayscrollbars';

  export default {
    mounted() {
      OverlayScrollbars(document.querySelector('.pageSidebar .os'), {
        scrollbars: { autoHide: 'leave' },
        className: 'os-theme-thin-light'
      });

      this.$bus.$emit('pageSidebar:loaded', null);
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/variables";
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .pageSidebar {
    opacity: 0;
    width: 260px;
    flex-shrink: 0;

    @include min(bp('md')) {
      position: sticky;
      top: 102px;
      height: calc(100vh - 102px);
    }

    @include themed() {
      border-right: 2px solid t('border');

      @include min(0, 676px) {
        border-right: unset;
        border-bottom: 2px solid t('border');
        height: 200px;
        width: unset;
      }
    }

    .os {
      height: 100%;
      padding: 40px 50px;

      :deep(.os-content) {
        white-space: nowrap;
      }

      .size {
        width: 100%;
        height: 40px;
      }
    }
  }
</style>
