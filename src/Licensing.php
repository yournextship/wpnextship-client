<?php
/**
 * WPNextShip Licensing
 *
 * @package WPNextShip
 */

if ( ! class_exists( 'WPNextShip_Licensing' ) ) {

	/**
	 * Class WPNextShip_Licensing
	 */
	class WPNextShip_Licensing {

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

			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'handle_activation' ) );
		}

		/**
		 * Add the admin menu.
		 */
		public function add_menu() {
			$capability = 'manage_options';
			$title      = __( 'License', 'wpnextship' );
			$slug       = $this->args['slug'] . '-license';

			if ( ! empty( $this->args['parent_slug'] ) ) {
				add_submenu_page(
					$this->args['parent_slug'],
					$title,
					$title,
					$capability,
					$slug,
					array( $this, 'render_page' )
				);
			} else {
				add_menu_page(
					$title,
					$title,
					$capability,
					$slug,
					array( $this, 'render_page' )
				);
			}
		}

		/**
		 * Handle License Activation.
		 */
		public function handle_activation() {
			if ( ! isset( $_POST['wpnextship_action'] ) || 'activate_license' !== $_POST['wpnextship_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			if ( ! isset( $_POST['_wpnextship_license_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnextship_license_nonce'] ) ), 'wpnextship_activate_license' ) ) {
				return;
			}

			$license_key    = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
			$customer_email = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';

			if ( empty( $license_key ) || empty( $customer_email ) ) {
				add_settings_error( 'wpnextship_license', 'invalid_input', __( 'Please provide both license key and email.', 'wpnextship' ), 'error' );
				return;
			}

			$response = wp_remote_post(
				trailingslashit( WPNEXTSHIP_API_URL ) . 'activate',
				array(
					'timeout' => 15,
					'body'    => array(
						'license_key'    => $license_key,
						'customer_email' => $customer_email,
						'domain'         => site_url(),
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				add_settings_error( 'wpnextship_license', 'request_failed', $response->get_error_message(), 'error' );
				return;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$body          = wp_remote_retrieve_body( $response );
			$data          = json_decode( $body, true );

			if ( 200 !== $response_code || empty( $data['activated'] ) ) {
				$message = isset( $data['error'] ) ? $data['error'] : __( 'Activation failed. Please try again.', 'wpnextship' );
				add_settings_error( 'wpnextship_license', 'activation_failed', $message, 'error' );
				return;
			}

			// Save license data.
			update_option(
				$this->option_name,
				array(
					'key'    => $license_key,
					'email'  => $customer_email,
					'status' => 'active',
				)
			);

			add_settings_error( 'wpnextship_license', 'activation_success', __( 'License activated successfully.', 'wpnextship' ), 'updated' );
		}

		/**
		 * Render the settings page.
		 */
		public function render_page() {
			$license_data = get_option( $this->option_name, array() );
			$status       = isset( $license_data['status'] ) && 'active' === $license_data['status'] ? 'active' : 'inactive';
			$key          = isset( $license_data['key'] ) ? $license_data['key'] : '';
			$email        = isset( $license_data['email'] ) ? $license_data['email'] : '';
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'License Management', 'wpnextship' ); ?></h1>
				<?php settings_errors( 'wpnextship_license' ); ?>
				
				<div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
					<h2>
						<?php esc_html_e( 'License Status', 'wpnextship' ); ?>: 
						<span class="wpnextship-badge wpnextship-badge-<?php echo esc_attr( $status ); ?>" style="background: <?php echo 'active' === $status ? '#46b450' : '#dc3232'; ?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 12px; vertical-align: middle;">
							<?php echo 'active' === $status ? esc_html__( 'Active', 'wpnextship' ) : esc_html__( 'Inactive', 'wpnextship' ); ?>
						</span>
					</h2>
					<p><?php esc_html_e( 'Enter your license key and email to activate automatic updates and support.', 'wpnextship' ); ?></p>
					
					<form method="post" action="">
						<?php wp_nonce_field( 'wpnextship_activate_license', '_wpnextship_license_nonce' ); ?>
						<input type="hidden" name="wpnextship_action" value="activate_license">
						
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="license_key"><?php esc_html_e( 'License Key', 'wpnextship' ); ?></label></th>
								<td>
									<input name="license_key" type="text" id="license_key" value="<?php echo esc_attr( $key ); ?>" class="regular-text" required />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="customer_email"><?php esc_html_e( 'Customer Email', 'wpnextship' ); ?></label></th>
								<td>
									<input name="customer_email" type="email" id="customer_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" required />
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Activate License', 'wpnextship' ); ?>">
						</p>
					</form>
				</div>
			</div>
			<?php
		}
	}
}
