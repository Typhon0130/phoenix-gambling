<template>
    <div class="search" :class="show ? 'show' : ''">
        <div class="container">
            <div class="input">
                <input id="searchInput" v-model="search" placeholder="Search...">
                <web-icon icon="fal fa-search" class="search"></web-icon>
                <div @click="show = false" class="close"><web-icon icon="fal fa-times" class="close"></web-icon></div>
            </div>
            <overlay-scrollbars :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }">
                <div class="results">
                    <div class="result" @click="show = false; $router.push(result._searchUrl)" v-for="result in results" :key="result.id">
                        <img :src="result.icon.replace('/s3', '/s2')" alt onerror="this.src = '/img/misc/unknown-game-image.jpg'">
                    </div>
                </div>
            </overlay-scrollbars>
        </div>
    </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import Bus from "../../bus";

    export default {
        computed: {
            ...mapGetters(['games'])
        },
        data() {
            return {
                show: false,
                search: '',
                results: []
            }
        },
        watch: {
            show() {
                if(this.show) {
                    setTimeout(() => {
                        document.querySelector('#searchInput').focus();
                    });
                }
            },
            search() {
                if(this.search === '' || this.search.length < 3) {
                    this.results = [];
                    return;
                }
                let results = [];

                const isMobile = window.innerWidth <= 991;

                this.games.forEach((game) => {
                    if(game.isMobile !== null)
                        if((game.isMobile && !isMobile) || (!game.isMobile && isMobile)) return;

                    if(game.name.toLowerCase().includes(this.search.toLowerCase()
                        || game.id.toLowerCase().includes(this.search.toLowerCase()))
                        || game.category[0].toLowerCase().includes(this.search.toLowerCase()) ) {
                        game._searchUrl = '/casino/game/' + game.id;
                        results.push(game);
                    }
                })
                this.results = results;
            }
        },
        created() {
            Bus.$on('search:toggle', () => this.show = !this.show);
        }
    }
</script>

<style lang="scss">
    @import "resources/sass/themes";

    .search {
        position: fixed;
        top: 0;
        left: 0;
        background: rgba(black, .8);
        backdrop-filter: blur(15px);
        z-index: 99999;
        pointer-events: none;
        width: 100%;
        height: 100%;
        opacity: 0;
        transition: opacity .3s ease;

        &.show {
            opacity: 1;
            pointer-events: unset;
        }

        .results {
            height: calc(100vh - 250px);
            display: flex;
            flex-wrap: wrap;
            justify-content: center;

            .os-content {
                display: flex;
                flex-wrap: wrap;
            }
        }

        .result {
            cursor: pointer;
            transition: all .3s ease;
            margin-right: 15px;
            margin-bottom: 15px;
            width: 260px;
            height: 170px;
            border-radius: 10px;

            @include themed() {
                background-color: t('sidebar');
            }

            &:hover {
                transform: scale(1.02);
            }

            img {
                width: 260px;
                height: 170px;
                border-radius: 10px;
            }
        }

        .input {
            margin-top: 50px;
            margin-bottom: 50px;
            position: relative;

            .icon {
                position: absolute;
                left: 30px;
                top: 50%;
                transform: translateY(-50%);
                opacity: .5;
            }

            .close {
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                opacity: .5;
                cursor: pointer;
                transition: opacity .3s ease;
                text-shadow: unset;
                color: white;

                &:hover {
                    opacity: 1;
                }
            }

            input {
                background: rgba(163, 190, 245, .1);
                border: none;
                border-radius: 100px;
                box-shadow: 0 0 0 3px transparent;
                color: #fff;
                font-size: 20px;
                outline: none;
                padding: 0 70px;
                transition: box-shadow .1s ease,background .1s ease,color .1s ease;
                width: 100%;
                height: 80px;
            }
        }
    }
</style>
