<template>
  <div class="logs animate">
    <iframe src="/log-viewer"></iframe>
  </div>
</template>

<script>
  export default {
    mounted() {
      this.$bus.$emit('loading:done');
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .logs {
    iframe {
      @include themed() {
        color: t('text');
      }
      height: calc(100vh - 280px);
      width: 100%;
    }
  }
</style>
