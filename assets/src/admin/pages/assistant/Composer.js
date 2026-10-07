/**
 * Message box with image attachments, send, and stop.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import { Button, ToggleControl } from '@wordpress/components';
import Icon from '../../components/Icon';

const MAX_IMAGES = 4;
const MAX_BYTES = 3932160;
const TYPES = [ 'image/png', 'image/jpeg', 'image/webp', 'image/gif' ];

function readImage( file ) {
	return new Promise( ( resolve, reject ) => {
		const reader = new window.FileReader();
		reader.onload = () => {
			const result = String( reader.result || '' );
			resolve( {
				name: file.name,
				media_type: file.type,
				data: result.slice( result.indexOf( ',' ) + 1 ),
				preview: result,
			} );
		};
		reader.onerror = () => reject( reader.error );
		reader.readAsDataURL( file );
	} );
}

export default function Composer( {
	onSend,
	onStop,
	running,
	disabled,
	draft,
	setDraft,
	autoApply,
	onAutoApply,
	vision = true,
} ) {
	const [ images, setImages ] = useState( [] );
	const [ error, setError ] = useState( '' );
	const input = useRef();
	const file = useRef();

	useEffect( () => {
		const node = input.current;
		if ( ! node ) {
			return;
		}
		node.style.height = 'auto';
		node.style.height = `${ Math.min( 240, node.scrollHeight ) }px`;
	}, [ draft ] );

	useEffect( () => {
		if ( ! running ) {
			input.current?.focus();
		}
	}, [ running ] );

	const attach = async ( files ) => {
		setError( '' );
		const picked = Array.from( files || [] );
		const next = [ ...images ];
		for ( const item of picked ) {
			if ( next.length >= MAX_IMAGES ) {
				setError(
					sprintf(
						/* translators: %d: maximum number of images. */ __(
							'You can attach up to %d images.',
							'mindio-magic-ai'
						),
						MAX_IMAGES
					)
				);
				break;
			}
			if ( ! TYPES.includes( item.type ) || item.size > MAX_BYTES ) {
				setError(
					__(
						'Images must be PNG, JPEG, WebP, or GIF and smaller than 3.75 MB.',
						'mindio-magic-ai'
					)
				);
				continue;
			}
			next.push( await readImage( item ) );
		}
		setImages( next );
	};

	const send = () => {
		if ( running || disabled || ( ! draft.trim() && ! images.length ) ) {
			return;
		}
		onSend(
			draft.trim(),
			images.map( ( image ) => ( {
				name: image.name,
				media_type: image.media_type,
				data: image.data,
			} ) )
		);
		setImages( [] );
	};

	return (
		<div className="mindio-ai-composer">
			{ images.length > 0 && (
				<ul className="mindio-ai-composer__images">
					{ images.map( ( image, index ) => (
						<li key={ index }>
							<img src={ image.preview } alt={ image.name } />
							<button
								type="button"
								onClick={ () =>
									setImages(
										images.filter( ( _, i ) => i !== index )
									)
								}
								aria-label={ sprintf(
									/* translators: %s: file name. */ __(
										'Remove %s',
										'mindio-magic-ai'
									),
									image.name
								) }
							>
								<Icon name="x" size={ 12 } />
							</button>
						</li>
					) ) }
				</ul>
			) }
			<div className="mindio-ai-composer__box">
				<label
					className="screen-reader-text"
					htmlFor="mindio-ai-message"
				>
					{ __( 'Message the assistant', 'mindio-magic-ai' ) }
				</label>
				<textarea
					id="mindio-ai-message"
					ref={ input }
					className="mindio-ai-composer__input"
					dir="auto"
					rows={ 1 }
					value={ draft }
					disabled={ disabled }
					placeholder={ __(
						'Ask anything about your site, or tell the assistant what to change…',
						'mindio-magic-ai'
					) }
					onChange={ ( event ) => setDraft( event.target.value ) }
					onPaste={ ( event ) => {
						const pasted = Array.from(
							event.clipboardData?.files || []
						).filter( ( f ) => f.type.startsWith( 'image/' ) );
						if ( pasted.length && vision ) {
							event.preventDefault();
							attach( pasted );
						}
					} }
					onKeyDown={ ( event ) => {
						if (
							event.key === 'Enter' &&
							! event.shiftKey &&
							! event.nativeEvent.isComposing
						) {
							event.preventDefault();
							send();
						}
					} }
				/>
				<div className="mindio-ai-composer__tools">
					{ vision && (
						<>
							<input
								ref={ file }
								type="file"
								accept={ TYPES.join( ',' ) }
								multiple
								hidden
								onChange={ ( event ) =>
									attach( event.target.files )
								}
							/>
							<Button
								className="mindio-ai-icon-button"
								label={ __(
									'Attach images',
									'mindio-magic-ai'
								) }
								showTooltip
								onClick={ () => file.current?.click() }
								disabled={ running || disabled }
								icon={ <Icon name="paperclip" size={ 18 } /> }
							/>
						</>
					) }
					{ running ? (
						<Button
							className="mindio-ai-send is-stop"
							variant="secondary"
							onClick={ onStop }
							label={ __( 'Stop', 'mindio-magic-ai' ) }
							showTooltip
							icon={ <Icon name="square" size={ 16 } /> }
						/>
					) : (
						<Button
							className="mindio-ai-send"
							variant="primary"
							onClick={ send }
							disabled={
								disabled ||
								( ! draft.trim() && ! images.length )
							}
							label={ __( 'Send', 'mindio-magic-ai' ) }
							showTooltip
							icon={ <Icon name="send" size={ 16 } /> }
						/>
					) }
				</div>
			</div>
			<div className="mindio-ai-composer__footer">
				{ error ? (
					<span className="mindio-ai-composer__error" role="alert">
						{ error }
					</span>
				) : (
					<span className="mindio-ai-composer__hint">
						{ __(
							'Changes are proposed for your review before anything is saved. Enter to send, Shift+Enter for a new line.',
							'mindio-magic-ai'
						) }
					</span>
				) }
				{ onAutoApply && (
					<ToggleControl
						className="mindio-ai-composer__toggle"
						label={ __(
							'Create drafts without review',
							'mindio-magic-ai'
						) }
						checked={ !! autoApply }
						onChange={ onAutoApply }
						__nextHasNoMarginBottom
					/>
				) }
			</div>
		</div>
	);
}
