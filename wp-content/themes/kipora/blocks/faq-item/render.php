<?php
/**
 * One question; the answer opens on click (native details, works without JS).
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;
?>
<details <?php echo get_block_wrapper_attributes( [ 'class' => 'faq__item' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<summary class="faq__question"><?php echo kipora_block_text( $attributes['question'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="faq__icon" aria-hidden="true"></span></summary>
	<p class="faq__answer"><?php echo kipora_block_text( $attributes['answer'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
</details>
