<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'kipora' ); ?></a>

<header class="site-header">
	<div class="site-wrap site-header__inner">
		<a class="site-header__brand" href="<?php echo esc_url( function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ) ); ?>">KIPORA</a>

		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Main menu', 'kipora' ); ?>">
			<?php
			wp_nav_menu(
				[
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'site-nav__list',
					'depth'          => 1,
					'fallback_cb'    => false,
				]
			);
			?>
		</nav>

		<a class="site-header__account" href="<?php echo esc_url( kipora_theme_page( 'account' ) ); ?>">
			<?php echo is_user_logged_in() ? esc_html( wp_get_current_user()->first_name ?: __( 'My account', 'kipora' ) ) : esc_html__( 'My account', 'kipora' ); ?>
		</a>

		<?php kipora_theme_languages(); ?>
	</div>
</header>

<main class="site-main" id="content">
	<div class="site-wrap">
