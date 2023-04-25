<template>
  <div class="footer">
    <div class="phoenix" onclick="window.open('https://phoenix-gambling.com', '_blank')">
      <img src="/img/phoenix.png" alt> <a href="javascript:void(0)">Phoenix Gambling</a>
    </div>
    All right reserved.
  </div>
</template>

<style lang="scss" scoped>
  @import "resources/sass/variables";

  .footer {
    padding: 30px 40px;
    border-top: 2px solid $border;
    margin-top: auto;
    display: flex;
    align-items: center;

    .phoenix {
      display: flex;
      align-items: center;
      margin-right: 10px;

      img {
        width: 30px;
        height: 30px;
        margin-right: 10px;
        cursor: pointer;
      }
    }
  }
</style>
