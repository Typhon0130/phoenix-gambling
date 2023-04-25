<template>
  <div :class="`supportWindow draggable ${show ? 'active' : ''}`">
    <div class="head">
      {{ $t('help.modal.title') }}
      <i class="fal fa-arrow-left" v-if="pageHistory.length > 1" @click="loadHistory"></i>
      <i class="fal fa-times" @click="show = false"></i>
    </div>
    <div class="supportWindowContent">
      <transition name="fade">
        <div class="demo" v-if="$isDemo && clickedOnAnything" @click="clickedOnAnything = false;">
          <div class="demoContent">
            Support chat is not available on the demo version.
            <br><br>
            <a href="https://t.me/casino_sales" target="_blank" @click.stop>Contact us</a> to purchase this casino software.
          </div>
        </div>
      </transition>

      <template v-if="page && response">
        <template v-if="page === 'index'">
          <div class="contentWrapper">
            <div class="content">
              <overlay-scrollbars
                :options="{ scrollbars: { autoHide: 'leave' }, overflowBehavior: { x: 'hidden' }, className: 'os-theme-thin-light' }">
                <div class="padding">
                  <div class="indexTitle">{{ $t('help.modal.page.index.title') }}</div>
                  <div class="indexSubtitle">{{ $t('help.modal.page.index.description') }}</div>

                  <div v-if="$checkPermission('manageTickets')"
                       class="indexMenuEntry margin" @click="loadPage('moderate')">
                    {{ $t('help.modal.page.index.menu.moderate') }}
                    <i class="fal fa-angle-right"></i>
                  </div>

                  <div :class="$checkPermission('manageTickets') ? '' : 'margin'" class="indexMenuEntry" @click="loadPage('live_chat')">
                    {{ $t('help.modal.page.index.menu.ask_question') }}
                    <i class="fal fa-angle-right"></i>
                  </div>
                  <div class="indexMenuEntry" @click="openFAQ()">
                    {{ $t('help.modal.page.index.menu.general_questions') }}
                    <i class="fal fa-angle-right"></i>
                  </div>
                  <div class="indexSubtitle" style="font-weight: 600; margin-top: 15px;">
                    {{ $t('help.modal.page.index.my_chats') }}
                  </div>
                  <div class="previousChats mt-2">
                    <div class="empty" v-if="response.length === 0"><web-icon icon="tumbleweed"></web-icon></div>
                    <template v-else>
                      <div class="userChat" @click="currentLiveChat = chat._id; loadPage('chat', { id: chat._id });"
                           :key="chat._id" v-for="chat in response">
                        <div class="avatar">
                          <img :src="chat.messages[chat.messages.length - 1].user.avatar" alt>
                        </div>
                        <div class="message">
                          <div class="name">{{ chat.messages[chat.messages.length - 1].user.name }}</div>
                          <div class="messagePreview">{{ chat.messages[chat.messages.length - 1].message }}</div>
                        </div>
                        <div class="unread" v-if="getUnread(chat, 'user') > 0">{{ getUnread(chat, 'user') }}</div>
                      </div>
                    </template>
                  </div>
                </div>
              </overlay-scrollbars>
            </div>
          </div>
        </template>
        <template v-else-if="page === 'moderate'">
          <div class="empty" v-if="response.length === 0"><web-icon icon="tumbleweed"></web-icon></div>
          <template v-else>
            <overlay-scrollbars
              :options="{ scrollbars: { autoHide: 'leave' }, overflowBehavior: { x: 'hidden' }, className: 'os-theme-thin-light' }">
              <div class="padding">
                <div class="userChat" @click="currentLiveChat = chat._id; loadPage('chat', { id: chat._id });"
                     :key="chat._id" v-for="chat in response">
                  <div class="avatar">
                    <img :src="chat.messages[chat.messages.length - 1].user.avatar" alt>
                  </div>
                  <div class="message">
                    <div class="name">{{ chat.messages[chat.messages.length - 1].user.name }}</div>
                    <div class="messagePreview">{{ chat.messages[chat.messages.length - 1].message }}</div>
                  </div>
                  <div class="unread" v-if="getUnread(chat, 'user') > 0">{{ getUnread(chat, 'user') }}</div>
                </div>
              </div>
            </overlay-scrollbars>
          </template>
        </template>
        <template v-else-if="page === 'chat' || page === 'live_chat'">
          <div class="contentWrapper chats withFooter">
            <div class="content">
              <overlay-scrollbars ref="messages"
                                  :options="{ scrollbars: { autoHide: 'leave' }, className: 'os-theme-thin-light' }">
                <div class="padding">
                  <div :class="'message ' + (message.user._id === user.user._id ? 'own' : '')"
                       v-for="message in response.messages">
                    <div class="avatar" v-if="message.user._id !== user.user._id">
                      <img :src="message.user.avatar">
                    </div>
                    <div class="messageContent">
                      {{ message.message }}
                    </div>
                    <div class="avatar" v-if="message.user._id === user.user._id">
                      <img :src="message.user.avatar">
                    </div>
                  </div>
                </div>
              </overlay-scrollbars>
            </div>
          </div>
          <div class="chatFooter">
            <div class="text"><input ref="message" @keyup.enter.exact.prevent="sendMessage()"
                                     :placeholder="$t('general.chat.enter_message')"></div>
            <div class="send"><i class="fas fa-paper-plane" @click="sendMessage()"></i></div>
          </div>
        </template>
        <template v-else>
          Unknown page
        </template>
      </template>
      <loader v-else></loader>
    </div>
  </div>
