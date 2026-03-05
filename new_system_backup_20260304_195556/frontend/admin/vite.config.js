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
          target: 'http://localhost:8080',
          changeOrigin: true,
          timeout: 300000,
          proxyTimeout: 300000
        },
        '/api': {
          target: 'http://localhost:8080',
          changeOrigin: true,
          timeout: 300000,
          proxyTimeout: 300000
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
      minify: 'terser', // 使用 terser 以支持更多压缩选项
      sourcemap: false, // 禁用 sourcemap 减少内存使用
      // 浏览器兼容性设置
      target: 'es2015',
      cssTarget: 'chrome80',
      // Terser 配置 - 移除console和debugger
      terserOptions: {
        compress: {
          drop_console: true,  // 移除所有console
          drop_debugger: true, // 移除debugger
          pure_funcs: ['console.log', 'console.info', 'console.debug'] // 移除特定console方法
        },
        format: {
          comments: false // 移除注释
        }
      },
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
