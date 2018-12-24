module.exports = {

    devServer: {
      proxy: {
          "/":{
              target:'http://test-vue-vue.loc/'
            }
        },
      port: 3000,
      host:'localhost'

    }
  }