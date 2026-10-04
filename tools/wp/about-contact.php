<?php
/**
 * "About" and "Contact" pages in blocks, in ET/RU/EN. The contact page moves
 * from the fixed "Kontakt" template to blocks (page heading, contacts from the
 * settings, the message form), so the client edits it like the other pages.
 *
 * python tools/deploy.py --wp about-contact            only pages without KIPORA blocks
 * python tools/deploy.py --wp about-contact --force    rewrite both pages
 */

defined( 'ABSPATH' ) || exit;

$force = ! empty( $_GET['force'] ); // phpcs:ignore WordPress.Security.NonceVerification
$map   = (array) get_option( 'kipora_setup_pages', [] );

$em    = static fn( string $s ): string => preg_replace( '/\*([^*]+)\*/u', '<em>$1</em>', esc_html( $s ) );
$block = static fn( string $name, array $attrs, array $inner = [] ): array => [
	'blockName'    => $name,
	'attrs'        => $attrs,
	'innerBlocks'  => $inner,
	'innerHTML'    => '',
	'innerContent' => array_fill( 0, count( $inner ), null ),
];

$about = [
	'et' => [
		'title'  => 'Hoolitseme *mälestuse* eest, kui olete kaugel',
		'lead'   => 'KIPORA hoolitseb hauaplatside eest Tallinna ja Harjumaa kalmistutel ning aitab lemmikloomaga hüvasti jätta. Tellimine, makse ja fotod on veebis, seega saab kõik korraldada mis tahes riigist.',
		'second' => 'Kirjuta meile',
		'how'    => 'Kuidas me *töötame*',
		'points' => [ 'Hinna näete enne maksmist: selle näitab kalkulaator', 'Sisselogimine Smart-ID või Mobiil-ID-ga, ilma paroolideta', 'Makse Montonio kaudu pangalingi või kaardiga', 'Pärast iga külastust fotod enne ja pärast', 'Mälestuskaart loo, fotode ja dokumentidega' ],
		'quote'  => 'Sait ja konto on kolmes keeles: *eesti, vene ja inglise*.',
		'faq'    => 'Korduma kippuvad *küsimused*',
	],
	'ru' => [
		'title'  => 'Ухаживаем за *памятью*, когда вы далеко',
		'lead'   => 'KIPORA ухаживает за местами захоронений на кладбищах Таллина и Харьюмаа и помогает проститься с питомцем. Заказ, оплата и фото онлайн, поэтому всё можно оформить из любой страны.',
		'second' => 'Написать нам',
		'how'    => 'Как мы *работаем*',
		'points' => [ 'Цена известна до оплаты: её показывает калькулятор', 'Вход через Smart-ID или Mobiil-ID, без паролей', 'Оплата через Montonio банковской ссылкой или картой', 'Фото до и после после каждого выезда', 'Карточка памяти с историей, фото и документами' ],
		'quote'  => 'Сайт и кабинет на трёх языках: *эстонском, русском и английском*.',
		'faq'    => 'Частые *вопросы*',
	],
	'en' => [
		'title'  => 'We look after *memory* while you are far away',
		'lead'   => 'KIPORA cares for graves at cemeteries in Tallinn and Harju County and helps you say goodbye to a pet. Ordering, payment and photos are online, so everything can be arranged from any country.',
		'second' => 'Write to us',
		'how'    => 'How we *work*',
		'points' => [ 'You know the price before paying: the calculator shows it', 'Log in with Smart-ID or Mobiil-ID, no passwords', 'Payment through Montonio by bank link or card', 'Photos before and after every visit', 'A memorial card with the story, photos and documents' ],
		'quote'  => 'The site and the account are in three languages: *Estonian, Russian and English*.',
		'faq'    => 'Frequently asked *questions*',
	],
];
$faq = [
	'et' => [ [ 'Kuidas ma tean, et töö on tehtud?', 'Pärast iga külastust lisame teie kontole fotod enne ja pärast ning saadame e-kirja.' ], [ 'Kas pean olema Eestis?', 'Ei. Tellimine, makse ja fotod on veebis. Sisselogimiseks on vaja Smart-ID-d või Mobiil-ID-d.' ], [ 'Kuidas maksta?', 'Montonio kaudu pangalingi või kaardiga. Hooajaline hooldus makstakse kogu hooaja eest korraga.' ] ],
	'ru' => [ [ 'Как я узнаю, что работа выполнена?', 'После каждого выезда мы загружаем фото до и после в ваш кабинет и присылаем письмо.' ], [ 'Нужно ли быть в Эстонии?', 'Нет. Заказ, оплата и фото онлайн. Для входа нужен Smart-ID или Mobiil-ID.' ], [ 'Как оплатить?', 'Через Montonio, банковской ссылкой или картой. Сезонный уход оплачивается сразу за весь сезон.' ] ],
	'en' => [ [ 'How do I know the work is done?', 'After every visit we upload photos before and after to your account and send you an email.' ], [ 'Do I need to be in Estonia?', 'No. Ordering, payment and photos are online. To log in you need Smart-ID or Mobiil-ID.' ], [ 'How do I pay?', 'Through Montonio, by bank link or card. Seasonal care is paid once for the whole season.' ] ],
];

