<template>
    <div class="container">
        <index-slider></index-slider>
        <game-category-search></game-category-search>
        <game-list :sort="{ type: 'provider', by: $route.params.provider }"></game-list>
    </div>
</template>
