<script>
  import Bus from '../../bus.js';

  export default {
    methods: {
      open() {
        Bus.$emit('modal:new', {
          name: 'sportsUnavailable',
          component: {
            template: `<div>
              <div><web-icon class="ic" icon="fal fa-fw fa-times"></web-icon></div>
              <div class="desc">{{ $i18n.t('sport.unavailable') }}</div></div>`
          }
        });
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .xmodal.sportsUnavailable {
    max-width: 350px;
    text-align: center;

    .ic {
      font-size: 4em;
      margin-top: 15px;
    }

    .desc {
      text-align: center;
      font-size: .9em;
      opacity: .8;
      margin-top: 15px;
    }
  }
</style>
