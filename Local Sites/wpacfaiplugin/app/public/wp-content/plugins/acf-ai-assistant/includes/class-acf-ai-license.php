<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACF_AI_License {
	private $license_key;
	private $api_url            = 'https://your-licensing-server.com/api/v1';
	private static $instance    = null;
	private $free_tier_limit    = 20;
	private $subscription_price = 20; // $20/month
	private $license_data;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->license_key = get_option( 'acf_ai_license_key' );
		add_action( 'admin_menu', array( $this, 'add_license_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'check_license_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_usage_notices' ) );
		add_action( 'admin_init', array( $this, 'maybe_reset_monthly_usage' ) );
	}

	public function add_license_menu() {
		add_submenu_page(
			'edit.php?post_type=acf-field-group',
			__( 'ACF AI License', 'acf-ai-assistant' ),
			__( 'AI License', 'acf-ai-assistant' ),
			'manage_options',
			'acf-ai-license',
			array( $this, 'render_license_page' )
		);
	}

	public function register_settings() {
		register_setting( 'acf_ai_license', 'acf_ai_license_key' );
	}

	public function render_license_page() {
		?>
		<div class="wrap">
			<h1><?php _e( 'ACF AI Assistant License', 'acf-ai-assistant' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'acf_ai_license' );
				do_settings_sections( 'acf_ai_license' );
				?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'License Key', 'acf-ai-assistant' ); ?></th>
						<td>
							<input type="text" 
									name="acf_ai_license_key" 
									value="<?php echo esc_attr( get_option( 'acf_ai_license_key' ) ); ?>" 
									class="regular-text"
									placeholder="<?php _e( 'Enter your license key', 'acf-ai-assistant' ); ?>"
							/>
							<?php submit_button( __( 'Save License', 'acf-ai-assistant' ) ); ?>
							<?php if ( $this->license_key ) : ?>
								<button type="button" class="button" id="verify_license">
									<?php _e( 'Verify License', 'acf-ai-assistant' ); ?>
								</button>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<?php
	}

	public function check_license_notice() {
		if ( ! $this->is_license_valid() && current_user_can( 'manage_options' ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						__( 'Please %1$sactivate your ACF AI Assistant license%2$s to enable AI features.', 'acf-ai-assistant' ),
						'<a href="' . admin_url( 'edit.php?post_type=acf-field-group&page=acf-ai-license' ) . '">',
						'</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	public function is_license_valid() {
		// Always valid on development environments
		if ( $this->is_development() ) {
			return true;
		}

		// Check if user has active subscription
		$subscription_status = get_option( 'acf_ai_subscription_status' );
		if ( $subscription_status === 'active' ) {
			return true;
		}

		// Check free tier usage
		return $this->get_monthly_requests() < $this->free_tier_limit;
	}

	public function can_make_request() {
		if ( $this->is_development() ) {
			return true;
		}

		if ( get_option( 'acf_ai_subscription_status' ) === 'active' ) {
			return true;
		}

		return $this->get_monthly_requests() < $this->free_tier_limit;
	}

	public function increment_usage() {
		if ( $this->is_development() ) {
			return;
		}

		$current_usage = $this->get_monthly_requests();
		update_option( 'acf_ai_monthly_requests', $current_usage + 1 );

		// Show upgrade notice when nearing limit
		if ( $current_usage >= ( $this->free_tier_limit - 5 ) && $current_usage < $this->free_tier_limit ) {
			add_action( 'admin_notices', array( $this, 'show_upgrade_notice' ) );
		}
	}

	public function get_monthly_requests() {
		return (int) get_option( 'acf_ai_monthly_requests', 0 );
	}

	public function get_remaining_requests() {
		if ( $this->is_development() || get_option( 'acf_ai_subscription_status' ) === 'active' ) {
			return PHP_INT_MAX;
		}

		$used = $this->get_monthly_requests();
		return max( 0, $this->free_tier_limit - $used );
	}

	public function maybe_reset_monthly_usage() {
		$last_reset = get_option( 'acf_ai_last_reset_date' );
		if ( ! $last_reset || strtotime( $last_reset ) < strtotime( 'first day of this month' ) ) {
			update_option( 'acf_ai_monthly_requests', 0 );
			update_option( 'acf_ai_last_reset_date', date( 'Y-m-d' ) );
		}
	}

	public function show_usage_notices() {
		if ( $this->is_development() ) {
			return;
		}

		$subscription_status = get_option( 'acf_ai_subscription_status' );
		if ( $subscription_status === 'active' ) {
			return;
		}

		$remaining = $this->get_remaining_requests();
		if ( $remaining <= 5 ) {
			$message = sprintf(
				'You have %d free AI requests remaining this month. <a href="%s">Upgrade to Premium</a> for unlimited requests.',
				$remaining,
				$this->get_subscription_url()
			);
			echo '<div class="notice notice-warning"><p>' . wp_kses_post( $message ) . '</p></div>';
		}

		if ( $remaining === 0 ) {
			$message = sprintf(
				'You have used all your free AI requests for this month. <a href="%s">Upgrade to Premium</a> for just $%d/month to continue using AI features.',
				$this->get_subscription_url(),
				$this->subscription_price
			);
			echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
		}
	}

	private function is_development() {
		return (
			in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ) ) || // Localhost check
			( isset( $_SERVER['HTTP_HOST'] ) && strpos( $_SERVER['HTTP_HOST'], '.local' ) !== false ) || // .local domain
			( isset( $_SERVER['HTTP_HOST'] ) && strpos( $_SERVER['HTTP_HOST'], 'localhost' ) !== false ) // localhost domain
		);
	}

	public function get_subscription_url() {
		return add_query_arg(
			array(
				'page' => 'acf-ai-settings',
				'tab'  => 'premium',
			),
			admin_url( 'admin.php' )
		);
	}

	private function verify_license() {
		$response = wp_remote_post(
			$this->api_url . '/verify',
			array(
				'body' => array(
					'license_key' => $this->license_key,
					'site_url'    => home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'invalid';
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		return isset( $data->status ) ? $data->status : 'invalid';
	}

	public function get_remaining_tokens() {
		if ( ! $this->is_license_valid() ) {
			return 0;
		}

		$response = wp_remote_get(
			$this->api_url . '/tokens',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->license_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		return isset( $data->remaining_tokens ) ? $data->remaining_tokens : 0;
	}

	public function is_premium_active() {
		$license = get_option('acf_ai_license');
		return $license && $license['status'] === 'valid';
	}

	public function validate_license($key) {
		// Implementation for license validation
	}

	public function get_feature_list() {
		return [
			'ai_images' => [
				'name' => __('AI Image Generation', 'acf-ai-assistant'),
				'available' => $this->is_premium_active()
			],
			'seo_analysis' => [
				'name' => __('SEO Optimization', 'acf-ai-assistant'),
				'available' => $this->is_premium_active()
			],
			'content_personalization' => [
				'name' => __('Audience Personalization', 'acf-ai-assistant'),
				'available' => $this->is_premium_active()
			],
			'translation' => [
				'name' => __('Multi-language Support', 'acf-ai-assistant'),
				'available' => $this->is_premium_active()
			]
		];
	}

	public function get_usage_stats() {
		return [
			'remaining_credits' => $this->get_remaining_credits(),
			'api_calls' => $this->get_api_usage(),
			'image_generations' => $this->get_image_usage()
		];
	}
}
