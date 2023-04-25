<template>
  <div class="appearance animate">
    <div class="type">
      <div class="title">Website logo</div>
      <div class="row">
        <div class="column">
          <button class="btn btn-primary" @click="uploadLogo"
            :class="$isDemo ? 'demoDisable' : ''"><web-icon icon="fal fa-fw fa-upload"></web-icon> Upload</button>
        </div>
        <div class="column">
          <img width="55" :src="'/img/misc/logo.png?' + Math.random()" alt>
        </div>
      </div>
    </div>
    <div class="type">
      <div class="title">Website favicon (16x16 px)</div>
      <div class="row">
        <div class="column">
          <button class="btn btn-primary" @click="uploadFavicon"
            :class="$isDemo ? 'demoDisable' : ''"><web-icon icon="fal fa-fw fa-upload"></web-icon> Upload</button>
        </div>
        <div class="column">
          <div class="browserTab">
            <div class="dots">
              <div class="dot"></div>
              <div class="dot"></div>
              <div class="dot"></div>
            </div>
            <div class="tab">
              <img :src="'/favicon.png?' + Math.random()" width="16" height="16" alt>
              {{ websiteName }}
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="type" v-if="initialWebsiteName !== null">
      <div class="title">Website name</div>
      <input type="text" placeholder="Website name" v-model="initialWebsiteName" @input="changeWebName($event.target.value)"
        :class="$isDemo ? 'demoDisable' : ''">
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import LoadingModal from "../modals/LoadingModal.vue";
  import CompilerModalOutput from "../modals/CompilerModalOutput.vue";
  import { mapGetters } from 'vuex';

  export default {
    data() {
      return {
        initialWebsiteName: null
      }
    },
    computed: {
      ...mapGetters(['websiteName'])
    },
    watch: {
      websiteName() {
        if(this.initialWebsiteName === null && this.websiteName !== null) this.initialWebsiteName = this.websiteName;
      }
    },
    mounted() {
      this.$bus.$emit('loading:done');

      if(this.websiteName !== null) this.initialWebsiteName = this.websiteName;
    },
    methods: {
      changeWebName(value) {
        window.axios.post('/admin/appearance/setWebsiteName', { name: value }).then(() => this.$store.dispatch('updateData'));
      },
      createUploader(callback) {
        const e = document.createElement('input');
        e.type = 'file';
        e.onchange = () => callback(e.files[0]);
        e.click();
      },
      uploadLogo() {
        this.createUploader((file) => {
          const data = new FormData();
          data.append('file', file);

          LoadingModal.methods.open().then(done => {
            window.axios.post('/admin/appearance/uploadLogo', data).then(() => {
              done();
              setTimeout(() => window.location.reload(), 1000);
            }).catch(() => {
              window.$bus.$emit('modal:close');
              CompilerModalOutput.methods.open('Check directory permissions.', 'Failed to upload logo');
            });
          });
        });
      },
      uploadFavicon() {
        this.createUploader((file) => {
          const data = new FormData();
          data.append('file', file);

          LoadingModal.methods.open().then(done => {
            window.axios.post('/admin/appearance/uploadFavicon', data).then(() => {
              done();
              setTimeout(() => window.location.reload(), 1000);
            }).catch(() => {
              window.$bus.$emit('modal:close');
              CompilerModalOutput.methods.open('Check directory permissions.', 'Failed to upload logo');
            });
          });
        });
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

  .appearance {
    .type {
      margin-bottom: 35px;

      &:last-child {
        margin-bottom: 0;
      }

      .browserTab {
        display: flex;
        align-content: center;
        border-radius: 15px;
        padding: 0 20px;

        @include themed() {
          background: t('block-2');
        }

        .dots {
          display: flex;
          align-items: center;
          margin-right: 15px;

          .dot {
            width: 12px;
            height: 12px;
            margin-right: 8px;
            border-radius: 50%;

            &:nth-child(1) {
              background: #fa6350;
            }

            &:nth-child(2) {
              background: #fcbb2e;
            }

            &:nth-child(3) {
              background: #2bc63e;
            }

            &:last-child {
              margin-right: 0;
            }
          }
        }

        .tab {
          @include themed() {
            background: t('input');
            padding: 10px 15px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            align-items: center;
            font-size: .9em;

            img {
              margin-right: 10px;
            }
          }
        }
      }

      .title {
        font-size: 1.1em;
        margin-bottom: 20px;
      }

      .row {
        display: flex;
        align-items: center;

        .column {
          display: flex;
          align-items: center;
          justify-content: center;

          &:first-child {
            margin-right: 25px;
          }
        }

        @include min(0, bp('md')) {
          flex-direction: column;
          align-items: unset;

          .column {
            margin-right: 0 !important;
            align-items: unset;
            justify-content: unset;
            margin-bottom: 15px;

            &:last-child {
              margin-bottom: 0;
            }
          }
        }
      }
    }
  }
</style>
