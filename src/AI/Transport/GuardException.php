<?php
/**
 * An outbound request blocked by the URL guard. Not a PSR-18 exception, so the SDK does not retry it.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GuardException extends \RuntimeException {
	public function __construct( public readonly \WP_Error $error ) {
		parent::__construct( $error->get_error_message() );
	}
}
