<?php

if (!defined('ABSPATH')) {
    exit;
}

class ACF_AI_Scripts {
    private static $instance = null;
    private $license;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->license = ACF_AI_License::get_instance();
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        // Enqueue premium JS
        wp_enqueue_script(
            'acf-ai-premium',
            ACF_AI_URL . '/assets/js/acf-ai-premium.js',
            array('jquery', 'acf-input'),
            ACF_AI_VERSION,
            true
        );

        // Localize script with license info
        wp_localize_script('acf-ai-premium', 'acf_ai_assistant', [
            'is_premium' => $this->license->is_premium_active(),
            'empty_prompt' => __('Please enter some content to analyze', 'acf-ai-assistant'),
            'error_message' => __('An error occurred. Please try again.', 'acf-ai-assistant')
        ]);
    }
}

// Initialize scripts
ACF_AI_Scripts::get_instance(); 