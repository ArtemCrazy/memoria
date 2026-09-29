<?php
/**
 * Top view of cemetery quarters: sectors A–D, rows of plots, pines along
 * the paths. One plot is marked: the place a customer names in the order
 * (sector and plot number). Decorative, hidden from screen readers.
 */
defined( 'ABSPATH' ) || exit;

$sectors = [
	'A' => [ 24, 24 ],
	'B' => [ 256, 24 ],
	'C' => [ 24, 214 ],
	'D' => [ 256, 214 ],
];
$cols    = 4;
$rows    = 4;
$w       = 38;
$h       = 24;
$gap_x   = 12;
$gap_y   = 14;
$mine    = [ 'B', 2, 1 ]; // sector, column, row
?>
<svg class="plot-map" viewBox="0 0 480 400" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
	<path class="plot-map__path" d="M240 8 V392 M8 200 H472" />
	<path class="plot-map__path" d="M8 8 H472 V392 H8 Z" />

	<?php foreach ( [ [ 240, 40 ], [ 240, 110 ], [ 240, 290 ], [ 240, 360 ], [ 60, 200 ], [ 150, 200 ], [ 330, 200 ], [ 420, 200 ] ] as [ $cx, $cy ] ) : ?>
		<circle class="plot-map__tree" cx="<?php echo (int) $cx; ?>" cy="<?php echo (int) $cy; ?>" r="9" />
	<?php endforeach; ?>

	<?php foreach ( $sectors as $letter => [ $x0, $y0 ] ) : ?>
		<text class="plot-map__sector" x="<?php echo (int) $x0; ?>" y="<?php echo (int) $y0 + 12; ?>"><?php echo esc_html( $letter ); ?></text>
		<?php for ( $r = 0; $r < $rows; $r++ ) : ?>
			<?php for ( $c = 0; $c < $cols; $c++ ) : ?>
				<?php
				$x = $x0 + 16 + $c * ( $w + $gap_x );
				$y = $y0 + 26 + $r * ( $h + $gap_y );
				if ( $letter === $mine[0] && $c === $mine[1] && $r === $mine[2] ) {
					$mine_xy = [ $x, $y ];
					continue;
				}
				?>
				<rect class="plot-map__plot" x="<?php echo (int) $x; ?>" y="<?php echo (int) $y; ?>" width="<?php echo (int) $w; ?>" height="<?php echo (int) $h; ?>" rx="2" />
			<?php endfor; ?>
		<?php endfor; ?>
	<?php endforeach; ?>

	<?php if ( isset( $mine_xy ) ) : ?>
		<rect class="plot-map__mine" x="<?php echo (int) $mine_xy[0]; ?>" y="<?php echo (int) $mine_xy[1]; ?>" width="<?php echo (int) $w; ?>" height="<?php echo (int) $h; ?>" rx="2" />
		<text class="plot-map__mine-label" x="<?php echo (int) $mine_xy[0] + $w / 2; ?>" y="<?php echo (int) $mine_xy[1] + 16; ?>" text-anchor="middle">12</text>
		<circle class="plot-map__candle" cx="<?php echo (int) $mine_xy[0] + $w - 5; ?>" cy="<?php echo (int) $mine_xy[1] + 5; ?>" r="2.5" />
	<?php endif; ?>
</svg>
