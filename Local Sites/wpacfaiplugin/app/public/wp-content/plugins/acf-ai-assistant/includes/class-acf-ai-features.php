<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles premium AI features for ACF fields including content generation,
 * translation, SEO optimization, and image generation.
 *
 * @since 1.0.0
 */
class ACF_AI_Features {
    /**
     * Singleton instance
     *
     * @var ACF_AI_Features|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return ACF_AI_Features Instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor. Adds action hooks for AJAX handlers and field registration.
     */
    private function __construct() {
        // Register premium fields on ACF init
        add_action('acf/init', array($this, 'register_premium_fields'));

        // Register AJAX handlers
        $this->register_ajax_handlers();
    }

    /**
     * Register AJAX handlers for various AI features
     */
    private function register_ajax_handlers() {
        $ajax_actions = array(
            'acf_ai_bulk_generate' => 'handle_bulk_generate',
            'acf_ai_translate_content' => 'handle_translation', 
            'acf_ai_seo_optimize' => 'handle_seo_optimization',
            'acf_ai_image_generate' => 'handle_image_generation',
            'acf_ai_content_template' => 'handle_content_template',
            'acf_ai_content_score' => 'handle_content_scoring',
            'acf_ai_ab_test' => 'handle_ab_testing',
            'acf_ai_content_calendar' => 'handle_content_calendar',
            'acf_ai_voice_tone' => 'handle_voice_tone',
            'acf_ai_content_repurpose' => 'handle_content_repurpose'
        );

        foreach ($ajax_actions as $action => $handler) {
            add_action("wp_ajax_$action", array($this, $handler));
        }
    }

    /**
     * Register premium ACF field groups
     */
    public function register_premium_fields() {
        // Only load if ACF is available
        if (!function_exists('acf_register_field_type')) {
            return;
        }
        
        // Cache field groups
        $cache_key = 'acf_ai_premium_fields';
        $fields = wp_cache_get($cache_key);
        
        if (false === $fields) {
            $fields = $this->generate_field_definitions();
            wp_cache_set($cache_key, $fields, 'acf_ai', 6 * HOUR_IN_SECONDS);
        }
        
        // Register cached fields
        foreach ($fields as $field) {
            acf_register_field_type($field);
        }
    }

