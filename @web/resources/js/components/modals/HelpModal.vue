<script>
  import Bus from '../../bus.js';

  export default {
    methods: {
      open(id) {
        Bus.$emit('modal:new', {
          name: 'help',
          title: window._.capitalize(id),
          component: {
            data() {
              return {
                id: id,
                page: 1,
                maxPage: null,
                pages: {}
              }
            },
            created() {
              let i = 1, ok = false;
              while (!ok) {
                const check = `game_help.${this.id}.${i}`;
                const translation = this.$i18n.t(check);
                if (check === translation) {
                  ok = true;
                  break;
                }

                this.pages[i] = translation;
                i++;
              }

              this.maxPage = i;
            },
            methods: {
              next() {
                if(this.page + 1 >= this.maxPage) {
                  Bus.$emit('modal:close');
                  return;
                }

                this.page++;
              }
            },
            template: `
              <div>
                  <div class="help-content">{{ $t(pages[page]) }}</div>
                  <button class="btn btn-primary btn-block" @click="next">{{ $t(page + 1 === maxPage ? 'game_help.close' : 'game_help.next') }}</button>
              </div>`
          }
        });
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .xmodal.help {
    max-width: 400px;
    text-align: center;

    .help-content {
      margin: 15px 0;
    }
  }
</style>
