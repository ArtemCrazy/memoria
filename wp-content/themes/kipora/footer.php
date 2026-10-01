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
</footer>

<?php wp_footer(); ?>
</body>
</html>
