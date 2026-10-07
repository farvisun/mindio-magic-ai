/**
 * A change the assistant proposed, with a before/after diff and Apply, Reject, and Undo.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Modal } from '@wordpress/components';
import { api, errorMessage } from '../../api';
import Icon from '../../components/Icon';
import { Badge } from '../../components/ui';
import { relativeTime } from '../../format';

function statusBadge( status ) {
	const map = {
		pending: [
			'accent',
			__( 'Waiting for review', 'mindio-magic-ai' ),
			'clock',
		],
		applying: [ 'accent', __( 'Applying…', 'mindio-magic-ai' ), 'loader' ],
		applied: [
			'success',
			__( 'Applied', 'mindio-magic-ai' ),
			'circle-check',
		],
		rejected: [ 'neutral', __( 'Rejected', 'mindio-magic-ai' ), 'x' ],
		reverted: [ 'neutral', __( 'Undone', 'mindio-magic-ai' ), 'undo-2' ],
		expired: [ 'neutral', __( 'Expired', 'mindio-magic-ai' ), 'clock' ],
		stale: [
			'warning',
			__( 'Out of date', 'mindio-magic-ai' ),
			'triangle-alert',
		],
		failed: [ 'danger', __( 'Failed', 'mindio-magic-ai' ), 'circle-alert' ],
	};
	const [ tone, label, icon ] = map[ status ] || [
		'neutral',
		status,
		'info',
	];
	return (
		<Badge tone={ tone } icon={ icon }>
			{ label }
		</Badge>
	);
}

function Value( { value, kind } ) {
	const [ open, setOpen ] = useState( false );
	const text = Array.isArray( value )
		? value.join( ', ' )
		: String( value ?? '' );
	if ( text === '' ) {
		return (
			<span className="mindio-ai-diff__empty">
				{ __( '(empty)', 'mindio-magic-ai' ) }
			</span>
		);
	}
	const long = text.length > 280;
	return (
		<div className={ `mindio-ai-diff__value is-${ kind }` }>
			<div
				className={ `mindio-ai-diff__text ${
					long && ! open ? 'is-clipped' : ''
				}` }
				dir="auto"
			>
				{ text }
			</div>
			{ long && (
				<button
					type="button"
					className="mindio-ai-link-button"
					onClick={ () => setOpen( ! open ) }
					aria-expanded={ open }
				>
					{ open
						? __( 'Show less', 'mindio-magic-ai' )
						: __( 'Show all', 'mindio-magic-ai' ) }
				</button>
			) }
		</div>
	);
}

export default function ProposalCard( { proposal, onChange } ) {
	const [ busy, setBusy ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ conflict, setConflict ] = useState( false );

	if ( ! proposal ) {
		return null;
	}
	const preview = proposal.preview || {};
	const target = preview.target || proposal.result?.target;
	const changes = preview.changes || [];

	const act = async ( action, data ) => {
		setBusy( action );
		setError( '' );
		try {
			const next = await api( `proposals/${ proposal.id }/${ action }`, {
				method: 'POST',
				data,
			} );
			onChange( next );
			setConflict( false );
		} catch ( e ) {
			if ( e?.code === 'undo_conflict' ) {
				setConflict( true );
			} else {
				setError( errorMessage( e ) );
				if (
					[ 'stale', 'expired', 'not_pending' ].includes( e?.code )
				) {
					onChange( {
						...proposal,
						status:
							e.code === 'not_pending' ? proposal.status : e.code,
					} );
				}
			}
		} finally {
			setBusy( '' );
		}
	};

	return (
		<article
			className={ `mindio-ai-proposal is-${ proposal.status } is-risk-${ proposal.risk }` }
			aria-label={ preview.summary || proposal.label }
		>
			<header className="mindio-ai-proposal__header">
				<span className="mindio-ai-proposal__icon">
					<Icon name="wand-sparkles" size={ 16 } />
				</span>
				<div className="mindio-ai-proposal__heading">
					<h3 className="mindio-ai-proposal__title" dir="auto">
						{ preview.summary || proposal.label }
					</h3>
					<div className="mindio-ai-proposal__meta">
						<span>{ proposal.label }</span>
						{ proposal.created_at && (
							<span aria-hidden="true">·</span>
						) }
						{ proposal.created_at && (
							<span>{ relativeTime( proposal.created_at ) }</span>
						) }
					</div>
				</div>
				{ statusBadge( proposal.status ) }
			</header>

			{ target && (
				<div className="mindio-ai-proposal__target">
					<Icon name="file-text" size={ 14 } />
					<span dir="auto">{ target.title }</span>
					{ target.edit_link && (
						<a href={ target.edit_link } target="_top">
							{ __( 'Open in editor', 'mindio-magic-ai' ) }
						</a>
					) }
				</div>
			) }

			{ changes.length > 0 && (
				<div
					className="mindio-ai-diff"
					role="table"
					aria-label={ __( 'Proposed changes', 'mindio-magic-ai' ) }
				>
					{ changes.map( ( change ) => (
						<div
							className="mindio-ai-diff__row"
							role="row"
							key={ change.field }
						>
							<div
								className="mindio-ai-diff__field"
								role="rowheader"
							>
								{ change.label }
							</div>
							<div className="mindio-ai-diff__cells" role="cell">
								{ change.before !== '' &&
									change.before !== undefined && (
										<Value
											value={ change.before }
											kind="before"
										/>
									) }
								{ change.before !== '' &&
									change.before !== undefined &&
									change.after !== '' && (
										<Icon
											name="arrow-up"
											size={ 14 }
											className="mindio-ai-diff__arrow"
										/>
									) }
								{ change.after !== '' && (
									<Value
										value={ change.after }
										kind="after"
									/>
								) }
							</div>
						</div>
					) ) }
				</div>
			) }

			{ preview.warning && proposal.status === 'pending' && (
				<p className="mindio-ai-proposal__warning">
					<Icon name="triangle-alert" size={ 14 } />
					{ preview.warning }
				</p>
			) }
			{ proposal.result?.automatic && (
				<p className="mindio-ai-proposal__note">
					{ __(
						'Applied automatically because you allow drafts without review.',
						'mindio-magic-ai'
					) }
				</p>
			) }
			{ proposal.status === 'stale' && (
				<p className="mindio-ai-proposal__note">
					{ __(
						'The content changed after this was proposed. Ask the assistant to propose it again.',
						'mindio-magic-ai'
					) }
				</p>
			) }
			{ proposal.result?.error && (
				<p className="mindio-ai-proposal__error">
					{ proposal.result.error }
				</p>
			) }
			{ error && (
				<p className="mindio-ai-proposal__error" role="alert">
					{ error }
				</p>
			) }

			{ ( proposal.status === 'pending' || proposal.can_undo ) && (
				<footer className="mindio-ai-proposal__actions">
					{ proposal.status === 'pending' && (
						<>
							<Button
								variant="primary"
								onClick={ () => act( 'apply' ) }
								isBusy={ busy === 'apply' }
								disabled={ !! busy }
							>
								<Icon name="check" size={ 16 } />
								{ __( 'Apply', 'mindio-magic-ai' ) }
							</Button>
							<Button
								variant="tertiary"
								onClick={ () => act( 'reject' ) }
								isBusy={ busy === 'reject' }
								disabled={ !! busy }
							>
								{ __( 'Reject', 'mindio-magic-ai' ) }
							</Button>
						</>
					) }
					{ proposal.can_undo && (
						<Button
							variant="secondary"
							onClick={ () => act( 'undo' ) }
							isBusy={ busy === 'undo' }
							disabled={ !! busy }
						>
							<Icon name="undo-2" size={ 16 } />
							{ __( 'Undo', 'mindio-magic-ai' ) }
						</Button>
					) }
					{ proposal.status === 'pending' && proposal.expires_at && (
						<span className="mindio-ai-proposal__expires">
							{ sprintf(
								/* translators: %s: relative time, for example "in 50 minutes". */
								__( 'Expires %s', 'mindio-magic-ai' ),
								relativeTime( proposal.expires_at )
							) }
						</span>
					) }
				</footer>
			) }

			{ conflict && (
				<Modal
					title={ __( 'Undo anyway?', 'mindio-magic-ai' ) }
					onRequestClose={ () => setConflict( false ) }
					size="small"
				>
					<p>
						{ __(
							'Someone edited this content after the change was applied. Undoing now restores the version from before the AI change and discards those later edits.',
							'mindio-magic-ai'
						) }
					</p>
					<div className="mindio-ai-modal-actions">
						<Button
							variant="tertiary"
							onClick={ () => setConflict( false ) }
						>
							{ __( 'Keep current version', 'mindio-magic-ai' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ () => act( 'undo', { force: true } ) }
							isBusy={ busy === 'undo' }
						>
							{ __(
								'Undo and discard later edits',
								'mindio-magic-ai'
							) }
						</Button>
					</div>
				</Modal>
			) }
		</article>
	);
}
