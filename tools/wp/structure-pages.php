<?php
/**
 * Pages from the search study (docs/seo-research.md): grave care, candle and
 * flowers for a memorial day, cemeteries (index and one page each), pets
 * (cremation, urns, memorial page) and prices, in ET/RU/EN, linked as
 * translations. Also rebuilds the header and footer menus.
 *
 * python tools/deploy.py --wp structure-pages            create missing pages, menus
 * python tools/deploy.py --wp structure-pages --force    also rewrite existing page content
 *
 * Cemetery facts: et.wikipedia.org and kalmistud.ee, checked 04.10.2026.
 * Texts are drafts for the client to correct in the editor.
 */

defined( 'ABSPATH' ) || exit;

$force = ! empty( $_GET['force'] ); // phpcs:ignore WordPress.Security.NonceVerification
$langs = [ 'et', 'ru', 'en' ];

$em    = static fn( string $s ): string => preg_replace( '/\*([^*]+)\*/u', '<em>$1</em>', esc_html( $s ) );
$block = static fn( string $name, array $attrs, array $inner = [] ): array => [
	'blockName'    => $name,
	'attrs'        => $attrs,
	'innerBlocks'  => $inner,
	'innerHTML'    => '',
	'innerContent' => array_fill( 0, count( $inner ), null ),
];
$para  = static fn( string $html ): array => [
	'blockName'    => 'core/paragraph',
	'attrs'        => [],
	'innerBlocks'  => [],
	'innerHTML'    => '<p>' . $html . '</p>',
	'innerContent' => [ '<p>' . $html . '</p>' ],
];
$intro = static fn( array $t, array $extra = [] ): array => $block( 'kipora/page-intro', array_merge( [ 'title' => $em( $t[0] ), 'lead' => esc_html( $t[1] ), 'buttonText' => esc_html( $t[2] ) ], $extra ) );
$faq   = static fn( string $title, array $qa ): array => $block(
	'kipora/faq',
	[ 'title' => $em( $title ) ],
	array_map( static fn( array $q ): array => $block( 'kipora/faq-item', [ 'question' => esc_html( $q[0] ), 'answer' => esc_html( $q[1] ) ] ), $qa )
);
$prices = static fn( string $group, string $title, string $note = '', string $button = '' ): array => $block( 'kipora/prices', array_filter( [ 'group' => $group, 'title' => $em( $title ), 'note' => esc_html( $note ), 'buttonText' => esc_html( $button ) ] ) );

// Shared texts per language.
$calc = [ 'et' => 'Arvuta hind', 'ru' => 'Рассчитать стоимость', 'en' => 'Calculate the price' ];
$pay  = [
	'et' => [ 'Kuidas maksta?', 'Montonio kaudu pangalingi või kaardiga. Hooajaline hooldus makstakse kogu hooaja eest korraga.' ],
	'ru' => [ 'Как оплатить?', 'Через Montonio, банковской ссылкой или картой. Сезонный уход оплачивается сразу за весь сезон.' ],
	'en' => [ 'How do I pay?', 'Through Montonio, by bank link or card. Seasonal care is paid once for the whole season.' ],
];
$done = [
	'et' => [ 'Kuidas ma tean, et töö on tehtud?', 'Pärast iga külastust lisame teie kontole fotod enne ja pärast ning saadame e-kirja.' ],
	'ru' => [ 'Как я узнаю, что работа выполнена?', 'После каждого выезда мы загружаем фото до и после в ваш кабинет и присылаем письмо.' ],
	'en' => [ 'How do I know the work is done?', 'After every visit we upload photos before and after to your account and send you an email.' ],
];
$abroad = [
	'et' => [ 'Kas pean olema Eestis?', 'Ei. Tellimine, makse ja fotod on veebis. Sisselogimiseks on vaja Smart-ID-d või Mobiil-ID-d.' ],
	'ru' => [ 'Нужно ли быть в Эстонии?', 'Нет. Заказ, оплата и фото онлайн. Для входа нужен Smart-ID или Mobiil-ID.' ],
	'en' => [ 'Do I need to be in Estonia?', 'No. Ordering, payment and photos are online. To log in you need Smart-ID or Mobiil-ID.' ],
];

