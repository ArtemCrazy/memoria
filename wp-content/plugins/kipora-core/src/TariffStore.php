<?php
/**
 * Tariffs live in one option as a tree. The admin page edits it,
 * the calculator and checkout read it.
 */

namespace Kipora;

final class TariffStore {

	public const OPTION = 'kipora_tariffs';

	public static function get(): array {
		$stored = get_option( self::OPTION );
		return is_array( $stored ) ? $stored : self::defaults();
	}

	public static function save( array $tree ): void {
		update_option( self::OPTION, self::sanitize( $tree ), false );
	}

	/**
	 * Placeholder prices until the client sends the real price list.
	 * Every number here is an example and is replaced in the admin.
	 */
	public static function defaults(): array {
		$l = static fn( string $et, string $ru, string $en ): array => [ 'et' => $et, 'ru' => $ru, 'en' => $en ];

		return [
			'grave' => [
				'packages'   => [
					[ 'id' => 'cleaning', 'label' => $l( 'Ühekordne platsi koristus', 'Разовая уборка участка', 'One-time plot cleaning' ), 'price' => 4500, 'per_size' => true, 'extras' => true ],
					[ 'id' => 'season', 'label' => $l( 'Hooajaline hooldus', 'Сезонный уход', 'Seasonal care' ), 'price' => 24000, 'per_size' => true, 'extras' => true ],
					[ 'id' => 'flowers', 'label' => $l( 'Lillede ja küünla viimine', 'Доставка и возложение цветов со свечой', 'Flowers and candle delivery' ), 'price' => 3500, 'per_size' => false, 'extras' => false ],
				],
				'sizes'      => [
					[ 'id' => 'single', 'label' => $l( 'Üksikplats', 'Одинарный', 'Single' ), 'coef' => 1 ],
					[ 'id' => 'double', 'label' => $l( 'Kaksikplats', 'Двойной', 'Double' ), 'coef' => 1.5 ],
					[ 'id' => 'family', 'label' => $l( 'Perekonnaplats', 'Семейный', 'Family' ), 'coef' => 2 ],
				],
				'extras'     => [
					[ 'id' => 'fence', 'label' => $l( 'Aia värvimine', 'Покраска ограды', 'Fence painting' ), 'price' => 6000, 'per_size' => true ],
					[ 'id' => 'stone', 'label' => $l( 'Kivi puhastus ja töötlus', 'Обработка камня', 'Headstone treatment' ), 'price' => 4000, 'per_size' => false ],
					[ 'id' => 'planting', 'label' => $l( 'Taimede istutamine', 'Посадка растений', 'Planting' ), 'price' => 3000, 'per_size' => true ],
				],
				'cemeteries' => [
					[ 'id' => 'metsakalmistu', 'name' => 'Metsakalmistu', 'zone' => 'tallinn', 'km' => 0 ],
					[ 'id' => 'rahumae', 'name' => 'Rahumäe kalmistu', 'zone' => 'tallinn', 'km' => 0 ],
					[ 'id' => 'parnamae', 'name' => 'Pärnamäe kalmistu', 'zone' => 'tallinn', 'km' => 0 ],
					[ 'id' => 'siselinna', 'name' => 'Siselinna kalmistu', 'zone' => 'tallinn', 'km' => 0 ],
					[ 'id' => 'liiva', 'name' => 'Liiva kalmistu', 'zone' => 'tallinn', 'km' => 0 ],
					[ 'id' => 'keila', 'name' => 'Keila kalmistu', 'zone' => 'harjumaa', 'km' => 28 ],
					[ 'id' => 'joelahtme', 'name' => 'Jõelähtme kalmistu', 'zone' => 'harjumaa', 'km' => 25 ],
				],
				'km_price'   => 80,
			],
			'pet'   => [
				'services' => [
					[ 'id' => 'cremation', 'label' => $l( 'Tuhastamine', 'Кремация', 'Cremation' ), 'price' => 15000 ],
					[ 'id' => 'transport', 'label' => $l( 'Transport', 'Транспортировка', 'Transport' ), 'price' => 4000 ],
				],
				'urns'     => [
					[ 'id' => 'none', 'label' => $l( 'Ilma urnita', 'Без урны', 'No urn' ), 'price' => 0 ],
					[ 'id' => 'wood', 'label' => $l( 'Puidust urn', 'Деревянная урна', 'Wooden urn' ), 'price' => 5500 ],
					[ 'id' => 'ceramic', 'label' => $l( 'Keraamiline urn', 'Керамическая урна', 'Ceramic urn' ), 'price' => 7500 ],
				],
				'options'  => [
					[ 'id' => 'engraving', 'label' => $l( 'Graveering', 'Гравировка', 'Engraving' ), 'price' => 2500 ],
					[ 'id' => 'memory_page', 'label' => $l( 'Digitaalne mälestusleht', 'Цифровая страница памяти', 'Digital memorial page' ), 'price' => 2000 ],
				],
			],
		];
	}