</template>

<script>
  import Bus from '../../bus';
  import {mapGetters} from 'vuex';
  import TermsModal from "../modals/TermsModal.vue";

  export default {
    data() {
      return {
        show: false,

        pageHistory: [],
        currentLiveChat: null,
        page: null,

        clickedOnAnything: false,

        response: null
      }
    },
    computed: {
      ...mapGetters(['user', 'isGuest'])
    },
    watch: {
      isGuest() {
        this.loadPage('index', {});
      }
    },
    methods: {
      sendMessage(message = null, callback = null) {
        if (!message) message = this.$refs['message'].value;

        this.whisper('SupportMessage', {
          channel: this.currentLiveChat,
          message: message
        }).then((e) => {
          this.currentLiveChat = e.id;
          if (callback) callback();
        }, (e) => {
          switch (e) {
            case 1:
              this.$toast.error(this.$i18n.t('chat.error.length'));
              break;
            case 2:
              this.$toast.error(this.$i18n.t('chat.error.muted'));
              break;
            case 3:
              this.$toast.error('Unknown chat id');
              break;
            case 4:
              this.$toast.error(this.$i18n.t('chat.error.closed'));
              break;
          }
        });

        if (!callback) this.$refs['message'].value = '';
      },
      getUnread(chat, target) {
        let unread = 0;
        _.forEach(chat.messages, (message) => {
          if (message.created_at <= (target === 'user' ? chat.user_read : chat.support_read)) return;
          unread++;
        });
        return unread;
      },
      openFAQ() {
        TermsModal.methods.open('faq');
      },
      loadHistory() {
        if (this.pageHistory.length === 0) return;

        const entry = this.pageHistory[this.pageHistory.length < 2 ? 0 : this.pageHistory.length - 2];
        this.loadPage(entry.page, entry.data, true);

        this.pageHistory.pop();
        this.currentLiveChat = null;
      },
      addBotMessage(message) {
        this.response.messages.push({message: message, user: {avatar: '/favicon.png'}});
      },
      loadPage(page, data = {}, fromHistory = false) {
        if(page !== 'index' && this.$isDemo) {
          this.clickedOnAnything = true;
          return;
        }

        if (!fromHistory) {
          this.pageHistory.push({
            page: page,
            data: data
          });
        }

        this.response = null;
        this.page = page;

        switch (page) {
          case 'index':
            axios.post('/api/user/supportChats').then(({data}) => this.response = data);
            break;
          case 'moderate':
            axios.post('/api/chat/moderate/support').then(({data}) => this.response = data);
            break;
          case 'chat':
            axios.post('/api/user/supportChat', {id: data.id}).then(({data}) => {
              this.response = data;
              setTimeout(() => this.$refs.messages._osInstace.scroll({y: '100%'}), 150);
            });
            break;
          case 'live_chat':
            this.response = {messages: []};
            this.currentLiveChat = 'new';

            this.$nextTick(() => {
              this.addBotMessage(this.$i18n.t('help.modal.page.live_chat.welcome_message'))
            });
            break;
        }
      }
    },
    mounted() {
      Bus.$on('event:supportMessage', (e) => {
        if (this.page === 'index') this.loadPage('index');
        else if (this.currentLiveChat === e.chat._id || (e.chat.user === this.user.user._id && (this.page === 'chat' || this.page === 'live_chat'))) {
          this.response.messages.push(e.message);
          this.$nextTick(() => this.$refs.messages._osInstace.scroll({y: '100%'}));
        }
      });

      Bus.$on('event:supportMessageAdmin', (e) => {
        if (this.currentLiveChat === e.chat._id) {
          if(e.chat.user === this.user.user._id) return;

          this.response.messages.push(e.message);
          this.$nextTick(() => this.$refs.messages._osInstace.scroll({y: '100%'}));
        } else if (this.page === 'moderate') this.loadPage('moderate');
      });

      Bus.$on('toggleSupportWindow', () => this.show = !this.show);

      let x, y;
      $('.supportWindow .head:not(i, svg)').on('mousedown', function (e) {
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
        if ($(this).hasClass('noselect')) $('.supportWindow').offset({
          top: e.pageY - y,
          left: e.pageX - x
        });
      });

      this.loadPage('index', {});
    }
  }
