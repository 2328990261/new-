import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig(({ command }) => {
  const config = {
    base: command === 'build' ? '/admin/' : '/',
    // 减少控制台输出 - 设置为silent完全不显示
    logLevel: 'info',
    plugins: [
      vue({
        template: {
          compilerOptions: {
            whitespace: 'preserve',
          }
        }
      })
    ],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src')
      }
    },
    server: {
      host: '0.0.0.0',
      port: 3002,
      hmr: {
        overlay: false
      },
      proxy: {
        '/admin/api': {
          target: 'http://localhost:8000',
          changeOrigin: true,
          timeout: 300000,
          proxyTimeout: 300000
        },
        '/api': {
          target: 'http://localhost:8000',
          changeOrigin: true,
          timeout: 300000,
          proxyTimeout: 300000
        },
        '/uploads': {
          target: 'http://localhost:8000',
          changeOrigin: true
        }
      }
    }
  };
  
  // 生产环境特殊配置
  if (command === 'build') {
    config.build = {
      minify: 'esbuild', // 使用 esbuild 代替 terser，内存占用更低
      sourcemap: false, // 禁用 sourcemap 减少内存使用
      // 浏览器兼容性设置
      target: 'es2015',
      cssTarget: 'chrome80',
      rollupOptions: {
        output: {
          manualChunks: {
            'vendor': ['vue', 'vue-router', 'pinia', 'element-plus', '@element-plus/icons-vue'] // 打包所有第三方库到一起
          }
        }
      },
      chunkSizeWarningLimit: 1500 // 提高块大小警告限制
    };
  }
  
  return config;
});
