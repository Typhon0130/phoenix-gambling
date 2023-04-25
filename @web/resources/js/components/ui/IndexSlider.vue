<template>
  <div class="slider">
    <div class="glide" id="slider">
      <div class="glide__track" data-glide-el="track">
        <ul class="glide__slides">
          <li class="glide__slide"
              :style="{ backgroundImage: `url('/img/misc/slider/pf.png') !important`, backgroundSize: 'cover !important', backgroundPosition: 'center !important' }"></li>
          <li class="glide__slide"
              :style="{ backgroundImage: `url('/img/misc/slider/main.png') !important`, backgroundSize: 'cover !important', backgroundPosition: 'center !important' }"></li>
          <li class="glide__slide"
              :style="{ backgroundImage: `url('/img/misc/slider/lossback.png') !important`, backgroundSize: 'cover !important', backgroundPosition: 'center !important' }"></li>
        </ul>
      </div>
      <div class="glide__arrows" data-glide-el="controls">
        <button class="glide__arrow glide__arrow--left" data-glide-dir="<"></button>
        <button class="glide__arrow glide__arrow--right" data-glide-dir=">"></button>
      </div>
      <div class="glide__bullets" data-glide-el="controls[nav]">
        <button class="glide__bullet" data-glide-dir="=0"></button>
        <button class="glide__bullet" data-glide-dir="=1"></button>
        <button class="glide__bullet" data-glide-dir="=2"></button>
      </div>
    </div>
  </div>
</template>

<script>
  import Glide from '@glidejs/glide';

  export default {
    mounted() {
      this.initSlider();
    },
    methods: {
      initSlider() {
        this.$nextTick(() => {
          new Glide('#slider', {
            type: 'carousel',
            perView: 1,
            peek: 350,
            focusAt: 'center',
            gap: 20,
            autoplay: 2500,
            keyboard: false,

            breakpoints: {
              600: {
                peek: 50,
                gap: 10,
              },
              1050: {
                peek: 100
              },
              1170: {
                peek: 150
              },
              1300: {
                peek: 210
              },
              1500: {
                peek: 200
              }
            }
          }).mount();
        });
      }
    }
  }
</script>
