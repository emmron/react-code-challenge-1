<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles integration with OpenAI's API for ACF AI Assistant
 * 
 * @since 1.0.0
 * @package ACF_AI_Assistant
 */
class ACF_AI_Field_Group {

    /**
     * Handles AJAX request for field group generation
     *
     * @since 1.0.0
     * @return void
     */
    public function handle_generate_request() {
        try {
            $this->verify_request();
            $this->verify_license();

            $params = $this->get_validated_params();
            $response = $this->generate_content($params['prompt'], $params['model']);
            
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }

            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Generates an ACF field group configuration from a prompt
     *
     * @param string $prompt Natural language prompt describing desired fields
     * @return array|WP_Error Field group configuration or error
     */
    public function generate_field_group($prompt) {
        $api = ACF_AI_API::get_instance();
        
        // Sanitize the input prompt
        $prompt = sanitize_textarea_field($prompt);
        
        // Generate the field configuration using the API
        $response = $api->generate_content($prompt);
        
        if (is_wp_error($response)) {
            error_log('ACF AI Field Group Error: ' . $response->get_error_message());
            return $response;
        }
        
        // Parse and validate the response
        $config = $this->parse_field_config($response);
        
        if (is_wp_error($config)) {
            error_log('ACF AI Field Group Error: Failed to parse configuration');
            return $config;
        }
        
        return $config;
    }

    /**
     * Parses and validates the API response into field configuration
     *
     * @param string $response JSON response from API
     * @return array|WP_Error Parsed field configuration or error
     */
    private function parse_field_config($response) {
        $config = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'invalid_json',
                __('Invalid JSON in field configuration response', 'acf-ai-assistant')
            );
        }

        if (!is_array($config)) {
            return new WP_Error(
                'invalid_config',
                __('Field configuration must be an array', 'acf-ai-assistant')
            );
        }

        return $config;
    }
}
