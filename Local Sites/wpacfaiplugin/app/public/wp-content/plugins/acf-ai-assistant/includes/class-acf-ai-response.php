<?php
namespace ACF_AI;

class Response {
    public static function success($data = []) {
        wp_send_json_success([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'timestamp' => current_time('timestamp'),
                'version' => ACF_AI_VERSION
            ]
        ]);
    }

    public static function error($code, $message, $status = 400) {
        wp_send_json_error([
            'status' => 'error',
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'meta' => [
                'timestamp' => current_time('timestamp'),
                'version' => ACF_AI_VERSION
            ]
        ], $status);
    }

    public static function rate_limit_exceeded() {
        self::error(
            'rate_limit_exceeded',
            __('API rate limit exceeded. Please try again later.', 'acf-ai-assistant'),
            429
        );
    }
} 