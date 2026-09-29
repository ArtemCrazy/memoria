<?php
/**
 * Memorial card fields for create and edit.
 *
 * @var array $edit Card values (name, born, died, biography, cemetery_name, sector, plot, kind).
 */
defined( 'ABSPATH' ) || exit;

use Kipora\Memorials;

$uid     = wp_unique_id( 'kp-card-' );
$is_new  = empty( $edit['id'] );
$is_pet  = Memorials::KIND_PET === $edit['kind'];
?>
<div class="kp-field">
	<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-name"><?php esc_html_e( 'Name', 'kipora' ); ?></label>
	<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-name" name="name" value="<?php echo esc_attr( $edit['name'] ); ?>" maxlength="120" required>
</div>
<div class="kp-form__row">
	<div class="kp-field">
		<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-born"><?php esc_html_e( 'Born', 'kipora' ); ?></label>
		<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-born" name="born" value="<?php echo esc_attr( $edit['born'] ); ?>" maxlength="20">
	</div>
	<div class="kp-field">
		<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-died"><?php esc_html_e( 'Died', 'kipora' ); ?></label>
		<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-died" name="died" value="<?php echo esc_attr( $edit['died'] ); ?>" maxlength="20">
	</div>
</div>
<?php if ( $is_new || ! $is_pet ) : ?>
	<div class="kp-form__place" data-kp-place <?php echo $is_pet ? 'hidden' : ''; ?>>
		<div class="kp-field">
			<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-cemetery"><?php esc_html_e( 'Cemetery', 'kipora' ); ?></label>
			<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-cemetery" name="cemetery_name" value="<?php echo esc_attr( $edit['cemetery_name'] ); ?>" maxlength="120">
		</div>
		<div class="kp-form__row">
			<div class="kp-field">
				<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-sector"><?php esc_html_e( 'Sector', 'kipora' ); ?></label>
				<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-sector" name="sector" value="<?php echo esc_attr( $edit['sector'] ); ?>" maxlength="40">
			</div>
			<div class="kp-field">
				<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-plot"><?php esc_html_e( 'Plot number', 'kipora' ); ?></label>
				<input class="kp-field__input" id="<?php echo esc_attr( $uid ); ?>-plot" name="plot" value="<?php echo esc_attr( $edit['plot'] ); ?>" maxlength="40">
			</div>
		</div>
	</div>
<?php endif; ?>
<div class="kp-field">
	<label class="kp-field__label" for="<?php echo esc_attr( $uid ); ?>-bio"><?php esc_html_e( 'Biography', 'kipora' ); ?> <span class="kp-field__optional"><?php esc_html_e( 'optional', 'kipora' ); ?></span></label>
	<textarea class="kp-field__input kp-field__input--area" id="<?php echo esc_attr( $uid ); ?>-bio" name="biography" rows="5"><?php echo esc_textarea( $edit['biography'] ); ?></textarea>
</div>
