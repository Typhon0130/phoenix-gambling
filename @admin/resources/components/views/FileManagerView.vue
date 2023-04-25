<template>
  <div class="fileManager animate" v-if="data">
    <div class="toolbar" :class="$isDemo ? 'demoDisable' : ''">
      <web-icon icon="fas fa-fw fa-upload" @click="upload" v-tooltip="'Upload'"></web-icon>
      <web-icon icon="fas fa-fw fa-download" :class="!selected || selected.isDir || selected.isPrevious ? 'disabled' : ''" @click="download" v-tooltip="'Download'"></web-icon>
      <web-icon icon="fas fa-fw fa-i-cursor" :class="!selected || selected.isPrevious ? 'disabled' : ''" @click="beginRename()" v-tooltip="'Rename'"></web-icon>
      <web-icon icon="fas fa-fw fa-trash" :class="!selected || selected.isPrevious ? 'disabled' : ''" @click="deleteFile" v-tooltip="'Delete'"></web-icon>
      <div class="separator"></div>
      <web-icon icon="fas fa-fw fa-folder-plus" @click="newDir" v-tooltip="'New directory'"></web-icon>
    </div>
    <div class="files" @click="selected = null">
      <div class="file" v-if="path !== ''" @click.stop="select({ isDir: true, path: pathLower, isPrevious: true })" :class="selected && selected.path === pathLower ? 'selected' : ''">
        <div class="preview">
          <web-icon icon="fas fa-fw fa-folder"></web-icon>
        </div>
        <div class="name">
          ...
        </div>
      </div>
      <div class="file" v-for="file in data" :key="file" @click.stop="select(file)" :class="[selected && selected.path === file.path ? 'selected' : '', file.warn ? 'warn' : '']">
        <div class="preview">
          <template v-if="file.isDir">
            <web-icon icon="fas fa-fw fa-folder"></web-icon>
          </template>
          <template v-else>
            <template v-if="file.preview">
              <div class="imagePreviewContainer" v-if="file.preview.type === 'image'">
                <img alt="Preview" :src="file.preview.content">
              </div>
              <template v-else>
                <web-icon icon="fas fa-fw fa-question-circle"></web-icon>
              </template>
            </template>
            <template v-else>
              <web-icon icon="fas fa-fw fa-file"></web-icon>
            </template>
          </template>
        </div>
        <div class="name">
          <template v-if="selected && selected.path === file.path && selected._renaming">
            <input :id="'r_' + selected.name.replace(/[^\w\s]/gi, '')" type="text" :value="selected.name" @keydown.enter="rename($event.target.value)" @keydown.esc="selected._renaming = false" @blur="selected._renaming = false">
          </template>
          <template v-else>
            {{ file.name }}
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import { withSidebar } from "../../utils/pageSidebar.js";

  export default {
    data() {
      return {
        data: null,
        path: '',
        selected: null,

        isLoading: false
      }
    },
    computed: {
      pathLower() {
        return this.path.substring(0, this.path.lastIndexOf('/'));
      }
    },
    methods: {
      upload() {
        const input = document.createElement('input');
        input.type = 'file';
        input.click();

        input.onchange = () => {
          let data = new FormData();

          data.append('file', input.files[0]);
          data.append('path', this.path);

          window.axios.post('/admin/fileManager/upload', data).then(() => {
            this.reload(this.path);
          }).catch(() => this.$toast.error('Failed to upload'));
        }
      },
      newDir() {
        const name = prompt('Directory name:');
        if(name) {
          window.axios.post('/admin/fileManager/newDir', { path: this.path + '/' + name }).then(() => {
            this.reload(this.path);
          }).catch(() => this.$toast.error('Failed to mkdir'));
        }
      },
      deleteFile() {
        window.axios.post('/admin/fileManager/delete', { path: this.selected.path }).then(() => {
          this.reload(this.path);
        }).catch(() => this.$toast.error('Failed to delete'));
      },
      beginRename() {
        this.selected._renaming = true;
        this.$nextTick(() => document.querySelector('#r_' + this.selected.name.replace(/[^\w\s]/gi, '')).focus());
      },
      rename(value) {
        this.selected._renaming = false;

        window.axios.post('/admin/fileManager/rename', { path: this.selected.path, name: this.selected.dirPath + value }).then(() => {
          this.reload(this.path);
        }).catch(() => this.$toast.error('Failed to rename'));
      },
      download() {
        window.open('/admin/fileManager/download/' + this.selected.path, '_blank');
      },
      reload(path) {
        this.isLoading = true;

        window.axios.post('/admin/fileManager/list', { path: path }).then(({ data }) => {
          this.selected = null;
          this.path = path;
          this.data = data;

          this.$bus.$emit('filesSidebar:setData', {
            path: this.path,
            data: this.data
          });

          this.isLoading = false;
        }).catch(() => {
          this.$toast.error('Failed to load directory');
          this.isLoading = false;
        });
      },
      select(file, skipSelect = false) {
        if(this.selected && this.selected._renaming) return;
        if(this.isLoading) return;

        if(skipSelect || (this.selected && this.selected.path === file.path)) {
          if(file.isDir) {
            this.reload(file.path);
          } else {
            this.download();
            this.selected = null;
          }

          return;
        }

        this.selected = file;
      }
    },
    mounted() {
      withSidebar(() => {
        window.axios.post('/admin/fileManager/list').then(({ data }) => {
          this.data = data;

          this.$bus.$emit('filesSidebar:setData', {
            path: this.path,
            data: this.data
          });

          this.$bus.$emit('loading:done');
          this.$bus.$emit('sidebarLoading:done');
        });

        this.$bus.$on('files:select', (file) => this.select(file, true));
      });
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";
  @import "resources/sass/container";

  .fileManager {
    width: 100%;

    .files {
      display: flex;
      flex-wrap: wrap;

      @include min(0, bp('md')) {
        justify-content: center;
      }

      .file {
        margin: 5px;
        padding: 10px;
        border-radius: 10px;
        transition: background .3s ease;
        background: transparent;
        text-align: center;
        width: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: fit-content;
        user-select: none;

        &.warn {
          @include themed() {
            color: t('criticalColor');
          }
        }

        &:hover {
          @include themed() {
            background: t('border');
          }
        }

        &.selected {
          @include themed() {
            background: t('input');
          }
        }

        .name {
          white-space: nowrap;
          width: 80%;
          text-overflow: ellipsis;
          overflow: hidden;
          text-align: center;
          margin: auto;
          margin-top: 5px;
          cursor: default;
          font-size: .9em;

          input {
            padding: 0;
            background: transparent;
            text-align: center;
            width: 100%;
            border-radius: 0;
          }
        }

        .preview {
          i {
            font-size: 4em;
            margin-top: 5px;
            margin-bottom: 5px;
          }

          .imagePreviewContainer {
            width: 78px;
            height: 68px;
            display: flex;
            overflow: hidden;

            img {
              width: 100%;
              border-radius: 5px;
              margin: auto;
              object-position: center;
            }
          }
        }
      }
    }

    .toolbar {
      width: 100%;
      position: sticky;
      top: 102px;
      padding: 15px 20px;
      display: flex;

      .separator {
        margin-right: 10px;
      }

      i {
        cursor: pointer;
        transition: all .3s ease;
        margin-right: 15px;

        &:hover {
          transform: scale(1.2);
        }
      }

      @include themed() {
        background: t('border');
      }
    }
  }
</style>
