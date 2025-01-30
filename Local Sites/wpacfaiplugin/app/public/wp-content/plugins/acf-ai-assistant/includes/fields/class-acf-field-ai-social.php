<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('acf_field_ai_social')):

class acf_field_ai_social extends acf_field {
    public function initialize() {
        $this->name = 'ai_social';
        $this->label = __('AI Social Media Generator', 'acf-ai-assistant');
        $this->category = 'content';
        $this->defaults = array(
            'platform' => 'twitter',
            'tone' => 'professional',
            'include_hashtags' => 1
        );

        add_action('wp_ajax_generate_social_content', array($this, 'ajax_generate_social'));
    }

    public function render_field($field) {
        $premium = ACF_AI_Premium::get_instance();
        $license = ACF_AI_License::get_instance();

        // Check if premium is active
        if (!$license->is_premium_active()) {
            echo '<div class="acf-notice -error"><p>';
            echo sprintf(
                __('This field requires a premium license. <a href="%s">Upgrade now</a> to unlock AI social media content generation.', 'acf-ai-assistant'),
                $license->get_subscription_url()
            );
            echo '</p></div>';
            return;
        }

        ?>
        <div class="acf-ai-social-generator">
            <div class="acf-input-wrap">
                <textarea 
                    id="<?php echo esc_attr($field['id']); ?>" 
                    name="<?php echo esc_attr($field['name']); ?>"
                    class="acf-ai-social-input"
                    rows="4"
                ><?php echo esc_textarea($field['value']); ?></textarea>
            </div>

            <div class="acf-ai-social-controls">
                <div class="acf-ai-social-options">
                    <label>
                        <?php _e('Source Content:', 'acf-ai-assistant'); ?>
                        <textarea class="acf-ai-social-content" rows="3" placeholder="<?php esc_attr_e('Enter the content to transform into social media posts', 'acf-ai-assistant'); ?>"></textarea>
                    </label>

                    <div class="acf-ai-social-settings">
                        <label>
                            <?php _e('Platform:', 'acf-ai-assistant'); ?>
                            <select class="acf-ai-social-platform">
                                <option value="twitter"><?php _e('Twitter', 'acf-ai-assistant'); ?></option>
                                <option value="linkedin"><?php _e('LinkedIn', 'acf-ai-assistant'); ?></option>
                                <option value="facebook"><?php _e('Facebook', 'acf-ai-assistant'); ?></option>
                                <option value="instagram"><?php _e('Instagram', 'acf-ai-assistant'); ?></option>
                            </select>
                        </label>

                        <label>
                            <?php _e('Tone:', 'acf-ai-assistant'); ?>
                            <select class="acf-ai-social-tone">
                                <option value="professional"><?php _e('Professional', 'acf-ai-assistant'); ?></option>
                                <option value="casual"><?php _e('Casual', 'acf-ai-assistant'); ?></option>
                                <option value="friendly"><?php _e('Friendly', 'acf-ai-assistant'); ?></option>
                                <option value="humorous"><?php _e('Humorous', 'acf-ai-assistant'); ?></option>
                                <option value="formal"><?php _e('Formal', 'acf-ai-assistant'); ?></option>
                            </select>
                        </label>
                    </div>
                </div>

                <button type="button" class="button button-primary acf-ai-generate-social">
                    <?php _e('Generate Social Post', 'acf-ai-assistant'); ?>
                </button>
            </div>

            <div class="acf-ai-social-preview" style="display: none;">
                <h4><?php _e('Generated Post', 'acf-ai-assistant'); ?></h4>
                <div class="acf-ai-social-post"></div>
                <div class="acf-ai-hashtags"></div>
            </div>
        </div>

        <style>
            .acf-ai-social-generator {
                max-width: 600px;
            }
            .acf-ai-social-controls {
                margin: 10px 0;
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #e1e1e1;
                border-radius: 4px;
            }
            .acf-ai-social-options {
                margin-bottom: 15px;
            }
            .acf-ai-social-content {
                width: 100%;
                margin: 5px 0;
            }
            .acf-ai-social-settings {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-top: 10px;
            }
            .acf-ai-social-settings label {
                display: block;
            }
            .acf-ai-social-settings select {
                width: 100%;
                margin-top: 5px;
            }
            .acf-ai-social-preview {
                margin-top: 15px;
                padding: 15px;
                background: #fff;
                border: 1px solid #e1e1e1;
                border-radius: 4px;
            }
            .acf-ai-social-post {
                margin: 10px 0;
                font-size: 1.1em;
            }
            .acf-ai-hashtags {
                margin-top: 10px;
                color: #0073aa;
            }
        </style>

        <script type="text/javascript">
        (function($) {
            function initSocialGenerator(field) {
                const $field = $(field);
                const $input = $field.find('.acf-ai-social-input');
                const $generateBtn = $field.find('.acf-ai-generate-social');
                const $preview = $field.find('.acf-ai-social-preview');
                const $post = $field.find('.acf-ai-social-post');
                const $hashtags = $field.find('.acf-ai-hashtags');

                $generateBtn.on('click', function() {
                    const content = $field.find('.acf-ai-social-content').val();
                    const platform = $field.find('.acf-ai-social-platform').val();
                    const tone = $field.find('.acf-ai-social-tone').val();

                    if (!content) {
                        alert('<?php _e("Please enter some content to transform", "acf-ai-assistant"); ?>');
                        return;
                    }

                    $generateBtn.prop('disabled', true).text('<?php _e("Generating...", "acf-ai-assistant"); ?>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'acf_ai_generate_social',
                            nonce: '<?php echo wp_create_nonce("acf_ai_nonce"); ?>',
                            content: content,
                            platform: platform,
                            tone: tone
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                $input.val(response.data.content).trigger('change');
                                $post.html(response.data.content.replace(/\n/g, '<br>'));
                                
                                if (response.data.hashtags && response.data.hashtags.length) {
                                    $hashtags.html(response.data.hashtags.map(tag => '#' + tag).join(' '));
                                } else {
                                    $hashtags.empty();
                                }
                                
                                $preview.slideDown();
                            } else {
                                alert(response.data || '<?php _e("Error generating social media content", "acf-ai-assistant"); ?>');
                            }
                        },
                        error: function() {
                            alert('<?php _e("Error connecting to server", "acf-ai-assistant"); ?>');
                        },
                        complete: function() {
                            $generateBtn.prop('disabled', false).text('<?php _e("Generate Social Post", "acf-ai-assistant"); ?>');
                        }
                    });
                });
            }

