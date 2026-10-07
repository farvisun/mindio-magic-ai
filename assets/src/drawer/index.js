/**
 * Assistant drawer loader. Runs on every admin screen, so it stays tiny: no React, no
 * data stores. The assistant itself loads in an iframe the first time the drawer opens.
 */
import { __ } from '@wordpress/i18n';
import './index.scss';

const config = window.mindioAIDrawer || {};

const SVG = {
	x: '<path d="M18 6 6 18" /><path d="m6 6 12 12" />',
	expand: '<path d="M15 3h6v6" /><path d="m21 3-7 7" /><path d="m3 21 7-7" /><path d="M9 21H3v-6" />',
	sparkles:
		'<path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z" /><path d="M20 2v4" /><path d="M22 4h-4" /><circle cx="4" cy="20" r="2" />',
};

function icon( name, size = 18 ) {
	return `<svg class="mindio-ai-drawer__icon" width="${ size }" height="${ size }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ SVG[ name ] }</svg>`;
}

function frameUrl() {
	const url = new URL( config.url, window.location.href );
	const screen = config.screen || {};
	if ( screen.screen ) {
		url.searchParams.set( 'mindio_screen', screen.screen );
	}
	if ( screen.post_id ) {
		url.searchParams.set( 'mindio_post', String( screen.post_id ) );
	}
	if ( screen.post_type ) {
		url.searchParams.set( 'mindio_post_type', screen.post_type );
	}
	return url.toString();
}

let root = null;
let panel = null;
let frame = null;
let returnFocus = null;

function build() {
	root = document.createElement( 'div' );
	root.className = 'mindio-ai-drawer';
	root.hidden = true;
	root.innerHTML = `
		<div class="mindio-ai-drawer__backdrop" data-close></div>
		<div class="mindio-ai-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="mindio-ai-drawer-title" tabindex="-1">
			<div class="mindio-ai-drawer__header">
				<span class="mindio-ai-drawer__mark">${ icon( 'sparkles', 16 ) }</span>
				<h2 class="mindio-ai-drawer__title" id="mindio-ai-drawer-title"></h2>
				<a class="mindio-ai-drawer__button" data-full>${ icon( 'expand', 16 ) }</a>
				<button type="button" class="mindio-ai-drawer__button" data-close>${ icon(
					'x'
				) }</button>
			</div>
			<div class="mindio-ai-drawer__body">
				<div class="mindio-ai-drawer__loading" role="status"><span class="mindio-ai-drawer__spinner" aria-hidden="true"></span><span></span></div>
			</div>
		</div>`;
	panel = root.querySelector( '.mindio-ai-drawer__panel' );
	root.querySelector( '.mindio-ai-drawer__title' ).textContent = __(
		'Mindio AI',
		'mindio-magic-ai'
	);
	root.querySelector(
		'.mindio-ai-drawer__loading span:last-child'
	).textContent = __( 'Loading the assistant…', 'mindio-magic-ai' );

	const full = root.querySelector( '[data-full]' );
	const fullUrl = new URL( config.url, window.location.href );
	fullUrl.searchParams.delete( 'mindio_embed' );
	full.href = fullUrl.toString();
	full.setAttribute(
		'aria-label',
		__( 'Open full page', 'mindio-magic-ai' )
	);
	full.title = __( 'Open full page', 'mindio-magic-ai' );

	const close = root.querySelector( 'button[data-close]' );
	close.setAttribute( 'aria-label', __( 'Close', 'mindio-magic-ai' ) );
	close.title = __( 'Close', 'mindio-magic-ai' );

	root.addEventListener( 'click', ( event ) => {
		if ( event.target.closest( '[data-close]' ) ) {
			hide();
		}
	} );
	root.addEventListener( 'keydown', onKeydown );
	document.body.appendChild( root );
}

function loadFrame() {
	frame = document.createElement( 'iframe' );
	frame.className = 'mindio-ai-drawer__frame';
	frame.title = __( 'Mindio AI assistant', 'mindio-magic-ai' );
	frame.src = frameUrl();
	frame.addEventListener( 'load', () => {
		root.classList.add( 'is-loaded' );
		// Same origin: Escape inside the assistant closes the drawer too.
		try {
			frame.contentWindow.document.addEventListener(
				'keydown',
				( event ) => {
					if (
						event.key === 'Escape' &&
						! event.defaultPrevented &&
						! frame.contentWindow.document.querySelector(
							'.components-modal__screen-overlay, .components-popover'
						)
					) {
						hide();
					}
				}
			);
		} catch ( e ) {
			// A cross-origin admin (rare proxies) only loses the shortcut.
		}
		if ( ! root.hidden ) {
			focusFrame();
		}
	} );
	root.querySelector( '.mindio-ai-drawer__body' ).appendChild( frame );
}

