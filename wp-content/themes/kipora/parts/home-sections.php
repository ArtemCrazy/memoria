<?php
/**
 * Home page sections below the hero, in the manner of the approved reference:
 * the problem, how ordering works, an honest comparison, the memorial card, FAQ.
 * Texts are interface strings (translated in tools/i18n.py). Photos come from
 * assets/img; a missing file falls back to an existing one until it is made.
 */
defined( 'ABSPATH' ) || exit;

$calculator = kipora_theme_page( 'calculator' );
$contact    = kipora_theme_page( 'contact' );
$divider    = '<svg class="section-head__divider" viewBox="0 0 69 6" aria-hidden="true" focusable="false"><path d="M1 3c5.7-3 11.3 3 17 0s11.3-3 17 0 11.3 3 17 0 11.3-3 16 0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
$heading    = static function ( string $tag, string $id, string $text, string $class = '' ) use ( $divider ): void {
	printf(
		'<header class="section-head %1$s"><span data-reveal="fade-in">%2$s</span><%3$s class="section-head__title" id="%4$s" data-reveal="heading">%5$s</%3$s></header>',
		esc_attr( $class ),
		$divider, // phpcs:ignore WordPress.Security.EscapeOutput — static SVG
		esc_attr( $tag ),
		esc_attr( $id ),
		kipora_theme_emphasis( $text ) // phpcs:ignore WordPress.Security.EscapeOutput — escaped inside
	);
};
$picture = static function ( string $name, string $fallback, string $class, string $alt, int $w, int $h ): void {
	[ $jpg, $webp ] = kipora_theme_image( $name, $fallback );
	printf(
		'<picture class="%1$s"><source srcset="%2$s" type="image/webp"><img src="%3$s" width="%4$d" height="%5$d" alt="%6$s" loading="lazy"></picture>',
		esc_attr( $class ),
		esc_url( $webp ),
		esc_url( $jpg ),
		(int) $w,
		(int) $h,
		esc_attr( $alt )
	);
};
?>

