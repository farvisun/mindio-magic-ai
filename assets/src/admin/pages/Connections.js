/**
 * Provider connections: add, edit, test, and choose the assistant default.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Modal, TextControl } from '@wordpress/components';
import { api, errorMessage } from '../api';
import { useBoot } from '../App';
import Icon from '../components/Icon';
import { Badge, Banner, EmptyState, Field, PageHeader } from '../components/ui';
import { formatNumber, relativeTime } from '../format';

function statusBadge( connection ) {
	if ( connection.status === 'active' ) {
		return (
			<Badge tone="success" icon="circle-check">
				{ __( 'Ready', 'mindio-magic-ai' ) }
			</Badge>
		);
	}
	if ( connection.status === 'needs_model' ) {
		return (
			<Badge tone="warning" icon="triangle-alert">
				{ __( 'Choose a model', 'mindio-magic-ai' ) }
			</Badge>
		);
	}
	return (
		<Badge tone="warning" icon="key-round">
			{ __( 'Needs a key', 'mindio-magic-ai' ) }
		</Badge>
	);
}

function keyLabel( connection ) {
	if ( connection.key_source === 'constant' ) {
		return __( 'From wp-config.php', 'mindio-magic-ai' );
	}
	if ( connection.key_source === 'stored' ) {
		return sprintf(
			/* translators: %s: last four characters of the key. */ __(
				'Saved, ends in %s',
				'mindio-magic-ai'
			),
			connection.key_hint
		);
	}
	return __( 'No key', 'mindio-magic-ai' );
}

/**
 * Help text under the API key field.
 *
 * @param {?Object} connection Connection being edited, if any.
 * @param {?Object} meta       Preset details.
 * @return {string} Help text.
 */
function keyHelp( connection, meta ) {
	if ( connection?.key_source === 'stored' ) {
		return sprintf(
			/* translators: %s: last four characters of the key. */
			__(
				'A key ending in %s is saved. Leave this empty to keep it.',
				'mindio-magic-ai'
			),
			connection.key_hint
		);
	}
	if ( meta?.constant_set ) {
		return sprintf(
			/* translators: %s: PHP constant name. */
			__(
				'Optional: %s is defined in wp-config.php and is used for this provider’s own address.',
				'mindio-magic-ai'
			),
			meta.constant
		);
	}
	return __(
		'Stored encrypted and never shown again. Only the last four characters are displayed.',
		'mindio-magic-ai'
	);
}

