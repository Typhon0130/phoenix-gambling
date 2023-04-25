<template>
  <div class="banner animate" v-if="banner">
    <div class="switch">
      <dashboard-switch :value="enabled" :onChange="toggle"></dashboard-switch>
      Enabled
    </div>
    <div class="type">
      <div class="bannerEditContent">
        <div class="smTitle">
          Edit
        </div>
        <div class="edit">
          <input type="text" v-model="title" placeholder="Title" @input="edit('title', title)">
          <input type="text" v-model="image" placeholder="Image" @input="edit('image', image)">
          <textarea placeholder="Content" v-model="content" rows="10" @input="edit('content', content)"></textarea>
        </div>
      </div>
      <div class="bannerPreviewContent">
        <div class="smTitle">
          Preview
        </div>

        <div class="preview">
          <img :src="image" alt>
          <div class="pTitle">{{ title }}</div>
          <div class="pContent" v-html="content"></div>
        </div>
      </div>
    </div>
    <div class="ogTitle">OpenGraph</div>
    <div class="ogDesc">This will be seen in social networks when someone shares a link to the website.</div>
    <div class="type">
      <div class="bannerEditContent">
        <div class="smTitle">
          Edit
        </div>
        <div class="edit">
          <input type="text" v-model="ogTitle" placeholder="Website Title" @input="edit('ogTitle', ogTitle)">
          <input type="text" v-model="ogImage" placeholder="Image (for social networks)" @input="edit('ogImage', ogImage)">
        </div>
      </div>
      <div class="bannerPreviewContent">
        <div class="smTitle">
          Preview
        </div>
        <div class="preview">
          <img :src="ogImage" alt>
          <div class="pTitle">{{ ogTitle }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import DashboardSwitch from "../ui/interactive/DashboardSwitch.vue";

  export default {
    data() {
      return {
        banner: null,
        enabled: null,
        image: '',
        title: '',
        content: '',
        ogTitle: '',
        ogImage: ''
      }
    },
    created() {
      window.axios.post('/admin/bannerSettings').then(({ data }) => {
        this.banner = data;
        this.enabled = data.enabled;
        this.image = data.image;
        this.title = data.title;
        this.content = data.content;
        this.ogTitle = data.ogSettingsWebsiteTitle;
        this.ogImage = data.ogSettingsWebsiteImage;

        this.$bus.$emit('loading:done');
      });
    },
    methods: {
      toggle() {
        this.enabled = !this.enabled;
        this.edit('state', this.enabled ? 'true' : 'false');
      },
      edit(key, value) {
        window.axios.post('/admin/bannerEdit', {
          editKey: key,
          editValue: value
        }).catch(() => {
          this.$toast.error('Failed to save');
        });
      }
    },
    components: {
      DashboardSwitch
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/variables";
  @import "resources/sass/container";
  @import "resources/sass/themes";

  .banner {
    .smTitle {
      font-size: 1.1em;
      font-weight: 400;
      margin-bottom: 15px;
    }

    .switch {
      display: flex;
      align-items: center;

      .switch {
        margin-right: 18px;
      }
    }

    .type {
      display: flex;
      margin-top: 30px;

      @include min(0, bp('md')) {
        flex-direction: column;
      }

      .bannerEditContent {
        margin-right: 30px;
        width: 20%;
        min-width: 200px;
        margin-bottom: 25px;

        @include min(0, bp('md')) {
          width: 100%;
          margin-right: 0;
        }

        .edit {
          display: flex;
          flex-direction: column;

          input {
            margin-bottom: 15px;
          }
        }
      }

      .bannerPreviewContent {
        .preview {
          img {
            width: 100%;
            min-height: 150px;
            max-width: 400px;
            min-width: 250px;
            border-radius: 10px;
            margin-bottom: 15px;
            height: 100%;

            @include themed() {
              background: t('block');
            }

            @include min(0, bp('md')) {
              min-width: 100px;
              min-height: 60px;
            }
          }

          .pTitle {
            font-weight: 400;
            font-size: 1.1em;
            margin-bottom: 15px;
          }
        }
      }
    }

    .ogTitle {
      font-size: 1.5em;
      margin-top: 15px;
      font-weight: 400;
      margin-bottom: 25px;
    }
  }
</style>
