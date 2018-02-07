
const config  = require('./config')
const done    = require('./done')
const webpack = require('webpack')

const env = process.env.NODE_ENV = process.argv.slice(2)[0] || 'production'
const webpackConfig = { mode: env, ...config }
const watchOptions = {
    aggregateTimeout: 300,
    ignored: /node_modules/,
    poll: true,
}

'production' === env
    ? webpack(webpackConfig, done)
    : webpack(webpackConfig).watch(watchOptions, done)