function focusFrame() {
	try {
		const input = frame.contentWindow.document.querySelector(
			'.mindio-ai-composer__input'
		);
		if ( input ) {
			input.focus();
			return;
		}
	} catch ( e ) {}
	frame.focus();
}

function onKeydown( event ) {
	if ( event.key === 'Escape' ) {
		event.preventDefault();
		hide();
		return;
	}
	if ( event.key !== 'Tab' ) {
		return;
	}
	// Keep focus inside the dialog; the iframe is one stop in the cycle.
	const items = [ ...panel.querySelectorAll( 'a[href], button, iframe' ) ];
	const first = items[ 0 ];
	const last = items[ items.length - 1 ];
	if ( event.shiftKey && panel.ownerDocument.activeElement === first ) {
		event.preventDefault();
		last.focus();
	} else if (
		! event.shiftKey &&
		panel.ownerDocument.activeElement === last
	) {
		event.preventDefault();
		first.focus();
	}
}

function show() {
	if ( ! root ) {
		build();
	}
	if ( ! root.hidden ) {
		return;
	}
	returnFocus = root.ownerDocument.activeElement;
	root.hidden = false;
	document.documentElement.classList.add( 'mindio-ai-drawer-open' );
	trigger()?.setAttribute( 'aria-expanded', 'true' );
	// Next frame so the slide-in transition runs.
	window.requestAnimationFrame( () => root.classList.add( 'is-open' ) );
	if ( ! frame ) {
		loadFrame();
		panel.focus();
	} else {
		focusFrame();
	}
}

function hide() {
	if ( ! root || root.hidden ) {
		return;
	}
	root.classList.remove( 'is-open' );
	document.documentElement.classList.remove( 'mindio-ai-drawer-open' );
	trigger()?.setAttribute( 'aria-expanded', 'false' );
	const done = () => {
		root.hidden = true;
	};
	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		done();
	} else {
		window.setTimeout( done, 200 );
	}
	if ( returnFocus && typeof returnFocus.focus === 'function' ) {
		returnFocus.focus();
	}
	returnFocus = null;
}

function toggle() {
	if ( root && ! root.hidden ) {
		hide();
	} else {
		show();
	}
}

function trigger() {
	return document.querySelector( '#wp-admin-bar-mindio-ai > .ab-item' );
}

function bindTrigger() {
	const link = trigger();
	if ( ! link ) {
		return;
	}
	link.setAttribute( 'role', 'button' );
	link.setAttribute( 'aria-haspopup', 'dialog' );
	link.setAttribute( 'aria-expanded', 'false' );
	link.addEventListener( 'click', ( event ) => {
		// Modified clicks still open the full page in a new tab.
		if (
			event.button !== 0 ||
			event.metaKey ||
			event.ctrlKey ||
			event.shiftKey ||
			event.altKey
		) {
			return;
		}
		event.preventDefault();
		toggle();
	} );
}

let commandRegistered = false;

function registerCommand() {
	if ( commandRegistered || ! config.commands ) {
		return;
	}
	const wp = window.wp || {};
	if ( ! wp.commands?.store || ! wp.data?.dispatch ) {
		return;
	}
	try {
		wp.data.dispatch( wp.commands.store ).registerCommand( {
			name: 'mindio-magic-ai/ask',
			label: __( 'Ask Mindio AI', 'mindio-magic-ai' ),
			searchLabel: __(
				'Ask Mindio AI assistant chat',
				'mindio-magic-ai'
			),
			callback: ( { close } ) => {
				close();
				show();
			},
		} );
		commandRegistered = true;
	} catch ( e ) {
		// An older or changed Command Palette API: the toolbar button still works.
	}
}

function init() {
	if ( ! config.url ) {
		return;
	}
	bindTrigger();
	registerCommand();
	// The palette scripts may print after ours.
	window.addEventListener( 'load', registerCommand, { once: true } );
	window.mindioAIDrawer.open = show;
	window.mindioAIDrawer.close = hide;
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
