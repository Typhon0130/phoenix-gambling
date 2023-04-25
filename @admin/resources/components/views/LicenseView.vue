<template>
  <div class="license animate" v-if="license">
    <template v-if="$isDemo">
      <dashboard-warning type="info">
        You are viewing a demo website.
      </dashboard-warning>

      <div class="smDescription"><a href="https://phoenix-gambling.com" target="_blank">Visit Phoenix Gambling website</a> or <a href="https://t.me/casino_sales" target="_blank">contact us</a> to purchase this casino software.</div>
    </template>
    <template v-else-if="!license.isValid">
      <dashboard-warning type="critical">
        You have to acquire a license to use this software.
      </dashboard-warning>

      <div class="smDescription">Don't have a license? <a href="https://t.me/casino_sales" target="_blank">Contact Phoenix Gambling</a></div>

      <input type="text" v-model="key" placeholder="License key">

      <template v-if="status !== null">
        <div class="status" v-if="status === 0">
          <div class="icon">❌</div>
          <div class="text">This license is not valid</div>
        </div>
        <div class="status" v-else-if="status === 1">
          <div class="icon">✅</div>
          <div class="text">This license is valid. Activating...</div>
        </div>
        <div class="status" v-else-if="status === 2">
          <div class="icon">⌛</div>
          <div class="text">Verifying license key...</div>
        </div>
        <div class="status" v-else-if="status === 3">
          <div class="icon">⚠️</div>
          <div class="text">
            You've exceeded website limit for your license.
            <br>
            Extend your license or remove existing website.
          </div>
        </div>
      </template>
    </template>
    <template v-else>
      <div class="verified">
        <web-icon icon="fas fa-fw fa-badge-check"></web-icon>
      </div>
      <div class="verifiedDesc">
        Your license is valid.
      </div>
      <div class="verifiedDesc2">
        License for {{ license.info.maxWebsites }} website(s) (<a href="javascript:void(0)" @click="viewWhitelist">{{ license.info.whitelist.length }} used</a>).
      </div>

      <div class="apTitle">Available products</div>
      <div class="availableProducts">
        <div class="products" v-for="product in license.phoenixFeatures" :key="product.id">
          <div class="name">{{ product.name }}</div>
          <template v-if="!license.info.features.filter(e => e.id === product.id)[0]">
            <div class="price">${{ product.priceUSD }} <template v-if="product.canBePurchasedOnlyOnce">(Once)</template></div>
          </template>
          <template v-else>
            <div class="purchased">
              <web-icon icon="fas fa-fw fa-badge-check"></web-icon> Purchased
            </div>
          </template>
          <div class="description">
            <div class="line" v-for="line in product.description" :key="line">{{ line }}</div>
          </div>
        </div>
      </div>

      <div class="unlink">
        <button class="btn btn-primary" @click="refreshCache"><web-icon icon="fal fa-fw fa-sync"></web-icon> Refresh</button>
        <button class="btn" @click="unlink"><web-icon icon="fal fa-fw fa-times"></web-icon> Remove license</button>
      </div>
    </template>
  </div>
</template>

<script>
  import DashboardWarning from "../ui/DashboardWarning.vue";
  import WebIcon from "../ui/WebIcon.vue";

  export default {
    data() {
      return {
        license: null,
        status: null,
        key: ''
      }
    },
    methods: {
      refreshCache() {
        window.axios.post('/admin/license/refresh').then(() => window.location.reload());
      },
      unlink() {
        if(confirm('Are you sure? This website will not work without a valid license.')) {
          window.axios.post('/admin/license/setKey', {
            key: '-',
            ip: '-'
          }).then(() => {
            this.$store.dispatch('setLicense', {
              isValid: false
            });
            window.location.reload();
          });
        }
      },
      viewWhitelist() {
        let r = '';
        this.license.info.whitelist.forEach(e => r += e + '\n');
        alert('Websites using this license:\n\n' + r);
      }
    },
    watch: {
      key() {
        this.status = 2;
        window.axios.post('/admin/serverIp').then(({ data }) => {
          window.axios.post('https://phoenix-gambling.com/license/installerVerify', {
            key: this.key,
            ip: data
          }).then(() => {
            this.status = 1;

            window.axios.post('/admin/license/setKey', {
              key: this.key,
              ip: data
            }).then(() => {
              window.axios.post('/license').then(({ data }) => {
                this.$store.dispatch('setLicense', data);
                window.location.reload();
              });
            });
          }).catch((e) => {
            const code = e.response.data.error.code;
            if(code === 1) this.status = 0;
            else if(code === 2) this.status = 3;
          });
        });
      }
    },
    created() {
      window.axios.post('/admin/license/info').then(({ data }) => {
        this.license = data;
        this.$bus.$emit('loading:done');
      });
    },
    components: {
      WebIcon,
      DashboardWarning
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .license {
    .verified {
      i {
        font-size: 3em;

        @include themed() {
          color: t('switchBackgroundOn');
        }
      }
    }

    .apTitle {
      font-size: 1.3em;
      margin-top: 35px;
      margin-bottom: 15px;
    }

    .unlink {
      margin-top: 25px;

      .btn:first-child {
        margin-right: 15px;
      }
    }

    .availableProducts {
      .products {
        margin-bottom: 35px;

        &:last-child {
          margin-bottom: 0;
        }

        .name {
          font-size: 1.1em;
        }

        .price, .purchased {
          opacity: .6;
          margin-top: 5px;
        }

        .purchased i {
          @include themed() {
            color: t('switchBackgroundOn');
          }
        }

        .description {
          margin-top: 15px;

          .line {
            margin-bottom: 5px;

            &:last-child {
              margin-bottom: 0;
            }
          }
        }
      }
    }

    .verifiedDesc {
      font-size: 1.4em;
      margin-top: 15px;
      margin-bottom: 10px;
    }

    .verifiedDesc2 {
      opacity: .8;
    }

    .smDescription {
      margin-bottom: 15px;
    }

    .status {
      opacity: .8;
      margin-top: 15px;
      line-height: 1.7em;
      display: flex;

      .icon {
        margin-right: 5px;
      }
    }
  }
</style>
