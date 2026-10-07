/**
 * One conversation: history, the live run, proposal cards, and the composer.
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	Fragment,
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { Button, SelectControl } from '@wordpress/components';
import { api, errorMessage, followRun } from '../../api';
import { useBoot } from '../../App';
import Icon from '../../components/Icon';
import { Banner } from '../../components/ui';
import Markdown from '../../markdown';
import { formatCost, formatTokens } from '../../format';
import { suggestions, toolLabel } from '../../tools';
import ProposalCard from './ProposalCard';
import Composer from './Composer';

const config = window.mindioAI || {};

const ACTIVITY_ICONS = {
	running: 'loader',
	error: 'circle-alert',
	done: 'check',
};

function screenContext() {
	const params = new URLSearchParams( window.location.search );
	return {
		screen: params.get( 'mindio_screen' ) || 'mindio-ai',
		post_id: parseInt( params.get( 'mindio_post' ) || '0', 10 ) || 0,
		post_type: params.get( 'mindio_post_type' ) || '',
	};
}

function Activity( { items } ) {
	if ( ! items.length ) {
		return null;
	}
	return (
		<ul
			className="mindio-ai-activity"
			aria-label={ __( 'Assistant activity', 'mindio-magic-ai' ) }
		>
			{ items.map( ( item, index ) => (
				<li
					key={ index }
					className={ `mindio-ai-activity__item is-${ item.state }` }
				>
					<Icon
						name={ ACTIVITY_ICONS[ item.state ] || 'check' }
						size={ 14 }
					/>
					<span>
						{ toolLabel( item.name, item.state !== 'running' ) }
					</span>
				</li>
			) ) }
		</ul>
	);
}

function UserMessage( { message } ) {
	const text = message.content
		.filter( ( b ) => b.type === 'text' )
		.map( ( b ) => b.text )
		.join( '\n' );
	const images = message.content.filter( ( b ) => b.type === 'image' );
	return (
		<div className="mindio-ai-msg is-user">
			<div className="mindio-ai-msg__bubble" dir="auto">
				{ text }
				{ images.length > 0 && (
					<div className="mindio-ai-msg__attachments">
						{ images.map( ( image, i ) => (
							<span key={ i } className="mindio-ai-chip">
								<Icon name="image" size={ 12 } />
								{ image.name ||
									__( 'Image', 'mindio-magic-ai' ) }
							</span>
						) ) }
					</div>
				) }
			</div>
		</div>
	);
}

function AssistantBlock( { children } ) {
	return (
		<div className="mindio-ai-msg is-assistant">
			<span className="mindio-ai-msg__avatar" aria-hidden="true">
				<Icon name="sparkles" size={ 14 } />
			</span>
			<div className="mindio-ai-msg__body">{ children }</div>
		</div>
	);
}

/**
 * Group stored messages into user turns and assistant turns (text, tools, proposals).
 *
 * @param {Array} messages Public messages.
 * @return {Array} Turns.
 */
function turns( messages ) {
	const out = [];
	let current = null;
	messages.forEach( ( message ) => {
		if ( message.role === 'user' ) {
			current = null;
			out.push( { type: 'user', message } );
			return;
		}
		if ( ! current ) {
			current = { type: 'assistant', parts: [], key: message.id };
			out.push( current );
		}
		message.content.forEach( ( block ) => {
			if ( block.type === 'text' && block.text.trim() ) {
				current.parts.push( { kind: 'text', text: block.text } );
			} else if ( block.type === 'tool_call' ) {
				current.parts.push( {
					kind: 'tool',
					name: block.name,
					id: block.id,
				} );
			} else if ( block.type === 'tool_result' ) {
				if ( block.proposal ) {
					current.parts.push( {
						kind: 'proposal',
						id: block.proposal,
					} );
				}
				const tool = current.parts.find(
					( p ) => p.kind === 'tool' && p.id === block.id
				);
				if ( tool ) {
					tool.error = block.is_error;
				}
			} else if ( block.type === 'notice' ) {
				current.parts.push( { kind: 'notice', code: block.code } );
			}
		} );
	} );
	return out;
}

