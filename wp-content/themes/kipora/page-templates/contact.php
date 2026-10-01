<?php
/**
 * Template Name: Kontakt
 *
 * Contact page after the approved reference: a headline that invites to write,
 * the page excerpt as lead, contacts in a row (only those filled in KIPORA →
 * Seaded), then the message form.
 */
defined( 'ABSPATH' ) || exit;

$contacts = class_exists( 'Kipora\\Settings' ) ? Kipora\Settings::contacts() : [];

get_header();
while ( have_posts() ) :
	the_post();
	?>
	<section class="contact-hero" aria-labelledby="contact-title">
		<header class="section-head">
			<span data-reveal="fade-in"><?php echo kipora_theme_divider(); // phpcs:ignore WordPress.Security.EscapeOutput — static SVG ?></span>
			<h1 class="contact-hero__title" id="contact-title" data-reveal="heading"><?php echo kipora_theme_emphasis( __( 'Tell us how we can *help*', 'kipora' ) ); // phpcs:ignore WordPress.Security.EscapeOutput — escaped inside ?></h1>
		</header>
		<?php if ( has_excerpt() ) : ?>
			<p class="contact-hero__lead" data-reveal="fade-up" style="--reveal-delay:.2s"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>

		<?php if ( $contacts ) : ?>
			<dl class="contact-hero__list" data-reveal="stagger">
				<?php foreach ( $contacts as $item ) : ?>
					<div class="contact-hero__item">
						<dt class="contact-hero__label"><?php echo esc_html( $item['label'] ); ?></dt>
						<dd class="contact-hero__value">
							<?php if ( $item['href'] ) : ?>
								<a href="<?php echo esc_url( $item['href'] ); ?>"><?php echo esc_html( $item['value'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $item['value'] ); ?>
							<?php endif; ?>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>

		<div class="contact-hero__actions" data-reveal="fade-up" style="--reveal-delay:.3s">
			<a class="kp-button kp-button--primary" href="#kp-contact"><?php esc_html_e( 'Send a message', 'kipora' ); ?></a>
			<a class="kp-button kp-button--quiet" href="<?php echo esc_url( kipora_theme_page( 'calculator' ) ); ?>"><?php esc_html_e( 'Calculate the price', 'kipora' ); ?></a>
		</div>
	</section>

	<section class="contact-form" aria-labelledby="contact-form-title">
		<div class="contact-form__intro">
			<header class="section-head">
				<span data-reveal="fade-in"><?php echo kipora_theme_divider(); // phpcs:ignore WordPress.Security.EscapeOutput — static SVG ?></span>
				<h2 class="section-head__title" id="contact-form-title" data-reveal="heading"><?php echo kipora_theme_emphasis( __( 'Write *to us*', 'kipora' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
			</header>
			<p class="contact-form__hint" data-reveal="fade-up"><?php esc_html_e( 'We will answer to the email you enter in the form.', 'kipora' ); ?></p>
		</div>
		<div class="contact-form__body" data-reveal="fade-up" style="--reveal-delay:.15s">
			<?php echo do_shortcode( '[kipora_contact]' ); // phpcs:ignore WordPress.Security.EscapeOutput — rendered form ?>
		</div>
	</section>
	<?php
endwhile;
get_footer();
