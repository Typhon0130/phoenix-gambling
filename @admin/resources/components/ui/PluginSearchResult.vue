<template>
  <div class="searchResults">
    <template v-if="!marketplace">
      <div class="searchResult" v-for="plugin in data" :key="plugin.id">
        <div class="searchResultContent">
          <img :alt="plugin.id" :src="plugin.logoUrl" width="85">
          <div class="title">{{ plugin.name }}</div>
          <div class="version">{{ plugin.version }}</div>
          <div class="description">
            <div v-for="line in plugin.description" :key="line">{{ line }}</div>
          </div>
          <div class="author" v-if="plugin.author.length > 0">{{ plugin.author }}</div>
        </div>
      </div>
    </template>
    <template v-else>
      <div class="searchResult" :class="plugin.isEnabled ? '' : 'noZoom'" v-for="plugin in data" :key="plugin.id">
        <div class="searchResultContent">
          <img :alt="plugin.id" :src="plugin.logoUrl" width="85">
          <div class="title">{{ plugin.name }}</div>
          <div class="version">{{ plugin.version }}</div>
          <div class="description">
            <div v-for="line in plugin.description" :key="line">{{ line }}</div>
          </div>
          <div class="author" v-if="plugin.author.length > 0">@ {{ plugin.author }}</div>
          <button class="btn" :class="[ $isDemo ? 'demoDisable' : '', plugin.isEnabled ? '' : 'btn-primary' ]" @click.stop="installPlugin(plugin.id)">
            <template v-if="plugin.isEnabled">
              <web-icon icon="fal fa-download"></web-icon> Uninstall
            </template>
            <template v-else>
              <web-icon icon="fal fa-download"></web-icon> Install
            </template>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
  import WebIcon from "./WebIcon.vue";
  import LoadingModal from "../modals/LoadingModal.vue";

  export default {
    props: {
      data: {
        type: Array
      },
      marketplace: {
        type: Boolean,
        default: false
      }
    },
    methods: {
      installPlugin(id) {
        LoadingModal.methods.open().then(done => {
          window.axios.post('/admin/plugins/toggle', {
            id: id
          }).then(() => {
            done();

            setTimeout(() => {
              window.location.reload();
            }, 1000);
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
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .searchResults {
    display: flex;
    flex-wrap: wrap;

    .searchResult {
      width: calc(25% - 22px);
      border-radius: 20px;
      transition: all .3s ease;
      cursor: pointer;
      margin: 10px;

      @include themed() {
        border: 1px solid t('border');
        background: t('block');
      }

      &:hover {
        transform: scale(1.05);
      }

      &.noZoom {
        transform: unset !important;
        cursor: unset;
      }

      .searchResultContent {
        padding: 35px;

        .title {
          margin-top: 15px;
          font-weight: 600;
          font-size: 1.1em;
        }

        .description {
          margin-top: 15px;
          opacity: .6;
        }

        .version {
          margin-top: 10px;
          opacity: .6;
        }

        .author {
          margin-top: 15px;
        }

        .btn {
          margin-top: 25px;
          width: fit-content;
        }
      }
    }
  }

  @media (max-width: 1600px) {
    .searchResults {
      .searchResult {
        width: calc(33% - 22px);
      }
    }
  }

  @media (max-width: 1200px) {
    .searchResults {
      .searchResult {
        width: calc(50% - 22px);
      }
    }
  }

  @include min(0, bp('md')) {
    .searchResults {
      .searchResult {
        width: 100%;
      }
    }
  }
</style>
