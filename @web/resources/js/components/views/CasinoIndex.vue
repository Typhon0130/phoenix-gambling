<template>
  <div class="container">
    <index-slider></index-slider>

    <game-category-search :style="{ marginTop: '20px' }"></game-category-search>

    <games :isIndex="true"></games>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex';
  import AuthModal from "../modals/AuthModal.vue";
  import PasswordResetModal from "../modals/PasswordResetModal.vue";
  import Games from "../ui/GameList.vue";
  import IndexSlider from "../ui/IndexSlider.vue";
  import GameCategorySearch from "../ui/GameCategorySearch.vue";

  export default {
    computed: {
      ...mapGetters(['games', 'isGuest'])
    },
    methods: {
      openFaucetModal() {
        this.$router.push('/bonus');
      },
      openAuthModal(type) {
        AuthModal.methods.open(type);
      }
    },
    created() {
      if (this.$route.params.user && this.$route.params.token)
        PasswordResetModal.methods.open(this.$route.params.user, this.$route.params.token);
    },
    components: {
      GameCategorySearch,
      Games,
      IndexSlider
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/variables";

  .slotExplorerIndex {
    .games {
      justify-content: unset;

      @media(max-width: 1610px) {
        justify-content: center;
      }
    }

    .game_poster {
      width: calc(20% - 14px) !important;
      height: 225px !important;
      margin: 7px !important;

      @media(max-width: 1500px) {
        width: calc(25% - 14px) !important;
      }

      @media(max-width: 991px) {
        width: 155px !important;
        height: 155px !important;
        margin: 3px !important;
      }
    }

    .text .name {
      display: none;
    }

    .index_cat {
      margin-left: 0;
      margin-bottom: 15px;
    }
  }

  .index_cat_banner {
    display: flex;

    .slots {
      background: url('/img/misc/index_slots.png') no-repeat left;
    }

    .sports {
      background: url('/img/misc/sports.png') no-repeat left;
    }

    .live_games_unavailable {
      background: rgba(255, 255, 255, .025);
      border-radius: 12px;
      width: 50%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      i, svg {
        font-size: 2.5em;
        margin-bottom: 15px;
      }
    }

    .slots, .sports {
      width: 50%;
      background-size: cover;
      height: 200px;
      margin: 5px;
      cursor: pointer;
      border-radius: 12px;
      transition: all .3s ease;

      &:hover {
        transform: scale(1.01);
      }
    }

    @media(max-width: 991px) {
      .slots, .sports {
        margin: 0;
      }

      .slots {
        margin-bottom: 10px;
      }
    }
  }

  .indexCategories {
    height: 75px;
    margin-bottom: 40px;

    .os-host {
      flex: 1;
      width: 0;

      .os-content {
        display: flex;
      }
    }

    @include themed() {
      display: flex;
      padding-left: 40px;
      padding-right: 40px;
      background: t('sidebar');

      .category {
        cursor: pointer;
        transition: color .3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 25px 20px;
        white-space: nowrap;

        svg, i {
          margin-right: 10px;
        }

        &:hover {
          color: t('secondary');
        }

        &.active {
          color: t('secondary');
        }
      }
    }
  }

  @media(max-width: 450px) {
    .indexCategories {
      padding-left: 10px !important;
      padding-right: 10px !important;
    }
  }
</style>
