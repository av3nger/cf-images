module.exports = {
	env: {
		browser: true,
		es6: true,
		jquery: true,
	},
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
	globals: {
		Atomics: 'readonly',
		SharedArrayBuffer: 'readonly',
	},
	parserOptions: {
		ecmaVersion: 2018,
		sourceType: 'module',
		requireConfigFile: false,
	},
	rules: {
		'no-invalid-this': 0,
		'@wordpress/i18n-ellipsis': 0,
		'comma-dangle': 0,
	},
};
