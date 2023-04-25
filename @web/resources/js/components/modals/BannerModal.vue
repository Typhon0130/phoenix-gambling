<script>
  import Bus from '../../bus.js';

  export default {
    methods: {
      open() {
        window.axios.post('/api/banner').then(({ data }) => {
          if(!data.enabled) return;

          Bus.$emit('modal:new', {
            name: 'banner',
            component: {
              data() {
                return {
                  banner: data
                }
              },
              template: `
                <div class="banner-content">
                  <div class="banner-image" :style="{ backgroundImage: \`url(${data.image})\` }"></div>
                  <div class="banner-content-container">
                    <div class="banner-title">{{ banner.title }}</div>
                    <div class="banner-content" v-html="banner.content"></div>
                  </div>
                </div>
              `
            }
          });
        });
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";
  @import "resources/sass/themes";

  .xmodal.banner {
    max-width: 400px;

    .banner-content {
      .banner-image {
        height: 250px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        margin-bottom: 15px;
        margin-left: -20px;
        width: calc(100% + 40px);
      }

      .banner-content-container {
        .banner-title {
          font-size: 1.25em;
          font-weight: bold;
          @include themed() {
            color: t('secondary');
          }
        }

        .banner-content {
          margin-top: 10px;
        }
      }
    }
  }
</style>
