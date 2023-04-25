<template>
  <div class="container sportIndex" v-infinite-scroll="() => page++">
    <template v-if="sportGames && showPage">
      <template v-if="active">
        <div class="categoriesContainer">
          <div class="categories">
            <div class="category" v-for="category in sportGames.filter(e => e.sportType === sportType)" :key="category.id"
                 :class="category.id === active.id ? 'active' : ''"
                  @click="$router.push('/' + sportLink + '/category/' + category.id)"
                  v-if="category[sportFilter === 'live' ? 'liveCount' : 'totalCount'] > 0">
              <div class="icon">
                <web-icon :icon="category.icon"></web-icon>
              </div>
              <div class="name">
                <div>{{ category.name }}</div>
                <div v-if="sportFilter === 'live'" class="liveCount">{{ category.liveCount }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="line">
          <loader v-if="!line"></loader>
          <template v-else>
            <div class="emptyCategory" v-if="line.games.length === 0">
              <web-icon icon="time"></web-icon>
              {{ $t('sport.emptyCategoryTitle') }}
            </div>

            <button v-if="isLeagueView" class="btn btn-primary goBack" @click="$router.push('/' + sportLink + '/category/' + active.id)">
              <web-icon icon="fal fa-chevron-left"></web-icon>
              Back
            </button>

            <div class="league" v-for="(category, i) in leagues.slice(0, 3 * this.page)" :key="category">
              <div class="leagueHeader" :class="isExpanded(category, i) ? 'active' : ''"
                   @click="sportFilter === 'live' || isLeagueView ? expand(category, i) : $router.push('/' + sportLink + '/league/' + active.id + '/' + category)">
                <web-icon v-if="c(category)" :icon="c(category).icon"></web-icon>
                {{ (c(category) ? c(category).id + ' - ' : (getFlagEmoji(category.split(' - ')[0]) ? getFlagEmoji(category.split(' - ')[0]) + '&emsp;' : '')) + category }}
                <i class="toggleChevron"
                   :class="isExpanded(category, i) ? 'fal fa-chevron-down' : 'fal fa-chevron-left'"></i>
              </div>
              <div class="leagueGames" v-if="isExpanded(category, i)">
                <sport-game v-for="game in filter(line.games.filter(e => e.league.name === category))" :key="game.id" :game="game"></sport-game>
              </div>
            </div>
          </template>
        </div>
      </template>
      <loader v-else></loader>
    </template>
    <loader v-else></loader>
  </div>
</template>

<script>
  import {mapGetters} from "vuex";
  import OverlayScrollbars from "overlayscrollbars";
  import SportGame from "./components/SportGame.vue";
  import { read, save } from "../../../utils/sportCache.js";
  import { getCode } from "country-list";

  export default {
    data() {
      return {
        active: null,
        line: null,
        updateInterval: null,
        loadingLine: false,

        expandedLeagues: [],
        toggledLeaguesAtLeastOnce: [],
        showPage: true,
        page: 1
      }
    },
    watch: {
      sportFilter() {
        this.loadingLine = false;
        this.line = null;

        this.reInit();
      },
      sportType() {
        this.reInit();
      },
      active() {
        this.page = 1;
        this.line = read(this.active.id);

        clearInterval(this.updateInterval);
        this.updateInterval = setInterval(() => this.load(), 1500);

        this.load();
      },
      sportGames() {
        this.showPage = false;
        if (this.active == null) this.setCategory();

        this.$nextTick(() => {
          this.showPage = true;
          this.$nextTick(this.initScrollbars);
        });
      }
    },
    mounted() {
      if (this.sportGames != null) this.setCategory();

      this.$nextTick(this.initScrollbars);
    },
    beforeDestroy() {
      clearInterval(this.updateInterval);
    },
    methods: {
      getFlagEmoji(countryName) {
        if(countryName === 'Turkiye') countryName = 'Turkey';
        if(countryName === 'England') countryName = 'United Kingdom';
        if(countryName === 'Russia') countryName = 'Russian Federation';

        const countryCode = getCode(countryName);
        if(countryCode == null) return null;

        const codePoints = countryCode
          .toUpperCase()
          .split('')
          .map(char => 127397 + char.charCodeAt());
        return String.fromCodePoint(...codePoints);
      },
      reInit() {
        this.page = 1;
        this.showPage = false;
        this.setCategory();

        this.$nextTick(() => {
          this.showPage = true;
          this.$nextTick(this.initScrollbars);
        });
      },
      initScrollbars() {
        OverlayScrollbars(document.querySelector('.categories'), {
          scrollbars: {autoHide: 'leave'},
          className: 'os-theme-thin-light'
        });
      },
      expand(category, i) {
        if(this.sportFilter === 'upcoming') {
          if (this.isExpanded(category, i))
            this.expandedLeagues = this.expandedLeagues.filter((e) => e !== category);
          else
            this.expandedLeagues.push(category);
        } else {
          if(this.isExpanded(category, i))
            this.expandedLeagues.push(category);
          else
            this.expandedLeagues = this.expandedLeagues.filter((e) => e !== category);
        }

        this.toggledLeaguesAtLeastOnce.push(category);
      },
      c(category) {
        return this.sportGames.filter((e) => e.id === this.line.games.filter((e) => e.league.name === category)[0].category)[0];
      },
      isExpanded(category, i) {
        return (this.isLeagueView || this.sportFilter === 'live') && (!this.expandedLeagues.includes(category) || (!this.toggledLeaguesAtLeastOnce.includes(category) && this.sportFilter === 'upcoming' && i === 0));
      },
      filter(array) {
        return array.filter(e => this.sportFilter === 'live' ? e.live : true)
          .sort((a, b) => a.liveStatus.createdAt - b.liveStatus.createdAt)
          .sort((a, b) => a.live && !b.live ? 1 : -1);
      },
      setCategory() {
        const categoryWithGames = this.sportGames.filter(e => e.sportType === this.sportType && e[this.sportFilter === 'live' ? 'liveCount' : 'totalCount'] > 0)[0];
        const setFirstAvailable = () => this.active = (categoryWithGames ? categoryWithGames : this.sportGames[0]);

        if(this.$route.params.id) {
          try {
            const e = this.sportGames.filter((e) => e.id === this.$route.params.id && e[this.sportFilter === 'live' ? 'liveCount' : 'totalCount'] > 0)[0];
            if (!e) setFirstAvailable();
            else this.active = e;
          } catch (e) {
            setFirstAvailable();
          }
        } else setFirstAvailable();
      },
      load() {
        if (!this.active || this.loadingLine) return;
        const prevCatId = this.active.id;
        this.loadingLine = true;

        axios.post('/api/sport/live', { type: this.active.id, isLive: this.sportFilter === 'live' }).then(({data}) => {
          this.loadingLine = false;
          if (this.active.id !== prevCatId) return;

          this.line = data;
          save(this.active.id, data);
        }).catch(() => {
          this.loadingLine = false;
        });
      }
    },
    components: {
      SportGame
    },
    computed: {
      ...mapGetters(['sportGames', 'sportFilter']),
      isLeagueView() {
        return this.$route.params.league;
      },
      leagues() {
        let leagues = this.filter(this.line.games).map((e) => e.league.name);
        if(this.isLeagueView)
          leagues = leagues.filter(e => e === this.$route.params.league);
        return Array.from(new Set(leagues));
      }
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";
  @import "resources/sass/themes";

  .sportIndex {
    display: flex;
    flex-direction: column;

    .goBack {
      margin-bottom: 25px;
      width: 150px;

      i {
        margin-right: 5px;
      }
    }

    .emptyCategory {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      svg, i {
        @include themed() {
          color: t('secondary');
          margin-bottom: 15px;
          margin-top: 10px;
          font-size: 3em;
        }
      }
    }

    .noGames {
      text-align: center;
      font-weight: 600;
      margin-top: 50px;
      margin-bottom: 50px;
      font-size: 1.2em;
    }

    .categoriesContainer {
      display: flex;
    }

    .league {
      margin-bottom: 15px;

      &:last-child {
        margin-bottom: 0;
      }

      .leagueHeader {
        @include themed() {
          background: t('sidebar');
        }

        padding: 15px 20px;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;

        .toggleChevron {
          margin-left: auto;
          margin-right: 0;
        }

        i, svg {
          margin-right: 10px;
        }

        &.active {
          border-bottom-left-radius: 0;
          border-bottom-right-radius: 0;
        }
      }
    }

    .categories {
      display: flex;
      margin-bottom: 25px;
      max-width: calc(100vw - 30px);
      width: 0;
      flex: 1;

      @include themed() {
        border-bottom: 1px solid t('border');
      }

      .os-content {
        display: flex;
      }

      .category {
        display: flex;
        align-items: center;
        justify-content: center;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        flex-direction: column;
        margin-right: 15px;
        opacity: .5;
        transition: opacity .3s ease, background .3s ease, border .3s ease;
        cursor: pointer;
        height: 70px;
        flex-shrink: 0;
        padding: 0 25px;
        border-bottom: 1px solid transparent;
        position: relative;

        .icon {
          margin-bottom: 5px;

          i, svg {
            font-size: 1.55em;
          }
        }

        @media(max-width: 991px) {
          padding: 0 10px;
          margin-right: 0;

          .icon {
            i, svg {
              font-size: 1em;
            }
          }
        }

        .name {
          font-size: .9em;
          display: flex;

          .liveCount {
            @include themed() {
              background: t('secondary');
              color: black;
              width: 20px;
              height: 20px;
              border-radius: 50%;
              display: flex;
              align-items: center;
              justify-content: center;
              margin-left: 10px;
              font-size: .8em;
              font-weight: 600;
            }
          }
        }

        &:hover, &.active {
          opacity: 1;
        }

        &.active {
          @include themed() {
            color: t('secondary');
            background: rgba(t('secondary'), .05);
            border-color: t('secondary');
          }
        }

        &:last-child {
          margin-right: 0;
        }
      }
    }

    .line {
      display: flex;
      flex-direction: column;
    }

    .loaderContainer {
      display: flex;

      .loader {
        margin: auto;
        margin-top: 50px;
        margin-bottom: 50px;
      }
    }
  }
</style>