// Cemetery facts (sources above). Names per language.
$cemeteries = [
	'metsakalmistu' => [
		'portal'   => '25',
		'lat'      => '59.469722',
		'lon'      => '24.870556',
		'address'  => 'Kloostrimetsa tee 36, Tallinn',
		'area'     => '48,3 ha',
		'order'    => 1,
		'district' => [ 'et' => 'Pirita, Kloostrimetsa', 'ru' => 'Пирита, Клоостриметса', 'en' => 'Pirita, Kloostrimetsa' ],
		'manager'  => [ 'et' => 'Tallinna linn', 'ru' => 'Город Таллин', 'en' => 'City of Tallinn' ],
		'name'     => [ 'et' => 'Metsakalmistu', 'ru' => 'Лесное кладбище (Метсакальмисту)', 'en' => 'Forest Cemetery (Metsakalmistu)' ],
		'short'    => [ 'et' => 'Metsakalmistul', 'ru' => 'Лесном кладбище', 'en' => 'Forest Cemetery' ],
		'slug'     => [ 'et' => 'metsakalmistu', 'ru' => 'lesnoe-kladbishhe', 'en' => 'forest-cemetery' ],
		'about'    => [
			'et' => 'Metsakalmistu asub Pirita linnaosas ja seda haldab Tallinna linn. Kalmistu on kasvanud 24,2 hektarilt 48,3 hektarini. Siia on maetud palju Eesti kultuuri-, spordi- ja avaliku elu tegelasi.',
			'ru' => 'Лесное кладбище находится в районе Пирита, им управляет город Таллин. Площадь кладбища выросла с 24,2 до 48,3 гектара. Здесь похоронены многие деятели эстонской культуры, спорта и общественной жизни.',
			'en' => 'The Forest Cemetery is in the Pirita district and is managed by the City of Tallinn. It has grown from 24.2 to 48.3 hectares. Many figures of Estonian culture, sport and public life are buried here.',
		],
	],
	'rahumae' => [
		'portal'   => '24',
		'lat'      => '59.392154',
		'lon'      => '24.701707',
		'address'  => 'Rahumäe tee 8a, Tallinn',
		'area'     => '',
		'order'    => 2,
		'district' => [ 'et' => 'Nõmme', 'ru' => 'Нымме', 'en' => 'Nõmme' ],
		'manager'  => [ 'et' => '', 'ru' => '', 'en' => '' ],
		'name'     => [ 'et' => 'Rahumäe kalmistu', 'ru' => 'Кладбище Рахумяэ', 'en' => 'Rahumäe Cemetery' ],
		'short'    => [ 'et' => 'Rahumäe kalmistul', 'ru' => 'кладбище Рахумяэ', 'en' => 'Rahumäe Cemetery' ],
		'slug'     => [ 'et' => 'rahumae', 'ru' => 'kladbishhe-rahumyae', 'en' => 'rahumae-cemetery' ],
		'about'    => [
			'et' => 'Rahumäe kalmistu avati 1903. aastal mitmele Tallinna kogudusele. 1910. aastatel rajati selle kõrvale uus juudi kalmistu, hiljem on kalmistut laiendatud Järve suunas.',
			'ru' => 'Кладбище Рахумяэ открыли в 1903 году для нескольких таллинских общин. В 1910-х рядом устроили новое еврейское кладбище, позже кладбище расширили в сторону Ярве.',
			'en' => 'Rahumäe Cemetery opened in 1903 for several Tallinn congregations. A new Jewish cemetery was laid out next to it in the 1910s, and the cemetery was later extended towards Järve.',
		],
	],
	'parnamae' => [
		'portal'   => '26',
		'lat'      => '59.476047',
		'lon'      => '24.900760',
		'address'  => 'Pärnamäe tee 36, Tallinn',
		'area'     => '109 ha',
		'order'    => 3,
		'district' => [ 'et' => 'Pirita, Laiaküla ja Lepiku', 'ru' => 'Пирита, Лайакюла и Лепику', 'en' => 'Pirita, Laiaküla and Lepiku' ],
		'manager'  => [ 'et' => '', 'ru' => '', 'en' => '' ],
		'name'     => [ 'et' => 'Pärnamäe kalmistu', 'ru' => 'Кладбище Пярнамяэ', 'en' => 'Pärnamäe Cemetery' ],
		'short'    => [ 'et' => 'Pärnamäe kalmistul', 'ru' => 'кладбище Пярнамяэ', 'en' => 'Pärnamäe Cemetery' ],
		'slug'     => [ 'et' => 'parnamae', 'ru' => 'kladbishhe-pyarnamyae', 'en' => 'parnamae-cemetery' ],
		'about'    => [
			'et' => 'Pärnamäe on Tallinna suurkalmistu, mida hakati kavandama 1950. aastate teisel poolel, kui Metsa- ja Liiva kalmistu olid täitumas. Kalmistu kontor asub aadressil Pärnamäe tee 36.',
			'ru' => 'Пярнамяэ — большое городское кладбище Таллина. Его начали проектировать во второй половине 1950-х, когда Лесное кладбище и Лийва заполнялись. Контора кладбища — Пярнамяэ теэ 36.',
			'en' => 'Pärnamäe is a large Tallinn city cemetery, planned in the late 1950s when the Forest and Liiva cemeteries were filling up. The cemetery office is at Pärnamäe tee 36.',
		],
	],
	'liiva' => [
		'portal'   => '21',
		'lat'      => '59.376702',
		'lon'      => '24.729662',
		'address'  => 'Kalmistu tee 34, Tallinn',
		'area'     => '133 ha',
		'order'    => 4,
		'district' => [ 'et' => 'Nõmme, Liiva', 'ru' => 'Нымме, Лийва', 'en' => 'Nõmme, Liiva' ],
		'manager'  => [ 'et' => '', 'ru' => '', 'en' => '' ],
		'name'     => [ 'et' => 'Liiva kalmistu', 'ru' => 'Кладбище Лийва', 'en' => 'Liiva Cemetery' ],
		'short'    => [ 'et' => 'Liiva kalmistul', 'ru' => 'кладбище Лийва', 'en' => 'Liiva Cemetery' ],
		'slug'     => [ 'et' => 'liiva', 'ru' => 'kladbishhe-liiva', 'en' => 'liiva-cemetery' ],
		'about'    => [
			'et' => 'Liiva kalmistu asub Nõmme linnaosas aadressil Kalmistu tee 34. Kalmistu pindala on 133 hektarit.',
			'ru' => 'Кладбище Лийва находится в районе Нымме, по адресу Калмисту теэ 34. Площадь кладбища 133 гектара.',
			'en' => 'Liiva Cemetery is in the Nõmme district at Kalmistu tee 34. It covers 133 hectares.',
		],
	],
	'siselinna' => [
		'portal'   => '27',
		'lat'      => '59.423056',
		'lon'      => '24.761822',
		'address'  => 'Toonela tee 7, Tallinn',
		'area'     => '18 ha',
		'order'    => 5,
		'district' => [ 'et' => 'Kesklinn, Juhkentali', 'ru' => 'Кесклинн, Юхкентали', 'en' => 'Kesklinn, Juhkentali' ],
		'manager'  => [ 'et' => 'Tallinna linn', 'ru' => 'Город Таллин', 'en' => 'City of Tallinn' ],
		'name'     => [ 'et' => 'Siselinna kalmistu', 'ru' => 'Кладбище Сиселинна', 'en' => 'Siselinna Cemetery' ],
		'short'    => [ 'et' => 'Siselinna kalmistul', 'ru' => 'кладбище Сиселинна', 'en' => 'Siselinna Cemetery' ],
		'slug'     => [ 'et' => 'siselinna', 'ru' => 'kladbishhe-siselinna', 'en' => 'siselinna-cemetery' ],
		'about'    => [
			'et' => 'Siselinna kalmistu on Tallinna linna hallatav kompleks, kuhu kuuluvad Aleksander Nevski kalmistu (1775), Juhkentali koolerakalmistu ja Vana-Kaarli kalmistu (1864). Kontor asub aadressil Toonela tee 7.',
			'ru' => 'Сиселинна — комплекс кладбищ под управлением города Таллина. В него входят кладбище Александра Невского (1775), холерное кладбище Юхкентали и Старое Каарли (1864). Контора — Тоонела теэ 7.',
			'en' => 'Siselinna is a complex of cemeteries managed by the City of Tallinn: the Alexander Nevsky Cemetery (1775), the Juhkentali cholera cemetery and the Old Kaarli Cemetery (1864). The office is at Toonela tee 7.',
		],
	],
];

