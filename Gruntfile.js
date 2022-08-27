module.exports = function( grunt ) {
	require( 'load-grunt-tasks' )( grunt );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		clean: {
			main: [ 'build/' ],
		},

		copy: {
			main: {
				src: [
					'App/**',
					'assets/**',
					'!assets/svn/**',
					'languages/**',
					'README.txt',
					'index.php',
					'cf-images.php',
					'uninstall.php',
				],
				dest: 'build/cf-images/',
				options: {
					noProcess: [ '**/*.{png,gif,jpg,ico,svg,eot,ttf,woff,woff2}' ],
					process( content, srcpath ) {
						const pkg = grunt.file.readJSON( 'package.json' );
						return content.replace( /\%\%VERSION\%\%/g, pkg.version );
					},
				},
			},
		},

		compress: {
			main: {
				options: {
					archive: './build/cf-images-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'build/cf-images/',
				src: [ '**/*' ],
				dest: 'cf-images/',
			},
		},
	} );

	grunt.registerTask( 'build', [ 'clean', 'copy', 'compress' ] );
};
