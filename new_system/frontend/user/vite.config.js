import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig(({ command }) => {
  const config = {
    base: command === 'build' ? '/user/' : '/',
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
      port: 3001,
      proxy: {
        '/api': {
          target: 'http://localhost:8080',
          changeOrigin: true
        },
        '/uploads': {
          target: 'http://localhost:8080',
          changeOrigin: true
        }
      }
    }
  };
  
  // 生产环境特殊配置
  if (command === 'build') {
    config.build = {
      minify: 'terser',
      terserOptions: {
        compress: {
          drop_console: true,
          drop_debugger: true,
          pure_funcs: ['console.log', 'console.info', 'console.debug']
        }
      },
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