            acf.addAction('ready_field/type=ai_social', initSocialGenerator);
            acf.addAction('append_field/type=ai_social', initSocialGenerator);
        })(jQuery);
        </script>
        <?php
    }

    public function render_field_settings($field) {
        // Default Platform
        acf_render_field_setting($field, array(
            'label'         => __('Default Platform', 'acf-ai-assistant'),
            'instructions'  => __('Default social media platform', 'acf-ai-assistant'),
            'type'         => 'select',
            'name'         => 'platform',
            'choices'      => array(
                'twitter'   => __('Twitter', 'acf-ai-assistant'),
                'linkedin'  => __('LinkedIn', 'acf-ai-assistant'),
                'facebook'  => __('Facebook', 'acf-ai-assistant'),
                'instagram' => __('Instagram', 'acf-ai-assistant'),
            )
        ));

        // Default Tone
        acf_render_field_setting($field, array(
            'label'         => __('Default Tone', 'acf-ai-assistant'),
            'instructions'  => __('Default tone for generated content', 'acf-ai-assistant'),
            'type'         => 'select',
            'name'         => 'tone',
            'choices'      => array(
                'professional' => __('Professional', 'acf-ai-assistant'),
                'casual'      => __('Casual', 'acf-ai-assistant'),
                'friendly'    => __('Friendly', 'acf-ai-assistant'),
                'humorous'    => __('Humorous', 'acf-ai-assistant'),
                'formal'      => __('Formal', 'acf-ai-assistant'),
            )
        ));

        // Include Hashtags
        acf_render_field_setting($field, array(
            'label'         => __('Include Hashtags', 'acf-ai-assistant'),
            'instructions'  => __('Automatically generate and include relevant hashtags', 'acf-ai-assistant'),
            'type'         => 'true_false',
            'name'         => 'include_hashtags',
            'ui'           => 1,
        ));
    }

    public function ajax_generate_social() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'acf_ai_nonce') || !current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $premium = ACF_AI_Premium::get_instance();
        $response = $premium->generate_social_content();

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        wp_send_json_success($response);
    }
}

acf_register_field_type('acf_field_ai_social');

endif;
