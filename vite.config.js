import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],

  // Entry and output are relative to project root (not themes/app),
  // matching the SilverStripe convention of keeping build config at project root.
  build: {
    outDir: resolve(__dirname, 'themes/app/dist'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: resolve(__dirname, 'themes/app/src/js/main.js'),
    },
  },

  // Dev server: proxy /api to the SilverStripe backend.
  // In development both servers run simultaneously; in production SilverStripe
  // serves both the page and the API from a single process.
  server: {
    port: 3000,
    origin: 'http://localhost:3000',
    proxy: {
      '/api': {
        target: 'http://localhost:8001',
        changeOrigin: true,
      },
    },
  },
})
