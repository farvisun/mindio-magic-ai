/**
 * WordPress coding standards. The @wordpress/* packages are provided by WordPress at
 * runtime (webpack externals), so they are not npm dependencies of this plugin.
 */
module.exports = {
	root: true,
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: { browser: true },
	settings: {
		'import/core-modules': [ '@wordpress/api-fetch', '@wordpress/components', '@wordpress/element', '@wordpress/i18n' ],
	},
	overrides: [
		{
			files: [ '**/*.test.js' ],
			env: { jest: true },
		},
	],
};
