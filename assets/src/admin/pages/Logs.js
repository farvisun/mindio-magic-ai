/**
 * Activity log: every configuration change, AI run, and applied change.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { Button, SelectControl } from '@wordpress/components';
import { api } from '../api';
import Icon from '../components/Icon';
import { Badge, Card, Loading, PageHeader } from '../components/ui';
import { formatDate, formatNumber } from '../format';

function eventLabel( event ) {
	const labels = {
		'connection.create': __( 'Connection added', 'mindio-magic-ai' ),
		'connection.update': __( 'Connection changed', 'mindio-magic-ai' ),
		'connection.delete': __( 'Connection deleted', 'mindio-magic-ai' ),
		'connection.test': __( 'Connection tested', 'mindio-magic-ai' ),
		'settings.update': __( 'Settings changed', 'mindio-magic-ai' ),
		'access.update': __( 'Access changed', 'mindio-magic-ai' ),
		'budgets.update': __( 'Budget changed', 'mindio-magic-ai' ),
		'prices.update': __( 'Prices changed', 'mindio-magic-ai' ),
		'budget.alert': __( 'Budget warning sent', 'mindio-magic-ai' ),
		'killswitch.on': __( 'AI paused', 'mindio-magic-ai' ),
		'killswitch.off': __( 'AI resumed', 'mindio-magic-ai' ),
		'keys.acknowledge': __( 'Keys re-entered', 'mindio-magic-ai' ),
		'proposal.apply': __( 'Change applied', 'mindio-magic-ai' ),
		'proposal.reject': __( 'Change rejected', 'mindio-magic-ai' ),
		'proposal.undo': __( 'Change undone', 'mindio-magic-ai' ),
		'run.completed': __( 'Assistant answered', 'mindio-magic-ai' ),
		'run.stopped': __( 'Assistant stopped', 'mindio-magic-ai' ),
		'run.failed': __( 'Assistant failed', 'mindio-magic-ai' ),
	};
	return labels[ event ] || event;
}

function details( item ) {
	const d = item.details || {};
	const parts = [];
	if ( d.tool ) {
		parts.push( d.tool );
	}
	if ( d.model ) {
		parts.push( d.model );
	}
	if ( d.tokens ) {
		parts.push(
			sprintf(
				/* translators: %s: token count. */ __(
					'%s tokens',
					'mindio-magic-ai'
				),
				formatNumber( d.tokens )
			)
		);
	}
	if ( d.host ) {
		parts.push( d.host );
	}
	if ( d.error ) {
		parts.push( d.error );
	}
	if ( d.tainted ) {
		parts.push( __( 'read untrusted content', 'mindio-magic-ai' ) );
	}
	if ( d.key_cleared ) {
		parts.push(
			__( 'key removed after address change', 'mindio-magic-ai' )
		);
	}
	if ( Array.isArray( d.fields ) && d.fields.length ) {
		parts.push( d.fields.join( ', ' ) );
	}
	return parts.join( ' · ' );
}

function csv( items ) {
	const cell = ( value ) => {
		let text = String( value ?? '' );
		// Neutralize spreadsheet formulas.
		if ( /^[=+\-@\t\r]/.test( text ) ) {
			text = "'" + text;
		}
		return `"${ text.replace( /"/g, '""' ) }"`;
	};
	const rows = [
		[ 'time', 'user', 'event', 'object', 'success', 'details', 'ip' ],
	];
	items.forEach( ( item ) =>
		rows.push( [
			item.created_at,
			item.user,
			item.event,
			`${ item.object_type }:${ item.object_id }`,
			item.success ? 'yes' : 'no',
			JSON.stringify( item.details ),
			item.ip,
		] )
	);
	return rows.map( ( row ) => row.map( cell ).join( ',' ) ).join( '\r\n' );
}

