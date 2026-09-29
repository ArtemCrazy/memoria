<?php
/**
 * Customer account: memorial cards, one card, orders, contact details.
 *
 * @var string   $view    cards | card | orders | profile
 * @var \WP_User $user
 * @var string   $base    Account page URL.
 * @var array    $notice
 * @var array    $contact
 * @var array    $cards   (cards)
 * @var array    $card    (card)
 * @var array    $files   (card)
 * @var array    $orders  (card, orders)
 */
defined( 'ABSPATH' ) || exit;

use Kipora\Files;
use Kipora\Lang;
use Kipora\Memorials;
use Kipora\Orders;
use Kipora\Pricing;

$lang  = Lang::current();
$tabs  = [
	'cards'   => __( 'Memorial cards', 'kipora' ),
	'orders'  => __( 'Orders', 'kipora' ),
	'profile' => __( 'Contact details', 'kipora' ),
];
$active = 'card' === $view ? 'cards' : $view;
$form   = static function ( string $action ) use ( $lang ): void {
	echo '<input type="hidden" name="action" value="kipora_' . esc_attr( $action ) . '">';
	echo '<input type="hidden" name="lang" value="' . esc_attr( $lang ) . '">';
	wp_nonce_field( 'kipora_' . $action );
};
$date = static fn( string $mysql ): string => mysql2date( 'd.m.Y', $mysql );
?>
<div class="kp-account">
	<header class="kp-account__head">
		<p class="kp-account__hello">
			<?php
			/* translators: %s: customer name */
			printf( esc_html__( 'Hello, %s', 'kipora' ), esc_html( $user->display_name ) );
			?>
		</p>
		<a class="kp-link kp-account__logout" href="<?php echo esc_url( wp_logout_url( $base ) ); ?>"><?php esc_html_e( 'Log out', 'kipora' ); ?></a>
	</header>

	<nav class="kp-account__nav" aria-label="<?php esc_attr_e( 'Account sections', 'kipora' ); ?>">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a class="kp-account__tab <?php echo $key === $active ? 'kp-account__tab--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'view', $key, $base ) ); ?>" <?php echo $key === $active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php include __DIR__ . '/parts/notice.php'; ?>

	<?php if ( 'cards' === $view ) : ?>

		<?php if ( $cards ) : ?>
			<ul class="kp-cards">
				<?php foreach ( $cards as $item ) : ?>
					<li class="kp-cards__item">
						<a class="kp-cards__link" href="<?php echo esc_url( add_query_arg( [ 'view' => 'card', 'id' => $item['id'] ], $base ) ); ?>">
							<span class="kp-cards__name"><?php echo esc_html( $item['name'] ); ?></span>
							<span class="kp-cards__years"><?php echo esc_html( Memorials::years( $item ) ); ?></span>
							<span class="kp-cards__place"><?php echo esc_html( Memorials::KIND_PET === $item['kind'] ? __( 'Pet', 'kipora' ) : Memorials::location( $item ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="kp-account__empty"><?php esc_html_e( 'There are no memorial cards yet. A card is created with the first order, or you can add one here: a name, years of life, a few words and photos.', 'kipora' ); ?></p>
		<?php endif; ?>

		<details class="kp-disclosure" <?php echo $cards ? '' : 'open'; ?>>
			<summary class="kp-disclosure__summary"><?php esc_html_e( 'Add a memorial card', 'kipora' ); ?></summary>
			<form class="kp-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php $form( 'card_save' ); ?>
				<div class="kp-choices kp-choices--inline">
					<label class="kp-choice">
						<input class="kp-choice__input" type="radio" name="kind" value="<?php echo esc_attr( Memorials::KIND_HUMAN ); ?>" checked data-kp-kind>
						<span class="kp-choice__body"><span class="kp-choice__title"><?php esc_html_e( 'Person', 'kipora' ); ?></span></span>
					</label>
					<label class="kp-choice">
						<input class="kp-choice__input" type="radio" name="kind" value="<?php echo esc_attr( Memorials::KIND_PET ); ?>" data-kp-kind>
						<span class="kp-choice__body"><span class="kp-choice__title"><?php esc_html_e( 'Pet', 'kipora' ); ?></span></span>
					</label>
				</div>
				<?php
				$edit = [ 'name' => '', 'born' => '', 'died' => '', 'biography' => '', 'cemetery_name' => '', 'sector' => '', 'plot' => '', 'kind' => Memorials::KIND_HUMAN ];
				include __DIR__ . '/parts/card-fields.php';
				?>
				<button type="submit" class="kp-button kp-button--primary"><?php esc_html_e( 'Create card', 'kipora' ); ?></button>
			</form>
		</details>

	<?php elseif ( 'card' === $view ) : ?>

		<a class="kp-link kp-account__back" href="<?php echo esc_url( add_query_arg( 'view', 'cards', $base ) ); ?>"><?php esc_html_e( 'All memorial cards', 'kipora' ); ?></a>

		<article class="kp-memorial">
			<header class="kp-memorial__head">
				<h2 class="kp-memorial__name"><?php echo esc_html( $card['name'] ); ?></h2>
				<p class="kp-memorial__years"><?php echo esc_html( Memorials::years( $card ) ); ?></p>
				<?php if ( Memorials::location( $card ) ) : ?>
					<p class="kp-memorial__place"><?php echo esc_html( Memorials::location( $card ) ); ?></p>
				<?php endif; ?>
			</header>
			<?php if ( $card['biography'] ) : ?>
				<div class="kp-memorial__bio"><?php echo wp_kses_post( wpautop( esc_html( $card['biography'] ) ) ); ?></div>
			<?php endif; ?>

			<details class="kp-disclosure">
				<summary class="kp-disclosure__summary"><?php esc_html_e( 'Edit card', 'kipora' ); ?></summary>
				<form class="kp-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php $form( 'card_save' ); ?>
					<input type="hidden" name="id" value="<?php echo esc_attr( $card['id'] ); ?>">
					<?php
					$edit = $card;
					include __DIR__ . '/parts/card-fields.php';
					?>
					<button type="submit" class="kp-button kp-button--primary"><?php esc_html_e( 'Save', 'kipora' ); ?></button>
				</form>
			</details>
		</article>

		<section class="kp-archive" aria-labelledby="kp-archive-title">
			<h3 class="kp-archive__title" id="kp-archive-title"><?php esc_html_e( 'Archive', 'kipora' ); ?></h3>
			<p class="kp-archive__lead"><?php esc_html_e( 'Photos and documents are visible only to you. Photo reports from our team are added here automatically.', 'kipora' ); ?></p>

			<?php if ( $files ) : ?>
				<ul class="kp-archive__grid">
					<?php foreach ( $files as $file ) : ?>
						<li class="kp-archive__item">
							<?php if ( Files::is_image( $file ) ) : ?>
								<a class="kp-archive__thumb" href="<?php echo esc_url( Files::url( (int) $file['id'] ) ); ?>" target="_blank" rel="noopener">
									<img src="<?php echo esc_url( Files::url( (int) $file['id'], true ) ); ?>" alt="<?php echo esc_attr( $file['name'] ); ?>" loading="lazy">
								</a>
							<?php else : ?>
								<a class="kp-archive__doc" href="<?php echo esc_url( Files::url( (int) $file['id'] ) ); ?>" target="_blank" rel="noopener">
									<span class="kp-archive__doc-type">PDF</span>
								</a>
							<?php endif; ?>
							<div class="kp-archive__meta">
								<span class="kp-archive__name"><?php echo esc_html( $file['name'] ); ?></span>
								<span class="kp-archive__kind">
									<?php
									echo esc_html(
										[
											Files::KIND_BEFORE  => __( 'Photo report: before', 'kipora' ),
											Files::KIND_AFTER   => __( 'Photo report: after', 'kipora' ),
											Files::KIND_ARCHIVE => $date( get_date_from_gmt( $file['created_at'] ) ),
										][ $file['kind'] ] ?? ''
									);
									?>
								</span>
							</div>
							<?php if ( Files::KIND_ARCHIVE === $file['kind'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-kp-confirm="<?php esc_attr_e( 'Delete this file? This cannot be undone.', 'kipora' ); ?>">
									<?php $form( 'file_delete' ); ?>
									<input type="hidden" name="file" value="<?php echo esc_attr( $file['id'] ); ?>">
									<button type="submit" class="kp-button kp-button--text"><?php esc_html_e( 'Delete', 'kipora' ); ?></button>
								</form>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<form class="kp-upload" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php $form( 'file_upload' ); ?>
				<input type="hidden" name="id" value="<?php echo esc_attr( $card['id'] ); ?>">
				<label class="kp-upload__drop">
					<input class="kp-upload__input" type="file" name="files[]" multiple accept="<?php echo esc_attr( Files::accept_types() ); ?>" data-kp-files>
					<span class="kp-upload__text"><?php esc_html_e( 'Choose photos or documents', 'kipora' ); ?></span>
					<span class="kp-upload__hint">
						<?php
						/* translators: %d: size in MB */
						printf( esc_html__( 'JPG, PNG, WEBP or PDF, up to %d MB each', 'kipora' ), (int) ( Files::MAX_BYTES / 1024 / 1024 ) );
						?>
					</span>
					<span class="kp-upload__chosen" data-kp-chosen></span>
				</label>
				<button type="submit" class="kp-button kp-button--primary"><?php esc_html_e( 'Upload', 'kipora' ); ?></button>
			</form>
		</section>

		<?php if ( $orders ) : ?>
			<section class="kp-card-orders">
				<h3 class="kp-card-orders__title"><?php esc_html_e( 'Orders for this card', 'kipora' ); ?></h3>
				<?php include __DIR__ . '/parts/orders-table.php'; ?>
			</section>
		<?php endif; ?>

	<?php elseif ( 'orders' === $view ) : ?>

		<?php if ( $orders ) : ?>
			<?php include __DIR__ . '/parts/orders-table.php'; ?>
		<?php else : ?>
			<p class="kp-account__empty">
				<?php esc_html_e( 'No orders yet.', 'kipora' ); ?>
				<a class="kp-link" href="<?php echo esc_url( Lang::page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'Calculate the price', 'kipora' ); ?></a>
			</p>
		<?php endif; ?>

	<?php else : ?>

		<form class="kp-form kp-form--narrow" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php $form( 'profile_save' ); ?>
			<p class="kp-form__hint"><?php esc_html_e( 'We send order confirmations and photo report notices to this email.', 'kipora' ); ?></p>
			<div class="kp-field">
				<label class="kp-field__label" for="kp-profile-email"><?php esc_html_e( 'Email', 'kipora' ); ?></label>
				<input class="kp-field__input" id="kp-profile-email" name="email" type="email" autocomplete="email" value="<?php echo esc_attr( $contact['email'] ); ?>">
			</div>
			<div class="kp-field">
				<label class="kp-field__label" for="kp-profile-phone"><?php esc_html_e( 'Phone', 'kipora' ); ?></label>
				<input class="kp-field__input" id="kp-profile-phone" name="phone" type="tel" autocomplete="tel" value="<?php echo esc_attr( $contact['phone'] ); ?>">
			</div>
			<p class="kp-form__hint">
				<?php
				/* translators: %s: masked personal code */
				printf( esc_html__( 'Logged in with personal code %s. The name comes from your Smart-ID or Mobile-ID certificate.', 'kipora' ), esc_html( (string) get_user_meta( $user->ID, 'kp_id_masked', true ) ) );
				?>
			</p>
			<button type="submit" class="kp-button kp-button--primary"><?php esc_html_e( 'Save', 'kipora' ); ?></button>
		</form>

	<?php endif; ?>
</div>
