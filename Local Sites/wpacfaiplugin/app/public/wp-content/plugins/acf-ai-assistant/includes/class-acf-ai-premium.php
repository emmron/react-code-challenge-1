<?php

if (!defined('ABSPATH')) {
    exit;
}

class ACF_AI_Premium {
    private static $instance = null;
    private $license;
    private $api;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->license = ACF_AI_License::get_instance();
        $this->api = ACF_AI_API::get_instance();

        // Only initialize if ACF is active
        if (!class_exists('ACF')) {
            add_action('admin_notices', array($this, 'show_acf_missing_notice'));
            return;
        }

        // Initialize premium features if license is valid
        if ($this->license->is_premium_active()) {
            // Register our field types
            add_action('acf/include_field_types', array($this, 'register_field_types'));
            
            // Add AJAX handlers for premium features
            add_action('wp_ajax_acf_ai_generate_headline', array($this, 'ajax_generate_headline'));
            add_action('wp_ajax_acf_ai_generate_social', array($this, 'ajax_generate_social'));
            add_action('wp_ajax_acf_ai_generate_email', array($this, 'ajax_generate_email'));
            add_action('wp_ajax_acf_ai_analyze_competition', array($this, 'ajax_analyze_competition'));
            add_action('wp_ajax_acf_ai_generate_cta', array($this, 'ajax_generate_cta'));
            
            // Add field settings
            add_action('acf/render_field_settings', array($this, 'add_ai_field_settings'));

            // New premium features
            add_action('acf/init', [$this, 'register_premium_fields']);
            $this->register_ajax_handlers();
            add_filter('acf_ai_content_suggestions', [$this, 'enhance_content_suggestions']);
        }
    }

    public function show_acf_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('ACF AI Assistant requires Advanced Custom Fields PRO to be installed and activated.', 'acf-ai-assistant'); ?></p>
        </div>
        <?php
    }

    public function register_field_types() {
        // Register our custom field types
        include_once dirname(__FILE__) . '/fields/class-acf-field-ai-headline.php';
        include_once dirname(__FILE__) . '/fields/class-acf-field-ai-social.php';
        include_once dirname(__FILE__) . '/fields/class-acf-field-ai-email.php';
        include_once dirname(__FILE__) . '/fields/class-acf-field-ai-cta.php';
    }

    public function add_ai_field_settings($field) {
        // Add AI enhancement settings to appropriate field types
        if (in_array($field['type'], array('text', 'textarea', 'wysiwyg'))) {
            acf_render_field_setting($field, array(
                'label'         => __('AI Enhancement', 'acf-ai-assistant'),
                'instructions' => __('Enable AI-powered content generation for this field', 'acf-ai-assistant'),
                'name'         => 'ai_enhancement',
                'type'         => 'true_false',
                'ui'           => 1,
            ));

            acf_render_field_setting($field, array(
                'label'         => __('AI Content Type', 'acf-ai-assistant'),
                'instructions' => __('Select the type of content to generate', 'acf-ai-assistant'),
                'name'         => 'ai_content_type',
                'type'         => 'select',
                'choices'     => array(
                    'headline' => __('Headline', 'acf-ai-assistant'),
                    'social'   => __('Social Media', 'acf-ai-assistant'),
                    'email'    => __('Email', 'acf-ai-assistant'),
                    'cta'      => __('Call to Action', 'acf-ai-assistant'),
                ),
                'conditional_logic' => array(
                    array(
                        array(
                            'field'    => 'ai_enhancement',
                            'operator' => '==',
                            'value'    => 1,
                        ),
                    ),
                ),
            ));
        }
    }

    public function ajax_generate_headline() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'acf_ai_nonce') || !current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized request', 'acf-ai-assistant'));
        }

        // Verify premium status
        if (!$this->license->is_premium_active()) {
            wp_send_json_error(__('Premium license required', 'acf-ai-assistant'));
        }

        $topic = sanitize_text_field($_POST['topic']);
        $style = sanitize_text_field($_POST['style']);
        $keywords = sanitize_text_field($_POST['keywords']);

        try {
            $response = $this->api->generate_headline($topic, $style, $keywords);
            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    // Similar AJAX handlers for other features...

    private function verify_premium_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'acf_ai_nonce')) {
            wp_send_json_error(__('Invalid nonce', 'acf-ai-assistant'));
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions', 'acf-ai-assistant'));
        }

        if (!$this->license->is_premium_active()) {
            wp_send_json_error(__('Premium license required', 'acf-ai-assistant'));
        }
    }

    /**
     * Register AJAX handlers for premium features
     */
    private function register_ajax_handlers() {
        $premium_actions = [
            'handle_automatic_images',
            'handle_seo_analysis',
            'handle_content_translation',
            'handle_personalized_content',
            'handle_historical_analysis'
        ];
        
        foreach ($premium_actions as $action) {
            add_action("wp_ajax_acf_ai_{$action}", [$this, $action]);
        }
    }

    /**
     * Generate AI-powered images based on content
     */
    public function handle_automatic_images() {
        // Verify nonce first
        if (!wp_verify_nonce($_POST['nonce'], 'acf_ai_assistant')) {
            ACF_AI_Response::error('invalid_nonce', __('Security check failed', 'acf-ai-assistant'), 403);
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            ACF_AI_Response::error('insufficient_permissions', __('You do not have permission to perform this action', 'acf-ai-assistant'), 403);
        }

        // License check
        if (!$this->license->is_premium_active()) {
            ACF_AI_Response::error('premium_required', __('Image generation requires premium license', 'acf-ai-assistant'), 402);
        }

        // Sanitize input
        $content = sanitize_textarea_field($_POST['content']);
        $style = sanitize_text_field($_POST['style']);
        
        try {
            $images = $this->api->generate_images($content, $style);
            ACF_AI_Response::success(['images' => $images]);
        } catch (Exception $e) {
            ACF_AI_Response::error('image_generation_failed', $e->getMessage());
        }
    }

    /**
     * Perform SEO analysis and suggestions
     */
    public function handle_seo_analysis() {
        if (!$this->verify_premium_request()) {
            ACF_AI_Response::error('premium_required', __('SEO analysis requires premium license', 'acf-ai-assistant'), 402);
        }

        $content = sanitize_textarea_field($_POST['content']);
        $keywords = sanitize_text_field($_POST['keywords']);
        
        $prompt = sprintf(
            "Analyze this content for SEO effectiveness and suggest improvements. Focus on: %s\n\nContent:\n%s",
            $keywords,
            $content
        );

        $analysis = $this->api->generate_content($prompt, 'gpt-4-seo');
        ACF_AI_Response::success(['analysis' => wp_kses_post($analysis)]);
    }

    /**
     * Enhanced content suggestions for premium users
     */
    public function enhance_content_suggestions($suggestions) {
        if ($this->license->is_premium_active()) {
            return array_merge($suggestions, [
                'tone_adjustment' => true,
                'readability_score' => true,
                'competitor_benchmarking' => true
            ]);
        }
        return $suggestions;
    }

    /**
     * Content personalization based on user segments
     */
    public function handle_personalized_content() {
        if (!$this->verify_premium_request()) {
            ACF_AI_Response::error('premium_required', __('Content personalization requires premium', 'acf-ai-assistant'), 402);
        }

        // Validate audience segments
        $segments = array_map('sanitize_text_field', (array)$_POST['segments']);
        $content = sanitize_textarea_field($_POST['content']);
        
        $variations = [];
        foreach ($segments as $segment) {
            $prompt = "Adapt this content for $segment audience:\n\n$content";
            $variations[$segment] = $this->api->generate_content($prompt);
        }
        
        ACF_AI_Response::success(['variations' => $variations]);
    }
}
