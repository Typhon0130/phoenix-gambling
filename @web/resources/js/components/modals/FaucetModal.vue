<script>
  import Bus from '../../bus';
  import AuthModal from "./AuthModal.vue";
  import { mapGetters } from 'vuex';
  import gsap from 'gsap';

  export default {
    computed: {
      ...mapGetters(['currencies', 'currency', 'user', 'isGuest'])
    },
    methods: {
      open() {
        Bus.$emit('modal:new', {
          name: 'faucet',
          component: {
            template: `
              <div class="faucet-content">
                <div class="wheel-bg"></div>
                <div class="ribbon" v-html="$t('faucet.spin')"></div>
                <button class="btn btn-primary wheelSpin">{{ $t('faucet.btn') }}</button>
              </div>`,
            mounted() {
              document.querySelector('.wheelSpin').addEventListener('click', () => {
                if(this.isGuest) {
                  Bus.$emit('modal:close');
                  this.openAuthModal();
                  return;
                }

                if(document.querySelector('.wheelSpin').classList.contains('disabled')) return;
                document.querySelector('.wheelSpin').classList.add('disabled');

                window.axios.post('/api/promocode/bonus').then(() => {
                  gsap.to(document.querySelector('.wheel-bg'), { duration: 3, ease: 'power4.easeOut', rotate: 360 * 2.5 });
                  setTimeout(() => {
                    Bus.$emit('modal:close');
                  }, 2250);
                }).catch((error) => {
                  if(error.response.data.code === 1) this.$toast.error(this.$i18n.t('faucet.timeout'));
                  if(error.response.data.code === 2) this.$toast.error(this.$i18n.t('general.error.should_have_empty_balance'));
                });
              });
            },
            methods: {
              openAuthModal() {
                AuthModal.methods.open('auth');
              }
            }
          }
        });
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .xmodal.faucet {
    max-width: 350px;
    border-radius: 15px;

    @include themed() {
      .ribbon {
        position: absolute;
        text-align: center;
        font-size: 13px !important;
        background: url('/img/misc/ribbon.png') no-repeat center;
        background-size: contain;
        left: 50%;
        transform: translateX(-50%);
        bottom: 90px;
        width: 280px;
        height: 110px;
        display: flex;
        flex-direction: column;
        color: white !important;
        align-items: center;
        justify-content: center;
        padding-top: 23px;
        z-index: 5;
      }
    }

    .modal_content {
      padding: 30px !important;
      background: linear-gradient(to bottom, #3b0c3d, #551259);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      .wheel-bg {
        display: flex;
        background: url('/img/misc/spin.png') no-repeat center;
        background-size: contain;

        width: 310px;
        height: 290px;
        z-index: 0;
        margin-top: -4px;
        transform: rotate(-8deg);
      }

      .btn {
        margin-top: 20px;
        background: #FFB034 !important;
        border-radius: 100px;
        padding: 15px 25px;
        color: white !important;
        font-weight: 600;
        font-size: 1.1em;
      }
    }

    .loader {
      margin: auto;
    }
  }
</style>
