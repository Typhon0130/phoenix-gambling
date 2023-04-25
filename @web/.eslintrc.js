module.exports = {
  env: {
    node: true,
    jquery: true
  },
  extends: [
    'eslint:recommended',
    'plugin:vue/essential',
  ],
  rules: {
    // https://eslint.vuejs.org/user-guide/#usage
    // override/add rules settings here, such as:
    // 'vue/no-unused-vars': 'error',
    'no-control-regex': 'off',

    // TODO: Should be fixed
    'vue/multi-word-component-names': 'off',
    'vue/no-reserved-keys': 'off',
    'vue/require-v-for-key': 'off',
    'vue/no-use-v-if-with-v-for': 'off',
    'vue/valid-v-model': 'off',
    'vue/no-parsing-error': 'off',
    'vue/valid-v-for': 'off',
    'no-undef': 'off',
    'no-unused-vars': 'off',
    'no-irregular-whitespace': 'off'
  }
}
