/**
 * Site settings: assistant defaults, review, access, privacy, and network rules.
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { api, errorMessage } from '../api';
import { useBoot } from '../App';
import { Banner, Card, Loading, PageHeader } from '../components/ui';

function lines( value ) {
	return ( Array.isArray( value ) ? value : [] ).join( '\n' );
}

function list( text ) {
	return text
		.split( /[\s,]+/ )
		.map( ( item ) => item.trim() )
		.filter( Boolean );
}

export default function Settings() {
	const boot = useBoot();
	const [ settings, setSettings ] = useState( null );
	const [ meta, setMeta ] = useState( {} );
	const [ access, setAccess ] = useState( null );
	const [ hosts, setHosts ] = useState( '' );
	const [ links, setLinks ] = useState( '' );
	const [ dirty, setDirty ] = useState( false );
	const [ status, setStatus ] = useState( { tone: '', text: '' } );
	const [ busy, setBusy ] = useState( false );

	useEffect( () => {
		Promise.all( [ api( 'settings' ), api( 'access' ) ] ).then(
			( [ s, a ] ) => {
				setSettings( s.settings );
				setMeta( { dedicatedKey: s.dedicated_key, proxy: s.proxy } );
				setHosts( lines( s.settings.private_hosts ) );
				setLinks( lines( s.settings.link_allowlist ) );
				setAccess( a );
			}
		);
	}, [] );

	useEffect( () => {
		const warn = ( event ) => {
			if ( dirty ) {
				event.preventDefault();
				event.returnValue = '';
			}
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );

	if ( ! settings || ! access ) {
		return (
			<div className="mindio-ai-page">
				<Loading />
			</div>
		);
	}

	const set = ( key, value ) => {
		setSettings( { ...settings, [ key ]: value } );
		setDirty( true );
	};
	const connections = ( boot.connections || [] ).filter(
		( c ) => c.status === 'active'
	);
	const current = connections.find(
		( c ) => c.id === settings.assistant_connection
	);

	const toggleCap = ( role, cap, on ) => {
		const map = { ...( access.map || {} ) };
		const caps = new Set( map[ role ] || [] );
		if ( on ) {
			caps.add( cap );
		} else {
			caps.delete( cap );
		}
		map[ role ] = Array.from( caps );
		setAccess( { ...access, map } );
		setDirty( true );
	};

	const save = async () => {
		setBusy( true );
		setStatus( { tone: '', text: '' } );
		try {
			const next = await api( 'settings', {
				method: 'PATCH',
				data: {
					...settings,
					private_hosts: list( hosts ),
					link_allowlist: list( links ),
				},
			} );
			await api( 'access', { method: 'PUT', data: { map: access.map } } );
			setSettings( next.settings );
			setHosts( lines( next.settings.private_hosts ) );
			setLinks( lines( next.settings.link_allowlist ) );
			setDirty( false );
			setStatus( {
				tone: 'success',
				text: __( 'Settings saved.', 'mindio-magic-ai' ),
			} );
			boot.refresh();
		} catch ( e ) {
			setStatus( { tone: 'danger', text: errorMessage( e ) } );
		} finally {
			setBusy( false );
		}
	};

	const number = ( key, label, help, min, max ) => (
		<TextControl
			type="number"
			min={ min }
			max={ max }
			label={ label }
			help={ help }
			value={ String( settings[ key ] ) }
			onChange={ ( v ) => set( key, parseInt( v, 10 ) || 0 ) }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		/>
	);

	return (
		<div className="mindio-ai-page mindio-ai-settings">
			<PageHeader
				icon="settings"
				title={ __( 'Settings', 'mindio-magic-ai' ) }
				description={ __(
					'Site-wide rules for the assistant. Only administrators can change them.',
					'mindio-magic-ai'
				) }
			/>

			<Card
				title={ __( 'Assistant', 'mindio-magic-ai' ) }
				description={ __(
					'Defaults for new conversations. Each conversation keeps the model it started with.',
					'mindio-magic-ai'
				) }
			>
				<div className="mindio-ai-form mindio-ai-form--grid">
					<SelectControl
						label={ __( 'Default connection', 'mindio-magic-ai' ) }
						value={ String( settings.assistant_connection ) }
						options={ [
							{
								value: '0',
								label: __(
									'First available connection',
									'mindio-magic-ai'
								),
							},
							...connections.map( ( c ) => ( {
								value: String( c.id ),
								label: c.name,
							} ) ),
						] }
						onChange={ ( v ) => {
							setSettings( {
								...settings,
								assistant_connection: parseInt( v, 10 ),
								assistant_model: '',
							} );
							setDirty( true );
						} }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<TextControl
						label={ __( 'Default model', 'mindio-magic-ai' ) }
						help={
							current
								? __(
										'Leave empty to use the connection’s default model.',
										'mindio-magic-ai'
								  )
								: ''
						}
						value={ settings.assistant_model }
						placeholder={ current?.models.default }
						onChange={ ( v ) => set( 'assistant_model', v ) }
						dir="ltr"
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Reasoning effort', 'mindio-magic-ai' ) }
						help={ __(
							'For models that support it. Higher effort is slower and uses more tokens.',
							'mindio-magic-ai'
						) }
						value={ settings.effort }
						options={ [
							{
								value: 'low',
								label: __( 'Low', 'mindio-magic-ai' ),
							},
							{
								value: 'medium',
								label: __(
									'Medium (recommended)',
									'mindio-magic-ai'
								),
							},
							{
								value: 'high',
								label: __( 'High', 'mindio-magic-ai' ),
							},
							{
								value: 'xhigh',
								label: __( 'Extra high', 'mindio-magic-ai' ),
							},
							{
								value: 'max',
								label: __( 'Maximum', 'mindio-magic-ai' ),
							},
						] }
						onChange={ ( v ) => set( 'effort', v ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					{ number(
						'max_output_tokens',
						__(
							'Maximum answer length (tokens)',
							'mindio-magic-ai'
						),
						'',
						256,
						64000
					) }
					{ number(
						'max_steps',
						__( 'Steps per message', 'mindio-magic-ai' ),
						__(
							'How many times the assistant may call tools before it must answer.',
							'mindio-magic-ai'
						),
						1,
						40
					) }
					{ number(
						'max_wall_seconds',
						__(
							'Time limit per message (seconds)',
							'mindio-magic-ai'
						),
						'',
						20,
						900
					) }
				</div>
			</Card>

			<Card
				title={ __( 'Review', 'mindio-magic-ai' ) }
				description={ __(
					'Every change is a proposal the user applies. You can let users skip review for new drafts only.',
					'mindio-magic-ai'
				) }
			>
				<div className="mindio-ai-form">
					<ToggleControl
						label={ __(
							'Allow users to create drafts without review',
							'mindio-magic-ai'
						) }
						help={ __(
							'Each user still has to turn this on for themselves. Never applies after the assistant read comments or other untrusted text.',
							'mindio-magic-ai'
						) }
						checked={ !! settings.allow_auto_apply }
						onChange={ ( v ) => set( 'allow_auto_apply', v ) }
						__nextHasNoMarginBottom
					/>
					{ number(
						'proposal_ttl_minutes',
						__(
							'Proposals expire after (minutes)',
							'mindio-magic-ai'
						),
						'',
						5,
						10080
					) }
				</div>
			</Card>

			<Card
				title={ __( 'Access', 'mindio-magic-ai' ) }
				description={ __(
					'Administrators always have full access. Users only ever act within their own WordPress permissions: an editor cannot change products through the assistant, for example.',
					'mindio-magic-ai'
				) }
				padded={ false }
			>
				<div className="mindio-ai-table-wrap">
					<table className="mindio-ai-table mindio-ai-access">
						<thead>
							<tr>
								<th>{ __( 'Role', 'mindio-magic-ai' ) }</th>
								{ Object.entries( access.capabilities ).map(
									( [ cap, label ] ) => (
										<th key={ cap }>{ label }</th>
									)
								) }
							</tr>
						</thead>
						<tbody>
							{ access.roles.map( ( role ) => (
								<tr key={ role.id }>
									<th scope="row">{ role.name }</th>
									{ Object.keys( access.capabilities ).map(
										( cap ) => (
											<td key={ cap }>
												<CheckboxControl
													label={ `${ role.name }: ${ access.capabilities[ cap ] }` }
													className="mindio-ai-access__check"
													checked={
														role.locked ||
														(
															access.map?.[
																role.id
															] || []
														).includes( cap )
													}
													disabled={ role.locked }
													onChange={ ( on ) =>
														toggleCap(
															role.id,
															cap,
															on
														)
													}
													__nextHasNoMarginBottom
												/>
											</td>
										)
									) }
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			</Card>

			<Card title={ __( 'Privacy and retention', 'mindio-magic-ai' ) }>
				<div className="mindio-ai-form mindio-ai-form--grid">
					{ number(
						'conversation_retention_days',
						__(
							'Delete conversations after (days)',
							'mindio-magic-ai'
						),
						__(
							'0 keeps them until deleted. Pinned conversations are kept.',
							'mindio-magic-ai'
						),
						0,
						3650
					) }
					{ number(
						'audit_retention_days',
						__(
							'Keep the activity log for (days)',
							'mindio-magic-ai'
						),
						'',
						7,
						3650
					) }
					<TextControl
						type="email"
						label={ __( 'Budget alert email', 'mindio-magic-ai' ) }
						help={ __(
							'Defaults to the site admin email.',
							'mindio-magic-ai'
						) }
						value={ settings.alert_email }
						onChange={ ( v ) => set( 'alert_email', v ) }
						dir="ltr"
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</div>
			</Card>

			<Card
				title={ __( 'Network and links', 'mindio-magic-ai' ) }
				description={ __(
					'Outbound requests are limited to public HTTPS addresses and pinned to the address that was checked.',
					'mindio-magic-ai'
				) }
			>
				{ meta.proxy && (
					<Banner tone="info">
						{ __(
							'WordPress sends requests through a proxy on this site, so the proxy resolves addresses.',
							'mindio-magic-ai'
						) }
					</Banner>
				) }
				<div className="mindio-ai-form mindio-ai-form--grid">
					<TextareaControl
						label={ __( 'Private hosts', 'mindio-magic-ai' ) }
						help={ __(
							'Exact host:port pairs allowed to use private networks or HTTP, one per line, for example a self-hosted model at 192.168.1.20:11434. Leave empty unless you need it.',
							'mindio-magic-ai'
						) }
						value={ hosts }
						onChange={ ( v ) => {
							setHosts( v );
							setDirty( true );
						} }
						rows={ 3 }
						dir="ltr"
						__nextHasNoMarginBottom
					/>
					<TextareaControl
						label={ __(
							'Clickable link hosts',
							'mindio-magic-ai'
						) }
						help={ __(
							'Links in answers are clickable only for this site and these hosts (one per line, *.example.com for subdomains).',
							'mindio-magic-ai'
						) }
						value={ links }
						onChange={ ( v ) => {
							setLinks( v );
							setDirty( true );
						} }
						rows={ 3 }
						dir="ltr"
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Live answers', 'mindio-magic-ai' ) }
						help={ __(
							'Automatic streams answers and switches to checking every second if the host buffers the stream.',
							'mindio-magic-ai'
						) }
						value={ settings.stream_transport }
						options={ [
							{
								value: 'auto',
								label: __(
									'Automatic (recommended)',
									'mindio-magic-ai'
								),
							},
							{
								value: 'sse',
								label: __( 'Always stream', 'mindio-magic-ai' ),
							},
							{
								value: 'poll',
								label: __(
									'Never stream (check periodically)',
									'mindio-magic-ai'
								),
							},
						] }
						onChange={ ( v ) => set( 'stream_transport', v ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</div>
				<p className="mindio-ai-muted">
					{ meta.dedicatedKey
						? __(
								'Keys are encrypted with MINDIO_MAGIC_AI_SECRET_KEY.',
								'mindio-magic-ai'
						  )
						: __(
								'Tip: define MINDIO_MAGIC_AI_SECRET_KEY in wp-config.php so saved keys survive a change of the site’s security salts.',
								'mindio-magic-ai'
						  ) }
				</p>
			</Card>

			<Card title={ __( 'Uninstall', 'mindio-magic-ai' ) }>
				<ToggleControl
					label={ __(
						'Delete all Mindio AI data when the plugin is deleted',
						'mindio-magic-ai'
					) }
					help={ __(
						'Removes connections, keys, conversations, usage, and the activity log. Changes already made to your content stay.',
						'mindio-magic-ai'
					) }
					checked={ !! settings.delete_on_uninstall }
					onChange={ ( v ) => set( 'delete_on_uninstall', v ) }
					__nextHasNoMarginBottom
				/>
			</Card>

			<div className={ `mindio-ai-savebar ${ dirty ? 'is-dirty' : '' }` }>
				<span role="status">
					{ status.text ||
						( dirty
							? __(
									'You have unsaved changes.',
									'mindio-magic-ai'
							  )
							: __( 'All changes saved.', 'mindio-magic-ai' ) ) }
				</span>
				<Button
					variant="primary"
					onClick={ save }
					isBusy={ busy }
					disabled={ busy || ! dirty }
				>
					{ __( 'Save settings', 'mindio-magic-ai' ) }
				</Button>
			</div>
		</div>
	);
}