    /**
     * Register the AI Content Generator field group
     */
    private function register_content_generator_field() {
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_content_generator',
            'title' => __('AI Content Generator', 'acf-ai-assistant'),
            'fields' => array(
                array(
                    'key' => 'field_ai_content_prompt',
                    'label' => __('Content Prompt', 'acf-ai-assistant'),
                    'name' => 'ai_content_prompt',
                    'type' => 'textarea',
                    'instructions' => __('Describe the content you want to generate', 'acf-ai-assistant'),
                    'required' => 1,
                ),
                array(
                    'key' => 'field_ai_content_type',
                    'label' => __('Content Type', 'acf-ai-assistant'),
                    'name' => 'ai_content_type',
                    'type' => 'select',
                    'choices' => array(
                        'blog_post' => __('Blog Post', 'acf-ai-assistant'),
                        'product_description' => __('Product Description', 'acf-ai-assistant'),
                        'meta_description' => __('Meta Description', 'acf-ai-assistant'),
                        'social_media' => __('Social Media Post', 'acf-ai-assistant'),
                        'email' => __('Email Content', 'acf-ai-assistant')
                    ),
                    'default_value' => 'blog_post',
                ),
                array(
                    'key' => 'field_ai_tone',
                    'label' => __('Content Tone', 'acf-ai-assistant'),
                    'name' => 'ai_tone',
                    'type' => 'select',
                    'choices' => array(
                        'professional' => __('Professional', 'acf-ai-assistant'),
                        'casual' => __('Casual', 'acf-ai-assistant'),
                        'friendly' => __('Friendly', 'acf-ai-assistant'),
                        'authoritative' => __('Authoritative', 'acf-ai-assistant'),
                        'humorous' => __('Humorous', 'acf-ai-assistant')
                    ),
                    'default_value' => 'professional',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ),
                ),
            ),
        ));
    }

    /**
     * Register the AI SEO Optimizer field group
     */
    private function register_seo_optimizer_field() {
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_seo',
            'title' => __('AI SEO Optimizer', 'acf-ai-assistant'),
            'fields' => array(
                array(
                    'key' => 'field_ai_keywords',
                    'label' => __('Target Keywords', 'acf-ai-assistant'),
                    'name' => 'ai_keywords',
                    'type' => 'textarea',
                    'instructions' => __('Enter target keywords (one per line)', 'acf-ai-assistant'),
                ),
                array(
                    'key' => 'field_ai_seo_suggestions',
                    'label' => __('SEO Suggestions', 'acf-ai-assistant'),
                    'name' => 'ai_seo_suggestions',
                    'type' => 'message',
                    'message' => __('Click "Analyze & Optimize" to get AI-powered SEO suggestions', 'acf-ai-assistant'),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '!=',
                        'value' => 'attachment',
                    ),
                ),
            ),
        ));
    }

    /**
     * Register the AI Image Generator field group
     */
    private function register_image_generator_field() {
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_image',
            'title' => __('AI Image Generator', 'acf-ai-assistant'),
            'fields' => array(
                array(
                    'key' => 'field_ai_image_prompt',
                    'label' => __('Image Description', 'acf-ai-assistant'),
                    'name' => 'ai_image_prompt',
                    'type' => 'textarea',
                    'instructions' => __('Describe the image you want to generate', 'acf-ai-assistant'),
                ),
                array(
                    'key' => 'field_ai_image_style',
                    'label' => __('Image Style', 'acf-ai-assistant'),
                    'name' => 'ai_image_style',
                    'type' => 'select',
                    'choices' => array(
                        'realistic' => __('Realistic', 'acf-ai-assistant'),
                        'artistic' => __('Artistic', 'acf-ai-assistant'),
                        'cartoon' => __('Cartoon', 'acf-ai-assistant'),
                        'minimalist' => __('Minimalist', 'acf-ai-assistant'),
                    ),
                ),
                array(
                    'key' => 'field_ai_image_size',
                    'label' => __('Image Size', 'acf-ai-assistant'),
                    'name' => 'ai_image_size',
                    'type' => 'select',
                    'choices' => array(
                        '256x256' => __('Small (256x256)', 'acf-ai-assistant'),
                        '512x512' => __('Medium (512x512)', 'acf-ai-assistant'),
                        '1024x1024' => __('Large (1024x1024)', 'acf-ai-assistant'),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '!=',
                        'value' => 'attachment',
                    ),
                ),
            ),
        ));
    }

    /**
     * Handle bulk content generation AJAX request
     */
    public function handle_bulk_generate() {
        if (!$this->verify_request()) {
            wp_send_json_error(__('Invalid request', 'acf-ai-assistant'));
        }

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        $content_type = sanitize_text_field($_POST['content_type']);
        $tone = sanitize_text_field($_POST['tone']);

        $results = array();
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }

            $prompt = $this->generate_smart_prompt($post, $content_type, $tone);
            $generated_content = $this->generate_ai_content($prompt);

            if (!is_wp_error($generated_content)) {
                $results[$post_id] = $generated_content;
            }
        }

        wp_send_json_success($results);
    }

    /**
     * Handle content translation AJAX request
     */
    public function handle_translation() {
        if (!$this->verify_request()) {
            wp_send_json_error(__('Invalid request', 'acf-ai-assistant'));
        }

        $content = sanitize_textarea_field($_POST['content']);
        $target_language = sanitize_text_field($_POST['target_language']);

        $prompt = sprintf(
            __("Translate the following content to %s, maintaining the original tone and meaning:\n\n%s", 'acf-ai-assistant'),
            $target_language,
            $content
        );

        $translated_content = $this->generate_ai_content($prompt);
        if (!is_wp_error($translated_content)) {
            wp_send_json_success(array('translation' => $translated_content));
        } else {
            wp_send_json_error($translated_content->get_error_message());
        }
    }

    /**
     * Handle SEO optimization AJAX request
     */
    public function handle_seo_optimization() {
        if (!$this->verify_request()) {
            wp_send_json_error(__('Invalid request', 'acf-ai-assistant'));
        }

        $content = sanitize_textarea_field($_POST['content']);
        $keywords = sanitize_textarea_field($_POST['keywords']);

        $prompt = sprintf(
            __("Analyze and optimize the following content for SEO, focusing on these keywords: %s\n\nContent:\n%s\n\nProvide:\n1. SEO Score (0-100)\n2. Optimization suggestions\n3. Optimized version of the content", 'acf-ai-assistant'),
            $keywords,
            $content
        );

        $seo_analysis = $this->generate_ai_content($prompt);
        if (!is_wp_error($seo_analysis)) {
            wp_send_json_success(array('analysis' => $seo_analysis));
        } else {
            wp_send_json_error($seo_analysis->get_error_message());
        }
    }

    /**
     * Handle image generation AJAX request
     */
    public function handle_image_generation() {
        if (!$this->verify_request()) {
            wp_send_json_error(__('Invalid request', 'acf-ai-assistant'));
        }

        $prompt = sanitize_textarea_field($_POST['prompt']);
        $style = sanitize_text_field($_POST['style']);
        $size = sanitize_text_field($_POST['size']);

        try {
            $response = $this->generate_ai_image($prompt, $size, $style);
            $image_url = $response['data'][0]['url'];

            $upload = $this->download_and_attach_image($image_url, $prompt);
            if (is_wp_error($upload)) {
                throw new Exception($upload->get_error_message());
            }

            wp_send_json_success(array(
                'image_id' => $upload['id'],
                'image_url' => $upload['url']
            ));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle AI-powered content template generation
     */
    public function handle_content_template() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $industry = sanitize_text_field($_POST['industry']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $goals = sanitize_text_field($_POST['goals']);

        $prompt = sprintf(
            "Create a detailed content template for a %s in the %s industry with these goals: %s\n\n" .
            "Include:\n" .
            "1. Structure outline\n" .
            "2. Key sections to include\n" .
            "3. Suggested word counts\n" .
            "4. Content tips\n" .
            "5. SEO recommendations\n" .
            "6. Call-to-action suggestions",
            $content_type,
            $industry,
            $goals
        );

        $template = $this->api->generate_content($prompt);
        wp_send_json_success(array('template' => $template));
    }

    /**
     * Handle content scoring and recommendations
     */
    public function handle_content_scoring() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $target_audience = sanitize_text_field($_POST['target_audience']);
        $goals = sanitize_text_field($_POST['goals']);

        $prompt = sprintf(
            "Analyze this content and provide a detailed score and recommendations:\n\n" .
            "Content:\n%s\n\n" .
            "Target Audience: %s\n" .
            "Goals: %s\n\n" .
            "Provide:\n" .
            "1. Overall score (0-100)\n" .
            "2. Readability score\n" .
            "3. Engagement potential\n" .
            "4. SEO effectiveness\n" .
            "5. Specific improvement suggestions\n" .
            "6. Areas of excellence",
            $content,
            $target_audience,
            $goals
        );

        $analysis = $this->api->generate_content($prompt);
        wp_send_json_success(array('analysis' => $analysis));
    }

    /**
     * Handle A/B testing suggestions
     */
    public function handle_ab_testing() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $element_type = sanitize_text_field($_POST['element_type']);
        $conversion_goal = sanitize_text_field($_POST['conversion_goal']);

        $prompt = sprintf(
            "Generate A/B testing variations for this %s, optimized for %s:\n\n" .
            "Original Content:\n%s\n\n" .
            "Provide:\n" .
            "1. 3 alternative versions\n" .
            "2. Expected impact on conversion\n" .
            "3. Testing hypothesis for each variation\n" .
            "4. Recommended test duration\n" .
            "5. Success metrics to track",
            $element_type,
            $conversion_goal,
            $content
        );

        $variations = $this->api->generate_content($prompt);
        wp_send_json_success(array('variations' => $variations));
    }

    /**
     * Handle content calendar planning
     */
    public function handle_content_calendar() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $industry = sanitize_text_field($_POST['industry']);
        $duration = intval($_POST['duration']);
        $goals = sanitize_text_field($_POST['goals']);
        $channels = sanitize_text_field($_POST['channels']);

        $prompt = sprintf(
            "Create a %d-month content calendar for a %s business focusing on %s across these channels: %s\n\n" .
            "Include:\n" .
            "1. Content themes by month\n" .
            "2. Content types and frequency\n" .
            "3. Key dates and events to leverage\n" .
            "4. Content distribution strategy\n" .
            "5. Success metrics for each content type\n" .
            "6. Resource requirements",
            $duration,
            $industry,
            $goals,
            $channels
        );

        $calendar = $this->api->generate_content($prompt);
        wp_send_json_success(array('calendar' => $calendar));
    }

    /**
     * Handle voice and tone analysis
     */
    public function handle_voice_tone() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $brand_guidelines = sanitize_textarea_field($_POST['brand_guidelines']);

        $prompt = sprintf(
            "Analyze the voice and tone of this content against brand guidelines:\n\n" .
            "Content:\n%s\n\n" .
            "Brand Guidelines:\n%s\n\n" .
            "Provide:\n" .
            "1. Voice consistency score\n" .
            "2. Tone appropriateness\n" .
            "3. Brand alignment analysis\n" .
            "4. Specific examples of good/poor alignment\n" .
            "5. Improvement suggestions\n" .
            "6. Alternative phrasings that better match the brand voice",
            $content,
            $brand_guidelines
        );

        $analysis = $this->api->generate_content($prompt);
        wp_send_json_success(array('analysis' => $analysis));
    }

    /**
     * Handle content repurposing suggestions
     */
    public function handle_content_repurpose() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $target_formats = sanitize_text_field($_POST['target_formats']);

        $prompt = sprintf(
            "Suggest ways to repurpose this content across different formats:\n\n" .
            "Original Content:\n%s\n\n" .
            "Target Formats: %s\n\n" .
            "For each format, provide:\n" .
            "1. Adapted content structure\n" .
            "2. Key points to highlight\n" .
            "3. Format-specific enhancements\n" .
            "4. Distribution channels\n" .
            "5. Expected engagement metrics\n" .
            "6. Required resources and time investment",
            $content,
            $target_formats
        );

        $suggestions = $this->api->generate_content($prompt);
        wp_send_json_success(array('suggestions' => $suggestions));
    }

    /**
     * Verify AJAX request authenticity and permissions
     *
     * @return bool Whether the request is valid
     */
    private function verify_request() {
        return check_ajax_referer('acf_ai_nonce', 'nonce', false) && 
               current_user_can('edit_posts') && 
               ACF_AI_License::get_instance()->is_license_valid();
    }

    /**
     * Verify premium request
     *
     * @return bool Whether the request is valid
     */
    private function verify_premium_request() {
        return check_ajax_referer('acf_ai_nonce', 'nonce', false) && 
               current_user_can('edit_posts') && 
               ACF_AI_License::get_instance()->is_license_valid();
    }

    /**
     * Generate a smart prompt based on post data
     *
     * @param WP_Post $post The post object
     * @param string $content_type Type of content to generate
     * @param string $tone Desired tone of content
     * @return string Generated prompt
     */
    private function generate_smart_prompt($post, $content_type, $tone) {
        $post_data = array(
            'title' => $post->post_title,
            'excerpt' => $post->post_excerpt,
            'categories' => wp_get_post_categories($post->ID, array('fields' => 'names')),
            'tags' => wp_get_post_tags($post->ID, array('fields' => 'names')),
        );

        return sprintf(
            __("Generate %s content with a %s tone for a post with the following details:\nTitle: %s\nExcerpt: %s\nCategories: %s\nTags: %s", 'acf-ai-assistant'),
            $content_type,
            $tone,
            $post_data['title'],
            $post_data['excerpt'],
            implode(', ', $post_data['categories']),
            implode(', ', $post_data['tags'])
        );
    }

    /**
     * Generate AI content using the API
     *
     * @param string $prompt The prompt to send to the AI
     * @return string|WP_Error Generated content or error
     */
    private function generate_ai_content($prompt) {
        return ACF_AI_API::get_instance()->generate_content($prompt);
    }

    /**
     * Generate an AI image using the API
     *
     * @param string $prompt Image description
     * @param string $size Desired image size
     * @param string $style Image style
     * @return array|WP_Error API response or error
     */
    private function generate_ai_image($prompt, $size, $style) {
        $api = ACF_AI_API::get_instance();
        return $api->generate_image(array(
            'prompt' => $this->enhance_image_prompt($prompt, $style),
            'size' => $size,
            'n' => 1,
            'response_format' => 'url',
        ));
    }

    /**
     * Enhance image prompt with style-specific prefix
     *
     * @param string $prompt Original prompt
     * @param string $style Desired style
     * @return string Enhanced prompt
     */
    private function enhance_image_prompt($prompt, $style) {
        $style_prompts = array(
            'realistic' => __('Create a photorealistic image of', 'acf-ai-assistant'),
            'artistic' => __('Create an artistic interpretation of', 'acf-ai-assistant'),
            'cartoon' => __('Create a cartoon-style illustration of', 'acf-ai-assistant'),
            'minimalist' => __('Create a minimalist design of', 'acf-ai-assistant'),
        );

        return $style_prompts[$style] . ' ' . $prompt;
    }

    /**
     * Download and attach an image to the media library
     *
     * @param string $url Image URL
     * @param string $title Image title
     * @return array|WP_Error Array with ID and URL on success, WP_Error on failure
     */
    private function download_and_attach_image($url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return $tmp;
        }

        $file_array = array(
            'name' => sanitize_title($title) . '.png',
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, 0);
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return $id;
        }

        return array(
            'id' => $id,
            'url' => wp_get_attachment_url($id)
        );
    }
}