// Pages: key => [ parent key, menu order, per language [ title, slug, excerpt, blocks ] ].
$pages = [];

$pages['care'] = [ '', 10, [] ];
$pages['candles'] = [ '', 11, [] ];
$pages['cemeteries'] = [ '', 12, [] ];
$pages['pets'] = [ '', 13, [] ];
$pages['cremation'] = [ 'pets', 1, [] ];
$pages['urns'] = [ 'pets', 2, [] ];
$pages['memorial'] = [ 'pets', 3, [] ];
$pages['prices'] = [ '', 14, [] ];
foreach ( $cemeteries as $id => $c ) {
	$pages[ "cemetery-{$id}" ] = [ 'cemeteries', $c['order'], [] ];
}

$text = [
	'care' => [
		'et' => [ 'Hauaplatsi hooldus', 'hauahooldus', 'Ühekordne või hooajaline hooldus Tallinnas ja Harjumaal, fotod enne ja pärast.', [ 'Hauaplatsi *hooldus* Tallinnas ja Harjumaal', 'Koristame platsi, hoolitseme taimede eest ning toome soovi korral lilled ja küünla. Pärast iga külastust ilmuvad fotod enne ja pärast teie kontole.' ], [ 'Mis kuulub *hooldusse*', [ 'Koristame prahi, lehed ja närtsinud lilled', 'Rohime platsi ja hoolitseme taimede eest', 'Soovi korral toome lilled ja küünla', 'Pildistame platsi enne ja pärast tööd' ], 'Hooajaline hooldus makstakse *kogu hooaja eest* korraga.' ], 'Hind sõltub platsi suurusest. Harjumaa kalmistutel lisandub transport kilomeetri järgi.', 'Kalmistud, kus töötame', 'Lisatööd' ],
		'ru' => [ 'Уход за могилой', 'ukhod-za-mogiloj', 'Разовый или сезонный уход в Таллине и Харьюмаа, фото до и после.', [ 'Уход за *могилой* в Таллине и Харьюмаа', 'Убираем участок, ухаживаем за растениями и по желанию приносим цветы и свечу. После каждого выезда фото до и после появляются в вашем кабинете.' ], [ 'Что входит в *уход*', [ 'Убираем мусор, листья и увядшие цветы', 'Пропалываем участок и ухаживаем за растениями', 'По желанию приносим цветы и свечу', 'Фотографируем участок до и после работы' ], 'Сезонный уход оплачивается *сразу за весь сезон*.' ], 'Цена зависит от размера участка. Для кладбищ Харьюмаа добавляется выезд по километражу.', 'Кладбища, где мы работаем', 'Дополнительные работы' ],
		'en' => [ 'Grave care', 'grave-care', 'One-time or seasonal care in Tallinn and Harju County, photos before and after.', [ 'Grave *care* in Tallinn and Harju County', 'We clean the plot, look after the plants and, if you wish, bring flowers and a candle. After every visit, photos before and after appear in your account.' ], [ 'What *care* includes', [ 'We remove litter, leaves and wilted flowers', 'We weed the plot and look after the plants', 'If you wish, we bring flowers and a candle', 'We photograph the plot before and after the work' ], 'Seasonal care is paid *for the whole season* at once.' ], 'The price depends on the plot size. Cemeteries in Harju County add travel by kilometre.', 'Cemeteries where we work', 'Additional work' ],
	],
	'candles' => [
		'et' => [ 'Küünal ja lilled tähtpäevaks', 'kuunla-suutamine', 'Toome lilled ja süütame küünla teie valitud päeval, foto saadame kontole.', [ 'Küünal ja lilled *tähtpäevaks*', 'Toome lilled ja süütame küünla teie valitud päeval: sünni- või surma-aastapäeval, hingedepäeval, jõululaupäeval. Foto ilmub teie kontole.' ], [ 'Päevad, mil *küünal* süüdatakse', [ '2. november, hingedepäev', '24. detsember, jõululaupäev', 'Kalmistupühad suvel', 'Lähedase sünni- ja surma-aastapäev' ], 'Hingedepäevaks ja jõuludeks tasub tellida *nädal või kaks varem*.' ] ],
		'ru' => [ 'Свеча и цветы к памятной дате', 'svecha-i-cvety', 'Приносим цветы и зажигаем свечу в выбранный день, фото присылаем в кабинет.', [ 'Свеча и цветы к *памятной дате*', 'Приносим цветы и зажигаем свечу в день, который вы выберете: день рождения или памяти, Hingedepäev, сочельник, Пасха и Радоница. Фото появится в вашем кабинете.' ], [ 'Дни, когда зажигают *свечу*', [ '2 ноября — Hingedepäev, день поминовения', '24 декабря — сочельник', 'Пасха и Радоница', 'День рождения и день памяти близкого' ], 'К Hingedepäev и Рождеству заказ лучше оформить *за одну-две недели*.' ] ],
		'en' => [ 'Candle and flowers for a memorial day', 'candle-and-flowers', 'We bring flowers and light a candle on the day you choose, the photo goes to your account.', [ 'Candle and flowers for a *memorial day*', 'We bring flowers and light a candle on the day you choose: a birthday or anniversary of death, All Souls\' Day (Hingedepäev), Christmas Eve. The photo appears in your account.' ], [ 'Days when a *candle* is lit', [ '2 November, All Souls\' Day (Hingedepäev)', '24 December, Christmas Eve', 'Cemetery days in summer', 'The birthday and anniversary of a loved one' ], 'For All Souls\' Day and Christmas, order *a week or two ahead*.' ] ],
	],
	'cemeteries' => [
		'et' => [ 'Kalmistud', 'kalmistud', 'Tallinna ja Harjumaa kalmistud, kus hooldame hauaplatse.', [ '*Kalmistud*, kus töötame', 'Hooldame hauaplatse Tallinna kalmistutel ja Harjumaal. Kalmistu lehel on aadress, link maetu otsingule ja hoolduse tellimine.' ], 'Kui teie kalmistut nimekirjas pole, <a href="%contact%">kirjutage meile</a>: arvutame hinna koos transpordiga.' ],
		'ru' => [ 'Кладбища', 'kladbishha', 'Кладбища Таллина и Харьюмаа, где мы ухаживаем за участками.', [ '*Кладбища*, где мы работаем', 'Ухаживаем за участками на кладбищах Таллина и Харьюмаа. На странице кладбища — адрес, ссылка на поиск захоронения и заказ ухода.' ], 'Если вашего кладбища нет в списке, <a href="%contact%">напишите нам</a>: рассчитаем цену вместе с выездом.' ],
		'en' => [ 'Cemeteries', 'cemeteries', 'Cemeteries in Tallinn and Harju County where we care for graves.', [ '*Cemeteries* where we work', 'We care for graves at cemeteries in Tallinn and Harju County. Each cemetery page has the address, a link to the grave search and the care order.' ], 'If your cemetery is not on the list, <a href="%contact%">write to us</a>: we will calculate the price including travel.' ],
	],
	'pets' => [
		'et' => [ 'Lemmikloomad', 'lemmikloomad', 'Tuhastamine, urn graveeringuga ja mälestusleht.', [ 'Kui lahkub *lemmikloom*', 'Tuhastamine, transport, urn graveeringuga ja mälestusleht. Hinna näete kohe, tellimine ja makse käivad veebis.' ], 'Mida teeme' ],
		'ru' => [ 'Питомцы', 'pitomcy', 'Кремация, урна с гравировкой и страница памяти.', [ 'Когда уходит *питомец*', 'Кремация, транспорт, урна с гравировкой и страница памяти. Цену видно сразу, заказ и оплата онлайн.' ], 'Что мы делаем' ],
		'en' => [ 'Pets', 'pets', 'Cremation, an urn with engraving and a memorial page.', [ 'When a *pet* passes away', 'Cremation, transport, an urn with engraving and a memorial page. You see the price right away, ordering and payment are online.' ], 'What we do' ],
	],
	'cremation' => [
		'et' => [ 'Lemmiklooma tuhastamine', 'tuhastamine', 'Tuhastamine ja transport, hind kalkulaatoris enne maksmist.', [ 'Lemmiklooma *tuhastamine*', 'Tuhastamine, vajadusel transport, urn ja graveering. Kogu hinna näete kalkulaatoris enne maksmist.' ] ],
		'ru' => [ 'Кремация питомца', 'kremaciya', 'Кремация и транспорт, цена в калькуляторе до оплаты.', [ 'Кремация *питомца*', 'Кремация, при необходимости транспорт, урна и гравировка. Полную цену вы увидите в калькуляторе до оплаты.' ] ],
		'en' => [ 'Pet cremation', 'cremation', 'Cremation and transport, the price in the calculator before payment.', [ 'Pet *cremation*', 'Cremation, transport if needed, an urn and engraving. You see the full price in the calculator before paying.' ] ],
	],
	'urns' => [
		'et' => [ 'Urnid ja graveering', 'urnid', 'Puidust või keraamiline urn, nime ja kuupäevade graveering.', [ 'Urnid ja *graveering*', 'Puidust või keraamiline urn ning nime ja kuupäevade graveering. Urni saab valida koos tuhastamisega.' ] ],
		'ru' => [ 'Урны и гравировка', 'urny', 'Деревянная или керамическая урна, гравировка имени и дат.', [ 'Урны и *гравировка*', 'Деревянная или керамическая урна и гравировка имени и дат. Урну можно выбрать вместе с кремацией.' ] ],
		'en' => [ 'Urns and engraving', 'urns', 'A wooden or ceramic urn, engraving of the name and dates.', [ 'Urns and *engraving*', 'A wooden or ceramic urn and engraving of the name and dates. You choose the urn together with the cremation.' ] ],
	],
	'memorial' => [
		'et' => [ 'Mälestusleht', 'malestusleht', 'Nimi, eluaastad, lugu ja fotod teie kontol.', [ 'Lemmiklooma *mälestusleht*', 'Nimi, eluaastad, lugu ja fotod ühes kohas, teie kontol. Sisse saab Smart-ID või Mobiil-ID-ga.' ] ],
		'ru' => [ 'Страница памяти', 'stranica-pamyati', 'Имя, годы жизни, история и фото в вашем кабинете.', [ 'Страница *памяти* питомца', 'Имя, годы жизни, история и фотографии в одном месте, в вашем личном кабинете. Вход через Smart-ID или Mobiil-ID.' ] ],
		'en' => [ 'Memorial page', 'memorial-page', 'Name, years, story and photos in your account.', [ 'A *memorial page* for your pet', 'Name, years of life, story and photos in one place, in your account. You log in with Smart-ID or Mobiil-ID.' ] ],
	],
	'prices' => [
		'et' => [ 'Hinnad', 'hinnad', 'Hooldus, lilled ja küünal, lisatööd ja lemmikloomade teenused.', [ '*Hinnad*', 'Need on samad hinnad, mida kasutab kalkulaator. Teie platsi ja kalmistu lõpphinna näitab kalkulaator enne maksmist.' ], [ 'Hauaplatsi hooldus', 'Lilled ja küünal', 'Lisatööd', 'Lemmikloomad', 'Urnid', 'Mälestusleht' ] ],
		'ru' => [ 'Цены', 'ceny', 'Уход, цветы и свеча, дополнительные работы и услуги для питомцев.', [ '*Цены*', 'Это те же цены, что в калькуляторе. Итог для вашего участка и кладбища калькулятор покажет до оплаты.' ], [ 'Уход за могилой', 'Цветы и свеча', 'Дополнительные работы', 'Питомцы', 'Урны', 'Страница памяти' ] ],
		'en' => [ 'Prices', 'prices', 'Care, flowers and candle, additional work and pet services.', [ '*Prices*', 'These are the same prices the calculator uses. The calculator shows the final price for your plot and cemetery before payment.' ], [ 'Grave care', 'Flowers and candle', 'Additional work', 'Pets', 'Urns', 'Memorial page' ] ],
	],
];

