<template>
  <div>
    <div class="pageTitle">
      Themes <i v-tooltip="'Reset'" class="far fa-fw fa-undo-alt" @click="reset()"></i>
    </div>
    <div class="themes animate" v-if="data">
      <div :style="{ display: selected === 'web' ? 'block' : 'none' }">
        <template v-if="webColors">
          <template v-for="groupKey in new Set(webColors.map(e => e.group))" :key="groupKey">
            <div class="themeName">Theme: {{ groupKey }}<div>Theme: {{ groupKey }}</div></div>
            <theme-color-picker v-for="color in webColors.filter(e => e.group === groupKey)" :key="color.key + color.group" :name="color.key" :value="color.value"
                :onChange="(v) => this.onChange(color.group, color.key, v)"></theme-color-picker>
          </template>
        </template>
        <hr>
        <div class="extend" @click="extendWeb = !extendWeb">
          Advanced configuration
          <web-icon :icon="`fal ${extendWeb ? 'fa-chevron-down' : 'fa-chevron-right'}`"></web-icon>
        </div>
        <div class="advancedConfig" :style="{ display: extendWeb ? 'block' : 'none' }">
          <div class="editor web"></div>
          <button class="btn btn-primary" @click="compile('editor')" :disabled="webText === null">Compile</button>
        </div>
      </div>
      <div :style="{ display: selected === 'dashboard' ? 'block' : 'none' }">
        <template v-if="dashboardColors">
          <template v-for="(groupKey, i) in new Set(dashboardColors.map(e => e.group))" :key="groupKey">
            <hr v-if="i > 0">
            <div class="themeName">Theme: {{ groupKey }}<div>Theme: {{ groupKey }}</div></div>
            <theme-color-picker v-for="color in dashboardColors.filter(e => e.group === groupKey)" :key="color.key + color.group" :name="color.key" :value="color.value"
                                :onChange="(v) => this.onChange(color.group, color.key, v)"></theme-color-picker>
          </template>
        </template>
        <hr>
        <div class="extend" @click="extendDashboard = !extendDashboard">
          Advanced configuration
          <web-icon :icon="`fal ${extendDashboard ? 'fa-chevron-down' : 'fa-chevron-right'}`"></web-icon>
        </div>
        <div class="advancedConfig" :style="{ display: extendDashboard ? 'block' : 'none' }">
          <div class="editor dashboard"></div>
          <button class="btn btn-primary" @click="compile('editor')" :disabled="dashboardText === null">Compile</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { EditorView, basicSetup } from 'codemirror';
  import { EditorState } from '@codemirror/state';
  import { css } from "@codemirror/lang-css";
  import { keymap } from '@codemirror/view';
  import { indentWithTab } from '@codemirror/commands';
  import { oneDark, oneDarkTheme } from '@codemirror/theme-one-dark';
  import { withSidebar } from "../../utils/pageSidebar.js";
  import WebIcon from "../ui/WebIcon.vue";
  import ThemeColorPicker from "../ui/themes/ThemeColorPicker.vue";
  import CompilerModalOutput from "../modals/CompilerModalOutput.vue";
  import LoadingModal from "../modals/LoadingModal.vue";

  export default {
    data() {
      return {
        data: null,

        extendWeb: false,
        extendDashboard: false,

        webColors: null,
        dashboardColors: null,

        selected: 'web',

        changes: [],

        webText: null,
        dashboardText: null,

        notificationSent: false
      }
    },
    methods: {
      reset() {
        LoadingModal.methods.open().then(setFlag => {
          window.axios.post('/admin/themes/reset', {
            target: this.selected
          }).then(() => {
            setFlag();
            setTimeout(() => this.$router.go(), 1000);
          }).catch((e) => CompilerModalOutput.methods.open(e.response.data.message.replaceAll(/[\u001b\u009b][[()#;?]*(?:[0-9]{1,4}(?:;[0-9]{0,4})*)?[0-9A-ORZcf-nqry=><]/g, '')));
        });
      },
      compile(mode = 'ui') {
        let text = mode === 'editor' ? this[this.selected === 'web' ? 'webText' : 'dashboardText'] : (this.selected === 'web' ? this.data.web : this.data.dashboard);

        if(mode === 'ui') {
          const lines = [];
          let currentGroup = null;

          text.replaceAll('\r', '').split('\n').forEach(line => {
            const skip = () => {
              lines.push(line);
            }

            if(!line.includes(':') || line.includes('{')) return skip();

            const split = line.split(': ');

            let name = split[0].replaceAll(' ', ''),
              value = split[1];

            if(value.endsWith(',')) value = value.substring(0, value.length - 1);

            const isGroupDef = value === '(';

            if(isGroupDef) currentGroup = name;

            if(name === '$themes' || name === 'name' || isGroupDef) return skip();

            let found = false;

            this.changes.forEach(change => {
              if(change.key === name && change.group === currentGroup) {
                lines.push(split[0] + ': ' + change.value + ',');
                found = true;
              }
            });

            if(!found) skip();
          });

          text = lines.join('\n');
        }

        LoadingModal.methods.open().then((setFlag) => {
          window.axios.post('/admin/themes/compile', {
            target: this.selected,
            text: text
          }).then(() => {
            setFlag();

            setTimeout(() => {
              if(this.selected === 'dashboard') window.location.reload();
              else this.$bus.$emit('modal:close');
            }, 1000);
          }).catch((e) => {
            this.$bus.$emit('modal:close');
            CompilerModalOutput.methods.open(e.response.data.message.replaceAll(/[\u001b\u009b][[()#;?]*(?:[0-9]{1,4}(?:;[0-9]{0,4})*)?[0-9A-ORZcf-nqry=><]/g, ''));
          });
        });
      },
      onChange(group, key, value) {
        if(!this.notificationSent) {
          this.notificationSent = true;

          this.$bus.$emit('notifications:add', {
            type: 'save',
            icon: 'fas fa-fw fa-save',
            text: 'Save the changes?',
            yes: () => {
              this.compile('ui');
              this.notificationSent = false;
            },
            no: () => {
              this.notificationSent = false;
            }
          });
        }

        const e = this.changes.filter(e => e.group === group && e.key === key)[0];
        if(e) e.value = value;
        else this.changes.push({
          group: group,
          key: key,
          value: value
        });
      },
      extractColors(doc) {
        let colors = [];

        let currentGroup = null;

        doc.replaceAll('\r', '').split('\n').forEach(e => {
          if(!e.includes(':') || e.includes('{')) return;
          const split = e.split(': ');

          let name = split[0].replaceAll(' ', ''),
            value = split[1];

          if(value.endsWith(',')) value = value.substring(0, value.length - 1);

          const isGroupDef = value === '(';

          if(isGroupDef) currentGroup = name;

          if(name === '$themes' || name === 'name' || isGroupDef) return;

          colors.push({
            group: currentGroup,
            key: name,
            value: value
          });
        });

        return colors;
      },
      getCmDoc(doc) {
        let result = "";
        const append = (text, lastLineBreak) => text.forEach((line, i) => result += line + (i === text.length - 1 && lastLineBreak ? "" : "\n"));
        if(doc.text) append(doc.text, true);
        else doc.children.forEach((child, i) => append(child.text, i === doc.children.length - 1));
        return result;
      },
      initEditor(element, content, saveKey) {
        let editorSaveDelayTimeout = null;

        new EditorView({
          state: EditorState.create({
            doc: content,
            extensions: [
              basicSetup,
              keymap.of([indentWithTab]),
              css(),
              oneDark,
              oneDarkTheme,
              EditorView.updateListener.of((v) => {
                if(v.docChanged) {
                  if(editorSaveDelayTimeout) clearTimeout(editorSaveDelayTimeout);
                  editorSaveDelayTimeout = setTimeout(() => {
                    editorSaveDelayTimeout = null;

                    this[saveKey] = this.getCmDoc(v.state.doc);
                  }, 50);
                }
              })
            ]
          }),
          parent: element
        });
      }
    },
    mounted() {
      withSidebar(() => {
        window.axios.post('/admin/themes/data').then(({ data }) => {
          this.data = data;

          this.$nextTick(() => {
            this.initEditor(document.querySelector('.themes .web'), data.web, 'webText');
            this.initEditor(document.querySelector('.themes .dashboard'), data.dashboard, 'dashboardText');

            this.webColors = this.extractColors(data.web);
            this.dashboardColors = this.extractColors(data.dashboard);
          });

          this.$bus.$emit('loading:done');
          this.$bus.$emit('sidebarLoading:done');
        });

        this.$bus.$on('themesSidebar:select', (id) => this.selected = id);
      });
    },
    components: {
      ThemeColorPicker,
      WebIcon
    }
  }
</script>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .pageTitle {
    i {
      cursor: pointer;
      font-size: 22px;
      transition: all .3s ease;
      margin-left: 20px;

      &:hover {
        transform: rotate(360deg);
      }
    }
  }

  .themes {
    position: relative;

    .title {
      font-size: 1.5em;
      margin-bottom: 15px;
    }

    .extend {
      display: flex;
      align-items: center;
      margin-bottom: 25px;
      opacity: .6;
      transition: opacity .3s ease;
      cursor: pointer;

      i {
        margin-left: 10px;
        margin-top: 2px;
      }

      &:hover {
        opacity: 1;
      }
    }

    .advancedConfig {
      .editor {
        margin-bottom: 15px;
      }

      .btn {
        margin-bottom: 25px;
      }
    }

    .themeName {
      background: linear-gradient(124deg, #ff2400, #e84c1d, #ff0b5e, #861de8, #1de840, #1ddde8, #2b1de8, #dd00f3, #dd00f3);
      background-size: 1500%;
      animation: animate 30s linear infinite;
      padding: 10px 15px;
      position: relative;
      margin-bottom: 25px;
      font-size: 1.1em;

      div {
        position: absolute;
        left: 2px;
        top: 0;
        width: calc(100% - 2px);
        height: 100%;
        padding: 10px 15px;

        @include themed() {
          background: t('background');
        }
      }
    }
  }
</style>
