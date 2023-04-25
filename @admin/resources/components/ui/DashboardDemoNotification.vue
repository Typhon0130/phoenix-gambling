<template>
  <div class="demoNotification" v-if="$isDemo && show">
    <web-icon icon="fal fa-fw fa-times" class="close" @click="show = false"></web-icon>
    <div class="title">Welcome to dashboard!</div>
    <div class="description">
      <div>You are viewing a demo version of the admin dashboard.</div>
      <div>Most of the data you will see is randomized, and any changes that you make won't be saved.</div>
      <div><a href="https://t.me/casino_sales" target="_blank">Contact us</a> to purchase this software and launch your own casino website.</div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "./WebIcon.vue";

  export default {
    data() {
      return {
        show: true
      }
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";
  @import "resources/sass/container";

  .demoNotification {
    position: fixed;
    bottom: 25px;
    right: 40px;
    z-index: 1;
    padding: 25px 55px 25px 30px;

    @include themed() {
      background: t('sidebar');
      border: 4px solid t('border');
      border-radius: 20px;
      box-shadow: 0 0 4px rgba(t('text'), .01);

      @include min(0, bp('md')) {
        padding: 25px 30px;
        bottom: 0;
        right: unset;
        left: 0;
        width: 100vw;
        border-radius: 0;
        box-shadow: unset;
        border-bottom: unset;
        border-right: unset;
        border-left: unset;
      }
    }

    .title {
      font-size: 1.1em;
      font-weight: 600;
      margin-bottom: 15px;
    }

    .description {
      div {
        margin-bottom: 5px;

        &:last-child {
          margin-bottom: 0;
        }
      }
    }

    .close {
      position: absolute;
      right: 15px;
      top: 15px;
      opacity: .5;
      transition: opacity .3s ease;
      cursor: pointer;
      font-size: 1.2em;

      &:hover {
        opacity: 1;
      }
    }
  }
</style>