</script>

<style lang="scss">
  @import "resources/sass/variables";

  .supportWindow {
    .os-host, .os-content {
      height: 475px;
    }

    .chats {
      .os-host, .os-content {
        height: 380px !important;
      }
    }

    @include themed() {
      bottom: 85px;
      right: $chat-width + 20px;
      width: 350px;
      height: 520px;

      .userChat {
        display: flex;
        flex-direction: row;
        align-items: center;
        position: relative;
        transition: background 0.3s ease;
        background: t('sidebar');
        cursor: pointer;
        padding: 15px;

        &:hover {
          background: t('body');
        }

        .avatar {
          display: flex;
          align-items: center;
          justify-content: center;
          margin-right: 15px;

          img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
          }
        }

        .message {
          display: flex;
          flex-direction: column;
          width: 100%;
          margin-bottom: unset !important;

          .name {
            font-weight: 600;
          }

          .messagePreview {
            text-overflow: ellipsis;
            width: 225px;
            overflow: hidden;
            white-space: nowrap;
          }
        }

        .unread {
          position: absolute;
          top: 50%;
          right: 15px;
          transform: translateY(-50%);
          border-radius: 50%;
          background: t('body');
          width: 32px;
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
          text-align: center;
          font-size: 0.8em;
        }
      }

      .supportWindowContent {
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;

        .demo {
          position: absolute;
          top: 0;
          left: 0;
          z-index: 100;
          width: 100%;
          height: calc(100% - 43px);
          background: rgba(t('sidebar'), .8);
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          backdrop-filter: blur(8px);

          .demoContent {
            width: 80%;
            text-align: center;
          }
        }

        .loaderContainer {
          margin: auto;
        }
      }

      .indexTitle {
        font-size: 2.4em;
        font-weight: 600;
      }

      .indexSubtitle {
        font-size: 1em;
        margin-top: 10px;
        opacity: .6;
        width: 80%;
      }

      .indexMenuEntry {
        background: t('sidebar');
        padding: 10px 15px;
        transition: border 0.3s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        border-bottom: 2px solid transparent;

        .fa-angle-right {
          margin-left: auto;
        }

        &:hover {
          border-bottom-color: t('secondary');
        }
      }

      .empty {
        width: fit-content;
        font-size: 6.5em;
        opacity: 0.3;
        height: fit-content;
        margin-left: auto;
        margin-right: auto;
      }

      .indexMenuEntry.margin {
        margin-top: 50px;
      }

      .message {
        display: flex;
        position: relative;
        margin-bottom: 10px;

        *:last-child {
          margin-bottom: 0;
        }

        .messageContent {
          padding: 10px 15px;
          border-radius: 3px;
          width: fit-content;
          max-width: 70%;
          font-size: 14px;
          background: lighten(t('input'), 1.5%);
          overflow-wrap: break-word;
          margin-top: 10px;

          &:first-child {
            margin-top: 0;
          }

          float: left;
          clear: both;
        }

        .avatar {
          margin-right: 10px;
          cursor: pointer;
          margin-top: auto;

          img, svg {
            width: 32px;
            height: 32px;
            border-radius: 50%;
          }
        }
      }

      .message.own {
        .avatar {
          margin-right: unset;
          margin-left: 10px;
        }

        .messageContent {
          box-shadow: 0 0 1px 2px t('secondary');
          float: right;
          color: white;
          margin-left: auto;
        }
      }

      .contentWrapper {
        height: calc(100% - 43px);
      }

      .contentWrapper.withFooter {
        height: calc(100% - 43px - 85px);
      }

      .chatFooter {
        display: flex;
        padding: 15px;
        height: 86px;

        .text {
          width: 90%;

          input {
            appearance: none;
            background: none;
            border: none;
            border-bottom: 2px solid #3c3b44;
            width: 100%;
            color: white;
            height: 56px;
          }
        }

        .send {
          display: flex;
          width: 10%;
          justify-content: center;

          i {
            color: t('secondary');
            margin-top: auto;
            margin-left: 14px;
            margin-right: auto;
            font-size: 1.1em;
            cursor: pointer;
            transition: color 0.3s ease;

            &:hover {
              color: lighten(t('secondary'), 1.5%);
            }
          }
        }
      }
    }
  }
</style>
