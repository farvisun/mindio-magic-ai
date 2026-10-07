/**
 * REST client for the plugin API, including the run event stream.
 */
import apiFetch from '@wordpress/api-fetch';

const config = window.mindioAI || {};
const root = config.restRoot || '';

apiFetch.use( apiFetch.createNonceMiddleware( config.nonce ) );

/**
 * Build an absolute URL for a plugin route. Works with pretty permalinks and with ?rest_route=.
 *
 * @param {string} path  Route path, without a leading slash.
 * @param {Object} query Query parameters.
 * @return {string} URL.
 */
export function routeUrl( path, query = {} ) {
	const url = new URL(
		root + path.replace( /^\//, '' ),
		window.location.href
	);
	Object.entries( query ).forEach( ( [ key, value ] ) => {
		if ( value !== undefined && value !== null && value !== '' ) {
			url.searchParams.set( key, String( value ) );
		}
	} );
	// Answer in the user's language, also for the raw fetch() calls that bypass apiFetch.
	if ( ! url.searchParams.has( '_locale' ) ) {
		url.searchParams.set( '_locale', 'user' );
	}
	return url.toString();
}

/**
 * Call a plugin route and resolve with the JSON body.
 *
 * @param {string} path             Route path.
 * @param {Object} options          Request options.
 * @param {string} [options.method] HTTP method, GET by default.
 * @param {Object} [options.data]   JSON body.
 * @param {Object} [options.query]  Query string values.
 * @return {Promise<any>} Response body.
 */
export function api( path, { method = 'GET', data, query } = {} ) {
	return apiFetch( {
		url: routeUrl( path, query ),
		method,
		data,
	} );
}

/**
 * Human-readable message for a failed request.
 *
 * @param {any} error Error thrown by apiFetch.
 * @return {string} Message.
 */
export function errorMessage( error ) {
	if ( ! error ) {
		return '';
	}
	if ( typeof error === 'string' ) {
		return error;
	}
	return error.message || error.code || String( error );
}

/**
 * Parse a text/event-stream body incrementally.
 */
class SseReader {
	constructor() {
		this.buffer = '';
	}

	feed( chunk ) {
		this.buffer += chunk;
		const events = [];
		let index;
		while ( ( index = this.buffer.search( /\r?\n\r?\n/ ) ) !== -1 ) {
			const raw = this.buffer.slice( 0, index );
			this.buffer = this.buffer
				.slice( index )
				.replace( /^\r?\n\r?\n/, '' );
			const event = { id: 0, type: 'message', data: '' };
			const data = [];
			raw.split( /\r?\n/ ).forEach( ( line ) => {
				if ( line.startsWith( ':' ) ) {
					return;
				}
				const colon = line.indexOf( ':' );
				const field = colon === -1 ? line : line.slice( 0, colon );
				const value =
					colon === -1
						? ''
						: line.slice( colon + 1 ).replace( /^ /, '' );
				if ( field === 'id' ) {
					event.id = parseInt( value, 10 ) || 0;
				} else if ( field === 'event' ) {
					event.type = value;
				} else if ( field === 'data' ) {
					data.push( value );
				}
			} );
			if ( data.length ) {
				try {
					event.data = JSON.parse( data.join( '\n' ) );
				} catch ( e ) {
					continue;
				}
				events.push( event );
			}
		}
		return events;
	}
}

/**
 * Execute a queued run and deliver its events.
 *
 * Streams over server-sent events when the server can; if no bytes arrive
 * within three seconds (a buffering proxy or host), or the stream ends early,
 * it falls back to polling the run's event log. Events are de-duplicated by
 * id, so both paths can overlap safely.
 *
 * @param {number}      runId               Run ID.
 * @param {Function}    onEvent             Receives { id, type, data }.
 * @param {Object}      options             Options.
 * @param {string}      [options.transport] 'auto', 'sse' or 'poll'.
 * @param {AbortSignal} [options.signal]    Cancels following the run.
 * @return {Promise<void>} Resolves when the run is done.
 */
export async function followRun(
	runId,
	onEvent,
	{ transport = 'auto', signal } = {}
) {
	let lastId = 0;
	let finished = false;

	const deliver = ( event ) => {
		if ( event.id && event.id <= lastId ) {
			return;
		}
		if ( event.id ) {
			lastId = event.id;
		}
		if ( event.type === 'done' ) {
			finished = true;
		}
		onEvent( event );
	};

	const poll = async () => {
		let delay = 400;
		while ( ! finished && ! signal?.aborted ) {
			let body;
			try {
				body = await api( `runs/${ runId }/events`, {
					query: { after: lastId },
				} );
			} catch ( error ) {
				await new Promise( ( resolve ) => setTimeout( resolve, 1500 ) );
				continue;
			}
			body.events.forEach( ( event ) =>
				deliver( {
					id: event.id,
					type: event.type,
					data: event.payload,
				} )
			);
			const status = body.run?.status;
			if (
				! finished &&
				status &&
				! [ 'queued', 'running', 'stopping' ].includes( status )
			) {
				// The worker ended without a final event (for example it was killed).
				deliver( { id: 0, type: 'done', data: { status } } );
			}
			if ( ! finished ) {
				await new Promise( ( resolve ) =>
					setTimeout( resolve, delay )
				);
				delay = body.events.length
					? 400
					: Math.min( 2000, delay + 300 );
			}
		}
	};

	if ( transport === 'poll' ) {
		// Start the worker without waiting for it; the event log carries the output.
		window
			.fetch( routeUrl( `runs/${ runId }/execute` ), {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': config.nonce,
					Accept: 'application/json',
				},
			} )
			.catch( () => {} );
		await poll();
		return;
	}

	let gotBytes = false;
	let fellBack = false;
	const controller = new window.AbortController();
	signal?.addEventListener( 'abort', () => controller.abort() );
	const fallback = new Promise( ( resolve ) => {
		setTimeout( () => {
			if ( ! gotBytes && ! finished ) {
				fellBack = true;
				resolve( poll() );
			}
		}, 3000 );
	} );

	const stream = ( async () => {
		let response;
		try {
			response = await window.fetch(
				routeUrl( `runs/${ runId }/execute` ),
				{
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': config.nonce,
						Accept: 'text/event-stream',
					},
					signal: controller.signal,
				}
			);
		} catch ( error ) {
			if ( ! fellBack ) {
				fellBack = true;
				await poll();
			}
			return;
		}
		const type = response.headers.get( 'content-type' ) || '';
		if (
			! response.ok ||
			! type.includes( 'text/event-stream' ) ||
			! response.body
		) {
			if ( ! fellBack ) {
				fellBack = true;
				await poll();
			}
			return;
		}
		const reader = response.body.getReader();
		const decoder = new window.TextDecoder();
		const sse = new SseReader();
		while ( true ) {
			let chunk;
			try {
				chunk = await reader.read();
			} catch ( error ) {
				break;
			}
			if ( chunk.done ) {
				break;
			}
			gotBytes = true;
			sse.feed( decoder.decode( chunk.value, { stream: true } ) ).forEach(
				( event ) => {
					if ( ! fellBack || event.id > lastId ) {
						deliver( event );
					}
				}
			);
			if ( finished ) {
				// The final event arrived; do not wait for the server to finish shutting down.
				reader.cancel().catch( () => {} );
				break;
			}
		}
		if ( ! finished && ! fellBack ) {
			fellBack = true;
			await poll();
		}
	} )();

	await Promise.race( [ stream, fallback ] );
	if ( ! finished ) {
		await stream;
	}
	// Polling finished the run; a buffered stream may still be open, so release it.
	controller.abort();
}
