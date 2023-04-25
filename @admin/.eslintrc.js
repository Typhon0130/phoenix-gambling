module.exports = {
  env: {
    node: true,
  },
  extends: [
    'eslint:recommended',
    'plugin:vue/vue3-essential',
  ],
  rules: {
    // https://eslint.vuejs.org/user-guide/#usage
    // override/add rules settings here, such as:
    // 'vue/no-unused-vars': 'error',
    'no-control-regex': 'off'
  }
}
