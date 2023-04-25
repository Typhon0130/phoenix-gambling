<template>
  <div class="games animate" v-if="games">
    <template v-for="provider in providers" :key="provider">
      <div class="provider">
        {{ provider }}
      </div>
      <div class="gameList">
        <div class="game" :class="game.isDisabled || game.isHidden ? 'disabledGame' : ''" v-for="game in games.filter(e => e.type === provider)" :key="game" @click="$router.push('/admin/game/' + game.id)">
          <div class="image" :style="{ backgroundImage: `url(${game.image})` }"></div>
          <div class="name">
            <web-icon v-if="game.isHidden" icon="far fa-eye-slash"></web-icon>
            <web-icon v-if="game.isDisabled" icon="far fa-times"></web-icon>
            {{ game.name }}
          </div>
        </div>
      </div>
    </template>
    <button class="btn btn-primary" @click="sync" :class="$isDemo ? 'demoDisable' : ''"><web-icon icon="fal fa-fw fa-sync"></web-icon> Sync</button>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import LoadingModal from "../modals/LoadingModal.vue";

  export default {
    data() {
      return {
        games: null,
        providers: []
      }
    },
    created() {
      window.axios.post('/api/data/games').then(({ data }) => {
        this.games = data;
        this.games.forEach(game => {
          if(!this.providers.includes(game.type)) this.providers.push(game.type);
        });

        this.$bus.$emit('loading:done');
      });
    },
    methods: {
      sync() {
        LoadingModal.methods.open().then(done => {
          window.axios.post('/admin/resyncGames').then(() => {
            done();
            setTimeout(() => window.location.reload(), 300);
          }).catch(() => {
            window.$bus.$emit('modal:close');
            this.$toast.error('Error');
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

  .games {
    .provider {
      font-size: 1.3em;
      margin-bottom: 15px;
    }

    .gameList {
      display: flex;
      margin-bottom: 25px;
      flex-wrap: wrap;
      align-items: center;

      .game {
        border-radius: 10px;
        position: relative;
        margin: 10px;
        cursor: pointer;
        transition: all .3s ease;

        &:hover {
          transform: scale(1.1);
        }

        &.disabledGame {
          opacity: .7;
        }

        .name {
          position: absolute;
          bottom: 15px;
          left: 15px;
          padding: 10px 15px;
          border-radius: 10px;
          background: rgba(black, .5);
          backdrop-filter: blur(20px);
          color: white;
          max-width: 80%;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;

          i {
            margin-right: 5px;
          }

          @include min(0, bp('md')) {
            font-size: .9em;
            max-width: 60%;
            padding: 5px 10px;
          }
        }

        .image {
          width: 150px;
          height: 150px;
          background-size: cover;
          background-position: center;
          border-radius: 10px;

          @include min(0, bp('md')) {
            width: 100px;
            height: 100px;

            .name {
              font-size: .9em;
              max-width: 60%;
            }
          }
        }
      }
    }
  }
</style>