function ConnectionForm( { presets, connection, onClose, onSaved } ) {
	const editing = !! connection;
	const [ preset, setPreset ] = useState( connection?.preset || 'anthropic' );
	const meta = presets.find( ( p ) => p.id === preset ) || presets[ 0 ];
	const [ name, setName ] = useState( connection?.name || '' );
	const [ baseUrl, setBaseUrl ] = useState(
		connection?.base_url || meta?.base_url || ''
	);
	const [ apiKey, setApiKey ] = useState( '' );
	const [ model, setModel ] = useState(
		connection?.models.default || meta?.default_model || ''
	);
	const [ fast, setFast ] = useState(
		connection?.models.fast || meta?.fast_model || ''
	);
	const [ timeout, setTimeoutValue ] = useState(
		String( connection?.options.timeout || 120 )
	);
	const [ headers, setHeaders ] = useState(
		( connection?.header_names || [] ).map( ( n ) => ( {
			name: n,
			value: '',
		} ) )
	);
	const [ models, setModels ] = useState(
		connection?.models.available || meta?.suggested || []
	);
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );

	const urlChanged =
		editing && baseUrl.replace( /\/$/, '' ) !== connection.base_url;
	const editableUrl = editing ? connection.editable_url : meta?.editable_url;

	const choosePreset = ( id ) => {
		const next = presets.find( ( p ) => p.id === id );
		setPreset( id );
		setBaseUrl( next?.base_url || '' );
		setModel( next?.default_model || '' );
		setFast( next?.fast_model || '' );
		setModels( next?.suggested || [] );
	};

	const save = async ( event ) => {
		event.preventDefault();
		setBusy( true );
		setError( '' );
		const data = {
			name,
			models: { default: model, fast },
			options: { timeout: parseInt( timeout, 10 ) || 120 },
			headers: headers.filter( ( h ) => h.name.trim() ),
		};
		if ( apiKey.trim() ) {
			data.api_key = apiKey.trim();
		}
		if ( editableUrl ) {
			data.base_url = baseUrl;
		}
		try {
			const saved = editing
				? await api( `connections/${ connection.id }`, {
						method: 'PATCH',
						data,
				  } )
				: await api( 'connections', {
						method: 'POST',
						data: { ...data, preset },
				  } );
			onSaved( saved );
		} catch ( e ) {
			setError( errorMessage( e ) );
		} finally {
			setBusy( false );
		}
	};

	const fetchModels = async () => {
		setBusy( true );
		setError( '' );
		try {
			const result = await api( `connections/${ connection.id }/models`, {
				method: 'POST',
			} );
			setModels( result.models );
		} catch ( e ) {
			setError( errorMessage( e ) );
		} finally {
			setBusy( false );
		}
	};

	const modelOptions = Array.from(
		new Set( [ model, fast, ...models ].filter( Boolean ) )
	).map( ( id ) => ( { value: id, label: id } ) );

	return (
		<Modal
			title={
				editing
					? __( 'Edit connection', 'mindio-magic-ai' )
					: __( 'Add a connection', 'mindio-magic-ai' )
			}
			onRequestClose={ onClose }
			className="mindio-ai-modal mindio-ai-connection-modal"
			size="medium"
		>
			<form onSubmit={ save } className="mindio-ai-form">
				{ ! editing && (
					<fieldset className="mindio-ai-presets">
						<legend className="mindio-ai-field__label">
							{ __( 'Provider', 'mindio-magic-ai' ) }
						</legend>
						<div className="mindio-ai-presets__grid">
							{ presets.map( ( item ) => (
								<label
									key={ item.id }
									htmlFor={ `mindio-ai-preset-${ item.id }` }
									className={ `mindio-ai-preset ${
										preset === item.id ? 'is-selected' : ''
									}` }
								>
									<input
										id={ `mindio-ai-preset-${ item.id }` }
										type="radio"
										name="mindio-ai-preset"
										value={ item.id }
										checked={ preset === item.id }
										onChange={ () =>
											choosePreset( item.id )
										}
									/>
									<span className="mindio-ai-preset__label">
										{ item.label }
									</span>
									{ item.constant_set && (
										<span className="mindio-ai-preset__hint">
											{ __(
												'Key in wp-config.php',
												'mindio-magic-ai'
											) }
										</span>
									) }
								</label>
							) ) }
						</div>
					</fieldset>
				) }

				<TextControl
					label={ __( 'Name', 'mindio-magic-ai' ) }
					value={ name }
					placeholder={ meta?.label }
					onChange={ setName }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>

				{ editableUrl ? (
					<Field
						label={ __( 'API base URL', 'mindio-magic-ai' ) }
						help={ __(
							'HTTPS only. A private or local address works only after an administrator adds its host and port to Settings → Private hosts.',
							'mindio-magic-ai'
						) }
						id="mindio-ai-base-url"
					>
						<input
							id="mindio-ai-base-url"
							className="mindio-ai-input"
							type="url"
							dir="ltr"
							required
							value={ baseUrl }
							placeholder="https://api.example.com/v1"
							onChange={ ( e ) => setBaseUrl( e.target.value ) }
						/>
					</Field>
				) : (
					<Field label={ __( 'API address', 'mindio-magic-ai' ) }>
						<code className="mindio-ai-code" dir="ltr">
							{ baseUrl }
						</code>
					</Field>
				) }
				{ urlChanged && (
					<Banner
						tone="warning"
						title={ __(
							'The saved key will be removed',
							'mindio-magic-ai'
						) }
					>
						{ __(
							'Keys are tied to the address they were saved for. Enter the key again for the new address.',
							'mindio-magic-ai'
						) }
					</Banner>
				) }

				<Field
					label={ __( 'API key', 'mindio-magic-ai' ) }
					id="mindio-ai-key"
					help={ keyHelp( editing ? connection : null, meta ) }
				>
					<input
						id="mindio-ai-key"
						className="mindio-ai-input"
						type="password"
						dir="ltr"
						autoComplete="off"
						spellCheck="false"
						value={ apiKey }
						onChange={ ( e ) => setApiKey( e.target.value ) }
						placeholder={ editing ? '••••••••' : '' }
					/>
					{ meta?.keys_url && (
						<a
							className="mindio-ai-field__link"
							href={ meta.keys_url }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Get a key', 'mindio-magic-ai' ) }
							<Icon name="external-link" size={ 12 } />
						</a>
					) }
				</Field>

				<div className="mindio-ai-form__row">
					<Field
						label={ __( 'Default model', 'mindio-magic-ai' ) }
						id="mindio-ai-model"
					>
						<input
							id="mindio-ai-model"
							className="mindio-ai-input"
							dir="ltr"
							list="mindio-ai-models"
							value={ model }
							onChange={ ( e ) => setModel( e.target.value ) }
							placeholder={ __( 'Model ID', 'mindio-magic-ai' ) }
						/>
					</Field>
					<Field
						label={ __(
							'Fast model (optional)',
							'mindio-magic-ai'
						) }
						id="mindio-ai-fast-model"
					>
						<input
							id="mindio-ai-fast-model"
							className="mindio-ai-input"
							dir="ltr"
							list="mindio-ai-models"
							value={ fast }
							onChange={ ( e ) => setFast( e.target.value ) }
							placeholder={ __( 'Model ID', 'mindio-magic-ai' ) }
						/>
					</Field>
					<datalist id="mindio-ai-models">
						{ modelOptions.map( ( option ) => (
							<option
								key={ option.value }
								value={ option.value }
							/>
						) ) }
					</datalist>
				</div>
				{ editing && (
					<Button
						variant="link"
						onClick={ fetchModels }
						disabled={ busy }
					>
						<Icon name="refresh-cw" size={ 14 } />
						{ __(
							'Load models from the provider',
							'mindio-magic-ai'
						) }
					</Button>
				) }

				<details className="mindio-ai-advanced">
					<summary>{ __( 'Advanced', 'mindio-magic-ai' ) }</summary>
					<TextControl
						type="number"
						min={ 10 }
						max={ 600 }
						label={ __( 'Timeout (seconds)', 'mindio-magic-ai' ) }
						value={ timeout }
						onChange={ setTimeoutValue }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<Field
						label={ __( 'Extra headers', 'mindio-magic-ai' ) }
						help={ __(
							'For gateways that need extra headers. Values are encrypted and write-only: leave a value empty to keep the saved one.',
							'mindio-magic-ai'
						) }
					>
						{ headers.map( ( header, index ) => (
							<div className="mindio-ai-header-row" key={ index }>
								<input
									className="mindio-ai-input"
									dir="ltr"
									aria-label={ __(
										'Header name',
										'mindio-magic-ai'
									) }
									value={ header.name }
									placeholder="X-Header"
									onChange={ ( e ) =>
										setHeaders(
											headers.map( ( h, i ) =>
												i === index
													? {
															...h,
															name: e.target
																.value,
													  }
													: h
											)
										)
									}
								/>
								<input
									className="mindio-ai-input"
									dir="ltr"
									type="password"
									autoComplete="off"
									aria-label={ __(
										'Header value',
										'mindio-magic-ai'
									) }
									value={ header.value }
									placeholder={ editing ? '••••' : '' }
									onChange={ ( e ) =>
										setHeaders(
											headers.map( ( h, i ) =>
												i === index
													? {
															...h,
															value: e.target
																.value,
													  }
													: h
											)
										)
									}
								/>
								<Button
									icon={ <Icon name="x" size={ 14 } /> }
									label={ __(
										'Remove header',
										'mindio-magic-ai'
									) }
									onClick={ () =>
										setHeaders(
											headers.filter(
												( _, i ) => i !== index
											)
										)
									}
								/>
							</div>
						) ) }
						<Button
							variant="secondary"
							size="small"
							onClick={ () =>
								setHeaders( [
									...headers,
									{ name: '', value: '' },
								] )
							}
						>
							<Icon name="plus" size={ 14 } />
							{ __( 'Add header', 'mindio-magic-ai' ) }
						</Button>
					</Field>
				</details>

				{ error && (
					<Banner
						tone="danger"
						title={ __( 'Could not save', 'mindio-magic-ai' ) }
					>
						{ error }
					</Banner>
				) }

				<div className="mindio-ai-modal-actions">
					<Button variant="tertiary" onClick={ onClose }>
						{ __( 'Cancel', 'mindio-magic-ai' ) }
					</Button>
					<Button
						variant="primary"
						type="submit"
						isBusy={ busy }
						disabled={ busy }
					>
						{ editing
							? __( 'Save changes', 'mindio-magic-ai' )
							: __( 'Add connection', 'mindio-magic-ai' ) }
					</Button>
				</div>
			</form>
		</Modal>
	);
}

