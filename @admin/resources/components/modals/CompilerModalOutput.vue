<script>
  import Bus from "../../bus.js";

  export default {
    methods: {
      open(text, message = 'Failed to compile.') {
        Bus.$emit('modal:new', {
          name: 'compilerOutput',
          component: {
            data() {
              return {
                text: text,
                message: message
              }
            },
            template: `<div>
              <div class="desc">{{ message }}</div>
              <div class="desc2">Log:</div>
              <pre>{{ text }}</pre>
            </div>`
          }
        })
      }
    }
  }
</script>

<style lang="scss">
  .modal.compilerOutput {
    padding: 15px 20px;

    .desc {
      font-size: 1.1em;
      margin-bottom: 5px;
    }

    .desc2 {
      margin-bottom: 25px;
      opacity: .6;
      font-size: .9em;
    }
  }
</style>