export default function Thread( { conversationId, onCreated, onTouched } ) {
	const boot = useBoot();
	const connections = ( boot.connections || [] ).filter(
		( c ) => c.key_source !== 'none' && c.models.default
	);
	const [ conversation, setConversation ] = useState( null );
	const [ proposals, setProposals ] = useState( {} );
	const [ loading, setLoading ] = useState( false );
	const [ draft, setDraft ] = useState( '' );
	const [ live, setLive ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ autoApply, setAutoApply ] = useState(
		!! boot.assistant?.auto_apply
	);
	const [ choice, setChoice ] = useState( () => {
		const preferred =
			connections.find( ( c ) => c.id === boot.assistant?.connection ) ||
			connections[ 0 ];
		return {
			connection: preferred?.id || 0,
			model:
				( preferred &&
					boot.assistant?.connection === preferred.id &&
					boot.assistant?.model ) ||
				preferred?.models.default ||
				'',
		};
	} );
	const scroller = useRef();
	// Follow new output only while the reader is at the bottom; scrolling up to read stops it.
	const pinned = useRef( true );
	const abort = useRef();
	const createdHere = useRef( 0 );

	const load = useCallback( async ( id ) => {
		if ( ! id ) {
			setConversation( null );
			setProposals( {} );
			return null;
		}
		setLoading( true );
		try {
			const data = await api( `conversations/${ id }` );
			setConversation( data );
			const map = {};
			( data.proposals || [] ).forEach( ( p ) => ( map[ p.id ] = p ) );
			setProposals( map );
			return data;
		} catch ( e ) {
			setError( errorMessage( e ) );
			return null;
		} finally {
			setLoading( false );
		}
	}, [] );

	const follow = useCallback(
		async ( run, id ) => {
			abort.current = new window.AbortController();
			setLive( {
				runId: run.id,
				text: '',
				activity: [],
				proposals: [],
				usage: null,
				notices: [],
				error: '',
			} );
			await followRun(
				run.id,
				( event ) => {
					setLive( ( state ) => {
						if ( ! state ) {
							return state;
						}
						const next = { ...state };
						switch ( event.type ) {
							case 'token':
								next.text =
									state.text + ( event.data.text || '' );
								break;
							case 'tool_start':
								next.activity = [
									...state.activity,
									{ name: event.data.name, state: 'running' },
								];
								break;
							case 'tool_run':
								if (
									! state.activity.some(
										( a ) =>
											a.name === event.data.name &&
											a.state === 'running'
									)
								) {
									next.activity = [
										...state.activity,
										{
											name: event.data.name,
											state: 'running',
										},
									];
								}
								break;
							case 'tool_result': {
								const index = state.activity
									.map(
										( a ) =>
											a.name === event.data.name &&
											a.state === 'running'
									)
									.lastIndexOf( true );
								if ( index >= 0 ) {
									next.activity = state.activity.map(
										( a, i ) =>
											i === index
												? {
														...a,
														state: event.data.ok
															? 'done'
															: 'error',
												  }
												: a
									);
								}
								break;
							}
							case 'proposal':
								next.proposals = [
									...state.proposals.filter(
										( p ) => p !== event.data.id
									),
									event.data.id,
								];
								next.activity = state.activity.map( ( a ) =>
									a.state === 'running'
										? { ...a, state: 'done' }
										: a
								);
								setProposals( ( map ) => ( {
									...map,
									[ event.data.id ]: event.data,
								} ) );
								break;
							case 'usage':
								next.usage = event.data;
								break;
							case 'notice':
								next.notices = [ ...state.notices, event.data ];
								break;
							case 'error':
								next.error =
									event.data.message || event.data.code;
								break;
						}
						return next;
					} );
				},
				{
					transport: boot.assistant?.transport || 'auto',
					signal: abort.current.signal,
				}
			);
			const fresh = await load( id );
			setLive( ( state ) =>
				state && state.error ? { ...state, done: true } : null
			);
			onTouched?.( fresh );
		},
		[ boot.assistant?.transport, load, onTouched ]
	);

	useEffect( () => {
		// A conversation this thread just created is already loaded and streaming.
		if ( conversationId && conversationId === createdHere.current ) {
			return;
		}
		setError( '' );
		setLive( null );
		load( conversationId ).then( ( data ) => {
			if (
				data?.active_run &&
				[ 'queued', 'running', 'stopping' ].includes(
					data.active_run.status
				)
			) {
				follow( data.active_run, data.id );
			}
		} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ conversationId ] );

	useEffect( () => () => abort.current?.abort(), [] );

	const messageCount = conversation?.messages?.length || 0;
	const hasLive = !! live;
	useEffect( () => {
		const node = scroller.current;
		if ( node && pinned.current && ( messageCount || hasLive ) ) {
			node.scrollTop = node.scrollHeight;
		}
	}, [
		messageCount,
		hasLive,
		live?.text,
		live?.activity?.length,
		live?.proposals?.length,
	] );

	const send = async ( text, images ) => {
		setError( '' );
		let id = conversationId;
		try {
			if ( ! id ) {
				const created = await api( 'conversations', {
					method: 'POST',
					data: {
						connection_id: choice.connection,
						model: choice.model,
					},
				} );
				id = created.id;
				createdHere.current = id;
				setConversation( { ...created, messages: [], proposals: [] } );
				onCreated?.( created );
			}
			const run = await api( `conversations/${ id }/runs`, {
				method: 'POST',
				data: { message: text, images, screen: screenContext() },
			} );
			setDraft( '' );
			setConversation( ( state ) => ( {
				...( state || {} ),
				id,
				messages: [
					...( state?.messages || [] ),
					{
						id: `local-${ Date.now() }`,
						role: 'user',
						content: [
							{ type: 'text', text },
							...images.map( ( img ) => ( {
								type: 'image',
								name: img.name,
							} ) ),
						],
					},
				],
			} ) );
			await follow( run, id );
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};

	const stop = async () => {
		if ( live?.runId ) {
			await api( `runs/${ live.runId }/stop`, { method: 'POST' } ).catch(
				() => {}
			);
		}
	};

	const changeProposal = ( next ) =>
		setProposals( ( map ) => ( { ...map, [ next.id ]: next } ) );

	const history = useMemo(
		() => turns( conversation?.messages || [] ),
		[ conversation?.messages ]
	);
	const running = !! live && ! live.done;
	const activeConnection = connections.find(
		( c ) => c.id === ( conversation?.connection_id || choice.connection )
	);
	const noConnection = ! connections.length;

	if ( noConnection ) {
		return (
			<div className="mindio-ai-thread is-empty">
				<div className="mindio-ai-welcome">
					<span className="mindio-ai-welcome__mark">
						<Icon name="plug" size={ 24 } />
					</span>
					<h2>
						{ __(
							'Connect an AI provider to begin',
							'mindio-magic-ai'
						) }
					</h2>
					<p>
						{ __(
							'Mindio AI uses your own API key from Anthropic, OpenAI, Google Gemini, or any OpenAI-compatible gateway. Nothing is sent anywhere until you add one.',
							'mindio-magic-ai'
						) }
					</p>
					<Button
						variant="primary"
						href={ config.pages?.connections }
						target="_top"
					>
						{ __( 'Add a connection', 'mindio-magic-ai' ) }
					</Button>
				</div>
			</div>
		);
	}

	const empty = ! conversationId && ! history.length;

	return (
		<div className="mindio-ai-thread">
			<div className="mindio-ai-thread__header">
				<h2 className="mindio-ai-thread__title" dir="auto">
					{ conversation?.title ||
						__( 'New conversation', 'mindio-magic-ai' ) }
				</h2>
				<div className="mindio-ai-thread__model">
					{ conversation?.id ? (
						<span
							className="mindio-ai-chip"
							title={ conversation.connection_name }
						>
							<Icon name="bot" size={ 12 } />
							{ conversation.model }
						</span>
					) : (
						connections.length > 0 && (
							<SelectControl
								className="mindio-ai-model-select"
								label={ __( 'Model', 'mindio-magic-ai' ) }
								hideLabelFromVision
								value={ `${ choice.connection }|${ choice.model }` }
								options={ connections.flatMap( ( c ) =>
									Array.from(
										new Set(
											[
												c.models.default,
												c.models.fast,
												...(
													c.models.available || []
												).slice( 0, 12 ),
											].filter( Boolean )
										)
									).map( ( model ) => ( {
										value: `${ c.id }|${ model }`,
										label: `${ c.name } · ${ model }`,
									} ) )
								) }
								onChange={ ( value ) => {
									const [ connection, ...rest ] =
										value.split( '|' );
									setChoice( {
										connection: parseInt( connection, 10 ),
										model: rest.join( '|' ),
									} );
								} }
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						)
					) }
				</div>
			</div>

			{ boot.kill_switch?.active && (
				<Banner
					tone="warning"
					title={ __( 'AI is paused', 'mindio-magic-ai' ) }
				>
					{ __(
						'An administrator paused all AI requests. You can read conversations, but the assistant cannot answer until AI is resumed.',
						'mindio-magic-ai'
					) }
				</Banner>
			) }

			<div
				className="mindio-ai-thread__scroll"
				ref={ scroller }
				onScroll={ ( event ) => {
					const node = event.currentTarget;
					pinned.current =
						node.scrollHeight - node.scrollTop - node.clientHeight <
						80;
				} }
				aria-live="polite"
				aria-busy={ running }
			>
				{ loading && ! conversation && (
					<div className="mindio-ai-skeleton" aria-hidden="true" />
				) }
				{ empty && (
					<div className="mindio-ai-welcome">
						<span className="mindio-ai-welcome__mark">
							<Icon name="sparkles" size={ 24 } />
						</span>
						<h2>
							{ sprintf(
								/* translators: %s: site name. */ __(
									'How can I help with %s today?',
									'mindio-magic-ai'
								),
								boot.site?.name || ''
							) }
						</h2>
						<p>
							{ __(
								'I can look things up across your content and store, draft and edit posts, handle comments, and update products. Every change waits for your approval and can be undone.',
								'mindio-magic-ai'
							) }
						</p>
						<div className="mindio-ai-suggestions">
							{ suggestions( boot.woocommerce ).map( ( item ) => (
								<button
									key={ item.text }
									type="button"
									className="mindio-ai-suggestion"
									onClick={ () => send( item.text, [] ) }
									disabled={ running }
								>
									<Icon name={ item.icon } size={ 16 } />
									<span>{ item.text }</span>
								</button>
							) ) }
						</div>
					</div>
				) }

				{ history.map( ( turn, index ) =>
					turn.type === 'user' ? (
						<UserMessage
							key={ turn.message.id }
							message={ turn.message }
						/>
					) : (
						<AssistantBlock key={ turn.key || index }>
							{ turn.parts.some( ( p ) => p.kind === 'tool' ) && (
								<Activity
									items={ turn.parts
										.filter( ( p ) => p.kind === 'tool' )
										.map( ( p ) => ( {
											name: p.name,
											state: p.error ? 'error' : 'done',
										} ) ) }
								/>
							) }
							{ turn.parts.map( ( part, i ) => (
								<Fragment key={ i }>
									{ part.kind === 'text' && (
										<Markdown text={ part.text } />
									) }
									{ part.kind === 'proposal' && (
										<ProposalCard
											proposal={ proposals[ part.id ] }
											onChange={ changeProposal }
										/>
									) }
									{ part.kind === 'notice' &&
										part.code === 'refusal' && (
											<p className="mindio-ai-msg__notice">
												{ __(
													'The model declined to answer this request.',
													'mindio-magic-ai'
												) }
											</p>
										) }
								</Fragment>
							) ) }
						</AssistantBlock>
					)
				) }

				{ live && (
					<AssistantBlock>
						<Activity items={ live.activity } />
						{ live.text ? (
							<Markdown text={ live.text } />
						) : (
							! live.error &&
							! live.done && (
								<span
									className="mindio-ai-typing"
									aria-label={ __(
										'The assistant is thinking',
										'mindio-magic-ai'
									) }
								>
									<i />
									<i />
									<i />
								</span>
							)
						) }
						{ live.proposals.map( ( id ) => (
							<ProposalCard
								key={ id }
								proposal={ proposals[ id ] }
								onChange={ changeProposal }
							/>
						) ) }
						{ live.notices.map( ( notice, i ) => (
							<p key={ i } className="mindio-ai-msg__notice">
								{ notice.message }
							</p>
						) ) }
						{ live.error && (
							<Banner
								tone="danger"
								title={ __(
									'The assistant could not finish',
									'mindio-magic-ai'
								) }
							>
								{ live.error }
							</Banner>
						) }
					</AssistantBlock>
				) }
			</div>

			{ error && (
				<Banner
					tone="danger"
					title={ __( 'Something went wrong', 'mindio-magic-ai' ) }
					actions={
						<Button
							variant="tertiary"
							onClick={ () => setError( '' ) }
						>
							{ __( 'Dismiss', 'mindio-magic-ai' ) }
						</Button>
					}
				>
					{ error }
				</Banner>
			) }

			<Composer
				onSend={ send }
				onStop={ stop }
				running={ running }
				disabled={ boot.kill_switch?.active }
				draft={ draft }
				setDraft={ setDraft }
				vision={
					activeConnection
						? activeConnection.capabilities?.vision !== false
						: true
				}
				autoApply={ autoApply }
				onAutoApply={
					boot.assistant?.auto_apply_allowed
						? async ( value ) => {
								setAutoApply( value );
								await api( 'preferences', {
									method: 'POST',
									data: { auto_apply: value },
								} ).catch( () => setAutoApply( ! value ) );
						  }
						: null
				}
			/>
			{ live?.usage && (
				<div className="mindio-ai-thread__usage" aria-live="off">
					{ formatTokens(
						( live.usage.input_tokens || 0 ) +
							( live.usage.output_tokens || 0 )
					) }
					{ live.usage.cost_micros > 0 &&
						` · ${ formatCost( live.usage.cost_micros ) }` }
				</div>
			) }
		</div>
	);
}
