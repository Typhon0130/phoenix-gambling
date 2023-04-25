<template>
  <div :class="`switch ${state ? 'on' : 'off'}`" @click="toggle"></div>
</template>

<script>
  export default {
    data() {
      return {
        state: false
      }
    },
    props: {
      value: {
        type: Boolean,
        default: false
      },
      onChange: {
        type: Function,
        default: null
      }
    },
    methods: {
      toggle() {
        this.state = !this.state;

        if(this.onChange) this.onChange(this.state);
      }
    },
    created() {
      this.state = this.value;
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .switch {
    border-radius: 25px;
    backdrop-filter: blur(25px);

    @include themed() {
      background: t('switchBackground');
    }

    transition: background .3s ease;

    $height: 30px;
    $off: 10px;

    width: 50px;
    height: $height;
    position: relative;
    cursor: pointer;

    &:after {
      content: '';
      border-radius: 50%;

      width: $height - $off;
      height: $height - $off;
      left: $off * .5;
      position: absolute;
      top: 50%;
      transform: translateY(-50%);

      @include themed() {
        background: t('switchBackgroundDot');
      }

      transition: left 0.3s ease, background .3s ease;
    }

    &.on {
      @include themed() {
        background: t('switchBackgroundOn');
      }

      &:after {
        left: calc(100% - #{$height} + #{$off * .5});

        @include themed() {
          background: t('switchBackgroundDotOn');
        }
      }
    }
  }
</style>
