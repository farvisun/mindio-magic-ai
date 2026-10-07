/**
 * Mindio AI admin app entry point.
 */
import { createRoot } from '@wordpress/element';
import App from './App';
import './index.scss';

function mount() {
	const node = document.getElementById( 'mindio-ai-root' );
	if ( ! node ) {
		return;
	}
	createRoot( node ).render(
		<App route={ node.dataset.route || 'assistant' } />
	);
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
