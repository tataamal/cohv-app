import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js', 'resources/scss/app.scss'],
      refresh: true,
    }),
    visualizer({
      filename: 'stats.html',
      open: true,
      gzipSize: true,
      brotliSize: true,
    }),
  ],
  build: {
    chunkSizeWarningLimit: 1500,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) return;

          if (id.includes('vue')) return 'vendor-vue';
          if (id.includes('react')) return 'vendor-react';
          if (id.includes('chart.js') || id.includes('echarts')) return 'vendor-charts';
          if (id.includes('monaco-editor')) return 'vendor-editor';
          if (id.includes('xlsx')) return 'vendor-xlsx';
          if (id.includes('three')) return 'vendor-three';
          if (id.includes('codemirror')) return 'vendor-codemirror';
          if (id.includes('lodash')) return 'vendor-lodash';
          if (id.includes('dayjs') || id.includes('moment')) return 'vendor-dates';
          if (id.includes('axios')) return 'vendor-axios';

          return 'vendor';
        },
      },
    },
  },
});