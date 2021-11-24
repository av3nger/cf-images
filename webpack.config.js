const path = require( 'path' );
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
	mode: 'production',

	entry: {
		'perf-app': path.resolve( __dirname, 'assets/_src/js/app.js' ),
	},

	output: {
		filename: '[name].min.js',
		path: path.resolve( __dirname, 'assets/js' ),
	},

	module: {
		rules: [
			{
				test: /\.s[ac]ss$/i,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					'sass-loader'
				],
			},
		],
	},

	devtool: 'source-map',

	watchOptions: {
		ignored: /node_modules/,
		poll: 1000,
	},

	plugins: [
		new MiniCssExtractPlugin({
			// Options similar to the same options in webpackOptions.output
			// both options are optional
			filename: '../css/[name].min.css',
			chunkFilename: '[id].min.css',
		}),
	],
};
