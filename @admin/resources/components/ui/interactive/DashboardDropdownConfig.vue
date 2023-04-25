<template>
  <div class="dropdownConfig" :class="show ? 'active' : ''">
    <div class="slot btn btn-primary" @click="show = !show">
      <slot></slot>
    </div>
    <div class="menu" v-if="show" v-click-outside="() => show = false">
      <div class="entry" v-for="(entry, i) in values" :key="i">
        <template v-if="entry.type === 'switch'">
          <div class="e">
            <dashboard-switch :value="entry.value === true" :onChange="(v) => entry.event(v, this)"></dashboard-switch>
            {{ entry.title }}
          </div>
        </template>
        <template v-if="entry.type === 'datepicker'">
          <div class="f">
            <div>{{ entry.title }}</div>
            <datepicker :clearable="false" :autoApply="true" ref="datepicker" minimumView="time" v-model="entry.value"
              @update:modelValue="(v) => { updatePickerValue(v); entry.value = v; entry.event(v, this); }" format="dd-MM-yyyy HH:mm" :dark="dashboardTheme !== 'light'"
              @closed="updatePickerValue(entry.value)"
              @open="updatePickerValue(entry.value)"
              @blur="updatePickerValue(entry.value)"
              @flowStep="updatePickerValue(entry.value)"
              @focus="updatePickerValue(entry.value)"
              @internalModelChange="updatePickerValue(entry.value)"></datepicker>
          </div>
        </template>
        <template v-if="entry.type === 'input'">
          <div class="f">
            <div>{{ entry.title }}</div>
            <input :disabled="disabled.includes(entry.title)" :placeholder="entry.title" :value="entry.value" type="text" @input="entry.event($event.target.value, this)">
          </div>
        </template>
        <template v-if="entry.type === 'select'">
          <div class="f">
            <div>{{ entry.title }}</div>
            <select @change="entry.event($event.target.value, this)">
              <option :value="value[0]" :key="value" v-for="value in entry.values" :selected="entry.value === value[0]">{{ value[1] }}</option>
            </select>
          </div>
        </template>
        <template v-else-if="entry.type === 'button'">
          <button class="btn btn-primary" v-html="entry.title" @click="entry.event(this)" :class="entry.disabled ? 'disabled' : ''"></button>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
  import DashboardSwitch from "./DashboardSwitch.vue";
  import Datepicker from '@vuepic/vue-datepicker';
  import { mapGetters } from 'vuex';

  export default {
    data() {
      return {
        show: false,

        disabled: []
      }
    },
    computed: {
      ...mapGetters(['dashboardTheme'])
    },
    methods: {
      updatePickerValue(v) {
        this.$nextTick(() => document.querySelector('.dropdownConfig .dp__input').value = new Date(v).toISOString());
      }
    },
    props: [ 'values' ],
    components: {
      DashboardSwitch,
      Datepicker
    }
  }
</script>

<style lang="scss" scoped>
  @import "../../../sass/themes";

  .dropdownConfig {
    position: relative;

    .menu {
      position: absolute;
      top: 50px;
      left: 0;
      border-radius: 10px;
      padding: 15px 20px;

      @include themed() {
        background: t('block-2');
        box-shadow: 0 0 1px rgba(t('block'), .5);
      }

      select {
        width: 100%;
      }

      .entry {
        margin-bottom: 15px;

        &:last-child {
          margin-bottom: 0;
        }
      }

      .f {
        div:first-child {
          margin-bottom: 10px;
          font-size: 1.1em;
        }
      }

      .e {
        display: flex;
        align-items: center;

        .switch {
          margin-right: 15px;
        }
      }
    }

    .slot {
      font-size: .9em;
    }
  }
</style>
