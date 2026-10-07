/**
 * Safe Markdown renderer for model output.
 *
 * Output is built as React elements, never as HTML strings, so nothing the
 * model writes can inject markup. Images are not rendered (a remote image
 * would leak data when it loads), and links are only clickable when they
 * point to this site or an allowlisted host.
 */
import { Fragment } from '@wordpress/element';

function allowedHref( href ) {
	const config = window.mindioAI || {};
	try {
		const url = new URL( href, window.location.href );
		if ( ! [ 'http:', 'https:', 'mailto:' ].includes( url.protocol ) ) {
			return null;
		}
		if ( url.protocol === 'mailto:' ) {
			return url.toString();
		}
		const own = new URL( config.adminUrl || window.location.href ).host;
		const allow = ( config.linkAllowlist || [] ).map( ( host ) =>
			host.toLowerCase()
		);
		const host = url.host.toLowerCase();
		const ok =
			host === own ||
			allow.some( ( entry ) =>
				entry.startsWith( '*.' )
					? host.endsWith( entry.slice( 1 ) )
					: host === entry
			);
		return ok ? url.toString() : null;
	} catch ( e ) {
		return null;
	}
}

const INLINE =
	/(`[^`]+`)|(\*\*[^*]+\*\*)|(__[^_]+__)|(\*(?![\s*])(?:\*\*[^*]+\*\*|[^*])+\*)|(!?\[[^\]]*\]\([^)\s]+\))/g;

function inline( text, keyPrefix ) {
	const out = [];
	let last = 0;
	let match;
	let i = 0;
	// A fresh expression per call: nested calls must not move the outer loop's lastIndex.
	const pattern = new RegExp( INLINE.source, 'g' );
	while ( ( match = pattern.exec( text ) ) !== null ) {
		if ( match.index > last ) {
			out.push( text.slice( last, match.index ) );
		}
		const token = match[ 0 ];
		const key = `${ keyPrefix }-${ i++ }`;
		if ( token.startsWith( '`' ) ) {
			out.push( <code key={ key }>{ token.slice( 1, -1 ) }</code> );
		} else if ( token.startsWith( '**' ) || token.startsWith( '__' ) ) {
			out.push(
				<strong key={ key }>
					{ inline( token.slice( 2, -2 ), key ) }
				</strong>
			);
		} else if ( token.startsWith( '*' ) ) {
			out.push(
				<em key={ key }>{ inline( token.slice( 1, -1 ), key ) }</em>
			);
		} else {
			const isImage = token.startsWith( '!' );
			const parts = /^!?\[([^\]]*)\]\(([^)\s]+)\)$/.exec( token );
			const label = parts ? parts[ 1 ] : token;
			const href = parts ? allowedHref( parts[ 2 ] ) : null;
			if ( isImage || ! href ) {
				// Unclickable: show the text and, for links, the address.
				out.push(
					<span key={ key } className="mindio-ai-md__blocked-link">
						{ label || parts?.[ 2 ] }
						{ ! isImage && parts && label !== parts[ 2 ]
							? ` (${ parts[ 2 ] })`
							: '' }
					</span>
				);
			} else {
				out.push(
					<a
						key={ key }
						href={ href }
						target="_blank"
						rel="noopener noreferrer nofollow"
					>
						{ label }
					</a>
				);
			}
		}
		last = match.index + token.length;
	}
	if ( last < text.length ) {
		out.push( text.slice( last ) );
	}
	return out;
}

