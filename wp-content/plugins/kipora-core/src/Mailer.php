<?php
/**
 * Emails on order status changes. Plain text, in the order's language.
 */

namespace Kipora;

final class Mailer {

	public static function register(): void {
		add_action( 'kipora_order_status', [ self::class, 'on_status' ], 10, 3 );
	}

	public static function on_status( int $order_id, string $status, string $previous ): void {
		$order = Orders::get( $order_id );
		if ( ! $order ) {
			return;
		}
		if ( Orders::PAID === $status && Orders::AWAITING === $previous ) {
			self::to_client( $order, 'paid' );
			self::to_team( $order );
		} elseif ( Orders::DONE === $status ) {
			self::to_client( $order, 'done' );
		}
	}

	private static function to_client( array $order, string $event ): void {
		$email = (string) ( $order['contact']['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			return;
		}
		switch_to_locale( Lang::locale( $order['lang'] ) );

		$service = Settings::get( 'service_name' );
		$account = add_query_arg( 'view', 'orders', Lang::page_url( 'account', $order['lang'] ) );
		$lines   = self::lines( $order );

		if ( 'paid' === $event ) {
			/* translators: 1: service name, 2: order number */
			$subject = sprintf( __( '%1$s: order %2$s is paid', 'kipora' ), $service, $order['reference'] );
			$body    = sprintf(
				/* translators: 1: name, 2: order number, 3: order lines, 4: account URL */
				__( "Hello, %1\$s.\n\nThank you, we have received the payment for order %2\$s.\n\n%3\$s\n\nWhen the work is done, the photo report will appear in your account:\n%4\$s", 'kipora' ),
				$order['contact']['name'] ?? '',
				$order['reference'],
				$lines,
				$account
			);
		} else {
			/* translators: 1: service name, 2: order number */
			$subject = sprintf( __( '%1$s: order %2$s is done', 'kipora' ), $service, $order['reference'] );
			$body    = sprintf(
				/* translators: 1: name, 2: order number, 3: account URL */
				__( "Hello, %1\$s.\n\nThe work for order %2\$s is done. The photo report is in your account:\n%3\$s", 'kipora' ),
				$order['contact']['name'] ?? '',
				$order['reference'],
				$account
			);
		}

		wp_mail( $email, $subject, $body . "\n\n" . $service );
		restore_previous_locale();
	}

	private static function to_team( array $order ): void {
		$to = Settings::get( 'notify_email' );
		if ( ! is_email( $to ) ) {
			return;
		}
		switch_to_locale( get_locale() );
		$card = Memorials::get( $order['memorial_id'] );
		$body = implode(
			"\n",
			array_filter(
				[
					self::lines( $order ),
					'',
					$card ? __( 'Memorial card', 'kipora' ) . ': ' . $card['name'] . ( Memorials::location( $card ) ? ', ' . Memorials::location( $card ) : '' ) : '',
					__( 'Customer', 'kipora' ) . ': ' . ( $order['contact']['name'] ?? '' ) . ', ' . ( $order['contact']['email'] ?? '' ) . ', ' . ( $order['contact']['phone'] ?? '' ),
					$order['comment'] ? __( 'Comment', 'kipora' ) . ': ' . $order['comment'] : '',
					'',
					admin_url( 'post.php?post=' . $order['id'] . '&action=edit' ),
				],
				static fn( $line ) => null !== $line
			)
		);
		/* translators: 1: order number, 2: amount */
		wp_mail( $to, sprintf( __( 'New paid order %1$s, %2$s', 'kipora' ), $order['reference'], Pricing::format( $order['total'] ) ), $body );
		restore_previous_locale();
	}

	private static function lines( array $order ): string {
		$out = [];
		foreach ( $order['lines'] as $line ) {
			$out[] = '— ' . $line['label'] . ': ' . Pricing::format( (int) $line['amount'] );
		}
		$out[] = __( 'Total', 'kipora' ) . ': ' . Pricing::format( $order['total'] );
		return implode( "\n", $out );
	}
}
