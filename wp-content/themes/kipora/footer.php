<?php defined( 'ABSPATH' ) || exit; ?>
	</div>
</main>

<footer class="site-footer">
	<div class="site-wrap site-footer__inner">
		<div>
			<p class="site-footer__brand">KIPORA</p>
			<p><?php bloginfo( 'description' ); ?></p>
			<?php $contacts = class_exists( 'Kipora\Settings' ) ? Kipora\Settings::contacts() : []; ?>
			<?php if ( $contacts ) : ?>
				<ul class="site-footer__contacts">
					<?php foreach ( $contacts as $item ) : ?>
						<li><?php echo $item['href'] ? '<a href="' . esc_url( $item['href'] ) . '">' . esc_html( $item['value'] ) . '</a>' : esc_html( $item['value'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		wp_nav_menu(
			[
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'site-footer__links',
				'depth'          => 1,
				'fallback_cb'    => false,
			]
		);
		?>
	</div>
	<div class="site-wrap">
		<div class="site-footer__bottom">
			<p class="site-footer__copy">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> KIPORA</p>
			<div class="cs-sign-frame">
				<a class="cs-sign cs-sign--serif" data-cs-sign="fly" href="https://crazy.studio" target="_blank" rel="noopener">
					<span class="cs-sign__bat" aria-hidden="true">
						<svg viewBox="5 10 90 34">
							<path class="cs-sign__wing cs-sign__wing--l" d="M46.6,22 C36,14.5 23,11 7,12.5 C8.5,19 8,23.5 10,28 C16,25.5 19,29.5 21.5,35.5 C24,29.5 28,28.5 32,34.5 C35,29.5 39,30 42,33.5 C44,30.5 45.4,27 46.6,22 Z"/>
							<path class="cs-sign__wing" d="M53.4,22 C64,14.5 77,11 93,12.5 C91.5,19 92,23.5 90,28 C84,25.5 81,29.5 78.5,35.5 C76,29.5 72,28.5 68,34.5 C65,29.5 61,30 58,33.5 C56,30.5 54.6,27 53.4,22 Z"/>
							<path d="M46.2,19.4 L43.4,13 L48.6,17.6 Z M53.8,19.4 L56.6,13 L51.4,17.6 Z"/>
							<path d="M50,17 C52.2,17 53.6,18.6 54,20.6 C54.6,25 55,30 53.4,36 C52.4,39.6 51.2,41.6 50,43 C48.8,41.6 47.6,39.6 46.6,36 C45,30 45.4,25 46,20.6 C46.4,18.6 47.8,17 50,17 Z"/>
						</svg>
					</span>
					<span class="cs-sign__words">
						<span class="cs-sign__made"><?php esc_html_e( 'Developed at', 'kipora' ); ?></span>
						<span class="cs-sign__name">Crazy Studio</span>
					</span>
				</a>
			</div>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