$cem_text = [
	'et' => [ 'Hauaplatsi hooldus: %s', 'Hauaplatsi hooldus *%s*', '%s. Koristame platsi, toome lilled ja küünla, fotod enne ja pärast saadame teie kontole.', 'Telli hooldus', 'Telli hooldus sellele kalmistule', 'Kalmistust', 'Kuidas leida hauaplats, kui ma ei tea selle numbrit?', 'Otsige maetu nime järgi kalmistute portaalist kalmistud.ee, seal on platsi number. Kui see ei õnnestu, kirjutage tellimusse nimi ja eluaastad.' ],
	'ru' => [ 'Уход за могилой: %s', 'Уход за могилой на *%s*', '%s. Убираем участок, приносим цветы и свечу, фото до и после присылаем в ваш кабинет.', 'Заказать уход', 'Заказать уход на этом кладбище', 'О кладбище', 'Как найти участок, если я не знаю его номер?', 'Найдите захоронение по имени в портале кладбищ kalmistud.ee, там указан номер участка. Если не получится, укажите в заказе имя и годы жизни.' ],
	'en' => [ 'Grave care: %s', 'Grave care at *%s*', '%s. We clean the plot, bring flowers and a candle and send photos before and after to your account.', 'Order care', 'Order care at this cemetery', 'About the cemetery', 'How do I find the plot if I do not know its number?', 'Search for the name in the cemetery portal kalmistud.ee, it shows the plot number. If that does not work, write the name and years of life in the order.' ],
];

