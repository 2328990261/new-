import path from "path"
import react from "@vitejs/plugin-react"
import { defineConfig, loadEnv } from "vite"

// 挂在主域名子路径时设置，例如 Nginx: location ^~ /iopaint/ { proxy_pass ... }
// 若用独立子域名反代到根路径，在 .env.production 里设 VITE_APP_BASE=/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "")
  const base = env.VITE_APP_BASE ?? "/iopaint/"

  return {
  base,
  plugins: [react()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  }
})
