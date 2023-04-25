import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import eslintPlugin from 'vite-plugin-eslint';
import viteCompression from 'vite-plugin-compression';

 import path from 'path';

export default defineConfig({
  envDir: '../',
  plugins: [
    laravel({
      hotFile: '../public/@admin_hot',
      input: [
        'resources/app.js'
      ],
      refresh: true
    }),
    vue({
      template: {
        transformAssetUrls: {
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
        return { runtime: JSON.stringify(`/build/@admin/${filename}`) }
      } else {
        return { relative: true }
      }
    }
  },
  build: {
    outDir: '../public/build/@admin',
    emptyOutDir: true,
    rollupOptions: {
      output:{
        manualChunks(id) {
          if (id.includes('node_modules')) {
            return id.toString().split('node_modules/')[1].split('/')[0].toString();
          }
        }
      }
    }
  },
  resolve: {
    alias: [
      {
        find: '@',
        replacement: path.resolve(__dirname, './resources/')
      },
      {
        find: /^~(.*)$/,
        replacement: '$1',
      },
      {
        find: 'vue-i18n',
        replacement: 'vue-i18n/dist/vue-i18n.cjs.js',
      }
    ],
  }
});
