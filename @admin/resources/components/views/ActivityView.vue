<template>
  <div class="activity">
    <div class="animate">
      <div class="entries">
        <template v-if="activity">
          <div class="h">
            <div>User</div>
            <div>Message</div>
            <div>Time</div>
          </div>
          <template v-if="activity.length === 0">
            No data to show.
          </template>
          <div class="entry" v-for="entry in activity" :key="entry" @click="$router.push('/admin/user/' + entry.user._id)">
            <div>
              <img alt :src="entry.user.avatar"> {{ entry.user.name }}
            </div>
            <div v-html="entry.html"></div>
            <div>{{ entry.time }}</div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
  import OverlayScrollbars from 'overlayscrollbars';

  export default {
    data() {
      return {
        activity: null
      }
    },
    mounted() {
      OverlayScrollbars(document.querySelector('.activity .entries'), {
        scrollbars: { autoHide: 'leave' },
        className: 'os-theme-thin-light'
      });
    },
    created() {
      window.axios.post('/admin/activity').then(({ data }) => {
        this.activity = data;
        this.$bus.$emit('loading:done');
      });
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/container";

  .activity {
    .entries {
      display: flex;
      flex-direction: column;
      margin-top: 15px;

      @include min(0, bp('md')) {
        width: calc(100vw - 140px);
      }

      width: calc(100vw - 350px);

      .h, .entry {
        min-width: 1000px;
      }

      .h div, .entry div {
        width: 100%;
        flex: 1;
        margin-right: 10px;
        white-space: nowrap;

        button {
          margin-right: 10px;

          &:last-child {
            margin-right: 0;
          }
        }
      }

      .h {
        display: flex;
        font-size: 1.1em;
        margin-bottom: 20px;
        margin-top: 10px;
      }

      .entry {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        cursor: pointer;

        div {
          display: flex;
          align-items: center;

          img {
            margin-right: 10px;
            width: 32px;
            height: 32px;
          }
        }
      }
    }
  }
</style>
