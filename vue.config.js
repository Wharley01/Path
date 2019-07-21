// let BrowserSyncPlugin = require('vue-cli-browser-sync-webpack-plugin');
let config = require("./path/project.pconf.json");

const PORT = config.PROJECT.port;
const HOST = config.PROJECT.host;
module.exports = {
  baseUrl: process.env.NODE_ENV === "production" ? "/dist/" : "/",
  devServer: {
    proxy: {
      "/api": {
        target: config.PROJECT.server
      },
      "/SSE": {
        target: config.PROJECT.server
      },
      "/src/assets/images": {
        target: config.PROJECT.server
      },
      "/path": {
        target: config.PROJECT.server
      }
    },
    port: PORT,
    historyApiFallback: true,
    host: HOST,
    hot: true,
    watchOptions: {
      aggregateTimeout: 300,
      poll: true,
      ignored: /node_modules/
    }
  },
  pluginOptions: {
    browserSync: {
      host: HOST,
      port: PORT,
      proxy: `http://${HOST}:${PORT}`,
      reload: false,
      open: true
    }
  }
};
