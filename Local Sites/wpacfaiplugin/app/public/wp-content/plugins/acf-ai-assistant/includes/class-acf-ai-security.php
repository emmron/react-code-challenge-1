<?php
class ACF_AI_Security {
    public static function encrypt_key($key) {
        if (!extension_loaded('openssl')) {
            return base64_encode($key);
        }
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt(
            $key, 
            'aes-256-cbc', 
            AUTH_KEY, 
            0, 
            $iv
        );
        
        return base64_encode($iv . $encrypted);
    }

    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_send_json_error([
                'code' => 'invalid_nonce',
                'message' => __('Security check failed', 'acf-ai-assistant')
            ], 403);
        }
    }

    public static function validate_content($content, $max_length = 2000) {
        $clean_content = wp_kses_post($content);
        return mb_substr($clean_content, 0, $max_length);
    }

    public static function validate_field_config($config) {
        $allowed_field_types = acf_get_field_types();
        $required_keys = ['title', 'fields', 'location'];
        
        foreach ($required_keys as $key) {
            if (!isset($config[$key])) {
                return new WP_Error('missing_key', sprintf(__('Missing required key: %s', 'acf-ai-assistant'), $key));
            }
        }
        
        foreach ($config['fields'] as $field) {
            if (!in_array($field['type'], array_keys($allowed_field_types))) {
                return new WP_Error('invalid_field_type', sprintf(__('Invalid field type: %s', 'acf-ai-assistant'), $field['type']));
            }
        }
        
        return $config;
    }
} 