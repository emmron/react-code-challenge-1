<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('acf_field_ai_headline')):

/**
 * ACF Field: AI Headline Generator
 * 
 * Provides an interface for generating optimized headlines using AI.
 * Features topic, style and keyword controls with real-time preview.
 * Premium license required.
 */
class ACF_Field_AI_Headline extends ACF_Field {

    /** @var string Nonce action for AJAX requests */
    private const NONCE_ACTION = 'acf_ai_headline_nonce';

    /** @var array Valid headline style options */
    private const VALID_STYLES = [
        'engaging' => 'Engaging',
        'professional' => 'Professional', 
        'casual' => 'Casual',
        'dramatic' => 'Dramatic',
        'informative' => 'Informative'
    ];

    /** @var array Default field configuration */
    private const DEFAULTS = [
        'default_value' => '',
        'placeholder' => '',
        'style' => 'engaging',
        'keywords' => '',
        'max_length' => 100,
        'min_length' => 20
    ];

    /**
     * Set up field type configuration
     */
    public function initialize() {
        $this->name = 'ai_headline';
        $this->label = __('AI Headline Generator', 'acf-ai-assistant');
        $this->category = 'content';
        $this->defaults = self::DEFAULTS;

        // Register AJAX handler with security nonce
        add_action('wp_ajax_acf_ai_generate_headline', [$this, 'handle_ajax_request']);
    }

    /**
     * Render the complete field interface
     *
     * @param array $field Field settings
     * @return void
     */
    public function render_field($field) {
        // Validate premium access first
        if (!$this->validate_premium_access()) {
            return;
        }

        // Sanitize field settings
        $field = wp_parse_args($field, self::DEFAULTS);
        $field = $this->sanitize_field_settings($field);

        // Render field components
        echo '<div class="acf-ai-headline-container">';
        $this->render_headline_input($field);
        $this->render_control_panel($field);
        $this->render_preview_panel();
        echo '</div>';

        // Load required assets
        $this->load_field_assets();
    }

    /**
     * Validate premium license access
     *
     * @return bool Access granted
     */
    private function validate_premium_access(): bool {
        $license = ACF_AI_License::get_instance();
        
        if ($license->is_premium_active()) {
            return true;
        }

        printf(
            '<div class="acf-notice -error"><p>%s</p></div>',
            sprintf(
                __('Premium license required. <a href="%s" target="_blank">Upgrade now</a> to access AI headlines.', 'acf-ai-assistant'),
                esc_url($license->get_subscription_url())
            )
        );

        return false;
    }

    /**
     * Sanitize and validate field settings
     *
     * @param array $field Raw field settings
     * @return array Sanitized settings
     */
    private function sanitize_field_settings(array $field): array {
        return [
            'id' => sanitize_key($field['id']),
            'name' => sanitize_key($field['name']),
            'value' => sanitize_text_field($field['value']),
            'placeholder' => sanitize_text_field($field['placeholder']),
            'style' => $this->validate_style($field['style']),
            'keywords' => sanitize_text_field($field['keywords']),
            'max_length' => absint($field['max_length']),
            'min_length' => absint($field['min_length'])
        ];
    }

    /**
     * Validate headline style selection
     *
     * @param string $style Selected style
     * @return string Valid style or default
     */
    private function validate_style(string $style): string {
        return array_key_exists($style, self::VALID_STYLES) 
            ? $style 
            : self::DEFAULTS['style'];
    }

    /**
     * Render the main headline input field
     *
     * @param array $field Sanitized field settings
     */
    private function render_headline_input(array $field) {
        ?>
        <div class="acf-ai-headline-input-wrap">
            <input type="text"
                id="<?php echo esc_attr($field['id']); ?>"
                name="<?php echo esc_attr($field['name']); ?>"
                value="<?php echo esc_attr($field['value']); ?>"
                class="acf-ai-headline-input"
                placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                maxlength="<?php echo esc_attr($field['max_length']); ?>"
                autocomplete="off"
                data-min-length="<?php echo esc_attr($field['min_length']); ?>"
            />
            <div class="acf-ai-headline-char-count"></div>
        </div>
        <?php
    }

    /**
     * Render the headline generation controls
     *
     * @param array $field Sanitized field settings
     */
    private function render_control_panel(array $field) {
        ?>
        <div class="acf-ai-headline-controls">
            <div class="acf-ai-control-grid">
                <?php
                $this->render_topic_control();
                $this->render_style_control($field['style']);
                $this->render_keyword_control($field['keywords']);
                ?>
            </div>
            
            <div class="acf-ai-control-actions">
                <button type="button" class="button button-primary acf-ai-generate-btn">
                    <span class="acf-ai-btn-text">
                        <?php esc_html_e('Generate Headlines', 'acf-ai-assistant'); ?>
                    </span>
                    <span class="acf-ai-btn-spinner" style="display:none;">
                        <?php esc_html_e('Generating...', 'acf-ai-assistant'); ?>
                    </span>
                </button>
                
                <button type="button" class="button acf-ai-clear-btn">
                    <?php esc_html_e('Clear All', 'acf-ai-assistant'); ?>
                </button>
            </div>
        </div>

        <div class="acf-ai-headline-preview" style="display:none;">
            <h4><?php esc_html_e('Generated Headlines', 'acf-ai-assistant'); ?></h4>
            <div class="acf-ai-preview-list"></div>
        </div>
        <?php
    }

    /**
     * Handle AJAX headline generation request
     */
    public function handle_ajax_request() {
        try {
            // Verify request authenticity
            if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
                throw new Exception(__('Invalid security token', 'acf-ai-assistant'));
            }

            if (!current_user_can('edit_posts')) {
                throw new Exception(__('Insufficient permissions', 'acf-ai-assistant'));
            }

            // Validate required fields
            $topic = $this->validate_required_field('topic');
            $style = $this->validate_style($_POST['style'] ?? '');
            $keywords = sanitize_text_field($_POST['keywords'] ?? '');

            // Generate headlines
            $premium = ACF_AI_Premium::get_instance();
            $headlines = $premium->generate_headlines($topic, $style, $keywords);

            if (is_wp_error($headlines)) {
                throw new Exception($headlines->get_error_message());
            }

            wp_send_json_success([
                'headlines' => array_map('esc_html', $headlines),
                'count' => count($headlines)
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Load required CSS and JavaScript assets
     */
    private function load_field_assets() {
        // Styles loaded via wp_add_inline_style()
        add_action('admin_footer', [$this, 'render_inline_styles']);
        
        // Scripts loaded via wp_add_inline_script()
        add_action('admin_footer', [$this, 'render_inline_scripts']);
    }
}

// Register the field type
acf_register_field_type('ACF_Field_AI_Headline');

endif;
