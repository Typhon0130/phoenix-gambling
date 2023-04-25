<script>
  import Bus from "../../bus.js";
  import LoaderAnimation from "../ui/LoaderAnimation.vue";

  export default {
    methods: {
      open() {
        return new Promise((resolve) => {
          Bus.$emit('modal:new', {
            name: 'loading',
            notDismissible: true,
            component: {
              data() {
                return {
                  done: false
                }
              },
              components: {
                LoaderAnimation
              },
              created() {
                resolve(() => this.done = true);
              },
              template: `<div>
                <loader-animation :done="done"></loader-animation>
              </div>`
            }
          });
        });
      }
    }
  }
</script>

<style lang="scss">
  .modal.loading {
    border-radius: 50% !important;
    min-width: unset !important;
    width: fit-content !important;

    .content {
      display: flex;
      align-items: center;
      justify-content: center;

      .loadingContent {
        display: flex;
        align-items: center;
        justify-content: center;
      }
    }
  }
</style>
