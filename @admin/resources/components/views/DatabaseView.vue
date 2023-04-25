<template>
  <div class="database animate" v-if="data">
    <div class="toolbar" :class="$isDemo ? 'demoDisable' : ''">
      <web-icon icon="fal fa-fw fa-chevron-left" :class="!collection || page <= 1 ? 'disabled' : ''" @click="page--; loadCollection()" v-tooltip="'Previous page'"></web-icon>
      <web-icon icon="fal fa-fw fa-chevron-right" :class="!collection || page >= pages ? 'disabled' : ''" @click="page++; loadCollection();" v-tooltip="'Next page'"></web-icon>
      <web-icon icon="fal fa-fw fa-chevron-double-right" @click="jump" :class="!collection ? 'disabled' : ''" v-tooltip="'Go to...'"></web-icon>
      <div class="separator"></div>
      <div class="right" v-if="collection">
        {{ selected.name }} ({{ page }} / {{ pages }})
      </div>
    </div>
    <div class="content" v-if="collection">
      <div class="cm" :data-cm-id="i" :key="i" v-for="(value, i) in collection">
        <div class="controls">
          <div v-tooltip="'Remove this document'" @click="remove(value._id.$oid)"
            :class="$isDemo ? 'demoDisable' : ''"><web-icon icon="fal fa-times"></web-icon></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import WebIcon from "../ui/WebIcon.vue";
  import { EditorView, basicSetup } from 'codemirror';
  import { EditorState } from '@codemirror/state';
  import { json } from "@codemirror/lang-json";
  import { keymap } from '@codemirror/view';
  import { indentWithTab } from '@codemirror/commands';
  import { oneDark, oneDarkTheme } from '@codemirror/theme-one-dark';
  import { withSidebar } from "../../utils/pageSidebar.js";

  export default {
    data() {
      return {
        data: null,
        selected: null,

        collection: null,

        page: null,
        pages: null
      }
    },
    watch: {
      selected() {
        if(!this.data) return;

        this.page = 1;
        this.pages = this.data.filter(e => e.name === this.selected.name)[0].pages;
        this.loadCollection();
      }
    },
    methods: {
      remove(id) {
        window.axios.post('/admin/database/deleteDocument/' + this.selected.name + '/' + id).then(() => this.loadCollection());
      },
      loadCodeMirror() {
        this.$nextTick(() => {
          this.collection.forEach((e, i) => {
            const element = document.querySelector(`[data-cm-id="${i}"]`);
            new EditorView({
              state: EditorState.create({
                doc: JSON.stringify(e, null, 2),
                extensions: [
                  basicSetup,
                  keymap.of([indentWithTab]),
                  oneDark,
                  oneDarkTheme,
                  json(),
                  EditorView.updateListener.of((v) => {
                    if(window.$isDemo) return;

                    if(v.docChanged) {
                      if(this.editorSaveDelayTimeout) clearTimeout(this.editorSaveDelayTimeout);
                      this.editorSaveDelayTimeout = setTimeout(() => {
                        this.editorSaveDelayTimeout = null;
                        try {
                          let text = JSON.parse(this.getCmDoc(v.state.doc));
                          if(!text._id || !text._id.$oid) {
                            this.$toast.error('This document lacks ObjectID. It won\'t be saved.');
                            return;
                          }

                          const objectId = text._id.$oid;
                          delete text['_id'];
                          window.axios.post('/admin/database/saveDocument/' + this.selected.name + '/' + objectId, {
                            object: text
                          }).catch(() => {
                            this.$toast.error('Failed to save this document.');
                          });
                        } catch (e) {
                          this.$toast.error('Invalid JSON input.');
                        }
                      }, 500);
                    }
                  })
                ]
              }),
              parent: element
            });
          });
        });
      },
      loadCollection() {
        this.collection = null;

        window.axios.post('/admin/database/collection/' + this.selected.name + '/' + this.page).then(({ data }) => {
          this.collection = data;
          this.loadCodeMirror();
        });
      },
      jump() {
        let page = prompt(`Enter page number (available pages: ${this.pages})`);
        if(!page) return;
        page = parseInt(page);
        if(isNaN(page) || page > this.pages || page < 1) return alert('Invalid page number.');
        this.page = page;
        this.loadCollection();
      },
      getCmDoc(doc) {
        let result = "";
        const append = (text, lastLineBreak) => text.forEach((line, i) => result += line + (i === text.length - 1 && lastLineBreak ? "" : "\n"));
        if(doc.text) append(doc.text, true);
        else doc.children.forEach((child, i) => append(child.text, i === doc.children.length - 1));
        return result;
      }
    },
    mounted() {
      withSidebar(() => {
        this.$bus.$on('db:select', (data) => this.selected = data);

        window.axios.post('/admin/database/collections').then(({ data }) => {
          this.data = data;
          this.$bus.$emit('dbSidebar:setData', data);

          this.$bus.$emit('loading:done');
          this.$bus.$emit('sidebarLoading:done');
        });
      });
    },
    components: {
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .database {
    width: 100%;

    [data-cm-id] {
      position: relative;
      margin-bottom: 10px;

      &:last-child {
        margin-bottom: 0;
      }

      &:hover {
        .controls {
          opacity: 1;
          pointer-events: all;
        }
      }

      .controls {
        position: absolute;
        top: 25px;
        right: 35px;

        @include themed() {
          background: t('background');
        }

        border-radius: 6px;
        z-index: 1;
        padding: 5px 10px;
        opacity: 0;
        pointer-events: none;
        transition: opacity .3s ease;

        div {
          display: flex;
          align-items: center;
          justify-content: center;
          opacity: .5;
          transition: opacity .3s ease;
          cursor: pointer;

          &:hover {
            opacity: 1;
          }

          svg {
            width: 14px;
            height: 14px;
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
      z-index: 3;

      .right {
        margin-left: auto;
      }

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
