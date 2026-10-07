/**
 * App shell: loads the bootstrap payload and renders the current page.
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	createContext,
	useCallback,
	useContext,
	useEffect,
	useState,
} from '@wordpress/element';
import { Button } from '@wordpress/components';
import { api, errorMessage } from './api';
import Icon from './components/Icon';
import { Banner, Loading } from './components/ui';
import Assistant from './pages/Assistant';
import Connections from './pages/Connections';
import Usage from './pages/Usage';
import Logs from './pages/Logs';
import Settings from './pages/Settings';

const config = window.mindioAI || {};

export const BootContext = createContext( null );
export const useBoot = () => useContext( BootContext );

const PAGES = {
	assistant: Assistant,
	connections: Connections,
	usage: Usage,
	logs: Logs,
	settings: Settings,
};

function nav( boot ) {
	const caps = boot.caps || {};
	return [
		{
			route: 'assistant',
			label: __( 'Assistant', 'mindio-magic-ai' ),
			icon: 'message-square',
			show: caps.mindio_ai_use_assistant,
		},
		{
			route: 'connections',
			label: __( 'Connections', 'mindio-magic-ai' ),
			icon: 'plug',
			show: caps.mindio_ai_use_assistant,
		},
		{
			route: 'usage',
			label: __( 'Usage & budgets', 'mindio-magic-ai' ),
			icon: 'chart-column',
			show: caps.mindio_ai_manage_settings,
		},
		{
			route: 'logs',
			label: __( 'Activity log', 'mindio-magic-ai' ),
			icon: 'activity',
			show: caps.mindio_ai_manage_settings,
		},
		{
			route: 'settings',
			label: __( 'Settings', 'mindio-magic-ai' ),
			icon: 'settings',
			show: caps.manage_options,
		},
	].filter( ( item ) => item.show );
}

function BrandMark() {
	return (
		<svg
			className="mindio-ai-brand__mark"
			width="28"
			height="28"
			viewBox="0 0 64 64"
			aria-hidden="true"
			focusable="false"
		>
			<rect width="64" height="64" rx="16" fill="#10110f" />
			<path
				d="M32 12l4.6 11.4L48 28l-11.4 4.6L32 44l-4.6-11.4L16 28l11.4-4.6L32 12z"
				fill="#d7ff38"
			/>
			<circle cx="46" cy="46" r="5" fill="#ff6846" />
		</svg>
	);
}

function AppBar( { boot, route } ) {
	return (
		<div className="mindio-ai-appbar">
			<div className="mindio-ai-brand">
				<BrandMark />
				<span className="mindio-ai-brand__name">
					{ __( 'Mindio AI', 'mindio-magic-ai' ) }
				</span>
				<span className="mindio-ai-brand__version">
					{ sprintf( 'v%s', boot.version ) }
				</span>
			</div>
			<nav
				className="mindio-ai-nav"
				aria-label={ __( 'Mindio AI sections', 'mindio-magic-ai' ) }
			>
				{ nav( boot ).map( ( item ) => (
					<a
						key={ item.route }
						href={ config.pages?.[ item.route ] }
						className={ `mindio-ai-nav__item ${
							item.route === route ? 'is-active' : ''
						}` }
						aria-current={
							item.route === route ? 'page' : undefined
						}
					>
						<Icon name={ item.icon } size={ 16 } />
						<span>{ item.label }</span>
					</a>
				) ) }
			</nav>
			<div className="mindio-ai-appbar__status">
				{ boot.kill_switch?.active && (
					<a
						className="mindio-ai-status is-warning"
						href={ config.pages?.usage }
					>
						<Icon name="power" size={ 14 } />
						{ __( 'Paused', 'mindio-magic-ai' ) }
					</a>
				) }
				<a
					className="mindio-ai-appbar__help"
					href={ config.docsUrl }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Docs', 'mindio-magic-ai' ) }
					<Icon name="external-link" size={ 13 } />
				</a>
			</div>
		</div>
	);
}

export default function App( { route } ) {
	const [ boot, setBoot ] = useState( null );
	const [ error, setError ] = useState( '' );

	const refresh = useCallback( async () => {
		try {
			setBoot( await api( 'bootstrap' ) );
			setError( '' );
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	}, [] );

	useEffect( () => {
		refresh();
	}, [ refresh ] );

	if ( error ) {
		return (
			<div className="mindio-ai-app">
				<Banner
					tone="danger"
					title={ __(
						'Mindio AI could not load',
						'mindio-magic-ai'
					) }
					actions={
						<Button variant="secondary" onClick={ refresh }>
							{ __( 'Try again', 'mindio-magic-ai' ) }
						</Button>
					}
				>
					{ error }
				</Banner>
			</div>
		);
	}
	if ( ! boot ) {
		return (
			<div className="mindio-ai-app">
				<Loading
					label={ __( 'Loading Mindio AI…', 'mindio-magic-ai' ) }
				/>
			</div>
		);
	}

	const Page = PAGES[ route ] || Assistant;

	return (
		<BootContext.Provider value={ { ...boot, refresh } }>
			<div
				className={ `mindio-ai-app is-${ route } ${
					config.embed ? 'is-embed' : ''
				}` }
			>
				{ ! config.embed && <AppBar boot={ boot } route={ route } /> }
				{ ! boot.keys_current && boot.caps?.manage_options && (
					<Banner
						tone="danger"
						title={ __(
							'Saved API keys can no longer be decrypted',
							'mindio-magic-ai'
						) }
						actions={
							route === 'connections' ? (
								<Button
									variant="secondary"
									onClick={ async () => {
										await api( 'keys/acknowledge', {
											method: 'POST',
										} );
										refresh();
									} }
								>
									{ __(
										'I re-entered the keys',
										'mindio-magic-ai'
									) }
								</Button>
							) : (
								<Button
									variant="secondary"
									href={ config.pages?.connections }
								>
									{ __(
										'Open Connections',
										'mindio-magic-ai'
									) }
								</Button>
							)
						}
					>
						{ __(
							'The site’s security keys changed after the provider keys were saved. Enter each key again. Define MINDIO_MAGIC_AI_SECRET_KEY in wp-config.php to avoid this in future.',
							'mindio-magic-ai'
						) }
					</Banner>
				) }
				<main className="mindio-ai-main">
					<Page />
				</main>
			</div>
		</BootContext.Provider>
	);
}