$map    = (array) get_option( 'kipora_structure_pages', [] );
$setup  = (array) get_option( 'kipora_setup_pages', [] );
// By reference: pages created below must be visible to the blocks built after them.
$id_of  = static function ( string $key, string $lang ) use ( &$map ): int {
	return (int) ( $map[ $key ][ $lang ] ?? 0 );
};

/** Blocks of a page, built after all pages exist (cards and links need their ids). */
$build = static function ( string $key, string $lang ) use ( &$map, $setup, $text, $cem_text, $cemeteries, $calc, $pay, $done, $abroad, $em, $block, $para, $intro, $faq, $prices, $id_of ): array {
	$url = static fn( string $k ): string => (string) get_permalink( $id_of( $k, $lang ) );
	switch ( true ) {
		case 'care' === $key:
			$t = $text['care'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ], [ 'preset' => [ 'direction' => 'grave' ], 'fallback' => 'card-grave', 'secondText' => esc_html( $text['cemeteries'][ $lang ][0] ), 'secondUrl' => $url( 'cemeteries' ) ] ),
				$block( 'kipora/problem', [ 'title' => $em( $t[4][0] ), 'fallback' => 'step-3', 'image' => [ 'alt' => '' ], 'quote' => $em( $t[4][2] ) ], array_map( static fn( string $p ): array => $block( 'kipora/point', [ 'text' => esc_html( $p ) ] ), $t[4][1] ) ),
				$prices( 'grave', $text['prices'][ $lang ][4][0], $t[5], $calc[ $lang ] ),
				$prices( 'extras', $t[7] ),
				$block( 'kipora/page-list', [ 'title' => $em( $t[6] ), 'parent' => $id_of( 'cemeteries', $lang ) ] ),
				$faq( [ 'et' => 'Korduma kippuvad *küsimused*', 'ru' => 'Частые *вопросы*', 'en' => 'Frequently asked *questions*' ][ $lang ], [ $done[ $lang ], $abroad[ $lang ], $pay[ $lang ] ] ),
			];
		case 'candles' === $key:
			$t = $text['candles'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ], [ 'preset' => [ 'direction' => 'grave', 'package' => 'flowers' ], 'fallback' => 'hero-lantern' ] ),
				$prices( 'flowers', $text['prices'][ $lang ][4][1], '', $calc[ $lang ] ),
				$block( 'kipora/problem', [ 'title' => $em( $t[4][0] ), 'fallback' => 'step-4', 'image' => [ 'alt' => '' ], 'quote' => $em( $t[4][2] ) ], array_map( static fn( string $p ): array => $block( 'kipora/point', [ 'text' => esc_html( $p ) ] ), $t[4][1] ) ),
				$faq( [ 'et' => 'Korduma kippuvad *küsimused*', 'ru' => 'Частые *вопросы*', 'en' => 'Frequently asked *questions*' ][ $lang ], [ $done[ $lang ], $abroad[ $lang ], $pay[ $lang ] ] ),
			];
		case 'cemeteries' === $key:
			$t = $text['cemeteries'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ], [ 'preset' => [ 'direction' => 'grave' ], 'fallback' => 'step-4' ] ),
				$block( 'kipora/page-list', [] ),
				$para( str_replace( '%contact%', esc_url( (string) get_permalink( (int) ( $setup['contact'][ $lang ] ?? 0 ) ) ), $t[4] ) ),
			];
		case str_starts_with( $key, 'cemetery-' ):
			$c  = $cemeteries[ substr( $key, 9 ) ];
			$ct = $cem_text[ $lang ];
			return [
				$intro( [ sprintf( $ct[1], $c['short'][ $lang ] ), sprintf( $ct[2], $c['address'] ), $ct[3] ], [ 'preset' => [ 'direction' => 'grave', 'cemetery' => substr( $key, 9 ) ], 'fallback' => 'step-4' ] ),
				$block(
					'kipora/cemetery',
					array_filter(
						[
							'cemetery'   => substr( $key, 9 ),
							'address'    => esc_html( $c['address'] ),
							'district'   => esc_html( $c['district'][ $lang ] ),
							'area'       => esc_html( $c['area'] ),
							'manager'    => esc_html( $c['manager'][ $lang ] ),
							'portal'     => $c['portal'],
							'lat'        => $c['lat'],
							'lon'        => $c['lon'],
							'buttonText' => esc_html( $ct[4] ),
						]
					)
				),
				[ 'blockName' => 'core/heading', 'attrs' => [], 'innerBlocks' => [], 'innerHTML' => '<h2 class="wp-block-heading">' . esc_html( $ct[5] ) . '</h2>', 'innerContent' => [ '<h2 class="wp-block-heading">' . esc_html( $ct[5] ) . '</h2>' ] ],
				$para( esc_html( $c['about'][ $lang ] ) ),
				$prices( 'grave', $text['prices'][ $lang ][4][0], $text['care'][ $lang ][5], $calc[ $lang ] ),
				$faq( [ 'et' => 'Korduma kippuvad *küsimused*', 'ru' => 'Частые *вопросы*', 'en' => 'Frequently asked *questions*' ][ $lang ], [ [ $ct[6], $ct[7] ], $done[ $lang ], $abroad[ $lang ] ] ),
			];
		case 'pets' === $key:
			$t = $text['pets'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ], [ 'preset' => [ 'direction' => 'pet' ], 'fallback' => 'card-pet' ] ),
				$block( 'kipora/page-list', [ 'title' => $em( $t[4] ) ] ),
				$prices( 'pet', $text['prices'][ $lang ][4][3], '', $calc[ $lang ] ),
				$faq( [ 'et' => 'Korduma kippuvad *küsimused*', 'ru' => 'Частые *вопросы*', 'en' => 'Frequently asked *questions*' ][ $lang ], [ $abroad[ $lang ], $pay[ $lang ] ] ),
			];
		case 'cremation' === $key:
			$t = $text['cremation'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ], [ 'preset' => [ 'direction' => 'pet' ], 'fallback' => 'card-pet' ] ),
				$prices( 'pet', $text['prices'][ $lang ][4][3], '', $calc[ $lang ] ),
				$prices( 'urns', $text['prices'][ $lang ][4][4] ),
			];
		case 'urns' === $key:
			$t = $text['urns'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ], [ 'preset' => [ 'direction' => 'pet' ] ] ),
				$prices( 'urns', $text['prices'][ $lang ][4][4], '', $calc[ $lang ] ),
			];
		case 'memorial' === $key:
			$t = $text['memorial'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ], [ 'preset' => [ 'direction' => 'pet' ], 'fallback' => 'section-memory-card' ] ),
				$prices( 'memory', $text['prices'][ $lang ][4][5], '', $calc[ $lang ] ),
			];
		case 'prices' === $key:
			$t = $text['prices'][ $lang ];
			return [
				$intro( [ $t[3][0], $t[3][1], $calc[ $lang ] ] ),
				$prices( 'grave', $t[4][0], $text['care'][ $lang ][5] ),
				$prices( 'flowers', $t[4][1] ),
				$prices( 'extras', $t[4][2] ),
				$prices( 'pet', $t[4][3] ),
				$prices( 'urns', $t[4][4] ),
				$prices( 'memory', $t[4][5], '', $calc[ $lang ] ),
			];
	}
	return [];
};

