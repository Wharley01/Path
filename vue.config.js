
module.exports = {

    devServer: {
        proxy: {
            "/api":{
                target:'http://myvueproj.loc/'
            }
        },
        port: 3000,
        host:'localhost',
        open: true,
        hot: true,
        watchOptions: {
            aggregateTimeout: 300,
            poll: true,
            ignored: /node_modules/
        }
    }
}