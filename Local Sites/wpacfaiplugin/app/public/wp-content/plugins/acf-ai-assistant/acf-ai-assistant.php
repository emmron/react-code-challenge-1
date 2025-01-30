<?php
/**
 * ACF AI Assistant Plugin
 *
 * @package ACF_AI_Assistant
 * @version 1.0.0
 */

/**
 * Plugin Name: ACF AI Assistant
 * Plugin URI: https://example.com/acf-ai-assistant
 * Description: AI-powered content generation for Advanced Custom Fields
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-ai-assistant
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'ACF_AI_VERSION', '1.0.0' );
define( 'ACF_AI_FILE', __FILE__ );
define( 'ACF_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACF_AI_URL', plugins_url( '', __FILE__ ) );
define( 'ACF_AI_MIN_ACF_VERSION', '5.0.0' );

// Load Composer autoloader
$autoloader = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
if (!file_exists($autoloader)) {
	add_action('admin_notices', function() {
		echo '<div class="notice notice-error"><p>' . 
			esc_html__('ACF AI Assistant requires Composer dependencies. Run "composer install" in the plugin directory.', 'acf-ai-assistant') . 
			'</p></div>';
	});
	return;
}
require_once $autoloader;

// Default Stripe keys from the image
define( 'ACF_AI_STRIPE_PK', 'pk_test_51QmieSHgMGuEpzBQ76NfK6aZe0UgLlK0Q04vRoNLrdZlptOmXcg1sc5z1JJHBy71SOLHsnxGprEhJc7WDjFVIZU3100vKSHKCrP' );
define( 'ACF_AI_STRIPE_SK', 'sk_test_51QmieSHgMGuEpzBQy9LMLincBLSv4x4M0HsvcZCdkmmWRW3obdOXaGH6imN1glZEB9iAuq9ZGa8P5FAXcO91o6K500103VCh5b' );

// Define subscription plans
define( 'ACF_AI_SUBSCRIPTION_PRICE', 20 ); // $20/month
define( 'ACF_AI_TOKENS_PER_MONTH', 2000 ); // 2000 tokens per month
define( 'ACF_AI_MAX_IMAGE_GENERATIONS', 50 ); // 50 images per month
define( 'ACF_AI_MAX_TRANSLATIONS', 100 ); // 100 translations per month

// Initialize WP-CLI commands if available
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/class-acf-ai-cli.php';
	ACF_AI_CLI::init();
}

final class ACF_AI_Assistant {
	private static $instance = null;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		// Core classes
		require_once ACF_AI_PATH . 'includes/class-acf-ai-api.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-security.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-response.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-license.php';
		
		// Feature classes
		require_once ACF_AI_PATH . 'includes/class-acf-ai-field-group.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-features.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-advanced.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-marketing.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-premium.php';

		// Admin interface
		if (is_admin()) {
			if (file_exists(ACF_AI_PATH . 'admin/class-acf-ai-admin.php')) {
				require_once ACF_AI_PATH . 'admin/class-acf-ai-admin.php';
			} else {
				add_action('admin_notices', function() {
					echo '<div class="notice notice-error"><p>' 
						. esc_html__('Admin interface file missing!', 'acf-ai-assistant') 
						. '</p></div>';
				});
			}
		}
	}

	private function init_hooks() {
		// Activation/Deactivation
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		// Initialize components
		add_action('plugins_loaded', array($this, 'init_components'), 5);
	}

	public function init_components() {
		// Verify ACF Pro is active
		if (!class_exists('ACF')) {
			add_action('admin_notices', array($this, 'acf_missing_notice'));
			return;
		}

		// Initialize core services
		ACF_AI_API::get_instance();
		ACF_AI_License::get_instance();
		
		// Initialize features
		new ACF_AI_Field_Group();
		new ACF_AI_Features();
		new ACF_AI_Advanced();
		new ACF_AI_Marketing();
		new ACF_AI_Premium();

		// Load text domain
		load_plugin_textdomain(
			'acf-ai-assistant',
			false,
			dirname(plugin_basename(__FILE__)) . '/languages/'
		);
	}

	public function acf_missing_notice() {
		echo '<div class="error"><p>';
		printf(
			__('ACF AI Assistant requires Advanced Custom Fields PRO to be installed and active. %s', 'acf-ai-assistant'),
			'<a href="https://www.advancedcustomfields.com/pro/" target="_blank">' . __('Get ACF Pro', 'acf-ai-assistant') . '</a>'
		);
		echo '</p></div>';
	}
}

// Initialize plugin
add_action( 'plugins_loaded', function() {
	// Check if ACF is active
	if ( ! class_exists( 'ACF' ) ) {
		add_action( 'admin_notices', function() {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'ACF AI Assistant requires Advanced Custom Fields to be installed and activated.', 'acf-ai-assistant' )
			);
		} );
		return;
	}

	// Check ACF version
	if ( version_compare( acf()->version, ACF_AI_MIN_ACF_VERSION, '<' ) ) {
		add_action( 'admin_notices', function() {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				sprintf(
					/* translators: %s: Minimum ACF version */
					esc_html__( 'ACF AI Assistant requires Advanced Custom Fields version %s or higher.', 'acf-ai-assistant' ),
					ACF_AI_MIN_ACF_VERSION
				)
			);
		} );
		return;
	}

	// Initialize plugin
	ACF_AI_Assistant::get_instance();
} );
