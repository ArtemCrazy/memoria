<?php

namespace Kipora\Auth;

/**
 * Login failure with a stable code. The code maps to a message the person
 * sees; the technical detail goes to the log only.
 */
final class AuthException extends \RuntimeException {

	public function __construct( public readonly string $reason, string $detail = '' ) {
		parent::__construct( $detail ?: $reason );
	}
}
