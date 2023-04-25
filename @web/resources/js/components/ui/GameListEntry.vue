<template>
  <div class="category-game" @click="game.isDisabled ? false : $router.push('/casino/game/' + game.id)">
    <div class="axes"><div></div></div>

    <div class="hover">
      <div class="playButton"></div>

      <div class="bottomInfo">
        <div class="gameName">{{ game.name }}</div>
        <div class="hover-category">{{ game.type }}</div>
      </div>
    </div>
    <div class="unavailable" v-if="game.isDisabled">
      <div class="slanting">
        <div class="content">
          {{ $t(game.isPlaceholder ? 'general.coming_soon' : 'general.not_available') }}
        </div>
      </div>
    </div>
    <div class="image" :style="{ backgroundImage: `url(${game.image})` }"></div>
    <div class="gameInfo" v-if="game.type !== 'Originals (Classic)'">
      <div>{{ game.name }}</div>
      <div>{{ game.type }}</div>
    </div>
  </div>
</template>

<script>
  export default {
    props: [
      'game'
    ]
  }
</script>
