<template>
  <div class="header">
    <div class="fixed">
      <web-icon icon="fal fa-bars mobileMenu" @click="$bus.$emit('mobileMenu:toggle')"></web-icon>
      <div class="search" v-click-outside="() => searchOpen = false" :class="license && license.isValid ? '' : 'disabled'">
        <div class="visible">
          <web-icon icon="fal fa-fw fa-search"></web-icon>
          <input type="text" placeholder="Search..." v-model="search" @click="searchOpen = true" @keydown.esc="searchOpen = false" @input="searchOpen = true">
        </div>
        <div class="results" :class="searchOpen ? 'open' : ''">
          <div class="empty" v-if="search.length === 0">
            Enter search query.
          </div>
          <div class="empty" v-if="results.length === 0 && search.length > 0">
            No results found.
          </div>
          <div class="list">
            <div class="result" v-for="result in results" :key="result" @click.stop="$router.push(result.path); searchOpen = false" v-html="result.meta.search.title"></div>
          </div>
        </div>
      </div>
      <div class="user" @click="$bus.$emit('dashboardConfig:toggle')">
        <div class="image" v-if="user" :style="{ backgroundImage: `url(${user.user.avatar})` }"></div>
        <web-icon icon="settings far fa-fw fa-chevron-down"></web-icon>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapGetters } from 'vuex';
  import WebIcon from "@/components/ui/WebIcon.vue";
  import OverlayScrollbars from 'overlayscrollbars';
  import * as stringSimilarity from 'string-similarity';

  export default {
    data() {
      return {
        searchOpen: '',
        search: '',
        results: []
      }
    },
    components: {
      WebIcon
    },
    watch: {
      search() {
        if(this.search.length === 0) {
          this.results = [];
          return;
        }

        const result = [];
        this.$router.getRoutes().forEach(route => {
          if(!route.meta.search) return;
          if(!this.check(route.meta.requiresPermission)) return;

          route.meta.search.keywords.forEach(keyword => {
            this.search.split(' ').forEach(s => {
              if(stringSimilarity.compareTwoStrings(s.toLowerCase(), keyword.toLowerCase()) > .5)
                if(!result.includes(route)) result.push(route);
            });
          });
        });
        this.results = result;
      }
    },
    methods: {
      check(permissions) {
        let flag = false;

        permissions.forEach(permission => {
          if(this.$permission.checkPermission(permission.id, permission.type)) flag = true;
        });

        return flag;
      }
    },
    mounted() {
      OverlayScrollbars(document.querySelector('.header .search .list'), {
        scrollbars: { autoHide: 'leave' },
        className: 'os-theme-thin-light'
      });
    },
    computed: {
      ...mapGetters(['user', 'license'])
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/variables";
  @import "resources/sass/container";
  @import "resources/sass/themes";

  $height: 102px;

  .header {
    z-index: 9;
    height: $height;
    flex-shrink: 0;

    .fixed {
      position: fixed;
      z-index: 9;
      padding: 40px 50px;
      display: flex;
      align-items: center;
      width: calc(100% - 250px);
      height: $height;
      left: 250px;
      top: 0;

      .search {
        position: relative;

        .results {
          position: absolute;
          top: 60px;
          width: 100%;
          border-radius: 10px;
          padding: 15px 20px;
          pointer-events: none;
          opacity: 0;
          transition: opacity .3s ease;
          font-size: .9em;

          &.open {
            pointer-events: unset;
            opacity: 1;
          }

          .empty {
            text-align: center;
          }

          .list {
            max-height: 200px;

            .result {
              display: flex;
              align-items: center;
              margin-bottom: 10px;
              padding: 10px 15px;
              cursor: pointer;
              border-radius: 10px;
              background: transparent;
              transition: background .3s ease;

              &:hover {
                @include themed() {
                  background: t('block');
                }
              }

              &:last-child {
                margin-bottom: 0;
              }

              :deep(i) {
                margin-left: 10px;
                font-size: .8em;
              }
            }
          }

          @include themed() {
            background: t('input');
            border: 1px solid t('border');
          }
        }

        .visible {
          position: relative;

          i {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 20px;
            pointer-events: none;
          }

          input {
            padding-left: 55px;

            @include min(0, bp('md')) {
              width: 135px;
            }
          }
        }
      }

      @include themed() {
        background: t('background');
        border-bottom: 2px solid t('border');
      }

      @include min(0, bp('md')) {
        left: 0;
        width: 100%;
      }
    }

    .mobileMenu {
      display: none;
      cursor: pointer;

      @include min(0, bp('md')) {
        display: block;
        margin-right: 25px;
      }
    }

    .user {
      margin-left: auto;
      cursor: pointer;
      display: flex;
      align-items: center;

      .image {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-size: cover;
      }

      .settings {
        margin-left: 10px;
        font-size: 1.3em;
        opacity: .6;
        cursor: pointer;
        transition: opacity .3s ease;
      }

      &:hover {
        .settings {
          opacity: 1;
        }
      }
    }
  }
</style>
