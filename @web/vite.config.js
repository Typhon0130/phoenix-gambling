import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import eslintPlugin from 'vite-plugin-eslint';
import viteCompression from 'vite-plugin-compression';

import path from 'path';

export default defineConfig({
  envDir: '../',
  plugins: [
    laravel({
      hotFile: '../public/@web_hot',
      input: [
        'resources/js/app.js'
      ],
      refresh: true
    }),
    vue({
      template: {
        transformAssetUrlsOptions: {
          base: null,
          includeAbsolute: false
        }
      }
    }),
    eslintPlugin(),
    viteCompression({
      algorithm: 'brotliCompress'
    })
  ],
  experimental: {
    renderBuiltUrl(filename, { hostType }) {
      if (hostType === 'js') {
        return { runtime: JSON.stringify(`/build/@web/${filename}`) }
      } else {
        return { relative: true }
      }
    }
  },
  build: {
    outDir: '../public/build/@web',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            return id.toString().split('node_modules/')[1].split('/')[0].toString();
          }
        },
      }
    }
  },
  resolve: {
    alias: [
      {
        find: 'vue',
        replacement: 'vue/dist/vue.min.js'
      },
      {
        find: '@',
        replacement: path.resolve(__dirname, './resources/')
      },
      {
        find: /^~(.*)$/,
        replacement: '$1',
      }
    ]
  }
});
