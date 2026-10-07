/**
 * Small shared UI pieces styled with the Mindio tokens.
 */
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import Icon from './Icon';

export function PageHeader( { title, description, actions, icon } ) {
	return (
		<header className="mindio-ai-page-header">
			<div className="mindio-ai-page-header__text">
				<h1 className="mindio-ai-page-header__title">
					{ icon && <Icon name={ icon } size={ 20 } /> }
					{ title }
				</h1>
				{ description && (
					<p className="mindio-ai-page-header__description">
						{ description }
					</p>
				) }
			</div>
			{ actions && (
				<div className="mindio-ai-page-header__actions">
					{ actions }
				</div>
			) }
		</header>
	);
}

export function Card( {
	title,
	description,
	actions,
	children,
	className = '',
	padded = true,
} ) {
	return (
		<section
			className={ `mindio-ai-card ${
				padded ? 'is-padded' : ''
			} ${ className }`.trim() }
		>
			{ ( title || actions ) && (
				<div className="mindio-ai-card__header">
					<div>
						{ title && (
							<h2 className="mindio-ai-card__title">{ title }</h2>
						) }
						{ description && (
							<p className="mindio-ai-card__description">
								{ description }
							</p>
						) }
					</div>
					{ actions && (
						<div className="mindio-ai-card__actions">
							{ actions }
						</div>
					) }
				</div>
			) }
			{ children }
		</section>
	);
}

export function Badge( { tone = 'neutral', children, icon } ) {
	return (
		<span className={ `mindio-ai-badge is-${ tone }` }>
			{ icon && <Icon name={ icon } size={ 12 } /> }
			{ children }
		</span>
	);
}

export function Banner( { tone = 'info', title, children, actions, icon } ) {
	const icons = {
		info: 'info',
		warning: 'triangle-alert',
		danger: 'circle-alert',
		success: 'circle-check',
	};
	return (
		<div
			className={ `mindio-ai-banner is-${ tone }` }
			role={ tone === 'danger' ? 'alert' : 'status' }
		>
			<Icon name={ icon || icons[ tone ] || 'info' } size={ 18 } />
			<div className="mindio-ai-banner__body">
				{ title && (
					<strong className="mindio-ai-banner__title">
						{ title }
					</strong>
				) }
				{ children && (
					<div className="mindio-ai-banner__text">{ children }</div>
				) }
			</div>
			{ actions && (
				<div className="mindio-ai-banner__actions">{ actions }</div>
			) }
		</div>
	);
}

export function EmptyState( { icon = 'sparkles', title, children, actions } ) {
	return (
		<div className="mindio-ai-empty">
			<span className="mindio-ai-empty__icon">
				<Icon name={ icon } size={ 22 } />
			</span>
			<h2 className="mindio-ai-empty__title">{ title }</h2>
			{ children && (
				<div className="mindio-ai-empty__text">{ children }</div>
			) }
			{ actions && (
				<div className="mindio-ai-empty__actions">{ actions }</div>
			) }
		</div>
	);
}

export function Loading( { label } ) {
	return (
		<div className="mindio-ai-loading" role="status">
			<Spinner />
			<span>{ label || __( 'Loading…', 'mindio-magic-ai' ) }</span>
		</div>
	);
}

export function Metric( { label, value, hint, icon, tone } ) {
	return (
		<div className={ `mindio-ai-metric ${ tone ? `is-${ tone }` : '' }` }>
			<div className="mindio-ai-metric__label">
				{ icon && <Icon name={ icon } size={ 14 } /> }
				{ label }
			</div>
			<div className="mindio-ai-metric__value">{ value }</div>
			{ hint && <div className="mindio-ai-metric__hint">{ hint }</div> }
		</div>
	);
}

export function Field( { label, help, children, id } ) {
	return (
		<div className="mindio-ai-field">
			{ label && (
				<label className="mindio-ai-field__label" htmlFor={ id }>
					{ label }
				</label>
			) }
			{ children }
			{ help && <p className="mindio-ai-field__help">{ help }</p> }
		</div>
	);
}
