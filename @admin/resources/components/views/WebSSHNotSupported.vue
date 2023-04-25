<template>
  <div class="page_not_found animate">
    <div class="title">Web SSH is not supported on this server OS</div>
    <div class="subtitle">You can use clients like <a href="https://www.putty.org/" target="_blank">PuTTY</a> to connect to your server.</div>
  </div>
</template>

<script>
  export default {
    created() {
      this.$bus.$emit('loading:done');
    }
  }
</script>

<style lang="scss" scoped>
  .page_not_found {
    text-align: center;
    margin-top: 50px;
    margin-bottom: 50px;

    .title {
      font-size: 3.5em;
      margin-bottom: 25px;
    }

    .subtitle {
      font-size: 1.05em;
      margin-top: 15px;
    }
  }

  @media(max-width: 1000px) {
    .page_not_found {
      .title {
        font-size: 2.0em !important;
      }

      .subtitle {
        font-size: 1.0em !important;
      }
    }
  }
</style>