foreach ( [ 'et', 'ru', 'en' ] as $lang ) {
	// Interface strings below (__()) in the page language.
	switch_to_locale( Kipora\Lang::locale( $lang ) );
	unload_textdomain( 'kipora', true );
	load_plugin_textdomain( 'kipora', false, dirname( plugin_basename( KIPORA_FILE ) ) . '/languages' );

	$contact_id = (int) ( $map['contact'][ $lang ] ?? 0 );
	$about_id   = (int) ( $map['about'][ $lang ] ?? 0 );
	$calc_url   = (string) get_permalink( (int) ( $map['calculator'][ $lang ] ?? 0 ) );

	$pages = [
		$about_id   => [
			$block(
				'kipora/page-intro',
				[
					'title'      => $em( $about[ $lang ]['title'] ),
					'lead'       => esc_html( $about[ $lang ]['lead'] ),
					'buttonText' => esc_html__( 'Calculate the price', 'kipora' ),
					'secondText' => esc_html( $about[ $lang ]['second'] ),
					'secondUrl'  => (string) get_permalink( $contact_id ),
					'fallback'   => 'section-memory',
				]
			),
			$block(
				'kipora/problem',
				[
					'title'    => $em( $about[ $lang ]['how'] ),
					'fallback' => 'step-2',
					'image'    => [ 'alt' => '' ],
					'quote'    => $em( $about[ $lang ]['quote'] ),
				],
				array_map( static fn( string $p ): array => $block( 'kipora/point', [ 'text' => esc_html( $p ) ] ), $about[ $lang ]['points'] )
			),
			$block(
				'kipora/faq',
				[ 'title' => $em( $about[ $lang ]['faq'] ) ],
				array_map( static fn( array $q ): array => $block( 'kipora/faq-item', [ 'question' => esc_html( $q[0] ), 'answer' => esc_html( $q[1] ) ] ), $faq[ $lang ] )
			),
		],
		$contact_id => [
			$block(
				'kipora/page-intro',
				[
					'title'      => $em( __( 'Tell us how we can *help*', 'kipora' ) ),
					'lead'       => esc_html( (string) get_post_field( 'post_excerpt', $contact_id ) ),
					'buttonText' => esc_html__( 'Send a message', 'kipora' ),
					'buttonUrl'  => '#kp-contact',
					'secondText' => esc_html__( 'Calculate the price', 'kipora' ),
					'secondUrl'  => $calc_url,
				]
			),
			$block( 'kipora/contacts', [] ),
			$block(
				'kipora/contact-form',
				[
					'title' => $em( __( 'Write *to us*', 'kipora' ) ),
					'hint'  => esc_html__( 'We will answer to the email you enter in the form.', 'kipora' ),
				]
			),
		],
	];

	foreach ( $pages as $id => $blocks ) {
		if ( ! $id || ! get_post( $id ) ) {
			continue;
		}
		if ( ! $force && str_contains( (string) get_post_field( 'post_content', $id ), '<!-- wp:kipora/' ) ) {
			echo "{$lang}: page {$id} already in blocks, skipped\n";
			continue;
		}
		wp_update_post( [ 'ID' => $id, 'post_content' => wp_slash( implode( "\n\n", array_map( 'serialize_block', $blocks ) ) ) ] );
		// Description for search results: the lead of the page heading.
		$lead = html_entity_decode( wp_strip_all_tags( (string) ( $blocks[0]['attrs']['lead'] ?? '' ) ), ENT_QUOTES );
		if ( '' !== $lead ) {
			update_post_meta( $id, 'kipora_seo_description', $lead );
		}
		// The fixed contact template is no longer used: the page is drawn by its blocks.
		update_post_meta( $id, '_wp_page_template', 'default' );
		echo "{$lang}: page {$id} " . get_permalink( $id ) . "\n";
	}
	restore_previous_locale();
}