	/** Normalises the tree coming from the admin form. */
	public static function sanitize( array $tree ): array {
		$label = static function ( $value ): array {
			$value = is_array( $value ) ? $value : [];
			return [
				'et' => sanitize_text_field( (string) ( $value['et'] ?? '' ) ),
				'ru' => sanitize_text_field( (string) ( $value['ru'] ?? '' ) ),
				'en' => sanitize_text_field( (string) ( $value['en'] ?? '' ) ),
			];
		};
		$id    = static fn( $value ): string => sanitize_key( (string) $value );
		$cents = static fn( $value ): int => max( 0, (int) round( (float) str_replace( ',', '.', (string) $value ) * 100 ) );
		$rows  = static function ( $list, callable $map ): array {
			$out  = [];
			$seen = [];
			foreach ( is_array( $list ) ? $list : [] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$item = $map( $row );
				if ( '' === $item['id'] || isset( $seen[ $item['id'] ] ) ) {
					continue;
				}
				$seen[ $item['id'] ] = true;
				$out[]               = $item;
			}
			return $out;
		};

		$grave = $tree['grave'] ?? [];
		$pet   = $tree['pet'] ?? [];

		return [
			'grave' => [
				'packages'   => $rows( $grave['packages'] ?? [], static fn( $r ) => [
					'id'       => $id( $r['id'] ?? '' ),
					'label'    => $label( $r['label'] ?? [] ),
					'price'    => $cents( $r['price'] ?? 0 ),
					'per_size' => ! empty( $r['per_size'] ),
					'extras'   => ! empty( $r['extras'] ),
					'hidden'   => ! empty( $r['hidden'] ),
				] ),
				'sizes'      => $rows( $grave['sizes'] ?? [], static fn( $r ) => [
					'id'     => $id( $r['id'] ?? '' ),
					'label'  => $label( $r['label'] ?? [] ),
					'coef'   => max( 0, round( (float) str_replace( ',', '.', (string) ( $r['coef'] ?? 1 ) ), 3 ) ),
					'hidden' => ! empty( $r['hidden'] ),
				] ),
				'extras'     => $rows( $grave['extras'] ?? [], static fn( $r ) => [
					'id'       => $id( $r['id'] ?? '' ),
					'label'    => $label( $r['label'] ?? [] ),
					'price'    => $cents( $r['price'] ?? 0 ),
					'per_size' => ! empty( $r['per_size'] ),
					'hidden'   => ! empty( $r['hidden'] ),
				] ),
				'cemeteries' => $rows( $grave['cemeteries'] ?? [], static fn( $r ) => [
					'id'     => $id( $r['id'] ?? '' ),
					'name'   => sanitize_text_field( (string) ( $r['name'] ?? '' ) ),
					'zone'   => 'harjumaa' === ( $r['zone'] ?? '' ) ? 'harjumaa' : 'tallinn',
					'km'     => max( 0, (int) ( $r['km'] ?? 0 ) ),
					'hidden' => ! empty( $r['hidden'] ),
				] ),
				'km_price'   => $cents( $grave['km_price'] ?? 0 ),
			],
			'pet'   => [
				'services' => $rows( $pet['services'] ?? [], static fn( $r ) => [
					'id'     => $id( $r['id'] ?? '' ),
					'label'  => $label( $r['label'] ?? [] ),
					'price'  => $cents( $r['price'] ?? 0 ),
					'hidden' => ! empty( $r['hidden'] ),
				] ),
				'urns'     => $rows( $pet['urns'] ?? [], static fn( $r ) => [
					'id'     => $id( $r['id'] ?? '' ),
					'label'  => $label( $r['label'] ?? [] ),
					'price'  => $cents( $r['price'] ?? 0 ),
					'hidden' => ! empty( $r['hidden'] ),
				] ),
				'options'  => $rows( $pet['options'] ?? [], static fn( $r ) => [
					'id'     => $id( $r['id'] ?? '' ),
					'label'  => $label( $r['label'] ?? [] ),
					'price'  => $cents( $r['price'] ?? 0 ),
					'hidden' => ! empty( $r['hidden'] ),
				] ),
			],
		];
	}

	/**
	 * Public view for the calculator: visible rows only, labels in one language.
	 */
	public static function public_view( string $lang ): array {
		$t    = self::get();
		$pick = static function ( array $rows, array $keys ) use ( $lang ): array {
			$out = [];
			foreach ( $rows as $row ) {
				if ( ! empty( $row['hidden'] ) ) {
					continue;
				}
				$item = [ 'id' => $row['id'], 'label' => isset( $row['label'] ) ? Pricing::label( $row, $lang ) : ( $row['name'] ?? '' ) ];
				foreach ( $keys as $key ) {
					$item[ $key ] = $row[ $key ] ?? null;
				}
				$out[] = $item;
			}
			return $out;
		};

		return [
			'grave' => [
				'packages'   => $pick( $t['grave']['packages'] ?? [], [ 'price', 'per_size', 'extras' ] ),
				'sizes'      => $pick( $t['grave']['sizes'] ?? [], [ 'coef' ] ),
				'extras'     => $pick( $t['grave']['extras'] ?? [], [ 'price', 'per_size' ] ),
				'cemeteries' => $pick( $t['grave']['cemeteries'] ?? [], [ 'zone', 'km' ] ),
				'km_price'   => (int) ( $t['grave']['km_price'] ?? 0 ),
			],
			'pet'   => [
				'services' => $pick( $t['pet']['services'] ?? [], [ 'price' ] ),
				'urns'     => $pick( $t['pet']['urns'] ?? [], [ 'price' ] ),
				'options'  => $pick( $t['pet']['options'] ?? [], [ 'price' ] ),
			],
		];
	}
}
