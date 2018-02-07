
const ExtractPlugin = require('extract-text-webpack-plugin')
const find = require('./find')
const path = require('path')

module.exports = {
    entry: _ => find('css', 'js'),
    output: { path: path.join(__dirname, '..'), filename: '[name]' },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                use: 'babel-loader',
            },
            {
                test: /\.(p|s)?css$/,
                use: [
                    ExtractPlugin.loader,
                    'css-loader?importLoaders=1&sourceMap',
                    'postcss-loader',
                ],
            }
        ]
    },
    plugins: [new ExtractPlugin({ filename: '[name]' })],
}
