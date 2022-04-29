const path = require('path');
const webpack = require('webpack');
//const HtmlWebpackPlugin = require('html-webpack-plugin')
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
//const HtmlInlineScriptPlugin = require('html-inline-script-webpack-plugin');
const { SubresourceIntegrityPlugin } = require('webpack-subresource-integrity');

const fs = require('fs');

//const BIOMES = JSON.parse(fs.readFileSync('./src/data/biomes.json', 'utf-8'));
//const ICONS = JSON.parse(fs.readFileSync('./src/data/icons.json', 'utf-8'));

module.exports = {
    entry: {
        bans: './src/coffee/entries/bans.coffee',
        editpoll: './src/coffee/entries/editpoll.coffee',
        rapsheet: './src/coffee/entries/rapsheet.coffee',
    },

    resolve: {
        extensions: ['.coffee', '.js']
    },
    output: {
        filename: 'assets/[name]-[contenthash].js',
        path: path.resolve(__dirname, 'packed'),
        crossOriginLoading: 'anonymous',
        assetModuleFilename: 'assets/[name]-[contenthash][ext][query]'
    },

    plugins: [
        new webpack.ProgressPlugin(),
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery" // Not used but whatever
        }),
        /*
        new HtmlWebpackPlugin({
            template: './src/html/index.hbs',
            inject: false,
            templateParameters: {
                'data': {
                    'BIOMES': BIOMES,
                    'ICONS': ICONS
                }
            }
        }),*/
        new SubresourceIntegrityPlugin({
            hashFuncNames: ['sha256', 'sha512'],
        }),
        //new HtmlInlineScriptPlugin(),
        new CleanWebpackPlugin(),
        //new WorkboxWebpackPlugin.GenerateSW({
        //  swDest: 'sw.js',
        //  clientsClaim: true,
        //  skipWaiting: false,
        //}),
    ],

    module: {
        rules: [
            {
                test: /\.s[ac]ss$/i,
                use: [
                    // Creates `style` nodes from JS strings
                    "style-loader",
                    // Translates CSS into CommonJS
                    "css-loader",
                    // Compiles Sass to CSS
                    "sass-loader",
                ],
            },
            {
                test: /\.coffee$/,
                loader: "coffee-loader",
            },
        ],
    },
};