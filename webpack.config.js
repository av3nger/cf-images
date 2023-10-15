const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

module.exports = {
	mode: 'production',

	entry: {
		'cf-images': path.resolve( __dirname, 'assets/_src/app.jsx' ),
		'cf-images-media': path.resolve( __dirname, 'assets/_src/js/media.js' ),
	},

	output: {
		clean: {
			keep: /images/,
		},
		filename: '[name].min.js',
		path: path.resolve( __dirname, 'assets/js' ),
	},

	module: {
		rules: [
			{
				test: /\.(jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [ '@babel/preset-env' ],
					},
				},
			},
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

	externals: {
		react: 'React',
		'react-dom': 'ReactDOM',
		'@wordpress/i18n': 'wp.i18n',
	},

	resolve: {
		extensions: [ '.js', '.jsx' ],
	},

	plugins: [
		new MiniCssExtractPlugin( {
			// Options similar to the same options in webpackOptions.output
			// both options are optional
			filename: '../css/[name].min.css',
			chunkFilename: '[id].min.css',
		} ),
	],
};
