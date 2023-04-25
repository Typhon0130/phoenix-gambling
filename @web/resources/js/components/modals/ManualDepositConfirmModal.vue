<script>
    import Bus from '../../bus';
    import { mapGetters } from 'vuex';

    export default {
        methods: {
            open() {
                Bus.$emit('modal:new', {
                    name: 'manualConfirmDeposit',
                    component: {
                        computed: {
                            ...mapGetters(['currencies', 'currency'])
                        },
                        data() {
                            return {
                                txId: '',

                                expand: false,
                                currencyPicked: null,

                                checking: false
                            }
                        },
                        methods: {
                            verify() {
                                if(this.checking) return;
                                this.checking = true;

                                let txId = this.txId;
                                if(txId.endsWith("/")) txId = txId.substring(0, txId.length - 1);
                                if(txId.includes("/")) txId = txId.substring(txId.lastIndexOf('/') + 1);

                                axios.get('/api/walletNotify/' + this.currencyPicked.id + '/' + txId).then(({ data }) => {
                                    this.checking = false;

                                    if(data.result === 'Success') this.$toast.success(data.result);
                                    else this.$toast.error(data.result);
                                }).catch(() => {
                                    this.$toast.error('Unknown error. Contact support');
                                    this.checking = false;
                                });
                            }
                        },
                        created() {
                            this.currencyPicked = this.currencies[this.currency];
                        },
                        template: `
                          <div>
                            <div class="description">Verify transaction through this window if your deposit didn't show up after 5 minutes.</div>
                            <!--<div class="txIdExample" v-tooltip="'Insert transaction id (green underline) as seen on this example.'"></div>-->
                            <div class="walletExchangeSelector" @click="expand = !expand">
                                <div class="wesContainer">
                                    <div class="icon"><web-icon :icon="currencyPicked.icon" :style="{ color: currencyPicked.style }"></web-icon></div>
                                    <div class="name">{{ currencyPicked.name }}</div>
                                </div>
                                <div class="exchangeList" v-if="expand">
                                    <overlay-scrollbars :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }">
                                        <div class="elEntry" v-for="currency in currencies" v-if="currency.balance" @click="currencyPicked = currency">
                                            <div class="icon"><web-icon :icon="currency.icon" :style="{ color: currency.style }"></web-icon></div>
                                            <div class="name">{{ currency.name }}</div>
                                        </div>
                                    </overlay-scrollbars>
                                </div>
                            </div>
                            <input placeholder="Transaction id" v-model="txId">
                            <button class="btn btn-primary" @click="verify" :disabled="checking || txId.length < 8">Verify</button>
                          </div>`
                    }
                });
            }
        }
    }
</script>

<style lang="scss">
    @import "resources/sass/variables";
    @import "resources/sass/themes";

    .xmodal.manualConfirmDeposit {
        max-width: 410px;

        .modal_content {
            padding: 40px !important;

            .description {
                text-align: center;
            }

            .txIdExample {
                background: url('/img/misc/txid_example.png');
                height: 33px;
                margin-left: -40px;
                width: calc(100% + 80px);
                margin-top: 10px;
                margin-bottom: 25px;
            }

            input {
                margin-bottom: 15px;
                margin-top: 50px;
            }

            btn {
                margin-top: 10px;
            }

            @include themed() {
                .walletExchangeSelector {
                    position: relative;
                    cursor: pointer;
                    width: 100px;
                    margin: auto;
                    margin-bottom: 20px;
                    margin-top: 20px;

                    &:last-child {
                        margin-right: 0;
                    }

                    .exchangeList {
                        position: absolute;
                        left: 0;
                        width: 100%;
                        z-index: 5;

                        .os-host {
                            max-height: 300px;
                        }

                        .elEntry {
                            background: t('body');
                            transition: background .3s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            padding: 10px 0;

                            &:hover {
                                background: lighten(t('body'), 5%);
                            }

                            .icon {
                                width: 25px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin-right: 5px;
                            }
                        }
                    }

                    .wesContainer {
                        display: flex;
                        padding: 6px 13px;
                        border-radius: 3px;
                        background: t('body');
                        transition: background .3s ease;

                        &:hover {
                            background: lighten(t('body'), 5%);
                        }

                        .icon {
                            width: 30px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin-right: 5px;
                        }

                        .name {
                            margin-right: 10px;
                        }
                    }
                }
            }
        }
    }
</style>
