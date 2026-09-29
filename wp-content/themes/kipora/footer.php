<?php defined( 'ABSPATH' ) || exit; ?>
	</div>
</main>

<footer class="site-footer">
	<div class="site-wrap site-footer__inner">
		<div>
			<p class="site-footer__brand">KIPORA</p>
			<p><?php bloginfo( 'description' ); ?></p>
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
