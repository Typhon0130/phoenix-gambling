<script>
    import Bus from '../../bus';

    export default {
        methods: {
            open(html) {
                Bus.$emit('modal:new', {
                    name: 'notification',
                    component: {
                        data() {
                            return {
                                html: html
                            }
                        },
                        template: `
                            <overlay-scrollbars :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }">
                                <div v-html="html"></div>
                            </overlay-scrollbars>`
                    }
                });
            }
        }
    }
</script>

<style lang="scss">
    @import "resources/sass/variables";

    .xmodal.notification {
        max-width: 350px;

        .os-content {
            padding-top: 10px !important;
        }
    }
</style>
