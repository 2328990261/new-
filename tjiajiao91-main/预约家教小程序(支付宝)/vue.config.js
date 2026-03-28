module.exports = {
  // 生产环境配置
  productionSourceMap: false,
  
  configureWebpack: config => {
    // 生产环境移除console
    if (process.env.NODE_ENV === 'production') {
      config.optimization = {
        ...config.optimization,
        minimizer: [
          ...config.optimization.minimizer || [],
        ]
      }
      
      // 配置terser移除console
      const TerserPlugin = require('terser-webpack-plugin')
      config.optimization.minimizer.push(
        new TerserPlugin({
          terserOptions: {
            compress: {
              drop_console: true,  // 移除console
              drop_debugger: true, // 移除debugger
              pure_funcs: ['console.log'] // 移除console.log
            },
            output: {
              comments: false // 移除注释
            }
          },
          extractComments: false
        })
      )
    }
  },
  
  chainWebpack: config => {
    // 生产环境优化
    if (process.env.NODE_ENV === 'production') {
      // 移除prefetch和preload
      config.plugins.delete('prefetch')
      config.plugins.delete('preload')
    }
  }
}
