<?php
/**
 * Contact form: message with up to three files (photos of the plot, documents).
 *
 * @var array $notice
 * @var array $old
 */
defined( 'ABSPATH' ) || exit;

$value = static fn( string $key ): string => (string) ( $old[ $key ] ?? '' );
?>
<form class="kp-form kp-form--narrow kp-contact" id="kp-contact" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php include __DIR__ . '/parts/notice.php'; ?>
	<input type="hidden" name="action" value="kipora_contact">
	<input type="hidden" name="lang" value="<?php echo esc_attr( \Kipora\Lang::current() ); ?>">
	<?php wp_nonce_field( 'kipora_contact' ); ?>

	<div class="kp-field">
		<label class="kp-field__label" for="kp-c-name"><?php esc_html_e( 'Name', 'kipora' ); ?></label>
		<input class="kp-field__input" id="kp-c-name" name="name" autocomplete="name" required value="<?php echo esc_attr( $value( 'name' ) ); ?>">
	</div>
	<div class="kp-form__row">
		<div class="kp-field">
			<label class="kp-field__label" for="kp-c-email"><?php esc_html_e( 'Email', 'kipora' ); ?></label>
			<input class="kp-field__input" id="kp-c-email" name="email" type="email" autocomplete="email" required value="<?php echo esc_attr( $value( 'email' ) ); ?>">
		</div>
		<div class="kp-field">
			<label class="kp-field__label" for="kp-c-phone"><?php esc_html_e( 'Phone', 'kipora' ); ?> <span class="kp-field__optional"><?php esc_html_e( 'optional', 'kipora' ); ?></span></label>
			<input class="kp-field__input" id="kp-c-phone" name="phone" type="tel" autocomplete="tel" value="<?php echo esc_attr( $value( 'phone' ) ); ?>">
		</div>
	</div>
	<div class="kp-field">
		<label class="kp-field__label" for="kp-c-message"><?php esc_html_e( 'Message', 'kipora' ); ?></label>
		<textarea class="kp-field__input kp-field__input--area" id="kp-c-message" name="message" rows="5" required><?php echo esc_textarea( $value( 'message' ) ); ?></textarea>
	</div>
	<label class="kp-upload__drop">
		<input class="kp-upload__input" type="file" name="files[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf" data-kp-files>
		<span class="kp-upload__text"><?php esc_html_e( 'Attach photos or documents', 'kipora' ); ?></span>
		<span class="kp-upload__hint"><?php esc_html_e( 'Up to 3 files, 10 MB each. For example, a photo of the plot.', 'kipora' ); ?></span>
		<span class="kp-upload__chosen" data-kp-chosen></span>
	</label>
	<p class="kp-contact__trap" aria-hidden="true">
		<label>Website <input name="website" tabindex="-1" autocomplete="off"></label>
	</p>
	<button type="submit" class="kp-button kp-button--primary"><?php esc_html_e( 'Send', 'kipora' ); ?></button>
</form>
