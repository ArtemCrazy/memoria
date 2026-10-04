<?php
/**
 * One line of a numbered list. The number comes from a CSS counter, so it
 * follows the order of the items after they are moved.
 *
 * @var array $attributes
 */
defined( 'ABSPATH' ) || exit;
?>
<li <?php echo get_block_wrapper_attributes( [ 'class' => 'numbered__item' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><span class="numbered__mark" aria-hidden="true"></span><span class="numbered__text"><?php echo kipora_block_text( $attributes['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></li>
