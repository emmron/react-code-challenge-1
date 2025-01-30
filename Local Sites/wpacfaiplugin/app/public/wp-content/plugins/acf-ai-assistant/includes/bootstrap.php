<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrap the plugin
 */
class ACF_AI_Bootstrap {
    private static $instance = null;
    private $plugin_path;
    private $plugin_url;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->plugin_path = plugin_dir_path(dirname(__FILE__));
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));

        add_action('plugins_loaded', array($this, 'init'), 20);
        register_activation_hook(dirname(__FILE__) . '/acf-ai-assistant.php', array($this, 'activate'));
        register_deactivation_hook(dirname(__FILE__) . '/acf-ai-assistant.php', array($this, 'deactivate'));
    }

    public function init() {
        // Check if ACF is active
        if (!class_exists('ACF')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . 
                     esc_html__('ACF AI Assistant Pro requires Advanced Custom Fields to be installed and activated.', 'acf-ai-assistant') . 
                     '</p></div>';
            });
            return;
        }

        // Load plugin files
        $this->load_files();

        // Initialize components
        $this->init_components();

        // Setup hooks
        $this->setup_hooks();
    }

    private function load_files() {
        $files = array(
            'class-acf-ai-license.php',
            'class-acf-ai-api.php',
            'class-acf-ai-field.php',
            'class-acf-ai-stripe.php',
            'class-acf-ai-settings.php',
            'class-acf-ai-features.php',
            'class-acf-ai-advanced.php',
            'class-acf-ai-marketing.php'
        );

        foreach ($files as $file) {
            require_once $this->plugin_path . 'includes/' . $file;
        }
    }

    private function init_components() {
        ACF_AI_License::get_instance();
        ACF_AI_API::get_instance();
        ACF_AI_Stripe::get_instance();
        ACF_AI_Settings::get_instance();
        ACF_AI_Features::get_instance();
        ACF_AI_Advanced::get_instance();
        ACF_AI_Marketing::get_instance();
    }

    private function setup_hooks() {
        // Register field type
        add_action('acf/include_field_types', function() {
            new ACF_AI_Field();
        });

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename($this->plugin_path . 'acf-ai-assistant.php'), 
            array($this, 'add_plugin_links')
        );

        // Add premium features to admin bar
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }

    public function activate() {
        if (!class_exists('ACF')) {
            deactivate_plugins(plugin_basename($this->plugin_path . 'acf-ai-assistant.php'));
            wp_die(
                esc_html__('This plugin requires Advanced Custom Fields to be installed and activated.', 'acf-ai-assistant'),
                'Plugin dependency check',
                array('back_link' => true)
            );
        }

        // Create default options
        add_option('acf_ai_license_key', '');
        add_option('acf_ai_openai_key', '');

        // Create necessary database tables
        $this->create_tables();

        // Schedule cron jobs
        wp_schedule_event(time(), 'daily', 'acf_ai_daily_maintenance');
    }

    public function deactivate() {
        // Clear transients
        delete_transient('acf_ai_license_status');
        
        // Clear scheduled hooks
        wp_clear_scheduled_hook('acf_ai_daily_maintenance');
    }

    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Token usage table
        $table_name = $wpdb->prefix . 'acf_ai_token_usage';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tokens_used int(11) NOT NULL,
            feature_type varchar(50) NOT NULL,
            date_used datetime DEFAULT CURRENT_TIMESTAMP,
            field_key varchar(255) NOT NULL,
            post_id bigint(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY date_used (date_used),
            KEY feature_type (feature_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Image generation table
        $table_name = $wpdb->prefix . 'acf_ai_image_usage';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            date_used datetime DEFAULT CURRENT_TIMESTAMP,
            image_prompt text NOT NULL,
            image_style varchar(50) NOT NULL,
            image_size varchar(20) NOT NULL,
            attachment_id bigint(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY date_used (date_used)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    public function add_plugin_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=acf-ai-settings') . '">' . 
                        esc_html__('Settings', 'acf-ai-assistant') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function add_admin_bar_menu($admin_bar) {
        if (!current_user_can('edit_posts') || !ACF_AI_License::get_instance()->is_license_valid()) {
            return;
        }

        $menu_items = array(
            array(
                'id' => 'acf-ai-tools',
                'title' => esc_html__('AI Tools', 'acf-ai-assistant'),
                'href' => admin_url('admin.php?page=acf-ai-settings'),
            ),
            array(
                'parent' => 'acf-ai-tools',
                'id' => 'acf-ai-bulk-generate',
                'title' => esc_html__('Bulk Content Generator', 'acf-ai-assistant'),
                'href' => admin_url('admin.php?page=acf-ai-bulk-generator'),
            ),
            // ... add other menu items here
        );

        foreach ($menu_items as $item) {
            $admin_bar->add_menu($item);
        }
    }
} 