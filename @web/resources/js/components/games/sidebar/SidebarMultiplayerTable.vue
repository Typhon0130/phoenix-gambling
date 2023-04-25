<template>
  <div class="sidebarMultiplayerBets">
    <overlay-scrollbars :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }">

    </overlay-scrollbars>
  </div>
</template>

<script>
  import Bus from '../../../bus';
  import {mapGetters} from 'vuex';
  import UserModal from "../../modals/UserModal.vue";

  export default {
    mounted() {
      window._openProfile = (id) => {
        UserModal.methods.open(id);
      }

      Bus.$on('sidebar:multiplayer:add', ({user, game, additional = null}) => {
        $('.sidebarMultiplayerBets .os-content').append(`
            <div class="sidebarMultiplayerBet">
                <div class="user">
                    <a href="javascript:void(0)" onclick="window._openProfile(${user._id})" target="_blank">${user.name}</a>
                </div>
                <div class="bet">
                    ${this.rawBitcoin(game.currency, game.wager)} ${this.currencies[game.currency].name}
                </div>
                ${additional ? `<div class="additional">
                    ${additional}
                </div>` : ''}
            </div>
        `);
      }, true);
      Bus.$on('sidebar:multiplayer:clear', () => $('.sidebarMultiplayerBets .os-content').html(''), true);
    },
    computed: {
      ...mapGetters(['currencies'])
    }
  }
</script>
