<?php

/**
 * ACF AI Assistant Field Type
 *
 * @package ACF_AI_Assistant
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if ACF is active
if (!class_exists('ACF')) {
    return;
}

if (!class_exists('ACF_AI_Field')) {

    /**
     * ACF field type for AI-powered content generation.
     */
    class ACF_AI_Field extends acf_field {

        /**
         * Initialize field type.
         */
        public function __construct() {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACF AI Assistant: Initializing field type');
            }

            $this->name = 'ai_assistant';
            $this->label = __('AI Assistant', 'acf-ai-assistant');
            $this->category = 'content';
            $this->defaults = array(
                'default_value' => '',
                'placeholder'   => '',
                'maxlength'    => '',
                'rows'         => 4,
                'ai_features'  => array(
                    'suggestions'   => true,
                    'analysis'     => true,
                    'optimization' => true,
                ),
            );

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACF AI Assistant: Field defaults set');
            }

            parent::__construct();

            // Enqueue assets
            add_action('acf/input/admin_enqueue_scripts', array($this, 'input_admin_enqueue_scripts'));

            // Proper ACF field registration
            add_action('acf/include_field_types', [$this, 'include_field_types']);
        }

        public function include_field_types() {
            acf_register_field_type(get_class($this));
        }

        /**
         * Render field settings.
         *
         * @param array $field Field settings.
         */
        public function render_field_settings($field) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACF AI Assistant: Rendering field settings');
                error_log('Field: ' . print_r($field, true));
            }

            // Default Value
            acf_render_field_setting(
                $field,
                array(
                    'label'        => __('Default Value', 'acf-ai-assistant'),
                    'instructions' => __('Enter a default value', 'acf-ai-assistant'),
                    'type'        => 'text',
                    'name'        => 'default_value',
                )
            );

            // Placeholder
            acf_render_field_setting(
                $field,
                array(
                    'label'        => __('Placeholder Text', 'acf-ai-assistant'),
                    'instructions' => __('Enter placeholder text', 'acf-ai-assistant'),
                    'type'        => 'text',
                    'name'        => 'placeholder',
                )
            );

            // MaxLength
            acf_render_field_setting(
                $field,
                array(
                    'label'        => __('Character Limit', 'acf-ai-assistant'),
                    'instructions' => __('Leave blank for no limit', 'acf-ai-assistant'),
                    'type'        => 'number',
                    'name'        => 'maxlength',
                )
            );

            // Rows
            acf_render_field_setting(
                $field,
                array(
                    'label'        => __('Rows', 'acf-ai-assistant'),
                    'instructions' => __('Sets the textarea height', 'acf-ai-assistant'),
                    'type'        => 'number',
                    'name'        => 'rows',
                    'min'         => 3,
                    'max'         => 20,
                )
            );

            // AI Features
            acf_render_field_setting(
                $field,
                array(
                    'label'        => __('AI Features', 'acf-ai-assistant'),
                    'instructions' => __('Select which AI features to enable', 'acf-ai-assistant'),
                    'type'        => 'checkbox',
                    'name'        => 'ai_features',
                    'choices'     => array(
                        'suggestions'   => __('Content Suggestions', 'acf-ai-assistant'),
                        'analysis'     => __('Content Analysis', 'acf-ai-assistant'),
                        'optimization' => __('SEO Optimization', 'acf-ai-assistant'),
                    ),
                )
            );

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACF AI Assistant: Field settings rendered');
            }
        }

        /**
         * Render field input.
         *
         * @param array $field Field settings.
         */
        public function render_field($field) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACF AI Assistant: Rendering field input');
                error_log('Field: ' . print_r($field, true));
            }

            // Ensure unique ID
            $field['id'] = uniqid('acf-ai-');

            // Begin field wrapper
            ?>
            <div class="acf-ai-assistant-field" data-field-id="<?php echo esc_attr($field['id']); ?>">
                <?php
                // Textarea input
                $atts = array(
                    'id'          => $field['id'],
                    'class'       => 'acf-ai-prompt',
                    'name'        => $field['name'],
                    'value'       => $field['value'],
                    'placeholder' => $field['placeholder'],
                    'rows'        => $field['rows'],
                    'maxlength'   => $field['maxlength'],
                );

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('ACF AI Assistant: Field attributes prepared');
                    error_log('Attributes: ' . print_r($atts, true));
                }
                ?>
                <textarea <?php echo acf_esc_attrs($atts); ?>></textarea>

                <div class="acf-ai-controls">
                    <?php if ($field['ai_features']['suggestions']) : ?>
                        <button type="button" class="button acf-ai-generate" data-action="generate">
                            <?php esc_html_e('Generate Suggestions', 'acf-ai-assistant'); ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($field['ai_features']['analysis']) : ?>
                        <button type="button" class="button acf-ai-analyze" data-action="analyze">
                            <?php esc_html_e('Analyze Content', 'acf-ai-assistant'); ?>
                        </button>
                    <?php endif; ?>

                    <span class="acf-ai-status"></span>
                </div>

                <div class="acf-ai-result"></div>
            </div>
            <?php

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('ACF AI Assistant: Field input rendered');
            }
        }

        /**
         * Enqueue admin scripts and styles.
         */
        public function input_admin_enqueue_scripts() {
            $url = trailingslashit( plugin_dir_url( __FILE__ ) );
            $version = ACF_AI_VERSION;

            // Register & include CSS
            wp_register_style(
                'acf-ai-assistant',
                "{$url}../assets/css/acf-ai-assistant.css",
                array( 'acf-input' ),
                $version
            );
            wp_enqueue_style( 'acf-ai-assistant' );

            // Register & include JS
            wp_register_script(
                'acf-ai-assistant',
                "{$url}../assets/js/acf-ai-assistant.js",
                array( 'acf-input' ),
                $version,
                true
            );

            wp_localize_script(
                'acf-ai-assistant',
                'acf_ai_assistant',
                array(
                    'ajax_url'      => admin_url( 'admin-ajax.php' ),
                    'nonce'         => wp_create_nonce( 'acf_ai_assistant' ),
                    'error_message' => __('An error occurred while processing your request.', 'acf-ai-assistant'),
                    'empty_prompt'  => __('Please enter some text before generating suggestions.', 'acf-ai-assistant'),
                )
            );

            wp_enqueue_script( 'acf-ai-assistant' );
        }

        /**
         * Format value for display.
         *
         * @param mixed $value   Field value.
         * @param int   $post_id Post ID.
         * @param array $field   Field settings.
         * @return mixed
         */
        public function format_value( $value, $post_id, $field ) {
            // Apply filters
            $value = apply_filters( 'acf/format_value/type=ai_assistant', $value, $post_id, $field );

            return $value;
        }

        /**
         * Validate value before saving.
         *
         * @param bool  $valid  Validation status.
         * @param mixed $value  Field value.
         * @param array $field  Field settings.
         * @param array $input  Raw input.
         * @return bool|string
         */
        public function validate_value( $valid, $value, $field, $input ) {
            if ( $field['required'] && empty( $value ) ) {
                return __('This field is required.', 'acf-ai-assistant');
            }

            if ( $field['maxlength'] && strlen( $value ) > $field['maxlength'] ) {
                return sprintf(
                    __('Value must not exceed %d characters.', 'acf-ai-assistant'),
                    $field['maxlength']
                );
            }

            return $valid;
        }
    }
}
