<template>
  <footer>
    <div class="container">
      <div class="footer">
        <div class="column grid">
          <div class="links">
            <div class="name">Phoenix</div>
            <div class="link-group">
              <router-link to="/">{{ $t('general.head.games') }}</router-link>
              <router-link to="/sport" v-if="$isAvailable('phoenixSport')">{{ $t('general.head.sport') }}</router-link>
            </div>
          </div>
          <div class="links">
            <div class="name">{{ $t('footer.features.more') }}</div>
            <div class="link-group">
              <router-link to="/privacyPolicy">{{ $t('footer.features.privacy') }}</router-link>
              <router-link to="/terms">{{ $t('footer.features.terms') }}</router-link>
            </div>
          </div>
          <div class="notes">
            <i>18+</i>
            <small>{{ $t('footer.legal') }}</small>
          </div>
        </div>
        <div class="column">
          <div class="social-icons">
            <div class="social-icon" onclick="window.open('https://example.com', '_blank')">
              <web-icon icon="fab fa-telegram"></web-icon>
            </div>
          </div>
        </div>
        <div class="column">
          <div class="copy">
            axes.bet is owned and operated by AX Group AG, a company incorporated under the laws of Seychelles with registration number ZK017299 (0). Axes.bet is licensed and regulated by Gaming authority of Seychelles (license no. ZK017299/1) under the license GLH-OCCHKTW0888722023 issued on April 18, 2022
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
  import { mapGetters } from 'vuex';
  import TermsModal from "../modals/TermsModal.vue";

  export default {
    computed: {
      ...mapGetters(['isGuest', 'currencies'])
    },
    methods: {
      openTerms(type) {
        TermsModal.methods.open(type);
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  footer {
    @include themed() {
      background: t('footer');
      margin-top: 15px;

      .footer {
        display: flex;
        flex-direction: column;
        padding: 0 50px;

        .column {
          display: flex;
          width: 100%;
          align-items: center;
          padding: 25px 0;
          border-bottom: 2px solid t('border');

          &.payment {
            img {
              width: 0;
              flex: 1;
            }

            @media(max-width: 991px) {
              display: none;
            }
          }

          &:last-child {
            border-bottom: none;
          }

          &.grid {
            display: grid;
            grid-gap: 15px;
            gap: 15px;
            grid-template-columns: auto auto 576px;
            align-items: flex-start;
            padding: 40px 0;

            @media(max-width: 1456px) {
              display: flex;
              flex-direction: column;
            }

            @media(max-width: 991px) {
              display: flex;
            }
          }

          .notes {
            display: flex;
            flex-direction: column;

            @media(max-width: 991px) {
              margin-top: 10px;
            }

            i {
              width: 36px;
              height: 36px;
              border: 2px solid #fff;
              border-radius: 50%;
              text-align: center;
              line-height: 32px;
              font-style: normal;
              font-weight: 600;
              font-size: 13px;
              margin-bottom: 15px;
            }

            small {
              font-size: 11px;
              opacity: .4;
            }
          }

          .links {
            display: flex;
            flex-direction: column;

            .name {
              text-transform: uppercase;
              margin-bottom: 15px;
              opacity: .6;
              font-weight: 600;
            }

            .link-group {
              display: flex;
              flex-direction: column;

              a {
                opacity: .4;
                margin-bottom: 5px;
                transition: opacity .3s ease;

                &:last-child {
                  margin-bottom: 0;
                }

                &:hover {
                  opacity: 1;
                }
              }
            }
          }

          .social-icons {
            display: flex;

            .social-icon {
              margin-right: 10px;
              border-radius: 10px;
              background: t('block');
              padding: 5px 10px;
              transition: background .3s ease, color .3s ease;
              cursor: pointer;

              &:hover {
                color: white;
                background: lighten(t('block'), 5%);
              }

              &:last-child {
                margin-right: 0;
              }
            }
          }

          .copy {
            opacity: .3;
            font-size: 11px;
          }
        }
      }
    }
  }

  @include media_breakpoint_down(md) {
    footer .footer {
      padding-bottom: 53px !important;

      .column {
        flex-direction: column;

        .copy {
          text-align: center;
          margin-bottom: 5px;
        }
      }
    }
  }
</style>
