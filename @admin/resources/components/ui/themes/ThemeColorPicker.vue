<template>
  <div class="colorPicker" :class="isHex ? 'hex' : ''">
    <div class="name">{{ name }}</div>
    <div class="value" @click="toggle" :style="isHex ? { background: color, color: getColorByBgColor(color) } : {}">
      <template v-if="isHex">
        <div class="v">{{ color }}</div>
        <div class="icon">
          <web-icon icon="fal fa-fw fa-pencil"></web-icon>
        </div>

        <div class="iro" ref="iro" :style="{ display: showPicker ? 'block' : 'none' }" v-click-outside="clickOutside"></div>
      </template>
      <template v-else>
        <input type="text" v-model="color">
        <div class="preview" v-tooltip="'Preview'" :style="{ background: color }"></div>
      </template>
    </div>
  </div>
</template>

<script>
  import { mapGetters } from 'vuex';
  import iro from '@jaames/iro';
  import WebIcon from "../WebIcon.vue";

  export default {
    data() {
      return {
        showPicker: false,

        color: this.value,
        alpha: 1,

        iro: null
      }
    },
    props: [
      'name',
      'value',
      'onChange'
    ],
    computed: {
      isHex() {
        return /^#[0-9a-fA-F]{8}$|#[0-9a-fA-F]{6}$|#[0-9a-fA-F]{4}$|#[0-9a-fA-F]{3}$/i.test(this.color);
      },
      ...mapGetters(['dashboardTheme'])
    },
    mounted() {
      if(this.isHex) {
        this.$nextTick(() => {
          this.iro = new iro.ColorPicker(this.$refs.iro, {
            width: 200,
            color: this.color,
            layout: [
              {
                component: iro.ui.Box,
                options: {}
              },
              {
                component: iro.ui.Slider,
                options: {
                  id: 'hue-slider',
                  sliderType: 'hue'
                }
              },
              {
                component: iro.ui.Slider,
                options: {
                  sliderType: 'alpha'
                }
              }
            ]
          });

          this.iro.on('color:change', (color) => {
            this.color = color.hex8String;
          })
        });
      }
    },
    methods: {
      getColorByBgColor(bgColor) {
        if(bgColor.length === 9) bgColor = bgColor.substring(0, 7);
        return (parseInt(bgColor.replace('#', ''), 16) > 0xffffff / 2) ? '#000' : '#fff';
      },
      toggle() {
        setTimeout(() => this.showPicker = true, 25);
      },
      clickOutside() {
        let state = this.showPicker;

        setTimeout(() => {
          if(state && this.showPicker)
            this.showPicker = false;
        }, 50);
      }
    },
    watch: {
      color() {
        this.onChange(this.color);
      }
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .colorPicker {
    margin-bottom: 25px;

    &.hex {
      width: fit-content;

      .value {
        cursor: pointer;
        border-radius: 10px;
        padding: 10px 15px;
        width: fit-content;

        @include themed() {
          box-shadow: 0 0 1px 2px rgba(t('text'), .1);
        }
      }
    }

    .name {
      font-size: 1.1em;
      margin-bottom: 15px;
    }

    .value {
      position: relative;
      display: flex;
      align-items: center;

      .preview {
        margin-left: 15px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: transparent;
      }
    }

    .icon {
      margin-left: 10px;
    }

    :deep(.iro) {
      position: absolute;
      z-index: 10;
      top: 50px;
      left: 0;
    }
  }
</style>
