/**
 * Locale-aware formatting. Persian locales get Persian digits and the Solar Hijri calendar.
 */
import { __, sprintf, _n } from '@wordpress/i18n';

const config = window.mindioAI || {};
const wpLocale = config.locale || document.documentElement.lang || 'en-US';

export const locale = wpLocale.replace( '_', '-' );
export const isPersian = locale.toLowerCase().startsWith( 'fa' );
export const isRtl = !! config.isRtl || document.documentElement.dir === 'rtl';

const dateLocale = isPersian ? 'fa-IR-u-ca-persian' : locale;

export function formatNumber( value, options = {} ) {
	try {
		return new Intl.NumberFormat( locale, options ).format( value || 0 );
	} catch ( e ) {
		return String( value || 0 );
	}
}

export function formatCompact( value ) {
	return formatNumber( value, {
		notation: 'compact',
		maximumFractionDigits: 1,
	} );
}

/**
 * Format micro-dollars as US dollars.
 *
 * @param {number} micros One millionth of a dollar.
 * @return {string} Amount.
 */
export function formatCost( micros ) {
	const dollars = ( micros || 0 ) / 1000000;
	const digits = dollars > 0 && dollars < 0.01 ? 4 : 2;
	return formatNumber( dollars, {
		style: 'currency',
		currency: 'USD',
		minimumFractionDigits: digits,
		maximumFractionDigits: digits,
	} );
}

/**
 * The REST API returns database times as GMT "YYYY-MM-DD HH:MM:SS" without a zone.
 *
 * @param {string} value GMT database time or an ISO 8601 string.
 * @return {Date} Date.
 */
export function toDate( value ) {
	const text = String( value );
	const zoneless = /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/.test( text );
	return new Date( zoneless ? text.replace( ' ', 'T' ) + 'Z' : text );
}

export function formatDate(
	iso,
	options = { dateStyle: 'medium', timeStyle: 'short' }
) {
	if ( ! iso ) {
		return '';
	}
	try {
		return new Intl.DateTimeFormat( dateLocale, options ).format(
			toDate( iso )
		);
	} catch ( e ) {
		return iso;
	}
}

export function formatDay( isoDate ) {
	return formatDate( `${ isoDate }T12:00:00Z`, {
		month: 'short',
		day: 'numeric',
		timeZone: 'UTC',
	} );
}

export function relativeTime( iso ) {
	if ( ! iso ) {
		return '';
	}
	const seconds = Math.round(
		( toDate( iso ).getTime() - Date.now() ) / 1000
	);
	const abs = Math.abs( seconds );
	try {
		const rtf = new Intl.RelativeTimeFormat( locale, { numeric: 'auto' } );
		if ( abs < 45 ) {
			return __( 'just now', 'mindio-magic-ai' );
		}
		if ( abs < 3600 ) {
			return rtf.format( Math.round( seconds / 60 ), 'minute' );
		}
		if ( abs < 86400 ) {
			return rtf.format( Math.round( seconds / 3600 ), 'hour' );
		}
		if ( abs < 86400 * 7 ) {
			return rtf.format( Math.round( seconds / 86400 ), 'day' );
		}
	} catch ( e ) {}
	return formatDate( iso, { dateStyle: 'medium' } );
}

export function formatTokens( count ) {
	return sprintf(
		/* translators: %s: number of tokens. */
		_n( '%s token', '%s tokens', count || 0, 'mindio-magic-ai' ),
		formatCompact( count || 0 )
	);
}
