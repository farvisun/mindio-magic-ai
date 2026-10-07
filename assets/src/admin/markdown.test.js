/**
 * Markdown renderer: terminates on every streamed prefix and never emits unsafe markup.
 */
import { renderToString } from '@wordpress/element';
import Markdown from './markdown';

const render = ( text ) => renderToString( <Markdown text={ text } /> );

const SAMPLE = [
	'I proposed a new **title** and **excerpt** with *nested **bold** text*:',
	'',
	'- The title is clearer',
	'1. Step one with `code`',
	'',
	'| Field | Before | After |',
	'| --- | --- | --- |',
	'| Title | Old | **New** |',
	'',
	'> Quoted __text__',
	'',
	'---',
	'',
	'```',
	'const x = "<b>";',
	'```',
	'See [the log](http://localhost/wp-admin/) and [evil](https://evil.example/steal).',
	'![tracker](https://evil.example/pixel.png)',
].join( '\n' );

describe( 'Markdown', () => {
	beforeAll( () => {
		window.mindioAI = {
			adminUrl: 'http://localhost/wp-admin/',
			linkAllowlist: [ '*.wordpress.org' ],
		};
	} );

	it( 'renders every prefix of a streamed reply', () => {
		for ( let n = 0; n <= SAMPLE.length; n++ ) {
			expect( typeof render( SAMPLE.slice( 0, n ) ) ).toBe( 'string' );
		}
	} );

	it( 'renders nested emphasis once', () => {
		const html = render( 'a **b** c *d **e** f* g' );
		expect( html.match( /<strong>/g ) ).toHaveLength( 2 );
		expect( html ).toContain( '<em>' );
	} );

	it( 'renders tables, lists, quotes, rules and code', () => {
		const html = render( SAMPLE );
		expect( html ).toContain( '<table>' );
		expect( html ).toContain( '<ul' );
		expect( html ).toContain( '<ol' );
		expect( html ).toContain( '<blockquote' );
		expect( html ).toContain( '<hr/>' );
		expect( html ).toContain( 'const x = "&lt;b' );
	} );

	it( 'only links to the site and allowlisted hosts', () => {
		const html = render( SAMPLE );
		expect( html ).toContain( 'href="http://localhost/wp-admin/"' );
		expect( html ).not.toContain( 'href="https://evil.example' );
		expect( html ).toContain( 'https://evil.example/steal' );
		expect(
			render( '[docs](https://developer.wordpress.org/x)' )
		).toContain( 'href="https://developer.wordpress.org/x"' );
		expect( render( '[x](javascript:alert(1))' ) ).not.toContain( 'href=' );
	} );

	it( 'never renders images', () => {
		const html = render( SAMPLE );
		expect( html ).not.toContain( '<img' );
	} );

	it( 'escapes raw HTML', () => {
		const html = render(
			'<script>alert(1)</script> <img src=x onerror=alert(1)>'
		);
		expect( html ).not.toContain( '<script' );
		expect( html ).not.toContain( '<img' );
	} );

	it( 'treats rules of any length as a rule', () => {
		expect( render( '-----' ) ).toContain( '<hr/>' );
		expect( render( '* * *' ) ).toContain( '<hr/>' );
	} );
} );
