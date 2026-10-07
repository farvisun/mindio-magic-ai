/**
 * Usage, cost, budgets, prices, and the kill switch.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	Button,
	Modal,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { api, errorMessage } from '../api';
import { useBoot } from '../App';
import Icon from '../components/Icon';
import {
	Badge,
	Banner,
	Card,
	Loading,
	Metric,
	PageHeader,
} from '../components/ui';
import {
	formatCompact,
	formatCost,
	formatDay,
	formatNumber,
	isRtl,
} from '../format';

function niceMax( value ) {
	if ( value <= 0 ) {
		return 1;
	}
	const power = Math.pow( 10, Math.floor( Math.log10( value ) ) );
	const steps = [ 1, 2, 2.5, 5, 10 ];
	return steps.map( ( s ) => s * power ).find( ( s ) => s >= value ) || value;
}

function DailyChart( { days } ) {
	const [ hover, setHover ] = useState( null );
	const width = 720;
	const height = 200;
	const pad = { top: 12, bottom: 24, side: 40 };
	const values = days.map( ( d ) => d.input_tokens + d.output_tokens );
	const max = niceMax( Math.max( ...values, 0 ) );
	const inner = width - pad.side * 2;
	const slot = inner / Math.max( 1, days.length );
	const bar = Math.max( 2, slot - 2 );
	const y = ( v ) =>
		pad.top + ( height - pad.top - pad.bottom ) * ( 1 - v / max );
	const x = ( i ) =>
		( isRtl ? width - pad.side - ( i + 1 ) * slot : pad.side + i * slot ) +
		1;

	return (
		<div className="mindio-ai-chart">
			<svg
				viewBox={ `0 0 ${ width } ${ height }` }
				role="img"
				aria-label={ __( 'Tokens per day', 'mindio-magic-ai' ) }
				onMouseLeave={ () => setHover( null ) }
			>
				{ [ 0, 0.5, 1 ].map( ( t ) => (
					<g key={ t }>
						<line
							className="mindio-ai-chart__grid"
							x1={ pad.side }
							x2={ width - pad.side }
							y1={ y( max * t ) }
							y2={ y( max * t ) }
						/>
						<text
							className="mindio-ai-chart__axis"
							x={ isRtl ? width - pad.side + 6 : pad.side - 6 }
							y={ y( max * t ) + 4 }
							textAnchor={ isRtl ? 'start' : 'end' }
						>
							{ formatCompact( max * t ) }
						</text>
					</g>
				) ) }
				{ days.map( ( day, i ) => {
					const value = values[ i ];
					const top = y( value );
					const h = Math.max( 0, height - pad.bottom - top );
					const r = Math.min( 4, bar / 2, h );
					const left = x( i );
					const path =
						h > 0
							? `M${ left },${ height - pad.bottom } V${
									top + r
							  } Q${ left },${ top } ${ left + r },${ top } H${
									left + bar - r
							  } Q${ left + bar },${ top } ${ left + bar },${
									top + r
							  } V${ height - pad.bottom } Z`
							: '';
					return (
						<g
							key={ day.date }
							onMouseEnter={ () => setHover( i ) }
							onFocus={ () => setHover( i ) }
							tabIndex={ 0 }
							aria-label={ `${ formatDay(
								day.date
							) }: ${ formatNumber( value ) }` }
						>
							<rect
								className="mindio-ai-chart__hit"
								x={ left - 1 }
								y={ pad.top }
								width={ slot }
								height={ height - pad.top - pad.bottom }
							/>
							{ path && (
								<path
									className={ `mindio-ai-chart__bar ${
										hover === i ? 'is-hover' : ''
									}` }
									d={ path }
								/>
							) }
							{ ( i === 0 ||
								i === days.length - 1 ||
								i === Math.floor( days.length / 2 ) ) && (
								<text
									className="mindio-ai-chart__axis"
									x={ left + bar / 2 }
									y={ height - 6 }
									textAnchor="middle"
								>
									{ formatDay( day.date ) }
								</text>
							) }
						</g>
					);
				} ) }
			</svg>
			{ hover !== null && days[ hover ] && (
				<div className="mindio-ai-chart__tooltip" role="status">
					<strong>{ formatDay( days[ hover ].date ) }</strong>
					<span>
						{ sprintf(
							/* translators: %s: token count. */ __(
								'%s tokens',
								'mindio-magic-ai'
							),
							formatNumber( values[ hover ] )
						) }
					</span>
					<span>
						{ sprintf(
							/* translators: %s: number of model calls. */ __(
								'%s calls',
								'mindio-magic-ai'
							),
							formatNumber( days[ hover ].calls )
						) }
					</span>
					<span>{ formatCost( days[ hover ].cost_micros ) }</span>
				</div>
			) }
			<details className="mindio-ai-chart__table">
				<summary>{ __( 'Show as table', 'mindio-magic-ai' ) }</summary>
				<div className="mindio-ai-table-wrap">
					<table className="mindio-ai-table">
						<thead>
							<tr>
								<th>{ __( 'Day', 'mindio-magic-ai' ) }</th>
								<th>{ __( 'Tokens', 'mindio-magic-ai' ) }</th>
								<th>{ __( 'Calls', 'mindio-magic-ai' ) }</th>
								<th>{ __( 'Cost', 'mindio-magic-ai' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ days.map( ( day, i ) => (
								<tr key={ day.date }>
									<td>{ formatDay( day.date ) }</td>
									<td>{ formatNumber( values[ i ] ) }</td>
									<td>{ formatNumber( day.calls ) }</td>
									<td>{ formatCost( day.cost_micros ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			</details>
		</div>
	);
}

function Prices( { onClose } ) {
	const [ rows, setRows ] = useState( null );
	const [ error, setError ] = useState( '' );
	useEffect( () => {
		api( 'prices' ).then( ( data ) =>
			setRows(
				Object.entries( data.prices || {} ).map( ( [ model, p ] ) => ( {
					model,
					...p,
				} ) )
			)
		);
	}, [] );
	const save = async () => {
		const prices = {};
		rows.filter( ( r ) => r.model.trim() ).forEach(
			( r ) =>
				( prices[ r.model.trim() ] = {
					input: r.input,
					output: r.output,
					cache_read: r.cache_read,
					cache_write: r.cache_write,
				} )
		);
		try {
			await api( 'prices', { method: 'PUT', data: { prices } } );
			onClose();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};
	return (
		<Modal
			title={ __( 'Model prices', 'mindio-magic-ai' ) }
			onRequestClose={ onClose }
			size="large"
		>
			<p className="mindio-ai-muted">
				{ __(
					'US dollars per million tokens, used to estimate cost and enforce cost budgets. Check your provider’s pricing page; models without a price are counted at zero cost.',
					'mindio-magic-ai'
				) }
			</p>
			{ ! rows ? (
				<Loading />
			) : (
				<div className="mindio-ai-table-wrap">
					<table className="mindio-ai-table mindio-ai-prices">
						<thead>
							<tr>
								<th>{ __( 'Model ID', 'mindio-magic-ai' ) }</th>
								<th>{ __( 'Input', 'mindio-magic-ai' ) }</th>
								<th>{ __( 'Output', 'mindio-magic-ai' ) }</th>
								<th>
									{ __( 'Cache read', 'mindio-magic-ai' ) }
								</th>
								<th>
									{ __( 'Cache write', 'mindio-magic-ai' ) }
								</th>
								<th />
							</tr>
						</thead>
						<tbody>
							{ rows.map( ( row, index ) => (
								<tr key={ index }>
									{ [
										'model',
										'input',
										'output',
										'cache_read',
										'cache_write',
									].map( ( field ) => (
										<td key={ field }>
											<input
												className="mindio-ai-input"
												dir="ltr"
												type={
													field === 'model'
														? 'text'
														: 'number'
												}
												step="0.01"
												min="0"
												aria-label={ field }
												value={ row[ field ] }
												onChange={ ( e ) =>
													setRows(
														rows.map( ( r, i ) =>
															i === index
																? {
																		...r,
																		[ field ]:
																			e
																				.target
																				.value,
																  }
																: r
														)
													)
												}
											/>
										</td>
									) ) }
									<td>
										<Button
											icon={
												<Icon name="x" size={ 14 } />
											}
											label={ __(
												'Remove',
												'mindio-magic-ai'
											) }
											onClick={ () =>
												setRows(
													rows.filter(
														( _, i ) => i !== index
													)
												)
											}
										/>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
			{ error && <Banner tone="danger">{ error }</Banner> }
			<div className="mindio-ai-modal-actions">
				<Button
					variant="secondary"
					onClick={ () =>
						setRows( [
							...( rows || [] ),
							{
								model: '',
								input: 0,
								output: 0,
								cache_read: 0,
								cache_write: 0,
							},
						] )
					}
				>
					{ __( 'Add model', 'mindio-magic-ai' ) }
				</Button>
				<span className="mindio-ai-spacer" />
				<Button variant="tertiary" onClick={ onClose }>
					{ __( 'Cancel', 'mindio-magic-ai' ) }
				</Button>
				<Button variant="primary" onClick={ save }>
					{ __( 'Save prices', 'mindio-magic-ai' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default function Usage() {
	const boot = useBoot();
	const [ days, setDays ] = useState( '30' );
	const [ data, setData ] = useState( null );
	const [ budget, setBudget ] = useState( {
		daily_tokens: '0',
		monthly_cost: '0',
	} );
	const [ saved, setSaved ] = useState( '' );
	const [ prices, setPrices ] = useState( false );
	const [ confirmPause, setConfirmPause ] = useState( false );
	const admin = !! boot.caps?.manage_options;

	const load = async () => {
		const result = await api( 'usage', { query: { days } } );
		setData( result );
		const b = result.budgets?.assistant || {};
		setBudget( {
			daily_tokens: String( b.daily_tokens || 0 ),
			monthly_cost: String( b.monthly_cost || 0 ),
		} );
	};

	useEffect( () => {
		load();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ days ] );

	const saveBudget = async () => {
		await api( 'budgets', {
			method: 'PUT',
			data: {
				budgets: {
					assistant: {
						daily_tokens: parseInt( budget.daily_tokens, 10 ) || 0,
						monthly_cost: parseFloat( budget.monthly_cost ) || 0,
					},
				},
			},
		} );
		setSaved( __( 'Budget saved.', 'mindio-magic-ai' ) );
		load();
	};

	const toggleKill = async ( active ) => {
		await api( 'killswitch', { method: 'POST', data: { active } } );
		setConfirmPause( false );
		boot.refresh();
		load();
	};

	if ( ! data ) {
		return (
			<div className="mindio-ai-page">
				<Loading />
			</div>
		);
	}

	const used = data.consumption?.assistant || {};
	const limits = data.budgets?.assistant || {};
	const tokenRatio = limits.daily_tokens
		? used.tokens_today / limits.daily_tokens
		: 0;
	const costRatio = limits.monthly_cost
		? used.cost_month / ( limits.monthly_cost * 1000000 )
		: 0;
	const unpriced = data.breakdown.some( ( row ) => ! row.priced );

	return (
		<div className="mindio-ai-page">
			<PageHeader
				icon="chart-column"
				title={ __( 'Usage & budgets', 'mindio-magic-ai' ) }
				description={ __(
					'Tokens and estimated cost of every AI call, with limits that stop spending before it happens.',
					'mindio-magic-ai'
				) }
				actions={
					<SelectControl
						label={ __( 'Period', 'mindio-magic-ai' ) }
						hideLabelFromVision
						value={ days }
						options={ [
							{
								value: '7',
								label: __( 'Last 7 days', 'mindio-magic-ai' ),
							},
							{
								value: '30',
								label: __( 'Last 30 days', 'mindio-magic-ai' ),
							},
							{
								value: '90',
								label: __( 'Last 90 days', 'mindio-magic-ai' ),
							},
						] }
						onChange={ setDays }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				}
			/>

			{ data.kill_switch && (
				<Banner
					tone="warning"
					title={ __( 'AI is paused', 'mindio-magic-ai' ) }
					actions={
						admin &&
						! boot.kill_switch?.locked && (
							<Button
								variant="primary"
								onClick={ () => toggleKill( false ) }
							>
								{ __( 'Resume AI', 'mindio-magic-ai' ) }
							</Button>
						)
					}
				>
					{ boot.kill_switch?.locked
						? __(
								'Paused by the MINDIO_MAGIC_AI_DISABLED constant in wp-config.php.',
								'mindio-magic-ai'
						  )
						: __(
								'No AI requests are sent until an administrator resumes AI.',
								'mindio-magic-ai'
						  ) }
				</Banner>
			) }

			<div className="mindio-ai-metrics">
				<Metric
					icon="activity"
					label={ __( 'Tokens today', 'mindio-magic-ai' ) }
					value={ formatCompact(
						( data.today?.input_tokens || 0 ) +
							( data.today?.output_tokens || 0 )
					) }
					hint={
						limits.daily_tokens
							? sprintf(
									/* translators: %s: percentage. */ __(
										'%s of daily budget',
										'mindio-magic-ai'
									),
									formatNumber( tokenRatio, {
										style: 'percent',
									} )
							  )
							: __( 'No daily limit', 'mindio-magic-ai' )
					}
					tone={ tokenRatio >= 0.8 ? 'warning' : undefined }
				/>
				<Metric
					icon="coins"
					label={ __( 'Cost this month', 'mindio-magic-ai' ) }
					value={ formatCost( data.month.cost_micros ) }
					hint={
						limits.monthly_cost
							? sprintf(
									/* translators: %s: percentage. */ __(
										'%s of monthly budget',
										'mindio-magic-ai'
									),
									formatNumber( costRatio, {
										style: 'percent',
									} )
							  )
							: __(
									'Estimated from model prices',
									'mindio-magic-ai'
							  )
					}
					tone={ costRatio >= 0.8 ? 'warning' : undefined }
				/>
				<Metric
					icon="message-square"
					label={ __( 'Model calls this month', 'mindio-magic-ai' ) }
					value={ formatNumber( data.month.calls ) }
				/>
				<Metric
					icon="gauge"
					label={ __( 'Tokens this month', 'mindio-magic-ai' ) }
					value={ formatCompact( data.month.tokens ) }
				/>
			</div>

			<Card
				title={ __( 'Tokens per day', 'mindio-magic-ai' ) }
				description={ __(
					'Input, cached input, and output tokens across all features.',
					'mindio-magic-ai'
				) }
			>
				<DailyChart days={ data.days } />
			</Card>

			<div className="mindio-ai-columns">
				<Card
					title={ __( 'By model', 'mindio-magic-ai' ) }
					padded={ false }
					actions={
						admin && (
							<Button
								variant="tertiary"
								onClick={ () => setPrices( true ) }
							>
								{ __( 'Edit prices', 'mindio-magic-ai' ) }
							</Button>
						)
					}
				>
					{ data.breakdown.length === 0 ? (
						<p className="mindio-ai-muted mindio-ai-pad">
							{ __(
								'No usage in this period.',
								'mindio-magic-ai'
							) }
						</p>
					) : (
						<div className="mindio-ai-table-wrap">
							<table className="mindio-ai-table">
								<thead>
									<tr>
										<th>
											{ __( 'Model', 'mindio-magic-ai' ) }
										</th>
										<th>
											{ __( 'Calls', 'mindio-magic-ai' ) }
										</th>
										<th>
											{ __( 'Input', 'mindio-magic-ai' ) }
										</th>
										<th>
											{ __(
												'Output',
												'mindio-magic-ai'
											) }
										</th>
										<th>
											{ __( 'Cost', 'mindio-magic-ai' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ data.breakdown.map( ( row ) => (
										<tr
											key={ `${ row.provider }-${ row.model }-${ row.feature }` }
										>
											<td
												dir="ltr"
												className="mindio-ai-table__model"
											>
												{ row.model }
												{ row.estimated && (
													<Badge tone="neutral">
														{ __(
															'estimated',
															'mindio-magic-ai'
														) }
													</Badge>
												) }
											</td>
											<td>
												{ formatNumber( row.calls ) }
											</td>
											<td>
												{ formatCompact(
													row.input_tokens +
														row.cache_read_tokens +
														row.cache_write_tokens
												) }
											</td>
											<td>
												{ formatCompact(
													row.output_tokens
												) }
											</td>
											<td>
												{ row.priced ? (
													formatCost(
														row.cost_micros
													)
												) : (
													<span className="mindio-ai-muted">
														{ __(
															'no price',
															'mindio-magic-ai'
														) }
													</span>
												) }
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					) }
					{ unpriced && (
						<p className="mindio-ai-muted mindio-ai-pad">
							{ __(
								'Some models have no price, so their cost is not counted toward cost budgets.',
								'mindio-magic-ai'
							) }
						</p>
					) }
				</Card>

				<Card
					title={ __( 'Assistant budget', 'mindio-magic-ai' ) }
					description={ __(
						'Limits are reserved before each call, so concurrent requests cannot overspend. 0 means no limit. You get an email at 80%.',
						'mindio-magic-ai'
					) }
				>
					<div className="mindio-ai-form">
						<TextControl
							type="number"
							min={ 0 }
							label={ __(
								'Daily token limit',
								'mindio-magic-ai'
							) }
							value={ budget.daily_tokens }
							onChange={ ( v ) =>
								setBudget( { ...budget, daily_tokens: v } )
							}
							disabled={ ! admin }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						<TextControl
							type="number"
							min={ 0 }
							step="0.01"
							label={ __(
								'Monthly cost limit (USD)',
								'mindio-magic-ai'
							) }
							value={ budget.monthly_cost }
							onChange={ ( v ) =>
								setBudget( { ...budget, monthly_cost: v } )
							}
							disabled={ ! admin }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
						{ admin && (
							<div className="mindio-ai-form__actions">
								<Button
									variant="primary"
									onClick={ saveBudget }
								>
									{ __( 'Save budget', 'mindio-magic-ai' ) }
								</Button>
								{ saved && (
									<span
										className="mindio-ai-saved"
										role="status"
									>
										{ saved }
									</span>
								) }
							</div>
						) }
					</div>
					{ admin &&
						! data.kill_switch &&
						! boot.kill_switch?.locked && (
							<div className="mindio-ai-danger-zone">
								<div>
									<strong>
										{ __(
											'Pause all AI',
											'mindio-magic-ai'
										) }
									</strong>
									<p className="mindio-ai-muted">
										{ __(
											'Stops every AI request on this site immediately, for all users.',
											'mindio-magic-ai'
										) }
									</p>
								</div>
								<Button
									variant="secondary"
									isDestructive
									onClick={ () => setConfirmPause( true ) }
								>
									<Icon name="power" size={ 14 } />
									{ __( 'Pause AI', 'mindio-magic-ai' ) }
								</Button>
							</div>
						) }
				</Card>
			</div>

			{ prices && (
				<Prices
					onClose={ () => {
						setPrices( false );
						load();
					} }
				/>
			) }
			{ confirmPause && (
				<Modal
					title={ __( 'Pause all AI?', 'mindio-magic-ai' ) }
					onRequestClose={ () => setConfirmPause( false ) }
					size="small"
				>
					<p>
						{ __(
							'Running answers stop at their next step and no new requests are sent until you resume AI.',
							'mindio-magic-ai'
						) }
					</p>
					<div className="mindio-ai-modal-actions">
						<Button
							variant="tertiary"
							onClick={ () => setConfirmPause( false ) }
						>
							{ __( 'Cancel', 'mindio-magic-ai' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ () => toggleKill( true ) }
						>
							{ __( 'Pause AI', 'mindio-magic-ai' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
