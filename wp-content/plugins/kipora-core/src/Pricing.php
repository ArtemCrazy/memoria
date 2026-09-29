<?php
/**
 * Price calculation. Pure logic without WordPress: the same code is used
 * by the calculator API, by checkout and by the CLI tests.
 *
 * All money is integer cents. Coefficients are floats (1.5 = +50%).
 */

namespace Kipora;

final class Pricing {

	public const DIRECTION_GRAVE = 'grave';
	public const DIRECTION_PET   = 'pet';

	/**
	 * @param array  $tariffs   Tariff tree, see TariffStore::defaults().
	 * @param array  $selection Customer selection from the calculator.
	 * @param string $lang      et | ru | en — language for line labels.
	 *
	 * @return array{valid:bool, errors:string[], lines:array<int,array{label:string,amount:int}>, total:int, summary:array}
	 */
	public static function calculate( array $tariffs, array $selection, string $lang = 'et' ): array {
		$direction = (string) ( $selection['direction'] ?? '' );

		if ( self::DIRECTION_GRAVE === $direction ) {
			return self::grave( $tariffs['grave'] ?? [], $selection, $lang );
		}
		if ( self::DIRECTION_PET === $direction ) {
			return self::pet( $tariffs['pet'] ?? [], $selection, $lang );
		}

		return self::result( [ 'direction' ], [], [] );
	}

	private static function grave( array $t, array $sel, string $lang ): array {
		$errors  = [];
		$lines   = [];
		$summary = [ 'direction' => self::DIRECTION_GRAVE ];

		$package = self::find( $t['packages'] ?? [], $sel['package'] ?? null );
		$size    = self::find( $t['sizes'] ?? [], $sel['size'] ?? null );

		if ( ! $package ) {
			$errors[] = 'package';
		}
		if ( ! $size ) {
			$errors[] = 'size';
		}
		if ( $errors ) {
			return self::result( $errors, [], $summary );
		}

		$coef = max( 0.0, (float) $size['coef'] );

		$summary['package'] = $package['id'];
		$summary['size']    = $size['id'];

		$amount  = ! empty( $package['per_size'] ) ? (int) round( (int) $package['price'] * $coef ) : (int) $package['price'];
		$lines[] = [
			'key'    => 'package:' . $package['id'],
			'label'  => self::label( $package, $lang ) . ( ! empty( $package['per_size'] ) ? ' · ' . self::label( $size, $lang ) : '' ),
			'amount' => $amount,
		];

		$extra_ids = array_values( array_unique( array_map( 'strval', (array) ( $sel['extras'] ?? [] ) ) ) );
		$summary['extras'] = [];
		if ( $extra_ids && empty( $package['extras'] ) ) {
			$errors[] = 'extras';
		}
		foreach ( $extra_ids as $extra_id ) {
			$extra = self::find( $t['extras'] ?? [], $extra_id );
			if ( ! $extra ) {
				$errors[] = 'extras';
				continue;
			}
			$summary['extras'][] = $extra['id'];
			$lines[]             = [
				'key'    => 'extra:' . $extra['id'],
				'label'  => self::label( $extra, $lang ),
				'amount' => ! empty( $extra['per_size'] ) ? (int) round( (int) $extra['price'] * $coef ) : (int) $extra['price'],
			];
		}

		$cemetery = self::find( $t['cemeteries'] ?? [], $sel['cemetery'] ?? null );
		if ( ! $cemetery ) {
			$errors[] = 'cemetery';
		} else {
			$summary['cemetery'] = $cemetery['id'];
			$km                  = max( 0, (int) ( $cemetery['km'] ?? 0 ) );
			if ( 'harjumaa' === ( $cemetery['zone'] ?? '' ) && $km > 0 ) {
				$lines[] = [
					'key'    => 'distance',
					'label'  => self::distance_label( $km, $lang ),
					'amount' => $km * max( 0, (int) ( $t['km_price'] ?? 0 ) ),
				];
			}
		}

		return self::result( array_values( array_unique( $errors ) ), $lines, $summary );
	}

	private static function pet( array $t, array $sel, string $lang ): array {
		$errors  = [];
		$lines   = [];
		$summary = [ 'direction' => self::DIRECTION_PET, 'services' => [], 'options' => [] ];

		$service_ids = array_values( array_unique( array_map( 'strval', (array) ( $sel['services'] ?? [] ) ) ) );
		foreach ( $service_ids as $id ) {
			$service = self::find( $t['services'] ?? [], $id );
			if ( ! $service ) {
				$errors[] = 'services';
				continue;
			}
			$summary['services'][] = $service['id'];
			$lines[]               = [
				'key'    => 'service:' . $service['id'],
				'label'  => self::label( $service, $lang ),
				'amount' => (int) $service['price'],
			];
		}
		if ( ! $summary['services'] ) {
			$errors[] = 'services';
		}

		if ( ! empty( $sel['urn'] ) ) {
			$urn = self::find( $t['urns'] ?? [], $sel['urn'] );
			if ( ! $urn ) {
				$errors[] = 'urn';
			} else {
				$summary['urn'] = $urn['id'];
				if ( (int) $urn['price'] > 0 ) {
					$lines[] = [
						'key'    => 'urn:' . $urn['id'],
						'label'  => self::label( $urn, $lang ),
						'amount' => (int) $urn['price'],
					];
				}
			}
		}

		foreach ( array_unique( array_map( 'strval', (array) ( $sel['options'] ?? [] ) ) ) as $id ) {
			$option = self::find( $t['options'] ?? [], $id );
			if ( ! $option ) {
				$errors[] = 'options';
				continue;
			}
			$summary['options'][] = $option['id'];
			$lines[]              = [
				'key'    => 'option:' . $option['id'],
				'label'  => self::label( $option, $lang ),
				'amount' => (int) $option['price'],
			];
		}

		return self::result( array_values( array_unique( $errors ) ), $lines, $summary );
	}

	/** Label in the requested language with a fallback to Estonian. */
	public static function label( array $item, string $lang ): string {
		$label = $item['label'] ?? '';
		if ( is_array( $label ) ) {
			return (string) ( $label[ $lang ] ?? '' ) !== '' ? (string) $label[ $lang ] : (string) ( $label['et'] ?? '' );
		}
		return (string) $label;
	}

	public static function format( int $cents ): string {
		$euros = intdiv( $cents, 100 );
		$rest  = $cents % 100;
		$int   = number_format( $euros, 0, ',', "\u{00A0}" );
		return ( 0 === $rest ? $int : $int . ',' . str_pad( (string) $rest, 2, '0', STR_PAD_LEFT ) ) . "\u{00A0}€";
	}

	private static function distance_label( int $km, string $lang ): string {
		$map = [
			'et' => 'Transport Harjumaal, %d km',
			'ru' => 'Выезд по Харьюмаа, %d км',
			'en' => 'Travel within Harju County, %d km',
		];
		return sprintf( $map[ $lang ] ?? $map['et'], $km );
	}

	private static function find( array $items, $id ): ?array {
		if ( null === $id || '' === $id ) {
			return null;
		}
		foreach ( $items as $item ) {
			if ( isset( $item['id'] ) && (string) $item['id'] === (string) $id && empty( $item['hidden'] ) ) {
				return $item;
			}
		}
		return null;
	}

	private static function result( array $errors, array $lines, array $summary ): array {
		$total = 0;
		foreach ( $lines as $line ) {
			$total += (int) $line['amount'];
		}
		return [
			'valid'   => ! $errors,
			'errors'  => $errors,
			'lines'   => $lines,
			'total'   => $errors ? 0 : $total,
			'summary' => $summary,
		];
	}
}
