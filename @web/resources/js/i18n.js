import Vue from 'vue';
import VueI18n from 'vue-i18n';

import {dateTimeFormats} from '../lang/formats/dateTimeFormats';
import {pluralizationRules} from '../lang/formats/pluralization';

Vue.use(VueI18n);

import en from '../lang/en.json';
import es from '../lang/es.json';
import fr from '../lang/fr.json';
import zh from '../lang/zh.json';

export function loadLocaleMessages() {
  return {
    en: en,
    es: es,
    fr: fr,
    zh: zh
  }
}

export const defaultLanguage = 'en';
export const languages = Object.getOwnPropertyNames(loadLocaleMessages());
export const selectedLocale = navigator.language.split('-')[0] || defaultLanguage;

export default new VueI18n({
  dateTimeFormats,
  pluralizationRules,
  locale: selectedLocale,
  fallbackLocale: 'en',
  messages: loadLocaleMessages(),
  silentTranslationWarn: true
});
