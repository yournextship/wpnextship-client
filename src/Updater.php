<?php
/**
 * WPNextShip Updater
 *
 * @package WPNextShip
 */

if ( ! class_exists( 'WPNextShip_Updater' ) ) {

	/**
	 * Class WPNextShip_Updater
	 */
	class WPNextShip_Updater {

		/**
		 * Arguments for the specific product.
		 *
		 * @var array
		 */
		private $args;

		/**
		 * License Option Name.
		 *
		 * @var string
		 */
		private $option_name;

		/**
		 * Constructor.
		 *
		 * @param array $args Plugin arguments.
		 */
		public function __construct( $args ) {
			$this->args        = $args;
			$this->option_name = '_wpnextship_license_' . $args['slug'];

			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		}

		/**
		 * Check for updates.
		 *
		 * @param object $transient Transient object.
		 * @return object Transient object.
		 */
		public function check_update( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$plugin_slug = plugin_basename( $this->args['file'] );

			// Get the license key.
			$license_data = get_option( $this->option_name, array() );
			if ( empty( $license_data['key'] ) || 'active' !== $license_data['status'] ) {
				if ( isset( $transient->response[ $plugin_slug ] ) ) {
					unset( $transient->response[ $plugin_slug ] );
				}
				return $transient;
			}

			// Remote request.
			$response = wp_remote_post(
				trailingslashit( WPNEXTSHIP_API_URL ) . 'update',
				array(
					'timeout' => 15,
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
					'body'    => wp_json_encode ( array(
						'license_key' => $license_data['key'],
						'url'         => site_url(),
						'slug'        => $this->args['slug'],
						'version'     => $this->args['version'],
					)),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $transient;
			}

            $response_code = wp_remote_retrieve_response_code( $response );
            $body          = wp_remote_retrieve_body( $response );
            $data          = json_decode( $body, true );

			// If the response code is 403, it means the license is invalid, expired, or revoked.
			if ( 403 === $response_code ) {
				$license_data = get_option( $this->option_name, array() );
				$license_data['status'] = 'inactive';
				update_option( $this->option_name, $license_data );

				if ( isset( $transient->response[ $plugin_slug ] ) ) {
					unset( $transient->response[ $plugin_slug ] );
				}

				return $transient;
			}

			if ( 200 !== $response_code ) {
				return $transient;
			}

			// Get the current installed version from the transient.
			$current_version = isset( $transient->checked[$plugin_slug] ) ? $transient->checked[$plugin_slug] : $this->args['version'];

			if ( ! empty( $data['new_version'] ) && version_compare( $data['new_version'], $current_version, '>' ) ) {

                $transient->response[$plugin_slug] = (object) array(
                    'new_version' => $data['new_version'],
                    'package'     => $data['package'],
                    'slug'        => $plugin_slug,
                    'url'         => $data['url']
                );
			}

			return $transient;
		}
	}
}
