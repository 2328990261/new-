import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ command }) => {
  const env = loadEnv(command === 'build' ? 'production' : 'development', process.cwd(), '')
  const backendTarget = env.VITE_BACKEND_URL || 'http://localhost:8000'
  const config = {
    base: command === 'build' ? '/user/' : '/',
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
          target: backendTarget,
          changeOrigin: true
        },
        '/uploads': {
          target: backendTarget,
          changeOrigin: true
        }
      }
    }
  }

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
      target: 'es2015',
      cssTarget: 'chrome80',
      rollupOptions: {
        output: {
          manualChunks: {
            vendor: ['vue', 'vue-router', 'pinia', 'element-plus', '@element-plus/icons-vue']
          }
        }
      },
      chunkSizeWarningLimit: 1500
    }
  }

  return config
})