function table( lines, key ) {
	const rows = lines.map( ( line ) =>
		line
			.trim()
			.replace( /^\|/, '' )
			.replace( /\|$/, '' )
			.split( '|' )
			.map( ( cell ) => cell.trim() )
	);
	const [ head, , ...body ] = rows;
	return (
		<div className="mindio-ai-md__table" key={ key }>
			<table>
				<thead>
					<tr>
						{ head.map( ( cell, i ) => (
							<th key={ i } dir="auto">
								{ inline( cell, `${ key }-h${ i }` ) }
							</th>
						) ) }
					</tr>
				</thead>
				<tbody>
					{ body.map( ( row, r ) => (
						<tr key={ r }>
							{ row.map( ( cell, c ) => (
								<td key={ c } dir="auto">
									{ inline( cell, `${ key }-${ r }-${ c }` ) }
								</td>
							) ) }
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	);
}

/**
 * @param {Object} props
 * @param {string} props.text Markdown.
 * @return {Element} Rendered blocks.
 */
export default function Markdown( { text } ) {
	const lines = String( text || '' )
		.replace( /\r\n?/g, '\n' )
		.split( '\n' );
	const blocks = [];
	let i = 0;
	let key = 0;

	while ( i < lines.length ) {
		const line = lines[ i ];

		if ( /^```/.test( line ) ) {
			const code = [];
			i++;
			while ( i < lines.length && ! /^```/.test( lines[ i ] ) ) {
				code.push( lines[ i++ ] );
			}
			i++;
			blocks.push(
				<pre key={ key++ } dir="ltr">
					<code>{ code.join( '\n' ) }</code>
				</pre>
			);
			continue;
		}

		if (
			/^\s*\|.*\|\s*$/.test( line ) &&
			i + 1 < lines.length &&
			/^\s*\|?\s*:?-{2,}/.test( lines[ i + 1 ] )
		) {
			const rows = [];
			while ( i < lines.length && /^\s*\|.*\|\s*$/.test( lines[ i ] ) ) {
				rows.push( lines[ i++ ] );
			}
			blocks.push( table( rows, key++ ) );
			continue;
		}

		const heading = /^(#{1,4})\s+(.*)$/.exec( line );
		if ( heading ) {
			const Tag = `h${ Math.min( 6, heading[ 1 ].length + 2 ) }`;
			blocks.push(
				<Tag key={ key++ } dir="auto">
					{ inline( heading[ 2 ], `h${ key }` ) }
				</Tag>
			);
			i++;
			continue;
		}

		if ( /^\s*([-*_])(\s*\1){2,}\s*$/.test( line ) ) {
			blocks.push( <hr key={ key++ } /> );
			i++;
			continue;
		}

		if ( /^\s*>/.test( line ) ) {
			const quote = [];
			while ( i < lines.length && /^\s*>/.test( lines[ i ] ) ) {
				quote.push( lines[ i++ ].replace( /^\s*>\s?/, '' ) );
			}
			blocks.push(
				<blockquote key={ key++ } dir="auto">
					{ inline( quote.join( ' ' ), `q${ key }` ) }
				</blockquote>
			);
			continue;
		}

		const listMatch = /^\s*([-*+]|\d+[.)])\s+/.exec( line );
		if ( listMatch ) {
			const ordered = /\d/.test( listMatch[ 1 ] );
			const items = [];
			const marker = ordered ? /^\s*\d+[.)]\s+/ : /^\s*[-*+]\s+/;
			while ( i < lines.length && marker.test( lines[ i ] ) ) {
				items.push(
					lines[ i++ ].replace( /^\s*([-*+]|\d+[.)])\s+/, '' )
				);
			}
			const List = ordered ? 'ol' : 'ul';
			blocks.push(
				<List key={ key++ } dir="auto">
					{ items.map( ( item, n ) => (
						<li key={ n }>
							{ inline( item, `l${ key }-${ n }` ) }
						</li>
					) ) }
				</List>
			);
			continue;
		}

		if ( line.trim() === '' ) {
			i++;
			continue;
		}

		const paragraph = [ lines[ i++ ] ];
		while (
			i < lines.length &&
			lines[ i ].trim() !== '' &&
			! /^(```|#{1,4}\s|\s*>|\s*([-*+]|\d+[.)])\s+)/.test( lines[ i ] ) &&
			! /^\s*\|.*\|\s*$/.test( lines[ i ] )
		) {
			paragraph.push( lines[ i++ ] );
		}
		blocks.push(
			<p key={ key++ } dir="auto">
				{ paragraph.map( ( row, n ) => (
					<Fragment key={ n }>
						{ n > 0 && <br /> }
						{ inline( row, `p${ key }-${ n }` ) }
					</Fragment>
				) ) }
			</p>
		);
	}

	return <div className="mindio-ai-md">{ blocks }</div>;
}