/** Title, slug and excerpt of a page in a language. */
$meta = static function ( string $key, string $lang ) use ( $text, $cemeteries, $cem_text ): array {
	if ( str_starts_with( $key, 'cemetery-' ) ) {
		$c = $cemeteries[ substr( $key, 9 ) ];
		return [ $c['name'][ $lang ], $c['slug'][ $lang ], $c['district'][ $lang ] . ', ' . $c['address'] ];
	}
	return array_slice( $text[ $key ][ $lang ], 0, 3 );
};

// 1. Create pages (parents first) and link translations.
foreach ( $pages as $key => [ $parent, $order ] ) {
	$translations = [];
	foreach ( $langs as $lang ) {
		[ $title, $slug, $excerpt ] = $meta( $key, $lang );
		$id = $id_of( $key, $lang );
		if ( ! $id || ! get_post( $id ) ) {
			$id = wp_insert_post(
				[
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_excerpt' => $excerpt,
					'post_parent'  => $parent ? $id_of( $parent, $lang ) : 0,
					'menu_order'   => $order,
				]
			);
			echo "created {$key}/{$lang}: {$id}\n";
		}
		$want = $parent ? $id_of( $parent, $lang ) : 0;
		if ( (int) get_post( $id )->post_parent !== $want ) {
			wp_update_post( [ 'ID' => $id, 'post_parent' => $want, 'menu_order' => $order ] );
			echo "parent {$key}/{$lang}: {$want}\n";
		}
		pll_set_post_language( $id, $lang );
		$translations[ $lang ] = $id;
		$map[ $key ][ $lang ]  = $id;
	}
	pll_save_post_translations( $translations );
}
update_option( 'kipora_structure_pages', $map, false );