export default function Logs() {
	const [ filter, setFilter ] = useState( '' );
	const [ outcome, setOutcome ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ data, setData ] = useState( null );
	const perPage = 50;

	useEffect( () => {
		setData( null );
		api( 'audit', {
			query: { event: filter, success: outcome, page, per_page: perPage },
		} ).then( setData );
	}, [ filter, outcome, page ] );

	const exportCsv = async () => {
		const all = await api( 'audit', {
			query: { event: filter, success: outcome, page: 1, per_page: 500 },
		} );
		const blob = new window.Blob( [ '﻿' + csv( all.items ) ], {
			type: 'text/csv;charset=utf-8',
		} );
		const link = document.createElement( 'a' );
		link.href = URL.createObjectURL( blob );
		link.download = `mindio-ai-activity-${ new Date()
			.toISOString()
			.slice( 0, 10 ) }.csv`;
		link.click();
		URL.revokeObjectURL( link.href );
	};

	const pages = data ? Math.max( 1, Math.ceil( data.total / perPage ) ) : 1;

	return (
		<div className="mindio-ai-page">
			<PageHeader
				icon="activity"
				title={ __( 'Activity log', 'mindio-magic-ai' ) }
				description={ __(
					'Who changed what: connections, settings, assistant runs, and every change applied or undone. Messages and keys are never logged.',
					'mindio-magic-ai'
				) }
				actions={
					<Button
						variant="secondary"
						onClick={ exportCsv }
						disabled={ ! data?.total }
					>
						<Icon name="download" size={ 14 } />
						{ __( 'Export CSV', 'mindio-magic-ai' ) }
					</Button>
				}
			/>
			<Card padded={ false }>
				<div className="mindio-ai-toolbar">
					<SelectControl
						label={ __( 'Event', 'mindio-magic-ai' ) }
						value={ filter }
						options={ [
							{
								value: '',
								label: __( 'All events', 'mindio-magic-ai' ),
							},
							{
								value: 'proposal.',
								label: __( 'Changes', 'mindio-magic-ai' ),
							},
							{
								value: 'run.',
								label: __(
									'Assistant runs',
									'mindio-magic-ai'
								),
							},
							{
								value: 'connection.',
								label: __( 'Connections', 'mindio-magic-ai' ),
							},
							{
								value: 'settings.',
								label: __( 'Settings', 'mindio-magic-ai' ),
							},
							{
								value: 'killswitch.',
								label: __(
									'Pause and resume',
									'mindio-magic-ai'
								),
							},
						] }
						onChange={ ( v ) => {
							setFilter( v );
							setPage( 1 );
						} }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Outcome', 'mindio-magic-ai' ) }
						value={ outcome }
						options={ [
							{
								value: '',
								label: __( 'Any outcome', 'mindio-magic-ai' ),
							},
							{
								value: '1',
								label: __( 'Succeeded', 'mindio-magic-ai' ),
							},
							{
								value: '0',
								label: __( 'Failed', 'mindio-magic-ai' ),
							},
						] }
						onChange={ ( v ) => {
							setOutcome( v );
							setPage( 1 );
						} }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					{ data && (
						<span className="mindio-ai-toolbar__count">
							{ sprintf(
								/* translators: %s: number of entries. */ __(
									'%s entries',
									'mindio-magic-ai'
								),
								formatNumber( data.total )
							) }
						</span>
					) }
				</div>
				{ ! data && <Loading /> }
				{ data && data.items.length === 0 && (
					<p className="mindio-ai-muted mindio-ai-pad">
						{ __(
							'No activity matches these filters.',
							'mindio-magic-ai'
						) }
					</p>
				) }
				{ data && data.items.length > 0 && (
					<div className="mindio-ai-table-wrap">
						<table className="mindio-ai-table mindio-ai-log">
							<thead>
								<tr>
									<th>{ __( 'When', 'mindio-magic-ai' ) }</th>
									<th>
										{ __( 'Event', 'mindio-magic-ai' ) }
									</th>
									<th>{ __( 'User', 'mindio-magic-ai' ) }</th>
									<th>
										{ __( 'Details', 'mindio-magic-ai' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ data.items.map( ( item ) => (
									<tr key={ item.id }>
										<td className="mindio-ai-log__time">
											{ formatDate( item.created_at ) }
										</td>
										<td>
											<span className="mindio-ai-log__event">
												{ item.success ? (
													<Icon
														name="circle-check"
														size={ 14 }
														className="is-success"
													/>
												) : (
													<Icon
														name="circle-alert"
														size={ 14 }
														className="is-danger"
													/>
												) }
												{ eventLabel( item.event ) }
											</span>
										</td>
										<td>
											{ item.user || (
												<span className="mindio-ai-muted">
													{ __(
														'System',
														'mindio-magic-ai'
													) }
												</span>
											) }
										</td>
										<td
											className="mindio-ai-log__details"
											dir="auto"
										>
											{ details( item ) }
											{ item.success === false && (
												<Badge tone="danger">
													{ __(
														'failed',
														'mindio-magic-ai'
													) }
												</Badge>
											) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
				) }
				{ pages > 1 && (
					<div className="mindio-ai-pagination">
						<Button
							variant="tertiary"
							disabled={ page <= 1 }
							onClick={ () => setPage( page - 1 ) }
						>
							{ __( 'Previous', 'mindio-magic-ai' ) }
						</Button>
						<span>
							{ sprintf(
								/* translators: 1: current page, 2: total pages. */ __(
									'Page %1$s of %2$s',
									'mindio-magic-ai'
								),
								formatNumber( page ),
								formatNumber( pages )
							) }
						</span>
						<Button
							variant="tertiary"
							disabled={ page >= pages }
							onClick={ () => setPage( page + 1 ) }
						>
							{ __( 'Next', 'mindio-magic-ai' ) }
						</Button>
					</div>
				) }
			</Card>
		</div>
	);
}