export default function Connections() {
	const boot = useBoot();
	const [ editing, setEditing ] = useState( null );
	const [ results, setResults ] = useState( {} );
	const [ busy, setBusy ] = useState( 0 );
	const [ removing, setRemoving ] = useState( null );
	const [ defaultId, setDefaultId ] = useState(
		boot.assistant?.connection || 0
	);
	const canManage = !! boot.manage_connections;
	const connections = boot.connections || [];

	const test = async ( connection ) => {
		setBusy( connection.id );
		try {
			const result = await api( `connections/${ connection.id }/test`, {
				method: 'POST',
			} );
			setResults( {
				...results,
				[ connection.id ]: { ok: true, ...result },
			} );
			boot.refresh();
		} catch ( e ) {
			setResults( {
				...results,
				[ connection.id ]: { ok: false, message: errorMessage( e ) },
			} );
		} finally {
			setBusy( 0 );
		}
	};

	const makeDefault = async ( connection ) => {
		setDefaultId( connection.id );
		await api( 'settings', {
			method: 'PATCH',
			data: { assistant_connection: connection.id, assistant_model: '' },
		} ).catch( () => {} );
		boot.refresh();
	};

	return (
		<div className="mindio-ai-page">
			<PageHeader
				icon="plug"
				title={ __( 'Connections', 'mindio-magic-ai' ) }
				description={ __(
					'Bring your own key. Each connection is a provider account; keys are encrypted, never shown again, and tied to the address they were saved for.',
					'mindio-magic-ai'
				) }
				actions={
					canManage && (
						<Button
							variant="primary"
							onClick={ () => setEditing( 'new' ) }
						>
							<Icon name="plus" size={ 16 } />
							{ __( 'Add connection', 'mindio-magic-ai' ) }
						</Button>
					)
				}
			/>

			{ ! boot.encryption && (
				<Banner
					tone="danger"
					title={ __(
						'Keys cannot be encrypted on this server',
						'mindio-magic-ai'
					) }
				>
					{ __(
						'Install the sodium or OpenSSL PHP extension before adding connections.',
						'mindio-magic-ai'
					) }
				</Banner>
			) }

			{ connections.length === 0 ? (
				<EmptyState
					icon="plug"
					title={ __( 'No connections yet', 'mindio-magic-ai' ) }
					actions={
						canManage && (
							<Button
								variant="primary"
								onClick={ () => setEditing( 'new' ) }
							>
								{ __(
									'Add your first connection',
									'mindio-magic-ai'
								) }
							</Button>
						)
					}
				>
					{ canManage
						? __(
								'Connect Anthropic Claude, OpenAI, Google Gemini, DeepSeek, or any OpenAI-compatible gateway. Nothing is sent to a provider until you add a connection and use the assistant.',
								'mindio-magic-ai'
						  )
						: __(
								'Ask a site administrator to add a provider connection.',
								'mindio-magic-ai'
						  ) }
				</EmptyState>
			) : (
				<div className="mindio-ai-connections">
					{ connections.map( ( connection ) => {
						const result = results[ connection.id ];
						return (
							<article
								key={ connection.id }
								className={ `mindio-ai-connection ${
									defaultId === connection.id
										? 'is-default'
										: ''
								}` }
							>
								<header className="mindio-ai-connection__header">
									<div>
										<h2
											className="mindio-ai-connection__name"
											dir="auto"
										>
											{ connection.name }
										</h2>
										<span className="mindio-ai-connection__provider">
											{ connection.preset_label }
										</span>
									</div>
									{ statusBadge( connection ) }
								</header>
								<dl className="mindio-ai-connection__facts">
									<div>
										<dt>
											{ __(
												'Address',
												'mindio-magic-ai'
											) }
										</dt>
										<dd dir="ltr">
											{ connection.bound_host }
										</dd>
									</div>
									<div>
										<dt>
											{ __( 'Key', 'mindio-magic-ai' ) }
										</dt>
										<dd>{ keyLabel( connection ) }</dd>
									</div>
									<div>
										<dt>
											{ __(
												'Default model',
												'mindio-magic-ai'
											) }
										</dt>
										<dd dir="ltr">
											{ connection.models.default || '—' }
										</dd>
									</div>
									{ connection.models.fetched > 0 && (
										<div>
											<dt>
												{ __(
													'Models available',
													'mindio-magic-ai'
												) }
											</dt>
											<dd>
												{ formatNumber(
													connection.models.available
														.length
												) }{ ' ' }
												·{ ' ' }
												{ relativeTime(
													new Date(
														connection.models
															.fetched * 1000
													).toISOString()
												) }
											</dd>
										</div>
									) }
								</dl>
								{ result && (
									<p
										className={ `mindio-ai-connection__result ${
											result.ok ? 'is-ok' : 'is-error'
										}` }
										role="status"
									>
										<Icon
											name={
												result.ok
													? 'circle-check'
													: 'circle-alert'
											}
											size={ 14 }
										/>
										{ result.ok
											? sprintf(
													/* translators: 1: latency in milliseconds, 2: number of models. */
													__(
														'Connected in %1$s ms · %2$s models',
														'mindio-magic-ai'
													),
													formatNumber(
														result.latency_ms
													),
													formatNumber(
														result.models
													)
											  ) +
											  ( result.model_found
													? ''
													: ' · ' +
													  __(
															'the default model was not in the list',
															'mindio-magic-ai'
													  ) )
											: result.message }
									</p>
								) }
								<footer className="mindio-ai-connection__actions">
									{ canManage && (
										<>
											<Button
												variant="secondary"
												onClick={ () =>
													test( connection )
												}
												isBusy={
													busy === connection.id
												}
												disabled={
													!! busy ||
													connection.key_source ===
														'none'
												}
											>
												{ __(
													'Test',
													'mindio-magic-ai'
												) }
											</Button>
											<Button
												variant="tertiary"
												onClick={ () =>
													setEditing( connection )
												}
											>
												{ __(
													'Edit',
													'mindio-magic-ai'
												) }
											</Button>
											{ boot.caps?.manage_options &&
												defaultId !== connection.id &&
												connection.status ===
													'active' && (
													<Button
														variant="tertiary"
														onClick={ () =>
															makeDefault(
																connection
															)
														}
													>
														{ __(
															'Use for the assistant',
															'mindio-magic-ai'
														) }
													</Button>
												) }
											<Button
												variant="tertiary"
												isDestructive
												onClick={ () =>
													setRemoving( connection )
												}
											>
												{ __(
													'Delete',
													'mindio-magic-ai'
												) }
											</Button>
										</>
									) }
									{ defaultId === connection.id && (
										<Badge tone="accent">
											{ __(
												'Assistant default',
												'mindio-magic-ai'
											) }
										</Badge>
									) }
								</footer>
							</article>
						);
					} ) }
				</div>
			) }

			{ editing && (
				<ConnectionForm
					presets={ boot.presets || [] }
					connection={ editing === 'new' ? null : editing }
					onClose={ () => setEditing( null ) }
					onSaved={ () => {
						setEditing( null );
						boot.refresh();
					} }
				/>
			) }
			{ removing && (
				<Modal
					title={ __( 'Delete connection?', 'mindio-magic-ai' ) }
					onRequestClose={ () => setRemoving( null ) }
					size="small"
				>
					<p>
						{ __(
							'The saved key is deleted. Conversations that use this connection can still be read but cannot continue.',
							'mindio-magic-ai'
						) }
					</p>
					<div className="mindio-ai-modal-actions">
						<Button
							variant="tertiary"
							onClick={ () => setRemoving( null ) }
						>
							{ __( 'Cancel', 'mindio-magic-ai' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ async () => {
								await api( `connections/${ removing.id }`, {
									method: 'DELETE',
								} );
								setRemoving( null );
								boot.refresh();
							} }
						>
							{ __( 'Delete', 'mindio-magic-ai' ) }
						</Button>
					</div>
				</Modal>
			) }
			<p className="mindio-ai-footnote">
				<Icon name="shield-check" size={ 14 } />
				{ __(
					'Requests go straight from your server to the provider you choose. Each provider handles data under its own terms; see the plugin readme for links.',
					'mindio-magic-ai'
				) }
			</p>
		</div>
	);
}
