<?php
/**
 * Order form for a logged-in customer.
 *
 * @var array  $quote
 * @var string $sel
 * @var string $calculator
 * @var string $cemetery
 * @var string $kind     human | pet
 * @var array  $cards    Customer cards of this kind.
 * @var array  $contact  {name, email, phone}
 * @var string $terms
 * @var array  $notice
 * @var array  $old      Values from a failed submit.
 */
defined( 'ABSPATH' ) || exit;

use Kipora\Memorials;
use Kipora\Montonio;

$is_pet   = Memorials::KIND_PET === $kind;
$old_card = isset( $old['card'] ) ? (int) $old['card'] : ( $cards ? (int) $cards[0]['id'] : 0 );
$value    = static fn( string $key, string $fallback = '' ): string => (string) ( $old[ $key ] ?? $fallback );
?>
<div class="kp-checkout">
	<aside class="kp-checkout__aside">
		<h2 class="kp-checkout__heading"><?php esc_html_e( 'Your order', 'kipora' ); ?></h2>
		<?php if ( $cemetery ) : ?>
			<p class="kp-checkout__place"><?php echo esc_html( $cemetery ); ?></p>
		<?php endif; ?>
		<?php
		$lines = $quote['lines'];
		$total = $quote['total'];
		include __DIR__ . '/parts/summary.php';
		?>
		<a class="kp-link" href="<?php echo esc_url( $calculator ); ?>"><?php esc_html_e( 'Change the order', 'kipora' ); ?></a>
	</aside>

	<form class="kp-checkout__main kp-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-kp-checkout>
		<?php include __DIR__ . '/parts/notice.php'; ?>
		<input type="hidden" name="action" value="kipora_checkout">
		<input type="hidden" name="sel" value="<?php echo esc_attr( $sel ); ?>">
		<input type="hidden" name="lang" value="<?php echo esc_attr( \Kipora\Lang::current() ); ?>">
		<?php wp_nonce_field( 'kipora_checkout' ); ?>

		<fieldset class="kp-form__group">
			<legend class="kp-form__legend"><?php echo esc_html( $is_pet ? __( 'For whom', 'kipora' ) : __( 'Whose resting place', 'kipora' ) ); ?></legend>
			<p class="kp-form__hint"><?php esc_html_e( 'The order is saved to a memorial card. Photo reports and documents stay there too.', 'kipora' ); ?></p>

			<?php if ( $cards ) : ?>
				<div class="kp-choices">
					<?php foreach ( $cards as $card ) : ?>
						<label class="kp-choice">
							<input class="kp-choice__input" type="radio" name="card" value="<?php echo esc_attr( $card['id'] ); ?>" <?php checked( $old_card, $card['id'] ); ?> data-kp-card>
							<span class="kp-choice__body">
								<span class="kp-choice__title"><?php echo esc_html( $card['name'] ); ?></span>
								<span class="kp-choice__meta"><?php echo esc_html( implode( ' · ', array_filter( [ Memorials::years( $card ), Memorials::location( $card ) ] ) ) ); ?></span>
							</span>
						</label>
					<?php endforeach; ?>
					<label class="kp-choice">
						<input class="kp-choice__input" type="radio" name="card" value="0" <?php checked( $old_card, 0 ); ?> data-kp-card>
						<span class="kp-choice__body">
							<span class="kp-choice__title"><?php esc_html_e( 'New memorial card', 'kipora' ); ?></span>
						</span>
					</label>
				</div>
			<?php else : ?>
				<input type="hidden" name="card" value="0">
			<?php endif; ?>

			<div class="kp-form__new" data-kp-new-card <?php echo $cards && $old_card ? 'hidden' : ''; ?>>
				<div class="kp-field">
					<label class="kp-field__label" for="kp-name"><?php echo esc_html( $is_pet ? __( 'Pet name', 'kipora' ) : __( 'Full name of the deceased', 'kipora' ) ); ?></label>
					<input class="kp-field__input" id="kp-name" name="name" value="<?php echo esc_attr( $value( 'name' ) ); ?>" maxlength="120" autocomplete="off">
				</div>
				<div class="kp-form__row">
					<div class="kp-field">
						<label class="kp-field__label" for="kp-born"><?php esc_html_e( 'Born', 'kipora' ); ?></label>
						<input class="kp-field__input" id="kp-born" name="born" value="<?php echo esc_attr( $value( 'born' ) ); ?>" maxlength="20" placeholder="1938">
					</div>
					<div class="kp-field">
						<label class="kp-field__label" for="kp-died"><?php esc_html_e( 'Died', 'kipora' ); ?></label>
						<input class="kp-field__input" id="kp-died" name="died" value="<?php echo esc_attr( $value( 'died' ) ); ?>" maxlength="20" placeholder="2019">
					</div>
				</div>
				<?php if ( ! $is_pet ) : ?>
					<div class="kp-form__row">
						<div class="kp-field">
							<label class="kp-field__label" for="kp-sector"><?php esc_html_e( 'Sector', 'kipora' ); ?></label>
							<input class="kp-field__input" id="kp-sector" name="sector" value="<?php echo esc_attr( $value( 'sector' ) ); ?>" maxlength="40">
						</div>
						<div class="kp-field">
							<label class="kp-field__label" for="kp-plot"><?php esc_html_e( 'Plot number', 'kipora' ); ?></label>
							<input class="kp-field__input" id="kp-plot" name="plot" value="<?php echo esc_attr( $value( 'plot' ) ); ?>" maxlength="40">
						</div>
					</div>
				<?php endif; ?>
			</div>
		</fieldset>

		<fieldset class="kp-form__group">
			<legend class="kp-form__legend"><?php esc_html_e( 'Contact details', 'kipora' ); ?></legend>
			<div class="kp-form__row">
				<div class="kp-field">
					<label class="kp-field__label" for="kp-email"><?php esc_html_e( 'Email', 'kipora' ); ?></label>
					<input class="kp-field__input" id="kp-email" name="email" type="email" autocomplete="email" required value="<?php echo esc_attr( $value( 'email', $contact['email'] ) ); ?>">
				</div>
				<div class="kp-field">
					<label class="kp-field__label" for="kp-phone"><?php esc_html_e( 'Phone', 'kipora' ); ?></label>
					<input class="kp-field__input" id="kp-phone" name="phone" type="tel" autocomplete="tel" value="<?php echo esc_attr( $value( 'phone', $contact['phone'] ) ); ?>">
				</div>
			</div>
			<div class="kp-field">
				<label class="kp-field__label" for="kp-comment"><?php esc_html_e( 'Comment for the team', 'kipora' ); ?> <span class="kp-field__optional"><?php esc_html_e( 'optional', 'kipora' ); ?></span></label>
				<textarea class="kp-field__input kp-field__input--area" id="kp-comment" name="comment" rows="3" maxlength="1000"><?php echo esc_textarea( $value( 'comment' ) ); ?></textarea>
			</div>
		</fieldset>

		<fieldset class="kp-form__group">
			<legend class="kp-form__legend"><?php esc_html_e( 'Payment', 'kipora' ); ?></legend>
			<div class="kp-choices kp-choices--inline">
				<label class="kp-choice">
					<input class="kp-choice__input" type="radio" name="method" value="<?php echo esc_attr( Montonio::METHOD_BANK ); ?>" <?php checked( $value( 'method', Montonio::METHOD_BANK ), Montonio::METHOD_BANK ); ?>>
					<span class="kp-choice__body"><span class="kp-choice__title"><?php esc_html_e( 'Bank link', 'kipora' ); ?></span></span>
				</label>
				<label class="kp-choice">
					<input class="kp-choice__input" type="radio" name="method" value="<?php echo esc_attr( Montonio::METHOD_CARD ); ?>" <?php checked( $value( 'method' ), Montonio::METHOD_CARD ); ?>>
					<span class="kp-choice__body"><span class="kp-choice__title"><?php esc_html_e( 'Card', 'kipora' ); ?></span></span>
				</label>
			</div>
			<label class="kp-check">
				<input class="kp-check__input" type="checkbox" name="terms" value="1" required>
				<span class="kp-check__label">
					<?php
					printf(
						/* translators: %s: link to terms of sale */
						esc_html__( 'I accept the %s', 'kipora' ),
						'<a href="' . esc_url( $terms ) . '" target="_blank" rel="noopener">' . esc_html__( 'terms of sale', 'kipora' ) . '</a>'
					);
					?>
				</span>
			</label>
		</fieldset>

		<button type="submit" class="kp-button kp-button--primary kp-button--wide">
			<?php
			/* translators: %s: amount */
			printf( esc_html__( 'Pay %s', 'kipora' ), esc_html( $quote['total_formatted'] ) );
			?>
		</button>
		<p class="kp-form__hint"><?php esc_html_e( 'Payment is processed by Montonio. After payment you return here.', 'kipora' ); ?></p>
	</form>
</div>
