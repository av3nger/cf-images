const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const TerserPlugin = require( 'terser-webpack-plugin' );

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

	optimization: {
		minimize: true,
		minimizer: [
			new TerserPlugin( {
				extractComments: false,
				terserOptions: {
					output: {
						comments: false,
					},
				},
			} ),
		],
		splitChunks: {
			cacheGroups: {
				vendor: {
					test: /[\\/]node_modules[\\/](react|react-dom)[\\/]/,
					name: 'cf-images-react',
					chunks: 'all',
				},
			},
		}
	},
};
