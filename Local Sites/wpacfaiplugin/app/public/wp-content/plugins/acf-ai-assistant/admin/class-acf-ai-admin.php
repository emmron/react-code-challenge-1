<?php
if (!defined('ABSPATH')) exit;

class ACF_AI_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_menu() {
        add_options_page(
            __('ACF AI Settings', 'acf-ai-assistant'),
            __('AI Assistant', 'acf-ai-assistant'),
            'manage_options',
            'acf-ai-settings',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
        register_setting('acf_ai_options', 'acf_ai_api_key');
        register_setting('acf_ai_options', 'acf_ai_license_key');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('ACF AI Assistant Settings', 'acf-ai-assistant'); ?></h1>
            <form method="post" action="options.php">
                <?php 
                settings_fields('acf_ai_options');
                do_settings_sections('acf_ai_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('API Key', 'acf-ai-assistant'); ?></th>
                        <td>
                            <input type="password" name="acf_ai_api_key" 
                                   value="<?php echo esc_attr(get_option('acf_ai_api_key')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('License Key', 'acf-ai-assistant'); ?></th>
                        <td>
                            <input type="text" name="acf_ai_license_key" 
                                   value="<?php echo esc_attr(get_option('acf_ai_license_key')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
} 