<?php
/**
 * Home page headline and lead in three languages. *Starred* words are set
 * in italic in the hero (see kipora_theme_emphasis in the theme).
 */

defined( 'ABSPATH' ) || exit;

$map   = (array) get_option( 'kipora_setup_pages', [] );
$texts = [
	'et' => [ 'Hoolitseme *puhkepaiga* eest, kui te ise *kohale ei jõua*', 'Koristame platsi, toome lilled ja küünla ning saadame fotod enne ja pärast tööd.' ],
	'ru' => [ 'Ухаживаем за *местом памяти*, когда вы *не можете приехать* сами', 'Убираем участок, приносим цветы и свечу и присылаем фото до и после работы.' ],
	'en' => [ 'We look after the *resting place* when you *can’t be there* yourself', 'We clean the plot, bring flowers and a candle, and send photos before and after the work.' ],
];
foreach ( $texts as $lang => [ $title, $lead ] ) {
	$id = (int) ( $map['home'][ $lang ] ?? 0 );
	if ( $id ) {
		wp_update_post( [ 'ID' => $id, 'post_title' => $title, 'post_excerpt' => $lead ] );
		echo "{$lang}: {$id}\n";
	}
}
