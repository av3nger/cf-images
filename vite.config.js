import { defineConfig } from 'vite';
import { resolve } from 'path';
import react from '@vitejs/plugin-react';

export default defineConfig( {
	build: {
		assetsDir: './',
		outDir: resolve( __dirname, 'assets/dist' ),
		rollupOptions: {
			input: {
				app: resolve( __dirname, 'assets/_src/js/app.js' ),
				media: resolve( __dirname, 'assets/_src/js/media.js' ),
			},
			output: {
				assetFileNames: '[name].min[extname]',
				entryFileNames: '[name].min.js',
				sourcemap: true,
			}
		},
	},
	css: {
		devSourcemap: true,
		sourceMap: true,
	},
	plugins: [
		react( {
			include: '**/*.{jsx,tsx}',
		} )
	],
} );
