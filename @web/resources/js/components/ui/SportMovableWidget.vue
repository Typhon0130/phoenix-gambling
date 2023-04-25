<template>
  <div class="sportMovableWidget" v-if="show">
    <div class="head">
      {{ $t('general.sportWidgetLive.title') }}
      <i class="fal fa-times" @click="show = false"></i>
    </div>
    <div class="content" id="movableWidgetContent">
      <div id="movableWidget"></div>
    </div>
  </div>
</template>

<script>
  import Bus from '../../bus';

  export default {
    data() {
      return {
        show: false,
        game: null
      }
    },
    watch: {
      show() {
        if(this.show) {
          this.$nextTick(() => {
            let x, y;
            $('.sportMovableWidget .head:not(.head i)').on('mousedown', function (e) {
              if (e.offsetX === undefined) {
                x = e.pageX - $(this).offset().left;
                y = e.pageY - $(this).offset().top;
              } else {
                x = e.offsetX;
                y = e.offsetY;
              }

              $('body').addClass('noselect');
            });

            $('body').on('mouseup', function (e) {
              $('body').removeClass('noselect');
            });

            $('body').on('mousemove', function (e) {
              if ($(this).hasClass('noselect')) $('.sportMovableWidget').offset({
                top: e.pageY - y,
                left: e.pageX - x
              });
            });
          });
        }
      }
    },
    created() {
      Bus.$on('sportMovableWidget:toggle', (game) => {
        this.show = true;

        this.$nextTick(() => {
          document.querySelector('#movableWidgetContent').innerHTML = '<div id="movableWidget"></div>';
          this.game = game;

          if(this.game.sportType === 'SPORTS') {
            if (window.SIR) {
              window.SIR('addWidget', '#movableWidget', 'match.lmtPlus', {matchId: this.game.id});
            }
          } else if(this.game.sportType === 'ESPORTS') {
            if(!this.game.twitch)
              document.querySelector('#movableWidgetContent').innerHTML = '<div class="notStarted">' + this.$i18n.t('sport.widgetUnavailable') + '</div>';
            else if(window.Twitch) {
              new window.Twitch.Embed('movableWidget', {
                width: '100%',
                height: 190,
                channel: this.game.twitch.substring(this.game.twitch.lastIndexOf('/') + 1),
                layout: "video"
              });
            }
          }
        });
      });
    }
  }
</script>
