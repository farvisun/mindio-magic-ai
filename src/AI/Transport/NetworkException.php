<?php
/**
 * PSR-18 network failure.
 *
 * @package MindioMagicAI
 */

namespace MindioMagicAI\AI\Transport;

use MindioMagicAI\Vendor\Psr\Http\Client\NetworkExceptionInterface;
use MindioMagicAI\Vendor\Psr\Http\Message\RequestInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NetworkException extends \RuntimeException implements NetworkExceptionInterface {
	public function __construct( private RequestInterface $request, string $message ) {
		parent::__construct( $message );
	}

	public function getRequest(): RequestInterface {
		return $this->request;
	}
}
