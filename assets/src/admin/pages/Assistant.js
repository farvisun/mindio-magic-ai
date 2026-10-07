/**
 * Assistant page: conversation list and the active conversation.
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
	TextControl,
} from '@wordpress/components';
import { api } from '../api';
import Icon from '../components/Icon';
import { relativeTime } from '../format';
import Thread from './assistant/Thread';

const config = window.mindioAI || {};

function currentId() {
	return (
		parseInt(
			new URLSearchParams( window.location.search ).get(
				'conversation'
			) || '0',
			10
		) || 0
	);
}

function setUrl( id ) {
	const url = new URL( window.location.href );
	if ( id ) {
		url.searchParams.set( 'conversation', String( id ) );
	} else {
		url.searchParams.delete( 'conversation' );
	}
	window.history.replaceState( {}, '', url.toString() );
}

export default function Assistant() {
	const [ list, setList ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ active, setActive ] = useState( currentId );
	const [ threadKey, setThreadKey ] = useState( () =>
		String( currentId() || 'new' )
	);
	const [ renaming, setRenaming ] = useState( null );
	const [ deleting, setDeleting ] = useState( null );
	const [ open, setOpen ] = useState( false );

	const refresh = useCallback( async () => {
		try {
			setList( await api( 'conversations', { query: { search } } ) );
		} catch ( e ) {
			setList( [] );
		}
	}, [ search ] );

	useEffect( () => {
		const timer = setTimeout( refresh, search ? 250 : 0 );
		return () => clearTimeout( timer );
	}, [ refresh, search ] );

	const select = ( id ) => {
		setActive( id );
		setThreadKey( id ? String( id ) : `new-${ Date.now() }` );
		setUrl( id );
		setOpen( false );
	};

	const update = async ( id, data ) => {
		await api( `conversations/${ id }`, { method: 'PATCH', data } );
		refresh();
	};

	const remove = async ( id ) => {
		await api( `conversations/${ id }`, { method: 'DELETE' } );
		setDeleting( null );
		if ( id === active ) {
			select( 0 );
		}
		refresh();
	};

	return (
		<div
			className={ `mindio-ai-assistant ${ open ? 'is-list-open' : '' }` }
		>
			<aside
				className="mindio-ai-conversations"
				aria-label={ __( 'Conversations', 'mindio-magic-ai' ) }
			>
				<div className="mindio-ai-conversations__top">
					<Button
						variant="primary"
						className="mindio-ai-new-chat"
						onClick={ () => select( 0 ) }
					>
						<Icon name="plus" size={ 16 } />
						{ __( 'New conversation', 'mindio-magic-ai' ) }
					</Button>
					<div className="mindio-ai-search">
						<Icon name="search" size={ 14 } />
						<label
							className="screen-reader-text"
							htmlFor="mindio-ai-conversation-search"
						>
							{ __( 'Search conversations', 'mindio-magic-ai' ) }
						</label>
						<input
							id="mindio-ai-conversation-search"
							type="search"
							dir="auto"
							value={ search }
							placeholder={ __(
								'Search conversations',
								'mindio-magic-ai'
							) }
							onChange={ ( e ) => setSearch( e.target.value ) }
						/>
					</div>
				</div>
				<ul className="mindio-ai-conversations__list">
					{ list.length === 0 && (
						<li className="mindio-ai-conversations__empty">
							{ search
								? __(
										'No conversations match.',
										'mindio-magic-ai'
								  )
								: __(
										'Your conversations will appear here.',
										'mindio-magic-ai'
								  ) }
						</li>
					) }
					{ list.map( ( item ) => (
						<li
							key={ item.id }
							className={ `mindio-ai-conversation ${
								item.id === active ? 'is-active' : ''
							}` }
						>
							<button
								type="button"
								className="mindio-ai-conversation__open"
								onClick={ () => select( item.id ) }
								aria-current={
									item.id === active ? 'true' : undefined
								}
							>
								{ item.pinned && (
									<Icon
										name="pin"
										size={ 12 }
										className="mindio-ai-conversation__pin"
									/>
								) }
								<span
									className="mindio-ai-conversation__title"
									dir="auto"
								>
									{ item.title ||
										__( 'Untitled', 'mindio-magic-ai' ) }
								</span>
								<span className="mindio-ai-conversation__time">
									{ relativeTime( item.updated_at ) }
								</span>
							</button>
							<DropdownMenu
								className="mindio-ai-conversation__menu"
								icon={
									<Icon name="chevron-down" size={ 14 } />
								}
								label={ __(
									'Conversation actions',
									'mindio-magic-ai'
								) }
								popoverProps={ { placement: 'bottom-end' } }
							>
								{ ( { onClose } ) => (
									<MenuGroup>
										<MenuItem
											icon={
												<Icon
													name={
														item.pinned
															? 'pin-off'
															: 'pin'
													}
													size={ 16 }
												/>
											}
											onClick={ () => {
												update( item.id, {
													pinned: ! item.pinned,
												} );
												onClose();
											} }
										>
											{ item.pinned
												? __(
														'Unpin',
														'mindio-magic-ai'
												  )
												: __(
														'Pin',
														'mindio-magic-ai'
												  ) }
										</MenuItem>
										<MenuItem
											icon={
												<Icon
													name="pencil"
													size={ 16 }
												/>
											}
											onClick={ () => {
												setRenaming( {
													id: item.id,
													title: item.title,
												} );
												onClose();
											} }
										>
											{ __(
												'Rename',
												'mindio-magic-ai'
											) }
										</MenuItem>
										<MenuItem
											icon={
												<Icon
													name="trash-2"
													size={ 16 }
												/>
											}
											isDestructive
											onClick={ () => {
												setDeleting( item );
												onClose();
											} }
										>
											{ __(
												'Delete',
												'mindio-magic-ai'
											) }
										</MenuItem>
									</MenuGroup>
								) }
							</DropdownMenu>
						</li>
					) ) }
				</ul>
				{ config.embed && (
					<a
						className="mindio-ai-conversations__full"
						href={ config.pages?.assistant }
						target="_top"
					>
						<Icon name="maximize-2" size={ 14 } />
						{ __( 'Open full page', 'mindio-magic-ai' ) }
					</a>
				) }
			</aside>
			<section className="mindio-ai-assistant__main">
				<Button
					className="mindio-ai-list-toggle"
					onClick={ () => setOpen( ! open ) }
					aria-expanded={ open }
					icon={ <Icon name="panel-left" size={ 18 } /> }
					label={ __( 'Conversations', 'mindio-magic-ai' ) }
				/>
				<Thread
					key={ threadKey }
					conversationId={ active }
					onCreated={ ( created ) => {
						setActive( created.id );
						setUrl( created.id );
						refresh();
					} }
					onTouched={ refresh }
				/>
			</section>

			{ renaming && (
				<Modal
					title={ __( 'Rename conversation', 'mindio-magic-ai' ) }
					onRequestClose={ () => setRenaming( null ) }
					size="small"
				>
					<form
						onSubmit={ async ( event ) => {
							event.preventDefault();
							await update( renaming.id, {
								title: renaming.title,
							} );
							setRenaming( null );
						} }
					>
						<TextControl
							label={ __( 'Title', 'mindio-magic-ai' ) }
							value={ renaming.title }
							onChange={ ( title ) =>
								setRenaming( { ...renaming, title } )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<div className="mindio-ai-modal-actions">
							<Button
								variant="tertiary"
								onClick={ () => setRenaming( null ) }
							>
								{ __( 'Cancel', 'mindio-magic-ai' ) }
							</Button>
							<Button variant="primary" type="submit">
								{ __( 'Save', 'mindio-magic-ai' ) }
							</Button>
						</div>
					</form>
				</Modal>
			) }
			{ deleting && (
				<Modal
					title={ __( 'Delete conversation?', 'mindio-magic-ai' ) }
					onRequestClose={ () => setDeleting( null ) }
					size="small"
				>
					<p>
						{ __(
							'The conversation and its history are deleted. Changes you already applied stay on the site.',
							'mindio-magic-ai'
						) }
					</p>
					<div className="mindio-ai-modal-actions">
						<Button
							variant="tertiary"
							onClick={ () => setDeleting( null ) }
						>
							{ __( 'Cancel', 'mindio-magic-ai' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ () => remove( deleting.id ) }
						>
							{ __( 'Delete', 'mindio-magic-ai' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
