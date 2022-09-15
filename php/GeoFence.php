<?php
/**
 * Map data functionality.
 * Author: Zachary K. Watkins <zwatkins.it@gmail.com>
 */

class GeoFence {

	/**
	 * Required constant model for the geofence. This must have the array keys used by
	 * geofence data in all parts of this Class in order to ensure correct operation.
	 *
	 * The business use will likely be to determine if an address's geolocation API
	 * coordinate is within a geographic region.
	 *
	 * @see http://en.wikipedia.org/wiki/Point_in_polygon
	 *
	 * @var array $geofence {
	 *     The latitude and longitude coordinates of the Home and Ranch service area.
	 *
	 *     @param array $vertices_x The X coordinates of the vertices of the polygon.
	 *     @param array $vertices_y The Y coordinates of the vertices of the polygon.
	 * }
	 */
	private $geofence = array(
		'vertices_x' => array(),
		'vertices_y' => array(),
	);

	/**
	 * The class constructor method.
	 *
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Validate the provided geofence data and return it if valid.
	 * Return false if not valid.
	 *
	 * @param mixed $geofence The geofence array, or invalid data.
	 *
	 * @return array|bool
	 */
	private function validate_geofence( $geofence ) {

		// Assume valid and check for invalid conditions.
		$valid     = true;

		// Variables for error detection and messaging.
		$message        = '';
		$type           = gettype( $geofence );
		$model_geofence = $this->geofence;

		// Use PHP constants to show the full class and function name in the custom error log message.
		$reference = __CLASS__ . '::' . __FUNCTION__ . '( geofence )';

		// Check conditions.
		if ( ! is_array( $geofence ) ) {
			$valid   = false;
			$message = "The $reference method requires an array, but a $type was given.";
		} else {
			// Check for each array key in the $geofence class variable.
			$geofence_keys = array_keys( $model_geofence );
			$missing_keys  = array();
			foreach ( $geofence_keys as $geofence_key ) {
				if ( ! array_key_exists( $geofence_key, $geofence ) ) {
					$missing_keys[] = $geofence_key;
				}
			}
			if ( ! empty( $missing_keys ) ) {
				$valid         = false;
				$required_keys = implode( ', ', $geofence_keys );
				$plural        = count( $missing_keys ) === 1 ? ' is' : 's are';
				$missing_keys  = implode( ', ', $missing_keys );
				$message       = "The $reference method requires the geofence parameter to have these keys: $required_keys. Instead, its key$plural $missing_keys.";
			} else {
				$mismatched_types = array();
				foreach ( $geofence as $key => $value ) {
					$model_type = gettype( $model_geofence[ $key ] );
					$type       = gettype( $value );
					if ( $type !== $model_type ) {
						$mismatched_types[] = "{$key}=>({$type} instead of {$model_type})";
					}
				}
				if ( ! empty( $mismatched_types ) ) {
					$valid            = false;
					$mismatched_types = implode( ', ', $mismatched_types );
					$message          = "The $reference method requires the geofence array parameter to have properties of specific variable types. Instead, it received a mismatched set: $mismatched_types";
				}
			}
		}

		if ( ! $valid ) {
			if ( ! $message ) {
				$message = "The $reference method encountered a scenario that caused an error and was not anticipated to do so.";
			}
			// Send an error message to the appropriate destination.
			$phrets_error_log = defined( 'PHRETS_ERROR_LOG' ) && ! empty( constant( 'PHRETS_ERROR_LOG' ) ) ? constant( 'PHRETS_ERROR_LOG' ) : get_option( 'phrets_log_location' );
			if ( $phrets_error_log ) {
				error_log( $message, 0, $phrets_error_log );
			} elseif ( defined( 'PHP_ERROR_LOG' ) ) {
				error_log( $message, 0, constant( 'PHP_ERROR_LOG' ) );
			} else {
				error_log( $message );
			}

			return false;
		} else {
			return $geofence;
		}
	}

	/**
	 * Set the default geofence coordinates.
	 *
	 * @param array $geofence {
	 *     The latitude and longitude coordinates of the Home and Ranch service area.
	 *
	 *     @param array $vertices_x The X coordinates of the vertices of the polygon.
	 *     @param array $vertices_y The Y coordinates of the vertices of the polygon.
	 * }
	 *
	 * @return void
	 */
	public function set_geofence( $geofence = array() ) {

		$geofence = $this->validate_geofence( $geofence );

		if ( false !== $geofence ) {
			$this->geofence = $geofence;
		}

	}

	/**
  	 * Determine if a coordinate is in a geofence.
	 *
	 * @param int $longitude The coordinate's X or longitude value.
	 * @param int $latitude  The coordinate's Y or latitude value.
	 *
	 * @return bool
	 */
	public function in_geofence( $longitude, $latitude, $geofence = array() ) {

		$vertices_x     = $this->geofence['vertices_x'];
		$vertices_y     = $this->geofence['vertices_y'];
		$points_polygon = count( $this->geofence['vertices_x'] );

		/**
		 * @see https://stackoverflow.com/questions/5065039/find-point-in-polygon-php
		 */
		$i = $j = $c = 0;

		for ( $i = 0, $j = $points_polygon-1 ; $i < $points_polygon; $j = $i++ ) {
			if (
				(($vertices_y[$i] > $latitude != ($vertices_y[$j] > $latitude)) &&
				($longitude < ($vertices_x[$j] - $vertices_x[$i]) * ($latitude - $vertices_y[$i]) / ($vertices_y[$j] - $vertices_y[$i]) + $vertices_x[$i]) )
			)
					$c = !$c;
		}

		return $c;
		/**
		 * End reference.
		 */

	}
}
