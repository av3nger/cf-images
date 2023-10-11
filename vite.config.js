import { defineConfig } from 'vite';
import { resolve } from 'path';
import react from '@vitejs/plugin-react';

export default defineConfig( {
	build: {
		assetsDir: './',
		outDir: resolve( __dirname, 'assets/dist' ),
		rollupOptions: {
			external: [
				'@wordpress/i18n'
			],
			input: {
				app: resolve( __dirname, 'assets/_src/app.jsx' ),
				media: resolve( __dirname, 'assets/_src/js/media.js' ),
			},
			output: {
				assetFileNames: '[name].min[extname]',
				entryFileNames: '[name].min.js',
				//format: 'iife',
				globals: {
					'@wordpress/i18n': 'wp.i18n'
				},
				sourcemap: true,
			}
		},
	},
	css: {
		devSourcemap: true,
	},
	plugins: [
		react( {
			include: '**/*.{jsx,tsx}',
		} )
	],
} );
