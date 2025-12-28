<?php
/**
 * WPNextShip SDK Loader
 *
 * @package WPNextShip
 */

define( 'WPNEXTSHIP_API_URL', 'https://app.wpnextship.com/api/v1' );

if ( ! class_exists( 'WPNextShip_SDK_Manager' ) ) {

	/**
	 * Class WPNextShip_SDK_Manager
	 */
	class WPNextShip_SDK_Manager {

		/**
		 * Registered products.
		 *
		 * @var array
		 */
		public static $products = array();

		/**
		 * Initialize the SDK for a plugin.
		 *
		 * @param array $args Arguments (slug, version, api_url, file).
		 */
		public static function init( $args ) {
			$defaults = array(
				'slug'    => '',
				'version' => '',
				'file'    => '',
			);

			$args = array_merge( $defaults, $args );

			if ( empty( $args['slug'] ) ) {
				return;
			}

			// Store the product details.
			self::$products[ $args['slug'] ] = $args;

			// Include required files.
			if ( ! class_exists( 'WPNextShip_Licensing' ) ) {
				require_once __DIR__ . '/src/Licensing.php';
			}
			if ( ! class_exists( 'WPNextShip_Updater' ) ) {
				require_once __DIR__ . '/src/Updater.php';
			}

			// Instantiate classes for this product.
			new WPNextShip_Licensing( $args );
			new WPNextShip_Updater( $args );
		}
	}
}
