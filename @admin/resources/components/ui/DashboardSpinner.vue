<template>
  <div class="spinner"></div>
</template>

<style lang="scss" scoped>
  @import "resources/sass/themes";

  .spinner {
    position: relative;
    height: 20px;
    width: 20px;
  }

  .spinner:before {
    content: '';
    box-sizing: border-box;
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #f6f;
    border-top-color: #0e0;
    border-right-color: #0dd;
    border-bottom-color: #f90;
    animation: spinner .6s linear infinite;
  }

  @keyframes spinner {
    to {transform: rotate(360deg);}
  }
</style>
