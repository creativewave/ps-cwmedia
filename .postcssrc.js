
module.exports = ({ env }) => ({
    sourceMap: env === 'production' || 'inline',
    plugins: {
        autoprefixer: env === 'production' ? {} : false,
    }
})
