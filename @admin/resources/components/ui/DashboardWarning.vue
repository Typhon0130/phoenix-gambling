<template>
  <div class="warn" :class="type">
    <web-icon :icon="types[type].icon"></web-icon>
    <slot></slot>
  </div>
</template>

<script>
  import WebIcon from "@/components/ui/WebIcon.vue";

  export default {
    data() {
      return {
        types: {
          info: {
            icon: 'fas fa-fw fa-info-circle',
            border: '#9aa1ff',
            color: '#dcdeff'
          },
          warning: {
            icon: 'fas fa-fw fa-exclamation-triangle',
            border: '#ffc99a',
            color: '#ffddbf'
          },
          critical: {
            icon: 'fal fa-fw fa-times',
            border: '#ef6262',
            color: '#ffaeae'
          }
        }
      }
    },
    components: {
      WebIcon
    },
    props: [
      'type'
    ]
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .warn {
    display: flex;
    align-items: center;
    margin-bottom: 25px;
    border: 2px solid white;
    padding: 20px 25px;
    border-radius: 500px;

    @include themed() {
      &.critical {
        border-color: t('criticalBorder');
        color: t('criticalColor');
      }

      &.warning {
        border-color: t('warningBorder');
        color: t('warningColor');
      }

      &.info {
        border-color: t('infoBorder');
        color: t('infoColor');
      }
    }

    i {
      margin-right: 10px;
      font-size: 1.1em;
      margin-top: 2px;
    }

    @include min(0, bp('md')) {
      border-radius: 15px;

      i {
        margin-right: 15px;
      }
    }
  }
</style>
