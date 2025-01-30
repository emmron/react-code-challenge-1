<?php

if (!defined('ABSPATH')) {
    exit;
}

class ACF_AI_Marketing {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('acf/init', array($this, 'register_marketing_fields'));
        add_action('wp_ajax_acf_ai_generate_headlines', array($this, 'handle_headline_generation'));
        add_action('wp_ajax_acf_ai_generate_social', array($this, 'handle_social_content'));
        add_action('wp_ajax_acf_ai_generate_email', array($this, 'handle_email_content'));
        add_action('wp_ajax_acf_ai_competitive_analysis', array($this, 'handle_competitive_analysis'));
        add_action('wp_ajax_acf_ai_content_upgrade', array($this, 'handle_content_upgrade'));
        add_action('wp_ajax_acf_ai_generate_cta', array($this, 'handle_cta_generation'));
    }

    public function register_marketing_fields() {
        // Headline Generator Field
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_headlines',
            'title' => 'AI Headline Generator',
            'fields' => array(
                array(
                    'key' => 'field_ai_headline_style',
                    'label' => 'Headline Style',
                    'name' => 'ai_headline_style',
                    'type' => 'select',
                    'choices' => array(
                        'curiosity' => 'Curiosity-Driven',
                        'benefit' => 'Benefit-Focused',
                        'emotional' => 'Emotional Appeal',
                        'urgency' => 'Urgency/Scarcity',
                        'numbers' => 'Number-Based',
                        'how_to' => 'How-To/Tutorial',
                    ),
                ),
                array(
                    'key' => 'field_ai_headline_count',
                    'label' => 'Number of Variations',
                    'name' => 'ai_headline_count',
                    'type' => 'number',
                    'min' => 3,
                    'max' => 10,
                    'default_value' => 5,
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

        // Social Media Content Generator
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_social',
            'title' => 'AI Social Media Generator',
            'fields' => array(
                array(
                    'key' => 'field_ai_social_platform',
                    'label' => 'Platform',
                    'name' => 'ai_social_platform',
                    'type' => 'select',
                    'choices' => array(
                        'twitter' => 'Twitter/X',
                        'linkedin' => 'LinkedIn',
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'tiktok' => 'TikTok',
                        'pinterest' => 'Pinterest',
                    ),
                ),
                array(
                    'key' => 'field_ai_social_goal',
                    'label' => 'Content Goal',
                    'name' => 'ai_social_goal',
                    'type' => 'select',
                    'choices' => array(
                        'engagement' => 'Drive Engagement',
                        'traffic' => 'Drive Traffic',
                        'leads' => 'Generate Leads',
                        'brand' => 'Brand Awareness',
                        'sales' => 'Direct Sales',
                    ),
                ),
                array(
                    'key' => 'field_ai_hashtags',
                    'label' => 'Generate Hashtags',
                    'name' => 'ai_hashtags',
                    'type' => 'true_false',
                    'default_value' => 1,
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

        // Email Marketing Generator
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_email',
            'title' => 'AI Email Marketing Generator',
            'fields' => array(
                array(
                    'key' => 'field_ai_email_type',
                    'label' => 'Email Type',
                    'name' => 'ai_email_type',
                    'type' => 'select',
                    'choices' => array(
                        'newsletter' => 'Newsletter',
                        'welcome' => 'Welcome Series',
                        'promotional' => 'Promotional',
                        'abandoned_cart' => 'Abandoned Cart',
                        'follow_up' => 'Follow-up',
                        'reengagement' => 'Re-engagement',
                    ),
                ),
                array(
                    'key' => 'field_ai_email_tone',
                    'label' => 'Email Tone',
                    'name' => 'ai_email_tone',
                    'type' => 'select',
                    'choices' => array(
                        'professional' => 'Professional',
                        'friendly' => 'Friendly',
                        'urgent' => 'Urgent',
                        'promotional' => 'Promotional',
                        'educational' => 'Educational',
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

        // Competitive Analysis Tool
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_competitive',
            'title' => 'AI Competitive Analysis',
            'fields' => array(
                array(
                    'key' => 'field_ai_competitor_urls',
                    'label' => 'Competitor URLs',
                    'name' => 'ai_competitor_urls',
                    'type' => 'textarea',
                    'instructions' => 'Enter competitor URLs (one per line)',
                ),
                array(
                    'key' => 'field_ai_analysis_focus',
                    'label' => 'Analysis Focus',
                    'name' => 'ai_analysis_focus',
                    'type' => 'select',
                    'choices' => array(
                        'content_gaps' => 'Content Gaps',
                        'keywords' => 'Keyword Opportunities',
                        'topics' => 'Topic Coverage',
                        'style' => 'Writing Style',
                        'structure' => 'Content Structure',
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

        // Call-to-Action Generator
        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_cta',
            'title' => 'AI CTA Generator',
            'fields' => array(
                array(
                    'key' => 'field_ai_cta_type',
                    'label' => 'CTA Type',
                    'name' => 'ai_cta_type',
                    'type' => 'select',
                    'choices' => array(
                        'button' => 'Button Text',
                        'inline' => 'Inline Text',
                        'banner' => 'Banner Copy',
                        'popup' => 'Popup Content',
                        'sidebar' => 'Sidebar Widget',
                    ),
                ),
                array(
                    'key' => 'field_ai_cta_goal',
                    'label' => 'Conversion Goal',
                    'name' => 'ai_cta_goal',
                    'type' => 'select',
                    'choices' => array(
                        'subscribe' => 'Newsletter Signup',
                        'download' => 'Resource Download',
                        'purchase' => 'Make Purchase',
                        'contact' => 'Contact Sales',
                        'trial' => 'Start Trial',
                        'demo' => 'Book Demo',
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

    public function handle_headline_generation() {
        // Verify nonce first
        if (!wp_verify_nonce($_POST['nonce'], 'acf_ai_assistant')) {
            wp_send_json_error(__('Security check failed', 'acf-ai-assistant'), 403);
        }

        // Check user capabilities
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'acf-ai-assistant'), 403);
        }

        // License check
        if (!$this->verify_premium_request()) {
            wp_send_json_error(__('Premium feature not available', 'acf-ai-assistant'), 402);
        }

        // Sanitize input
        $content = sanitize_textarea_field($_POST['content']);
        $style = sanitize_text_field($_POST['style']);
        $count = min(10, intval($_POST['count']));

        $prompt = sprintf(
            "Generate %d %s-style headlines for the following content. Make them compelling and optimized for clicks while maintaining authenticity:\n\n%s",
            $count,
            $style,
            $content
        );

        $headlines = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($headlines)) {
            wp_send_json_success(array('headlines' => $headlines));
        } else {
            wp_send_json_error($headlines->get_error_message());
        }
    }

    public function handle_social_content() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $platform = sanitize_text_field($_POST['platform']);
        $goal = sanitize_text_field($_POST['goal']);
        $include_hashtags = isset($_POST['hashtags']) && $_POST['hashtags'] == '1';

        $prompt = sprintf(
            "Create engaging social media content for %s with the goal of %s. Include relevant hashtags: %s\n\nContent to adapt:\n%s",
            $platform,
            $goal,
            $include_hashtags ? 'yes' : 'no',
            $content
        );

        $social_content = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($social_content)) {
            wp_send_json_success(array('content' => $social_content));
        } else {
            wp_send_json_error($social_content->get_error_message());
        }
    }

    public function handle_email_content() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $type = sanitize_text_field($_POST['type']);
        $tone = sanitize_text_field($_POST['tone']);

        $prompt = sprintf(
            "Generate a %s email with a %s tone based on this content. Include subject line, preview text, and email body:\n\n%s",
            $type,
            $tone,
            $content
        );

        $email = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($email)) {
            wp_send_json_success(array('email' => $email));
        } else {
            wp_send_json_error($email->get_error_message());
        }
    }

    public function handle_competitive_analysis() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $urls = sanitize_textarea_field($_POST['urls']);
        $focus = sanitize_text_field($_POST['focus']);
        $content = sanitize_textarea_field($_POST['content']);

        $prompt = sprintf(
            "Analyze the following content against competitors, focusing on %s. Provide actionable insights and recommendations:\n\nOur Content:\n%s\n\nCompetitor URLs:\n%s",
            $focus,
            $content,
            $urls
        );

        $analysis = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($analysis)) {
            wp_send_json_success(array('analysis' => $analysis));
        } else {
            wp_send_json_error($analysis->get_error_message());
        }
    }

    public function handle_content_upgrade() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        
        $prompt = sprintf(
            "Analyze and upgrade this content to make it more engaging, persuasive, and valuable. Provide the following:\n" .
            "1. Enhanced version of the content\n" .
            "2. Added value elements (examples, statistics, expert quotes)\n" .
            "3. Improved structure and flow\n" .
            "4. Content upgrades suggestions (downloadables, resources)\n\n" .
            "Content to upgrade:\n%s",
            $content
        );

        $upgrade = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($upgrade)) {
            wp_send_json_success(array('upgrade' => $upgrade));
        } else {
            wp_send_json_error($upgrade->get_error_message());
        }
    }

    public function handle_cta_generation() {
        if (!$this->verify_premium_request()) {
            wp_send_json_error('Premium feature not available');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $type = sanitize_text_field($_POST['type']);
        $goal = sanitize_text_field($_POST['goal']);

        $prompt = sprintf(
            "Generate compelling %s CTA content to achieve the goal of %s. Base it on this content:\n\n%s",
            $type,
            $goal,
            $content
        );

        $cta = ACF_AI_API::get_instance()->generate_content($prompt);
        if (!is_wp_error($cta)) {
            wp_send_json_success(array('cta' => $cta));
        } else {
            wp_send_json_error($cta->get_error_message());
        }
    }

    private function verify_premium_request() {
        return check_ajax_referer('acf_ai_nonce', 'nonce', false) && 
               current_user_can('edit_posts') && 
               ACF_AI_License::get_instance()->is_premium_license_valid();
    }
}
