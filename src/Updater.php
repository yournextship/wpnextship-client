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

			// Get the license key.
			$license_data = get_option( $this->option_name, array() );
			if ( empty( $license_data['key'] ) || 'active' !== $license_data['status'] ) {
				return $transient;
			}

			// Remote request.
			$response = wp_remote_post(
				trailingslashit( WPNEXTSHIP_API_URL ) . 'update',
				array(
					'timeout' => 15,
					'body'    => array(
						'license_key' => $license_data['key'],
						'url'         => site_url(),
						'slug'        => $this->args['slug'],
						'version'     => $this->args['version'],
					),
				)
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return $transient;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( ! empty( $data['new_version'] ) && version_compare( $data['new_version'], $this->args['version'], '>' ) ) {
				$obj              = new stdClass();
				$obj->slug        = $this->args['slug'];
				$obj->plugin      = $this->args['file'];
				$obj->new_version = $data['new_version'];
				$obj->url         = $data['url']; // Info URL.
				$obj->package     = $data['package']; // Download URL.

				$transient->response[ $this->args['file'] ] = $obj;
			}

			return $transient;
		}
	}
}
