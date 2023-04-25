<template>
  <div class="index animate">
    <template v-if="license && !license.isValid">
      <dashboard-warning type="critical">
        <template v-if="$permission.checkPermission('*')">
          Your license is not valid.
          <div style="width: 5px"></div>
          <router-link to="/admin/license">Open licensing page to resolve this problem.</router-link>
        </template>
        <template v-else>
          Website is currently unavailable. Contact your system administrator.
        </template>
      </dashboard-warning>
    </template>
    <template v-else-if="validation">
      <template v-if="!validation.isConfigured">
        <template v-if="!$permission.checkPermission('*')">
          <dashboard-warning type="warning">
            Statistics are not configured. Contact your system administration.
          </dashboard-warning>
        </template>
        <template v-else>
          <div class="step">
            <div class="number">1</div>
            <div class="content">
              <div class="title">Getting credentials</div>
              <div class="text">
                <div>
                  The first thing you’ll need to do is to get some credentials to use Google API’s.
                  You should already have created Google account and be signed in.
                  Head over to <a href="https://console.developers.google.com/apis" target="_blank">Google API’s site</a> and click "Select a project" in the header.
                </div>
                <img src="/img/dashboard/analytics/guide-1.png">
                <div>
                  Next up we must specify which API’s the project may consume. In the list of API Library find "Google Analytics Data API". On the next screen click "Enable".
                </div>
                <div class="mt">
                  Now that you’ve created a project that has access to the Analytics API it’s time to download a file with these credentials. Click "Credentials" in the sidebar. You’ll want to create a "Service account key".
                </div>
                <img src="/img/dashboard/analytics/guide-3.png">
                <div>
                  On the next screen you can give the service account a name. You can name it anything you’d like. In the service account id you’ll see an email address. We’ll use this email address later on in this guide.
                </div>
                <img src="/img/dashboard/analytics/guide-4.png">
                <div>
                  Select "JSON" as the key type and click "Create" to download the JSON file.
                </div>
                <img src="/img/dashboard/analytics/guide-5.png">
                <div>
                  Save the json somewhere on your computer. You'll need it later.
                </div>
              </div>
            </div>
          </div>
          <div class="step">
            <div class="number">2</div>
            <div class="content">
              <div class="title">Granting permissions to your Analytics property</div>
              <div class="text">
                <div>
                  You should have already created a Google Analytics 4 account on the <a href="https://analytics.google.com/analytics" target="_blank">Analytics site</a>.
                </div>
                <div class="mt">
                  Go to "User management" in the Admin-section of the property.
                </div>
                <img src="/img/dashboard/analytics/guide-7.png">
                <div>
                  On this screen you can grant access to the email address found in the "client_email" key from the json file you download in the previous step. Analyst role is enough.
                </div>
                <img src="/img/dashboard/analytics/guide-8.png">
              </div>
            </div>
          </div>
          <div class="step">
            <div class="number">3</div>
            <div class="content">
              <div class="title">Getting the measurement and stream id</div>
              <div class="text">
                <div>
                  You can get the right value on the <a href="https://analytics.google.com/analytics" target="_blank">Analytics site</a>.
                </div>
                <div class="mt">
                  Go to "Settings", click on "Property Settings" and copy the "Property ID".
                </div>
                <div class="mt">
                  Go to "Settings", click on "Data Streams" and create "Web" data stream. Click on the created data stream and copy the "Measurement ID" and "Stream ID".
                </div>
              </div>
            </div>
          </div>
          <div class="step">
            <div class="number">4</div>
            <div class="content">
              <div class="title">Configure dashboard</div>
              <div class="text">
                <div>Upload the downloaded .json file and enter the obtained ids.</div>
                <div class="mt">
                  <input placeholder="Property ID" v-model="propertyId" type="text">
                </div>
                <div class="mt">
                  <input placeholder="Measurement ID" v-model="measurementId" type="text">
                </div>
                <div class="mt">
                  <input placeholder="Stream ID" v-model="streamId" type="text">
                </div>
                <div class="fileUpload mt">
                  <button class="btn btn-primary" @click="upload"><web-icon icon="fal fa-fw fa-upload"></web-icon> Upload .json</button>
                  <div style="margin-left: 10px" v-if="file">Uploaded</div>
                </div>
              </div>
            </div>
          </div>
          <button class="btn btn-primary" :disabled="file === null || streamId.length < 2 || measurementId.length < 2 || propertyId.length < 2" @click="done"><web-icon icon="fal fa-fw fa-check"></web-icon> Done</button>
        </template>
      </template>
      <template v-else>
        <div class="block-container wrap">
          <div class="basicStats">
            <div class="stat">
              <web-icon icon="fal fa-users fa-fw"></web-icon>
              <div class="name">Users</div>
              <div class="number">
                <dashboard-spinner v-if="!registeredUsers"></dashboard-spinner>
                <template v-else>
                  <div v-tooltip="'Today'">{{ registeredUsers.today }}</div>
                  /
                  <div v-tooltip="'Total'">{{ registeredUsers.count }}</div>
                </template>
              </div>
            </div>
            <div class="stat" v-if="$permission.checkPermission('wallet')">
              <web-icon icon="fal fa-wallet fa-fw"></web-icon>
              <div class="name">Balance</div>
              <div class="number">
                <dashboard-spinner v-if="!balance"></dashboard-spinner>
                <template v-else>
                  ${{ balance.balance.toFixed(2) }}
                </template>
              </div>
            </div>
            <div class="stat blue clickable" @click="analyticsType = analyticsType === 'general' ? 'games' : 'general'">
              <web-icon icon="fal fa-dice-d20 fa-fw"></web-icon>
              <div class="name">Games</div>
              <div class="number">
                <dashboard-spinner v-if="!gamesTotal"></dashboard-spinner>
                <template v-else>
                  <div v-tooltip="'Today'">{{ gamesTotal.today }}</div>
                  /
                  <div v-tooltip="'Total'">{{ gamesTotal.count }}</div>

                  <web-icon icon="fal fa-chevron-right"></web-icon>
                </template>
              </div>
            </div>
            <div class="stat yellow">
              <web-icon icon="fal fa-arrow-down fa-fw"></web-icon>
              <div class="name">Deposits</div>
              <div class="number">
                <dashboard-spinner v-if="!invoicesTotal"></dashboard-spinner>
                <template v-else>
                  <div v-tooltip="'Today'">{{ invoicesTotal.today }}</div>
                  /
                  <div v-tooltip="'Total'">{{ invoicesTotal.count }}</div>
                </template>
              </div>
            </div>
            <div class="stat red clickable" v-if="$permission.checkPermission('*')" @click="toggleMaintenance">
              <web-icon icon="fal fa-tools fa-fw"></web-icon>
              <div class="name">Maintenance</div>
              <div class="number">
                <dashboard-spinner v-if="maintenance === null"></dashboard-spinner>
                <web-icon :icon="maintenance ? 'fal fa-fw fa-check' : 'fal fa-fw fa-times'" v-else></web-icon>
              </div>
            </div>
          </div>
          <template v-if="analyticsType === 'general'">
            <total-users-analytics></total-users-analytics>
            <new-users-analytics></new-users-analytics>
            <registered-users-analytics></registered-users-analytics>
            <pages-analytics></pages-analytics>
            <popular-device-types-analytics></popular-device-types-analytics>
            <popular-browsers-analytics></popular-browsers-analytics>
            <operation-system-analytics></operation-system-analytics>
            <region-analytics></region-analytics>
            <country-analytics></country-analytics>
            <city-analytics></city-analytics>
          </template>
          <template v-else-if="analyticsType === 'games'">
            <game-analytics v-for="game in games" :game="game" :key="game.id + game.type"></game-analytics>
          </template>
        </div>
      </template>
    </template>
  </div>
