// let BrowserSyncPlugin = require('vue-cli-browser-sync-webpack-plugin');
let config = require("./path/project.pconf.json");
const path = require("path");

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
      "/assets": {
        target: config.PROJECT.server
      }
    },
    port: PORT,
    historyApiFallback: true,
    host: HOST,
    hot: true,
    inline: false,
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
  // configureWebpack:{
  //     plugins:[
  //         new BrowserSyncPlugin({
  //             host: HOST,
  //             port: PORT,
  //             proxy: `http://${HOST}:${PORT}`,
  //             reload: false
  //         }),
  //     ]
  // }
};