// 2. Content (only for pages without KIPORA blocks, unless --force).
foreach ( array_keys( $pages ) as $key ) {
	foreach ( $langs as $lang ) {
		$id   = $id_of( $key, $lang );
		$post = get_post( $id );
		if ( ! $force && str_contains( (string) $post->post_content, '<!-- wp:kipora/' ) ) {
			continue;
		}
		Kipora\Lang::use( $lang );
		$blocks  = $build( $key, $lang );
		$content = implode( "\n\n", array_map( 'serialize_block', $blocks ) );
		// Description for search results: the lead of the page heading.
		$lead = html_entity_decode( wp_strip_all_tags( (string) ( $blocks[0]['attrs']['lead'] ?? '' ) ), ENT_QUOTES );
		if ( '' !== $lead ) {
			update_post_meta( $id, 'kipora_seo_description', $lead );
		}
		[ $title, , $excerpt ] = $meta( $key, $lang );
		wp_update_post( [ 'ID' => $id, 'post_content' => wp_slash( $content ), 'post_title' => $title, 'post_excerpt' => $excerpt ] );
		echo "content {$key}/{$lang}: " . strlen( $content ) . " bytes\n";
	}
}

// 3. Menus: header gets the main directions, footer the rest.
$menus = [
	// The calculator is the button in the header, so it is not repeated in the menu.
	'primary' => [ [ 'map', 'care' ], [ 'map', 'cemeteries' ], [ 'map', 'pets' ], [ 'setup', 'contact' ] ],
	'footer'  => [ [ 'map', 'candles' ], [ 'map', 'prices' ], [ 'setup', 'calculator' ], [ 'setup', 'about' ], [ 'setup', 'terms' ], [ 'setup', 'privacy' ] ],
];
$labels = [
	'et' => [ 'care' => 'Hauahooldus' ],
	'ru' => [ 'calculator' => 'Калькулятор' ],
];
foreach ( $langs as $lang ) {
	foreach ( $menus as $location => $items ) {
		$menu = wp_get_nav_menu_object( "KIPORA {$location} {$lang}" );
		if ( ! $menu ) {
			continue;
		}
		foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		foreach ( $items as $pos => [ $source, $key ] ) {
			$page_id = 'map' === $source ? $id_of( $key, $lang ) : (int) ( $setup[ $key ][ $lang ] ?? 0 );
			if ( ! $page_id ) {
				continue;
			}
			$args = [
				'menu-item-object-id' => $page_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $pos + 1,
			];
			if ( isset( $labels[ $lang ][ $key ] ) ) {
				$args['menu-item-title'] = $labels[ $lang ][ $key ];
			}
			wp_update_nav_menu_item( $menu->term_id, 0, $args );
		}
		echo "menu {$location}/{$lang}: " . count( $items ) . " items\n";
	}
}

delete_option( 'rewrite_rules' );
foreach ( $langs as $lang ) {
	echo "{$lang}: " . get_permalink( $id_of( 'cemetery-metsakalmistu', $lang ) ) . "\n";
}