</template>

<script>
  import DashboardWarning from "../ui/DashboardWarning.vue";
  import { mapGetters } from 'vuex';
  import WebIcon from "../ui/WebIcon.vue";

  import PagesAnalytics from "./analytics/PagesAnalytics.vue";
  import NewUsersAnalytics from "./analytics/NewVisitsAnalytics.vue";
  import TotalUsersAnalytics from "./analytics/TotalVisitsAnalytics.vue";
  import PopularDeviceTypesAnalytics from "./analytics/PopularDeviceTypesAnalytics.vue";
  import PopularBrowsersAnalytics from "./analytics/PopularBrowsersAnalytics.vue";
  import OperationSystemAnalytics from "./analytics/OperatingSystemAnalytics.vue";
  import CountryAnalytics from "./analytics/CountryAnalytics.vue";
  import CityAnalytics from "./analytics/CityAnalytics.vue";
  import RegionAnalytics from "./analytics/RegionAnalytics.vue";
  import RegisteredUsersAnalytics from "./analytics/RegisteredUsersAnalytics.vue";
  import GameAnalytics from "./analytics/GameAnalytics.vue";
  import DashboardSpinner from "../ui/DashboardSpinner.vue";

  export default {
    data() {
      return {
        validation: null,
        file: null,
        propertyId: '',
        streamId: '',
        measurementId: '',

        registeredUsers: null,
        gamesTotal: null,
        invoicesTotal: null,
        balance: null,
        maintenance: null,

        analyticsType: 'general'
      }
    },
    created() {
      if(!this.license) window.location.href = '/';
      else {
        window.axios.post('/admin/stats/validate').then(({ data }) => {
          this.validation = data;
          this.$bus.$emit('loading:done');

          if(data.isConfigured) {
            window.axios.post('/admin/stats/registeredUsersTotal').then(({ data }) => this.registeredUsers = data);
            window.axios.post('/admin/stats/gamesTotal').then(({ data }) => this.gamesTotal = data);
            window.axios.post('/admin/stats/invoicesTotal').then(({ data }) => this.invoicesTotal = data);
            window.axios.post('/admin/stats/totalWalletBalance').then(({ data }) => this.balance = data);

            if(this.$permission.checkPermission('*')) {
              window.axios.post('/admin/maintenance/status').then(({ data }) => this.maintenance = data.status);
            }
          }
        });
      }
    },
    computed: {
      ...mapGetters(['license', 'games'])
    },
    methods: {
      toggleMaintenance() {
        this.maintenance = !this.maintenance;
        if(!window.$isDemo) window.axios.post('/admin/maintenance/toggle');
      },
      done() {
        let data = new FormData();
        data.append('file', this.file);
        data.append('propertyId', this.propertyId);
        data.append('streamId', this.streamId);
        data.append('measurementId', this.measurementId);
        window.axios.post('/admin/stats/configure', data).then(() => {
          window.location.reload();
        }).catch(() => this.$toast.error('Failed to authorize using these credentials. If you believe that the information you\'ve provided is correct, try again after a few minutes - sometimes there might be a delay in Google servers.'));
      },
      upload() {
        const input = document.createElement('input');
        input.type = 'file';

        input.onchange = () => {
          this.file = input.files[0];
        }

        input.click();
      }
    },
    components: {
      DashboardSpinner,
      RegisteredUsersAnalytics,
      RegionAnalytics,
      CityAnalytics,
      CountryAnalytics,
      OperationSystemAnalytics,
      PopularBrowsersAnalytics,
      PopularDeviceTypesAnalytics,
      TotalUsersAnalytics,
      NewUsersAnalytics,
      WebIcon,
      DashboardWarning,
      PagesAnalytics,
      GameAnalytics
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";
  @import "resources/sass/container";

  .index {
    .block-container {
      display: flex;
      $margin: 10px;
      margin-left: -$margin;
      margin-top: -$margin;
      width: calc(100% + #{$margin * 2});
      margin-bottom: 10px;

      .basicStats {
        margin: 10px;

        .stat {
          @include themed() {
            padding: 15px 25px;
            border-radius: 10px;
            border: 2px solid t('border');
            display: flex;
            align-items: center;
            min-width: 250px;
            margin-bottom: 10px;
            transition: all .3s ease;

            &.clickable {
              cursor: pointer;

              &:hover {
                transform: scale(1.05);
              }
            }

            &.red {
              border-color: t('criticalBorder');
              color: t('criticalColor');
            }

            &.yellow {
              border-color: t('warningBorder');
              color: t('warningColor');
            }

            &.blue {
              border-color: t('infoBorder');
              color: t('infoColor');
            }

            &:last-child {
              margin-bottom: 0;
            }

            i, svg {
              margin-right: 10px;
            }

            .name {
              margin-top: -2px;
              margin-right: 5px;
            }

            .number {
              margin-top: -2px;
              margin-left: auto;
              font-weight: 600;
              display: flex;

              :deep(.spinner) {
                border-bottom: none;
              }

              svg, i {
                margin-right: 0;
                margin-top: 4px;
                margin-left: 10px;
              }

              div {
                border-bottom: 1px solid rgba(t('text'), .4);
              }
            }
          }
        }
      }

      @include min(0, bp('md')) {
        flex-direction: column;
      }

      &.wrap {
        flex-wrap: wrap;

        :deep(.block) {
          flex-shrink: unset;
        }
      }

      :deep(.block) {
        @include themed() {
          $margin: 10px;

          border: 1px solid t('border');
          padding: 35px 40px;
          border-radius: 10px;
          margin: $margin;
          flex: 1;
          display: flex;
          flex-direction: column;
          background: t('block');

          .demo {
            display: flex;
            align-items: center;
            opacity: .8;
            margin-top: 10px;

            svg, i {
              margin-right: 15px;
              font-size: 1.1em;
            }

            .description {
              div:first-child {
                margin-bottom: 5px;
              }

              div:last-child {
                margin-bottom: 0;
              }
            }
          }

          @include min(0, bp('md')) {
            flex: unset !important;
          }

          &.chart {
            .content {
              display: flex;
              align-items: center;
              justify-content: center;
              height: 100%;

              :deep(.loadingContent) {
                margin: auto;
              }

              @media(max-width: 991px) {
                justify-content: unset;
              }
            }
          }

          &.fit-content {
            height: fit-content;
          }

          .title {
            font-family: 'Roboto', sans-serif;
            font-size: 1.7em;
            opacity: .8;
            margin-bottom: 15px;
          }

          .subtitle {
            font-family: 'Roboto', sans-serif;
            font-size: 1em;
            opacity: .5;
            margin-bottom: 5px;
          }

          .content {
            padding-top: 15px;
            margin-bottom: auto;

            .separator {
              margin-left: -40px;
              width: calc(100% + 40px / 2 + 20px);
              padding: 15px 0 15px 40px;
              background: t('block-2');
              margin-top: 15px;
              margin-bottom: 15px;
            }

            input {
              background: t('block-2');
              border: 1px solid t('border');
            }
          }

          .footer {
            margin-top: 30px;
          }

          &.block-small {
            flex: 0 300px;
          }

          &.block-full {
            flex: 1;
          }

          &.primary {
            border-color: t('secondary');
          }
        }
      }
    }

    .step {
      display: flex;
      margin-bottom: 25px;

      .number {
        font-weight: 600;
        flex-shrink: 0;
        position: sticky;
        top: 140px;

        @include themed() {
          border-radius: 50%;
          background: t('input');
          width: 45px;
          height: 45px;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 0 1px 2px t('block');
          margin-right: 20px;
        }
      }

      .text {
        margin-top: 10px;
        display: flex;
        flex-direction: column;

        .mt {
          margin-top: 15px;
        }

        .fileUpload {
          display: flex;
          align-items: center;
        }

        img {
          max-width: 800px;
          width: 100%;
          margin-top: 20px;
          margin-bottom: 20px;
        }
      }

      .content {
        display: flex;
        justify-content: center;
        flex-direction: column;

        .title {
          text-transform: uppercase;
          opacity: .6;
          font-size: 1.1em;
          transition: opacity .3s ease;
        }
      }
    }
  }
</style>
