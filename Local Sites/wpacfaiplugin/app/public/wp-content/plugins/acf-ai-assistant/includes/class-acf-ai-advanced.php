<?php

if (!defined('ABSPATH')) {
    exit;
}

class ACF_AI_Advanced {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('acf/init', array($this, 'register_advanced_fields'));
        add_action('wp_ajax_acf_ai_analyze_sentiment', array($this, 'handle_sentiment_analysis'));
        add_action('wp_ajax_acf_ai_generate_schema', array($this, 'handle_schema_generation'));
        add_action('wp_ajax_acf_ai_generate_variations', array($this, 'handle_content_variations'));
        add_action('wp_ajax_acf_ai_summarize', array($this, 'handle_content_summary'));
        add_action('wp_ajax_acf_ai_extract_keywords', array($this, 'handle_keyword_extraction'));
        add_action('wp_ajax_acf_ai_generate_faq', array($this, 'handle_faq_generation'));
        add_action('wp_ajax_acf_ai_field_creation', array($this, 'handle_field_creation'));
    }

    public function register_advanced_fields() {
        // Content Variations Field
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_variations',
            'title' => 'AI Content Variations',
            'fields' => array(
                array(
                    'key' => 'field_ai_variation_count',
                    'label' => 'Number of Variations',
                    'name' => 'ai_variation_count',
                    'type' => 'number',
                    'min' => 1,
                    'max' => 5,
                    'default_value' => 3,
                ),
                array(
                    'key' => 'field_ai_variation_length',
                    'label' => 'Variation Length',
                    'name' => 'ai_variation_length',
                    'type' => 'select',
                    'choices' => array(
                        'shorter' => 'Shorter Version',
                        'similar' => 'Similar Length',
                        'longer' => 'Extended Version',
                    ),
                    'default_value' => 'similar',
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

        // Schema Generator Field
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_schema',
            'title' => 'AI Schema Generator',
            'fields' => array(
                array(
                    'key' => 'field_ai_schema_type',
                    'label' => 'Schema Type',
                    'name' => 'ai_schema_type',
                    'type' => 'select',
                    'choices' => array(
                        'Article' => 'Article',
                        'Product' => 'Product',
                        'FAQPage' => 'FAQ Page',
                        'Event' => 'Event',
                        'Recipe' => 'Recipe',
                        'Review' => 'Review',
                        'Service' => 'Service',
                        'Organization' => 'Organization',
                    ),
                ),
                array(
                    'key' => 'field_ai_schema_preview',
                    'label' => 'Generated Schema',
                    'name' => 'ai_schema_preview',
                    'type' => 'textarea',
                    'readonly' => 1,
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

        // Content Analysis Field
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_analysis',
            'title' => 'AI Content Analysis',
            'fields' => array(
                array(
                    'key' => 'field_ai_sentiment',
                    'label' => 'Sentiment Analysis',
                    'name' => 'ai_sentiment',
                    'type' => 'message',
                ),
                array(
                    'key' => 'field_ai_keywords',
                    'label' => 'Key Phrases',
                    'name' => 'ai_keywords',
                    'type' => 'textarea',
                    'readonly' => 1,
                ),
                array(
                    'key' => 'field_ai_summary',
                    'label' => 'AI Summary',
                    'name' => 'ai_summary',
                    'type' => 'textarea',
                    'readonly' => 1,
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

        // FAQ Generator Field
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_faq',
            'title' => 'AI FAQ Generator',
            'fields' => array(
                array(
                    'key' => 'field_ai_faq_count',
                    'label' => 'Number of FAQs',
                    'name' => 'ai_faq_count',
                    'type' => 'number',
                    'min' => 3,
                    'max' => 15,
                    'default_value' => 5,
                ),
                array(
                    'key' => 'field_ai_faq_focus',
                    'label' => 'FAQ Focus',
                    'name' => 'ai_faq_focus',
                    'type' => 'select',
                    'choices' => array(
                        'general' => 'General Information',
                        'technical' => 'Technical Details',
                        'benefits' => 'Benefits & Features',
                        'comparison' => 'Comparisons',
                        'troubleshooting' => 'Troubleshooting',
                    ),
                ),
                array(
                    'key' => 'field_ai_faq_output',
                    'label' => 'Generated FAQs',
                    'name' => 'ai_faq_output',
                    'type' => 'repeater',
                    'layout' => 'block',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_ai_faq_question',
                            'label' => 'Question',
                            'name' => 'question',
                            'type' => 'text',
                        ),
                        array(
                            'key' => 'field_ai_faq_answer',
                            'label' => 'Answer',
                            'name' => 'answer',
                            'type' => 'textarea',
                        ),
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

    public function handle_sentiment_analysis() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        
        $prompt = sprintf(
            "Analyze the sentiment and tone of the following content. Provide a detailed analysis including:\n" .
            "1. Overall sentiment (positive/negative/neutral)\n" .
            "2. Emotional tone\n" .
            "3. Writing style\n" .
            "4. Key emotional triggers\n\n" .
            "Content:\n%s",
            $content
        );

        $analysis = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($analysis)) {
            wp_send_json_success(array('analysis' => $analysis));
        } else {
            wp_send_json_error($analysis->get_error_message());
        }
    }

    public function handle_schema_generation() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $post_id = intval($_POST['post_id']);
        $schema_type = sanitize_text_field($_POST['schema_type']);
        
        $post = get_post($post_id);
        $post_meta = get_post_meta($post_id);
        
        $prompt = sprintf(
            "Generate a complete JSON-LD schema markup for a %s based on this content:\n" .
            "Title: %s\n" .
            "Content: %s\n" .
            "Include all relevant fields for SEO optimization.",
            $schema_type,
            $post->post_title,
            wp_strip_all_tags($post->post_content)
        );

        $schema = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($schema)) {
            wp_send_json_success(array('schema' => $schema));
        } else {
            wp_send_json_error($schema->get_error_message());
        }
    }

    public function handle_content_variations() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $count = min(5, intval($_POST['count']));
        $length = sanitize_text_field($_POST['length']);
        
        $prompt = sprintf(
            "Generate %d unique variations of the following content, maintaining the same message but with %s length and different writing styles:\n\n%s",
            $count,
            $length,
            $content
        );

        $variations = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($variations)) {
            wp_send_json_success(array('variations' => $variations));
        } else {
            wp_send_json_error($variations->get_error_message());
        }
    }

    public function handle_content_summary() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        
        $prompt = sprintf(
            "Create a comprehensive yet concise summary of the following content, highlighting the main points and key takeaways:\n\n%s",
            $content
        );

        $summary = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($summary)) {
            wp_send_json_success(array('summary' => $summary));
        } else {
            wp_send_json_error($summary->get_error_message());
        }
    }

    public function handle_keyword_extraction() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        
        $prompt = sprintf(
            "Extract and analyze key phrases from this content. For each key phrase, provide its importance score and context:\n\n%s",
            $content
        );

        $keywords = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($keywords)) {
            wp_send_json_success(array('keywords' => $keywords));
        } else {
            wp_send_json_error($keywords->get_error_message());
        }
    }

    public function handle_faq_generation() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $count = min(15, intval($_POST['count']));
        $focus = sanitize_text_field($_POST['focus']);
        
        $prompt = sprintf(
            "Generate %d frequently asked questions and detailed answers about this content, focusing on %s aspects:\n\n%s",
            $count,
            $focus,
            $content
        );

        $faqs = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($faqs)) {
            wp_send_json_success(array('faqs' => $faqs));
        } else {
            wp_send_json_error($faqs->get_error_message());
        }
    }

    public function handle_field_creation() {
        if (!$this->verify_premium_request()) {
            ACF_AI_Response::error('premium_required', __('Premium feature not available', 'acf-ai-assistant'), 402);
        }

        $description = sanitize_textarea_field($_POST['description']);
        $post_type = sanitize_text_field($_POST['post_type']);
        
        $prompt = sprintf(
            "Generate ACF field group configuration in JSON format for: %s\n" .
            "Post Type: %s\n" .
            "Include:\n" .
            "- Logical field names\n" .
            "- Appropriate field types\n" .
            "- Conditional logic\n" .
            "- Field labels and instructions\n" .
            "- Required validation\n" .
            "- Return only valid JSON",
            $description,
            $post_type
        );

        $field_group = new ACF_AI_Field_Group();
        $result = $field_group->generate_fields($prompt);
        
        if (is_wp_error($result)) {
            ACF_AI_Response::error('field_creation_failed', $result->get_error_message(), 500);
        }
        
        ACF_AI_Response::success([
            'field_group' => $result,
            'edit_link' => admin_url('post.php?post=' . $result['ID'] . '&action=edit')
        ]);
    }

    private function verify_premium_request() {
        return check_ajax_referer('acf_ai_nonce', 'nonce', false) && 
               current_user_can('edit_posts') && 
               ACF_AI_License::get_instance()->is_premium_license_valid();
    }
} 