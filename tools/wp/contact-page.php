<?php
/**
 * Contact pages use the "Kontakt" template; the lead comes from the excerpt.
 * The form is rendered by the template, so the page body stays empty.
 */

defined( 'ABSPATH' ) || exit;

$map   = (array) get_option( 'kipora_setup_pages', [] );
$leads = [
	'et' => 'Kui teie kalmistut pole kalkulaatoris, on küsimus teenuse kohta või soovite midagi erilist, kirjutage meile. Vastame tavaliselt ühe tööpäeva jooksul.',
	'ru' => 'Если вашего кладбища нет в калькуляторе, есть вопрос об услуге или особое пожелание, напишите нам. Обычно отвечаем в течение рабочего дня.',
	'en' => 'If your cemetery is not in the calculator, you have a question or a special request, write to us. We usually answer within one working day.',
];
foreach ( $leads as $lang => $lead ) {
	$id = (int) ( $map['contact'][ $lang ] ?? 0 );
	if ( ! $id ) {
		continue;
	}
	update_post_meta( $id, '_wp_page_template', 'page-templates/contact.php' );
	wp_update_post( [ 'ID' => $id, 'post_excerpt' => $lead, 'post_content' => '' ] );
	echo "contact/{$lang}: {$id}\n";
}