<section class="problem" aria-labelledby="problem-title">
	<div class="problem__media" data-reveal="fade-up">
		<?php $picture( 'section-problem', 'card-grave', 'problem__photo', __( 'A grave plot overgrown with leaves and weeds', 'kipora' ), 560, 600 ); ?>
		<?php foreach ( [ 1, 2 ] as $n ) : ?>
			<svg class="problem__leaf problem__leaf--<?php echo (int) $n; ?>" viewBox="0 0 40 80" aria-hidden="true" focusable="false"><path d="M20 2C34 18 38 44 20 78 2 44 6 18 20 2Z" fill="currentColor"/></svg>
		<?php endforeach; ?>
	</div>
	<div class="problem__content">
		<?php $heading( 'h2', 'problem-title', __( 'Usually it goes *like this*', 'kipora' ) ); ?>
		<ol class="numbered" role="list" data-reveal="stagger">
			<?php
			foreach ( [
				__( 'A trip to the cemetery once a season, if it works out', 'kipora' ),
				__( 'Calling relatives who live closer', 'kipora' ),
				__( 'Weeds and leaves grow faster than you can come', 'kipora' ),
				__( 'No way to know whether the candle is burning', 'kipora' ),
				__( 'A heavy feeling on memorial days', 'kipora' ),
			] as $i => $item ) :
				?>
				<li class="numbered__item"><span class="numbered__mark" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><span class="numbered__text"><?php echo esc_html( $item ); ?></span></li>
			<?php endforeach; ?>
		</ol>
	</div>
	<p class="quote problem__quote" data-reveal="fade-up"><?php echo kipora_theme_emphasis( __( '*Memory does not depend on distance.* The plot can be cared for even when you are on the other side of the world.', 'kipora' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
</section>

<section class="steps" aria-labelledby="steps-title">
	<svg class="steps__blob" viewBox="0 0 200 200" aria-hidden="true" focusable="false"><path d="M200 0v160c-18 22-52 28-70 10-20 20-58 14-66-12-30 4-52-22-40-50C-2 96 2 58 30 50 26 22 52 0 80 0Z" fill="currentColor"/></svg>
	<div class="steps__top">
		<?php $heading( 'h2', 'steps-title', __( 'How it *works*', 'kipora' ), 'section-head--left' ); ?>
	</div>
	<p class="rule-label" data-reveal="fade-in"><span class="rule-label__text"><?php esc_html_e( 'Four steps', 'kipora' ); ?></span></p>
	<ol class="steps__list" role="list" data-reveal="stagger">
		<?php
		$steps = [
			[ 'step-1', 'card-pet', __( 'Calculate the price', 'kipora' ), __( 'Choose the service, plot size and cemetery. The price appears right away.', 'kipora' ), __( 'Person choosing a service on a phone', 'kipora' ) ],
			[ 'step-2', 'hero-lantern', __( 'Log in and pay', 'kipora' ), __( 'Smart-ID or Mobiil-ID, then a bank link or a card.', 'kipora' ), __( 'Paying on a phone', 'kipora' ) ],
			[ 'step-3', 'card-grave', __( 'We do the work', 'kipora' ), __( 'We clean the plot, care for the plants, bring flowers and a candle.', 'kipora' ), __( 'Cleaning a grave plot', 'kipora' ) ],
			[ 'step-4', 'hero-lantern', __( 'See the photos', 'kipora' ), __( 'Photos before and after appear in your account.', 'kipora' ), __( 'A tidy grave with flowers and a candle', 'kipora' ) ],
		];
		foreach ( $steps as $i => [ $img, $fallback, $title, $text, $alt ] ) :
			?>
			<li class="step">
				<?php $picture( $img, $fallback, 'step__photo', $alt, 560, 400 ); ?>
				<p class="step__label">
					<?php
					/* translators: %d: step number */
					echo esc_html( sprintf( __( 'Step %d', 'kipora' ), $i + 1 ) );
					?>
				</p>
				<h3 class="step__title"><?php echo esc_html( $title ); ?></h3>
				<p class="step__text"><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>
</section>

<section class="compare" aria-labelledby="compare-title">
	<?php $heading( 'h2', 'compare-title', __( 'An honest *comparison*', 'kipora' ), 'section-head--center' ); ?>
	<?php
	$rows = [
		[ __( 'Time', 'kipora' ), __( 'Half a day or more', 'kipora' ), __( 'A few minutes to order', 'kipora' ) ],
		[ __( 'The trip', 'kipora' ), __( 'You drive yourself', 'kipora' ), __( 'We go', 'kipora' ) ],
		[ __( 'Tools and materials', 'kipora' ), __( 'Bring your own', 'kipora' ), __( 'We bring them', 'kipora' ) ],
		[ __( 'Checking the result', 'kipora' ), __( 'Only on site', 'kipora' ), __( 'Photos before and after', 'kipora' ) ],
		[ __( 'Over the season', 'kipora' ), __( 'Every visit from scratch', 'kipora' ), __( 'Seasonal care in one payment', 'kipora' ) ],
	];
	?>
	<div class="compare__grid" data-reveal="stagger">
		<div class="compare__col compare__col--labels" aria-hidden="true">
			<p class="compare__head">&nbsp;</p>
			<?php foreach ( $rows as $row ) : ?>
				<p class="compare__cell"><?php echo esc_html( $row[0] ); ?></p>
			<?php endforeach; ?>
		</div>
		<?php foreach ( [ 1 => __( 'Going yourself', 'kipora' ), 2 => 'KIPORA' ] as $col => $title ) : ?>
			<div class="compare__col <?php echo 2 === $col ? 'compare__col--brand' : 'compare__col--base'; ?>">
				<h3 class="compare__head"><?php echo esc_html( $title ); ?></h3>
				<dl class="compare__list">
					<?php foreach ( $rows as $row ) : ?>
						<div class="compare__cell">
							<dt class="compare__label"><?php echo esc_html( $row[0] ); ?></dt>
							<dd class="compare__value"><?php echo esc_html( $row[ $col ] ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="compare__cta" data-reveal="fade-up">
		<p class="quote"><?php echo kipora_theme_emphasis( __( 'You pay only for what you choose. *The price is fixed before payment.*', 'kipora' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		<a class="kp-button kp-button--primary" href="<?php echo esc_url( $calculator ); ?>"><?php esc_html_e( 'Calculate the price', 'kipora' ); ?></a>
	</div>
</section>

<section class="memory" aria-labelledby="memory-title">
	<div class="memory__content">
		<?php $heading( 'h2', 'memory-title', __( 'A *memorial card* for each loved one', 'kipora' ) ); ?>
		<p class="memory__lead" data-reveal="fade-up"><?php esc_html_e( 'Every order is linked to a memorial card. It keeps everything you want to preserve:', 'kipora' ); ?></p>
		<ol class="numbered numbered--large" role="list" data-reveal="stagger">
			<?php
			foreach ( [
				__( 'Name and years of life', 'kipora' ),
				__( 'A story and photos', 'kipora' ),
				__( 'Documents', 'kipora' ),
				__( 'The exact place: sector and plot', 'kipora' ),
				__( 'Every photo report', 'kipora' ),
			] as $i => $item ) :
				?>
				<li class="numbered__item"><span class="numbered__mark" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><span class="numbered__text"><?php echo esc_html( $item ); ?></span></li>
			<?php endforeach; ?>
		</ol>
	</div>
	<div class="memory__media" data-reveal="fade-up">
		<?php $picture( 'section-memory', 'card-pet', 'memory__photo', __( 'Old family photographs in a box', 'kipora' ), 500, 550 ); ?>
		<div class="memory__card" data-reveal="pop" style="--reveal-delay:.35s">
			<?php $picture( 'section-memory-card', 'card-grave', 'memory__card-photo', '', 320, 320 ); ?>
			<p class="memory__card-text"><?php esc_html_e( 'Only you can see the card. You log in with Smart-ID or Mobiil-ID.', 'kipora' ); ?></p>
		</div>
	</div>
</section>

<section class="faq" aria-labelledby="faq-title">
	<?php $heading( 'h2', 'faq-title', __( 'Frequently asked *questions*', 'kipora' ), 'section-head--center' ); ?>
	<div class="faq__list" data-reveal="stagger">
		<?php
		$faq = [
			[ __( 'How do I know the work is done?', 'kipora' ), __( 'After every visit we upload photos before and after to your account and send you an email.', 'kipora' ) ],
			[ __( 'Do I need to be in Estonia?', 'kipora' ), __( 'No. Ordering, payment and photos are online. To log in you need Smart-ID or Mobiil-ID.', 'kipora' ) ],
			[ __( 'How do I pay?', 'kipora' ), __( 'Through Montonio, by bank link or card. Seasonal care is paid once for the whole season.', 'kipora' ) ],
			[ __( 'My cemetery is not in the calculator', 'kipora' ), __( 'Write to us with the name of the cemetery and, if you can, a photo of the plot. We will calculate the price.', 'kipora' ) ],
			[ __( 'Do you also help with pets?', 'kipora' ), __( 'Yes: cremation, transport, an urn, engraving and a digital memorial page.', 'kipora' ) ],
			[ __( 'Who can see the memorial card?', 'kipora' ), __( 'Only you. Photos and documents in the archive are not public and are not shown to search engines.', 'kipora' ) ],
		];
		foreach ( $faq as [ $q, $a ] ) :
			?>
			<details class="faq__item">
				<summary class="faq__question"><?php echo esc_html( $q ); ?><span class="faq__icon" aria-hidden="true"></span></summary>
				<p class="faq__answer"><?php echo esc_html( $a ); ?></p>
			</details>
		<?php endforeach; ?>
	</div>
	<p class="faq__more" data-reveal="fade-up">
		<?php esc_html_e( 'Did not find an answer?', 'kipora' ); ?>
		<a class="kp-link" href="<?php echo esc_url( $contact ); ?>"><?php esc_html_e( 'Write to us', 'kipora' ); ?></a>
	</p>
</section>
