<template>
    <div class="container">
        <index-slider></index-slider>
        <game-category-search></game-category-search>
        <game-list :sort="{ type: 'tag', by: $route.params.tag }"></game-list>
    </div>
</template>
