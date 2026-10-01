<?php
/**
 * One-time (idempotent) setup of the KIPORA test site:
 * theme + plugins, Polylang with ET / RU / EN, functional pages and menus.
 * Runs inside WordPress: python tools/deploy.py --wp setup  (run twice on a fresh site).
 *
 * Page texts here are working drafts to show the structure. The client's
 * own texts replace them in the admin.
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';

$log = static function ( string $msg ): void {
	echo $msg, "\n";
};

// 1. Theme and core plugin.
if ( 'kipora' !== get_stylesheet() ) {
	switch_theme( 'kipora' );
	$log( 'theme: kipora' );
}
if ( ! is_plugin_active( 'kipora-core/kipora-core.php' ) ) {
	$r = activate_plugin( 'kipora-core/kipora-core.php' );
	$log( 'kipora-core: ' . ( is_wp_error( $r ) ? $r->get_error_message() : 'activated' ) );
}

// 2. Polylang.
if ( ! is_dir( WP_PLUGIN_DIR . '/polylang' ) ) {
	WP_Filesystem();
	$zip = download_url( 'https://downloads.wordpress.org/plugin/polylang.latest-stable.zip' );
	$r   = is_wp_error( $zip ) ? $zip : unzip_file( $zip, WP_PLUGIN_DIR );
	if ( ! is_wp_error( $zip ) ) {
		wp_delete_file( $zip );
	}
	$log( 'polylang download: ' . ( is_wp_error( $r ) ? $r->get_error_message() : 'ok' ) );
}
if ( ! is_plugin_active( 'polylang/polylang.php' ) ) {
	$r = activate_plugin( 'polylang/polylang.php' );
	$log( 'polylang: ' . ( is_wp_error( $r ) ? $r->get_error_message() : 'activated, run the script again' ) );
	return;
}
// 3. Languages. Estonian is the default and has no /et/ prefix.
// Polylang does not boot its front-end model until a language exists, so use the admin model.
$model     = ( function_exists( 'PLL' ) && PLL() && PLL()->model ) ? PLL()->model : new PLL_Admin_Model( new \WP_Syntex\Polylang\Options\Options() );
$languages = [
	'et' => [ 'name' => 'Eesti', 'locale' => 'et', 'flag' => 'ee', 'term_group' => 0 ],
	'ru' => [ 'name' => 'Русский', 'locale' => 'ru_RU', 'flag' => 'ru', 'term_group' => 1 ],
	'en' => [ 'name' => 'English', 'locale' => 'en_US', 'flag' => 'gb', 'term_group' => 2 ],
];
$added = false;
foreach ( $languages as $slug => $lang ) {
	if ( ! $model->get_language( $slug ) ) {
		$r = $model->add_language( $lang + [ 'slug' => $slug, 'rtl' => 0 ] );
		$log( "language {$slug}: " . ( is_wp_error( $r ) ? $r->get_error_message() : 'added' ) );
		$added = true;
	}
}
if ( $added || ! function_exists( 'PLL' ) || ! PLL() || ! PLL()->model ) {
	$log( 'languages ready, run the script again for pages and menus' );
	return;
}
$options                  = get_option( 'polylang' );
$options['default_lang']  = 'et';
$options['hide_default']  = 1;
$options['force_lang']    = 1;
$options['rewrite']       = 1;
$options['redirect_lang'] = 1; // language home is /ru/, not /ru/glavnaya/
$options['browser']       = 0;
update_option( 'polylang', $options );
PLL()->model->clean_languages_cache();

// Core translations for ru_RU (admin and default strings).
if ( ! in_array( 'ru_RU', get_available_languages(), true ) ) {
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	$r = wp_download_language_pack( 'ru_RU' );
	$log( 'ru_RU language pack: ' . ( $r ? 'ok' : 'failed' ) );
}

// 4. Pages. key => [lang => [title, content, excerpt]]
$p = static fn( string $text ): string => "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->\n";
$h = static fn( string $text ): string => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$text}</h2>\n<!-- /wp:heading -->\n";
$ol = static fn( array $items ): string => "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">" . implode( '', array_map( static fn( $i ) => "<!-- wp:list-item --><li>{$i}</li><!-- /wp:list-item -->", $items ) ) . "</ol>\n<!-- /wp:list -->\n";
$sc = static fn( string $code ): string => "<!-- wp:shortcode -->\n{$code}\n<!-- /wp:shortcode -->\n";

$pages = [
	'home'       => [
		'et' => [
			'Hoolitseme teie lähedaste puhkepaiga eest',
			$h( 'Kaks teenust' )
			. $p( '<strong>Kalmistu hooldus.</strong> Koristame platsi üks kord või terve hooaja, värvime aia, puhastame kivi, istutame taimi ning toome lilled ja küünla. Töötame Tallinna kalmistutel ja Harjumaal.' )
			. $p( '<strong>Lemmikloomad.</strong> Tuhastamine ja transport, urn, graveering ning digitaalne mälestusleht.' )
			. $h( 'Kuidas tellimus käib' )
			. $ol( [ 'Arvutage hind kalkulaatoris: teenus, platsi suurus, lisatööd ja kalmistu.', 'Logige sisse Smart-ID või Mobiil-ID-ga ja makske pangalingi või kaardiga.', 'Pärast tööd näete oma kontol fotosid enne ja pärast.' ] )
			. $h( 'Mälestuskaart' )
			. $p( 'Iga tellimus on seotud mälestuskaardiga: nimi, eluaastad, lühike elulugu, fotod ja dokumendid ning täpne asukoht kalmistul. Kaarti näete ainult teie.' ),
			'Koristame kalmuplatsi, toome lilled ja küünla ning saadame fotod enne ja pärast tööd. Tallinnas ja Harjumaal.',
		],
		'ru' => [
			'Ухаживаем за местом, где покоятся ваши близкие',
			$h( 'Два направления' )
			. $p( '<strong>Уход за захоронениями.</strong> Убираем участок разово или весь сезон, красим ограду, обрабатываем камень, сажаем растения, приносим цветы и свечу. Работаем на кладбищах Таллина и в Харьюмаа.' )
			. $p( '<strong>Домашние животные.</strong> Кремация и транспортировка, урна, гравировка и цифровая страница памяти.' )
			. $h( 'Как проходит заказ' )
			. $ol( [ 'Рассчитайте стоимость в калькуляторе: услуга, размер участка, дополнительные работы и кладбище.', 'Войдите через Smart-ID или Mobiil-ID и оплатите банковской ссылкой или картой.', 'После работы в личном кабинете появятся фото до и после.' ] )
			. $h( 'Карточка памяти' )
			. $p( 'Каждый заказ связан с карточкой памяти: имя, годы жизни, несколько слов о человеке, фотографии и документы, точное место на кладбище. Карточку видите только вы.' ),
			'Убираем участок, приносим цветы и свечу, присылаем фото до и после работы. В Таллине и Харьюмаа.',
		],
		'en' => [
			'We look after the resting place of your loved ones',
			$h( 'Two services' )
			. $p( '<strong>Grave care.</strong> We clean the plot once or for the whole season, paint the fence, treat the headstone, plant flowers and bring flowers with a candle. We work at cemeteries in Tallinn and Harju County.' )
			. $p( '<strong>Pets.</strong> Cremation and transport, an urn, engraving and a digital memorial page.' )
			. $h( 'How ordering works' )
			. $ol( [ 'Calculate the price: service, plot size, extra work and cemetery.', 'Log in with Smart-ID or Mobile-ID and pay by bank link or card.', 'After the work, photos before and after appear in your account.' ] )
			. $h( 'Memorial card' )
			. $p( 'Every order is linked to a memorial card: name, years of life, a few words, photos and documents, and the exact location at the cemetery. Only you can see the card.' ),
			'We clean the grave plot, bring flowers and a candle, and send photos before and after the work. In Tallinn and Harju County.',
		],
	],
	'calculator' => [
		'et' => [ 'Hinnakalkulaator', $p( 'Valige teenus ja vaadake hinda kohe. Harjumaa kalmistutel lisandub transport kilomeetri järgi.' ) . $sc( '[kipora_calculator]' ) ],
		'ru' => [ 'Калькулятор стоимости', $p( 'Выберите услугу и сразу увидите цену. Для кладбищ Харьюмаа добавляется выезд по километражу.' ) . $sc( '[kipora_calculator]' ) ],
		'en' => [ 'Price calculator', $p( 'Choose a service and see the price right away. Cemeteries in Harju County add travel by kilometre.' ) . $sc( '[kipora_calculator]' ) ],
	],
	'checkout'   => [
		'et' => [ 'Tellimus', $sc( '[kipora_checkout]' ) ],
		'ru' => [ 'Оформление заказа', $sc( '[kipora_checkout]' ) ],
		'en' => [ 'Order', $sc( '[kipora_checkout]' ) ],
	],
	'account'    => [
		'et' => [ 'Minu konto', $sc( '[kipora_account]' ) ],
		'ru' => [ 'Личный кабинет', $sc( '[kipora_account]' ) ],
		'en' => [ 'My account', $sc( '[kipora_account]' ) ],
	],
	'about'      => [
		'et' => [ 'Teenusest', $p( 'Siia tuleb kliendi tekst teenuse kohta: kes me oleme, kuidas töötame ja millele saab kindel olla.' ) ],
		'ru' => [ 'О сервисе', $p( 'Здесь будет текст клиента о сервисе: кто мы, как работаем и на что можно рассчитывать.' ) ],
		'en' => [ 'About', $p( 'The client text about the service goes here: who we are, how we work and what you can rely on.' ) ],
	],
	'contact'    => [
		'et' => [ 'Kontakt', $p( 'Kirjutage meile: vastame tavaliselt ühe tööpäeva jooksul. Kui teie kalmistut kalkulaatoris pole, lisage selle nimi ja foto platsist.' ) . $sc( '[kipora_contact]' ) ],
		'ru' => [ 'Контакты', $p( 'Напишите нам: обычно отвечаем в течение рабочего дня. Если вашего кладбища нет в калькуляторе, укажите его название и приложите фото участка.' ) . $sc( '[kipora_contact]' ) ],
		'en' => [ 'Contact', $p( 'Write to us: we usually answer within one working day. If your cemetery is not in the calculator, add its name and a photo of the plot.' ) . $sc( '[kipora_contact]' ) ],
	],
	'terms'      => [
		'et' => [ 'Müügitingimused', $p( 'Müügitingimuste tekst lisatakse kliendi juristi poolt.' ) ],
		'ru' => [ 'Условия продажи', $p( 'Текст условий продажи предоставит юрист клиента.' ) ],
		'en' => [ 'Terms of sale', $p( 'The terms of sale text will be provided by the client\'s lawyer.' ) ],
	],
	'privacy'    => [
		'et' => [ 'Privaatsuspoliitika', $p( 'Privaatsuspoliitika teksti lisab kliendi jurist.' ) ],
		'ru' => [ 'Политика конфиденциальности', $p( 'Текст политики конфиденциальности предоставит юрист клиента.' ) ],
		'en' => [ 'Privacy policy', $p( 'The privacy policy text will be provided by the client\'s lawyer.' ) ],
	],
];

$map = (array) get_option( 'kipora_setup_pages', [] );
foreach ( $pages as $key => $langs ) {
	$translations = [];
	foreach ( $langs as $lang => $data ) {
		$id = (int) ( $map[ $key ][ $lang ] ?? 0 );
		if ( ! $id || ! get_post( $id ) ) {
			$id = wp_insert_post(
				[
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $data[0],
					'post_content' => $data[1],
					'post_excerpt' => $data[2] ?? '',
				]
			);
			$log( "page {$key}/{$lang}: {$id}" );
		}
		pll_set_post_language( $id, $lang );
		$translations[ $lang ] = $id;
		$map[ $key ][ $lang ]  = $id;
	}
	pll_save_post_translations( $translations );
}
update_option( 'kipora_setup_pages', $map, false );

$functional = [];
foreach ( [ 'calculator', 'checkout', 'account', 'terms', 'privacy', 'contact', 'about' ] as $key ) {
	$functional[ $key ] = $map[ $key ]['et'];
}
update_option( 'kipora_pages', $functional );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $map['home']['et'] );
update_option( 'wp_page_for_privacy_policy', $map['privacy']['et'] );
update_option( 'blogdescription', 'Kalmistu hooldus ja lemmikloomade mälestusteenused Tallinnas ja Harjumaal' );

// Site tagline per language (Polylang string translations).
$tagline = get_option( 'blogdescription' );
foreach ( [ 'ru' => 'Уход за захоронениями и услуги памяти для питомцев в Таллине и Харьюмаа', 'en' => 'Grave care and pet memorial services in Tallinn and Harju County' ] as $slug => $translation ) {
	$language = PLL()->model->get_language( $slug );
	$mo       = new PLL_MO();
	$mo->import_from_db( $language );
	$mo->add_entry( $mo->make_entry( $tagline, $translation ) );
	$mo->export_to_db( $language );
}

// 5. Menus per language. Polylang keeps the assignment in its own option:
// polylang[nav_menus][theme][location][lang] = menu id.
$locations = get_theme_mod( 'nav_menu_locations', [] );
$pll_menus = [];
foreach ( array_keys( $languages ) as $lang ) {
	foreach ( [ 'primary' => [ 'calculator', 'about', 'contact' ], 'footer' => [ 'terms', 'privacy', 'contact' ] ] as $location => $keys ) {
		$name = "KIPORA {$location} {$lang}";
		$menu = wp_get_nav_menu_object( $name );
		$id   = $menu ? $menu->term_id : wp_create_nav_menu( $name );
		if ( ! $menu ) {
			foreach ( $keys as $key ) {
				wp_update_nav_menu_item(
					$id,
					0,
					[
						'menu-item-object-id' => $map[ $key ][ $lang ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					]
				);
			}
		}
		$locations[ 'et' === $lang ? $location : "{$location}___{$lang}" ] = $id;
		$pll_menus[ $location ][ $lang ]                                 = $id;
	}
}
set_theme_mod( 'nav_menu_locations', $locations );
$options                         = get_option( 'polylang' );
$options['nav_menus']['kipora'] = $pll_menus;
update_option( 'polylang', $options );

// Rebuilt on the next normal request, when Polylang adds language prefixes.
delete_option( 'rewrite_rules' );
$log( 'done' );
foreach ( $functional as $key => $id ) {
	$log( "{$key}: " . get_permalink( $id ) );
}
