<template>
    <div class="customHistory" :data-custom-history="id"></div>
</template>

<script>
    import Bus from '../../../bus';
    import { mapGetters } from 'vuex';

    export default {
        data() {
            return {
                id: '_'+Math.random()
            }
        },
        watch: {
            entries() {
                if(this.entries.length > 30) this.entries.pop()
            }
        },
        computed: {
            ...mapGetters(['gameInstance'])
        },
        mounted() {
            Bus.$on('game:customHistory:add', (e) => {
                const el = $('<div>').addClass('element').html(e.text).attr('style', e.style);
                $(`[data-custom-history="${this.id}"]`).prepend(el);
                el.hide().slideDown('fast');
            });
        }
    }
</script>

<style lang="scss">
    @import "resources/sass/variables";

    .customHistory {
        position: absolute;
        right: 60px;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        flex-direction: column;
        text-align: center;
        height: 180px;
        width: 45px;
        border-radius: 5px;
        overflow: hidden;

        @include themed() {
            background: rgba(darken(t('sidebar'), 10%), .5);
        }

        .element {
            padding: 0.955em 0;
            display: flex;
            align-content: center;
            justify-content: center;
            font-size: 0.9em;
            font-weight: 600;
            color: black;
            min-height: 45px;
        }
    }

    @include media-breakpoint-down(md) {
        .customHistory {
            transform: translateY(-50%) translateX(100%) scale(0.7);
        }
    }
</style>
