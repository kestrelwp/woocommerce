<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;
/**
 * Payments settings utilities class.
 */
class Utils {
	/**
	 * Apply order mappings to a base order map.
	 *
	 * @param array $base_map     The base order map.
	 * @param array $new_mappings The order mappings to apply.
	 *                            This can be a full or partial list of the base one,
	 *                            but it can also contain (only) new IDs and their orders.
	 *
	 * @return array The updated base order map, normalized.
	 */
	public static function order_map_apply_mappings( array $base_map, array $new_mappings ): array {
		// Make sure the base map is sorted ascending by their order values.
		// We don't normalize first because the order values have meaning.
		asort( $base_map );

		$updated_map = $base_map;
		// Apply the new mappings in the order they were given.
		foreach ( $new_mappings as $id => $order ) {
			// If the ID is not in the base map, we ADD it at the desired order. Otherwise, we MOVE it.
			if ( ! isset( $base_map[ $id ] ) ) {
				$updated_map = self::order_map_add_at_order( $updated_map, $id, $order );
				continue;
			}

			$updated_map = self::order_map_move_at_order( $updated_map, $id, $order );
		}

		return self::order_map_normalize( $updated_map );
	}

	/**
	 * Move an id at a specific order in an order map.
	 *
	 * This method is used to simulate the behavior of a drag&drop sorting UI:
	 * - When moving an id down, all the ids with an order equal or lower than the desired order
	 *   but equal or higher than the current order are decreased by 1.
	 * - When moving an id up, all the ids with an order equal or higher than the desired order
	 *   but equal or lower than the current order are increased by 1.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to place.
	 * @param int    $order     The order at which to place the id.
	 *
	 * @return array The updated order map. This map is not normalized.
	 */
	public static function order_map_move_at_order( array $order_map, string $id, int $order ): array {
		// If the id is not in the order map, return the order map as is.
		if ( ! isset( $order_map[ $id ] ) ) {
			return $order_map;
		}

		// If the id is already at the desired order, return the order map as is.
		if ( $order_map[ $id ] === $order ) {
			return $order_map;
		}

		// If there is no id at the desired order, just place the id there.
		if ( ! in_array( $order, $order_map, true ) ) {
			$order_map[ $id ] = $order;

			return $order_map;
		}

		// We apply the normal behavior of a drag&drop sorting UI.
		$existing_order = $order_map[ $id ];
		if ( $order > $existing_order ) {
			// Moving down.
			foreach ( $order_map as $key => $value ) {
				if ( $value <= $order && $value >= $existing_order ) {
					--$order_map[ $key ];
				}
			}
		} else {
			// Moving up.
			foreach ( $order_map as $key => $value ) {
				if ( $value >= $order && $value <= $existing_order ) {
					++$order_map[ $key ];
				}
			}
		}

		// Place the id at the desired order.
		$order_map[ $id ] = $order;

		return $order_map;
	}

	/**
	 * Place an id at a specific order in an order map.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to place.
	 * @param int    $order     The order at which to place the id.
	 *
	 * @return array The updated order map.
	 */
	public static function order_map_place_at_order( array $order_map, string $id, int $order ): array {
		// If the id is already at the desired order, return the order map as is.
		if ( isset( $order_map[ $id ] ) && $order_map[ $id ] === $order ) {
			return $order_map;
		}

		// If there is no id at the desired order, just place the id there.
		if ( ! in_array( $order, $order_map, true ) ) {
			$order_map[ $id ] = $order;

			return $order_map;
		}

		// Bump the order of everything with an order equal or higher than the desired order.
		foreach ( $order_map as $key => $value ) {
			if ( $value >= $order ) {
				++$order_map[ $key ];
			}
		}

		// Place the id at the desired order.
		$order_map[ $id ] = $order;

		return $order_map;
	}

	/**
	 * Add an id to a specific order in an order map.
	 *
	 * @param array  $order_map The order map.
	 * @param string $id        The id to move.
	 * @param int    $order     The order to move the id to.
	 *
	 * @return array The updated order map. If the id is already in the order map, the order map is returned as is.
	 */
	public static function order_map_add_at_order( array $order_map, string $id, int $order ): array {
		// If the id is in the order map, return the order map as is.
		if ( isset( $order_map[ $id ] ) ) {
			return $order_map;
		}

		return self::order_map_place_at_order( $order_map, $id, $order );
	}

	/**
	 * Normalize an order map.
	 *
	 * Sort the order map by the order and ensure the order values start from 0 and are consecutive.
	 *
	 * @param array $order_map The order map.
	 *
	 * @return array The normalized order map.
	 */
	public static function order_map_normalize( array $order_map ): array {
		asort( $order_map );

		return array_flip( array_keys( $order_map ) );
	}

	/**
	 * Change the minimum order of an order map.
	 *
	 * @param array $order_map     The order map.
	 * @param int   $new_min_order The new minimum order.
	 *
	 * @return array The updated order map.
	 */
	public static function order_map_change_min_order( array $order_map, int $new_min_order ): array {
		// Sanity checks.
		if ( empty( $order_map ) ) {
			return array();
		}

		$updated_map = array();
		$bump        = $new_min_order - min( $order_map );
		foreach ( $order_map as $id => $order ) {
			$updated_map[ $id ] = $order + $bump;
		}

		asort( $updated_map );

		return $updated_map;
	}
}
