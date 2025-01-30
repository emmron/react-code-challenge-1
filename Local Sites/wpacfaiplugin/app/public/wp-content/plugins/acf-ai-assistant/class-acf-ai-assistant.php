<?php
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
 *
 * @package ACF_AI_Assistant
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('ACF_AI_VERSION', '1.0.0');
define('ACF_AI_FILE', __FILE__);
define('ACF_AI_PATH', plugin_dir_path(__FILE__));
define('ACF_AI_URL', plugins_url('', __FILE__));
define('ACF_AI_MIN_ACF_VERSION', '5.0.0');
define('ACF_AI_MIN_PHP_VERSION', '7.4');
define('ACF_AI_MIN_WP_VERSION', '5.8');

/**
 * Main plugin class
 */
final class ACF_AI_Assistant {

	/**
	 * Plugin instance
	 *
	 * @var ACF_AI_Assistant
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return ACF_AI_Assistant
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Debug point for initialization
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('ACF AI Assistant: Initializing plugin');
		}

		// Check requirements
		if (!$this->check_requirements()) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('ACF AI Assistant: Requirements check failed');
			}
			return;
		}

		// Load dependencies
		$this->load_dependencies();

		// Initialize components
		$this->init_components();

		// Register hooks
		$this->register_hooks();

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('ACF AI Assistant: Plugin initialized successfully');
		}
	}

	/**
	 * Check plugin requirements
	 *
	 * @return bool
	 */
	private function check_requirements() {
		$requirements_met = true;

		// Debug point for requirements check
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('ACF AI Assistant: Checking requirements');
		}

		// Check PHP version
		if (version_compare(PHP_VERSION, ACF_AI_MIN_PHP_VERSION, '<')) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('ACF AI Assistant: PHP version requirement not met');
			}
			add_action('admin_notices', function() {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					sprintf(
						/* translators: %s: Minimum PHP version */
						esc_html__('ACF AI Assistant requires PHP version %s or higher.', 'acf-ai-assistant'),
						ACF_AI_MIN_PHP_VERSION
					)
				);
			});
			$requirements_met = false;
		}

		// Check WordPress version
		if (version_compare(get_bloginfo('version'), ACF_AI_MIN_WP_VERSION, '<')) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('ACF AI Assistant: WordPress version requirement not met');
			}
			add_action('admin_notices', function() {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					sprintf(
						/* translators: %s: Minimum WordPress version */
						esc_html__('ACF AI Assistant requires WordPress version %s or higher.', 'acf-ai-assistant'),
						ACF_AI_MIN_WP_VERSION
					)
				);
			});
			$requirements_met = false;
		}

		// Check if ACF is active
		if (!class_exists('ACF')) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('ACF AI Assistant: ACF plugin not found');
			}
			add_action('admin_notices', function() {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__('ACF AI Assistant requires Advanced Custom Fields to be installed and activated.', 'acf-ai-assistant')
				);
			});
			$requirements_met = false;
		} else {
			// Check ACF version
			$acf_version = acf()->version;
			if (version_compare($acf_version, ACF_AI_MIN_ACF_VERSION, '<')) {
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('ACF AI Assistant: ACF version requirement not met');
				}
				add_action('admin_notices', function() {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						sprintf(
							/* translators: %s: Minimum ACF version */
							esc_html__('ACF AI Assistant requires Advanced Custom Fields version %s or higher.', 'acf-ai-assistant'),
							ACF_AI_MIN_ACF_VERSION
						)
					);
				});
				$requirements_met = false;
			}
		}

		return $requirements_met;
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		// Load Composer autoloader if it exists
		if (file_exists(ACF_AI_PATH . 'vendor/autoload.php')) {
			require_once ACF_AI_PATH . 'vendor/autoload.php';
		}

		// Load plugin files
		require_once ACF_AI_PATH . 'includes/class-acf-ai-field.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-api.php';
		require_once ACF_AI_PATH . 'includes/class-acf-ai-settings.php';
	}

	/**
	 * Initialize plugin components
	 */
	private function init_components() {
		// Initialize settings
		ACF_AI_Settings::get_instance();

		// Register field type
		add_action('acf/include_field_types', function() {
			new ACF_AI_Field();
		});
	}

	/**
	 * Register plugin hooks
	 */
	private function register_hooks() {
		// Load text domain
		add_action('init', function() {
			load_plugin_textdomain(
				'acf-ai-assistant',
				false,
				dirname(plugin_basename(ACF_AI_FILE)) . '/languages'
			);
		});

		// Add settings link to plugins page
		add_filter('plugin_action_links_' . plugin_basename(ACF_AI_FILE), function($links) {
			$settings_link = sprintf(
				'<a href="%s">%s</a>',
				admin_url('options-general.php?page=acf-ai-settings'),
				esc_html__('Settings', 'acf-ai-assistant')
			);
			array_unshift($links, $settings_link);
			return $links;
		});
	}
}

// Initialize plugin
add_action('plugins_loaded', array('ACF_AI_Assistant', 'get_instance'));
