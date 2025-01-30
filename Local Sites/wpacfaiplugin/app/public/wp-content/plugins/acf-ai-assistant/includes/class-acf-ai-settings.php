<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACF AI Assistant Settings
 *
 * @package ACF_AI_Assistant
 */

/**
 * ACF AI Assistant Settings Manager
 * 
 * Handles all plugin settings, admin UI, premium features, and analytics.
 * Implements singleton pattern for global access.
 */
class ACF_AI_Settings {

    /**
     * Settings instance.
     *
     * @var ACF_AI_Settings
     */
    private static $instance = null;

    /**
     * Settings page slug.
     *
     * @var string
     */
    private $page_slug = 'acf-ai-settings';

    /**
     * Settings group name.
     *
     * @var string
     */
    private $option_group = 'acf_ai_settings';

    /** @var array Core settings */
    private const SETTINGS = [
        'openai' => [
            'api_key' => '',
            'model' => 'gpt-3.5-turbo',
            'tokens' => 1000
        ],
        'features' => [
            'headlines' => false,
            'social' => false, 
            'email' => false,
            'analysis' => false,
            'content' => false,
            'cta' => false
        ],
        'analytics' => [
            'warning_threshold' => 80,
            'tracking' => false
        ]
    ];

    /** @var array Admin menu structure */
    private const MENU = [
        'parent' => [
            'title' => 'ACF AI Assistant',
            'menu' => 'ACF AI',
            'capability' => 'manage_options',
            'slug' => 'acf-ai',
            'icon' => 'dashicons-robot',
            'position' => 30
        ],
        'children' => [
            'features' => [
                'title' => 'Premium Features',
                'menu' => 'Features'
            ],
            'stats' => [
                'title' => 'Analytics',
                'menu' => 'Stats'
            ]
        ]
    ];

    /**
     * Get settings instance.
     *
     * @return ACF_AI_Settings
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize settings.
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        $this->init_hooks();
        $this->maybe_enable_dev();
    }

    /**
     * Register core hooks
     */
    private function init_hooks(): void {
        add_action('admin_menu', [$this, 'setup_menu']);
        add_action('admin_enqueue_scripts', [$this, 'load_assets']);
    }

    /**
     * Enable dev features on local environments
     */
    private function maybe_enable_dev(): void {
        if ($this->is_local()) {
            add_filter('acf_ai_license', '__return_true');
            add_filter('acf_ai_features', fn($f) => array_map(fn() => true, $f));
        }
    }

    /**
     * Check if running locally
     */
    private function is_local(): bool {
        return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
            || str_contains($_SERVER['HTTP_HOST'] ?? '', '.local')
            || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');
    }

    /**
     * Create admin menu structure
     */
    public function setup_menu(): void {
        $parent = self::MENU['parent'];
        
        add_menu_page(
            $parent['title'],
            $parent['menu'],
            $parent['capability'],
            $parent['slug'],
            [$this, 'render_main'],
            $parent['icon'],
            $parent['position']
        );

        foreach (self::MENU['children'] as $id => $child) {
            add_submenu_page(
                $parent['slug'],
                $child['title'],
                $child['menu'],
                $parent['capability'],
                "{$parent['slug']}-{$id}",
                [$this, "render_{$id}"]
            );
        }
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting(
            $this->option_group,
            'acf_ai_openai_key',
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this, 'sanitize_api_key' ),
                'default'           => '',
            )
        );

        register_setting(
            $this->option_group,
            'acf_ai_default_model',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'gpt-3.5-turbo',
            )
        );

        register_setting(
            $this->option_group,
            'acf_ai_max_tokens',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 2000,
            )
        );

        add_settings_section(
            'acf_ai_api_settings',
            __( 'API Settings', 'acf-ai-assistant' ),
            array( $this, 'render_api_section' ),
            $this->page_slug
        );

        add_settings_field(
            'acf_ai_openai_key',
            __( 'OpenAI API Key', 'acf-ai-assistant' ),
            array( $this, 'render_api_key_field' ),
            $this->page_slug,
            'acf_ai_api_settings'
        );

        add_settings_field(
            'acf_ai_default_model',
            __( 'Default Model', 'acf-ai-assistant' ),
            array( $this, 'render_model_field' ),
            $this->page_slug,
            'acf_ai_api_settings'
        );

        add_settings_field(
            'acf_ai_max_tokens',
            __( 'Max Tokens', 'acf-ai-assistant' ),
            array( $this, 'render_max_tokens_field' ),
            $this->page_slug,
            'acf_ai_api_settings'
        );

        // Features
        foreach (array_keys(self::SETTINGS['features']) as $feature) {
            register_setting('acf_ai', "acf_ai_{$feature}", [
                'type' => 'boolean',
                'default' => false
            ]);
        }

        // Analytics
        register_setting('acf_ai', 'acf_ai_tracking', [
            'type' => 'boolean',
            'default' => self::SETTINGS['analytics']['tracking']
        ]);

        register_setting('acf_ai', 'acf_ai_warning', [
            'type' => 'integer',
            'sanitize_callback' => [$this, 'validate_threshold'],
            'default' => self::SETTINGS['analytics']['warning_threshold']
        ]);
    }

    /**
     * Load admin assets
     */
    public function load_assets(string $hook): void {
        if (!$this->is_plugin_page($hook)) {
            return;
        }

        wp_enqueue_style('acf-ai-admin', 
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            [],
            ACF_AI_VERSION
        );

        wp_enqueue_script('acf-ai-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            ['jquery'],
            ACF_AI_VERSION,
            true
        );
    }

    /**
     * Check if current page belongs to plugin
     */
    private function is_plugin_page(string $hook): bool {
        $pages = array_merge(
            ["toplevel_page_{$this->MENU['parent']['slug']}"],
            array_map(
                fn($id) => "{$this->MENU['parent']['slug']}_page_{$id}",
                array_keys(self::MENU['children'])
            )
        );
        return in_array($hook, $pages, true);
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( $this->page_slug );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render API settings section.
     */
    public function render_api_section() {
        ?>
        <p>
            <?php
            printf(
                /* translators: %s: OpenAI API key URL */
                esc_html__( 'Enter your OpenAI API key. You can get one from %s.', 'acf-ai-assistant' ),
                '<a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render API key field.
     */
    public function render_api_key_field() {
        $api_key = get_option( 'acf_ai_openai_key' );
        ?>
        <input
            type="password"
            name="acf_ai_openai_key"
            value="<?php echo esc_attr( $api_key ); ?>"
            class="regular-text"
            autocomplete="new-password"
        />
        <?php
    }

    /**
     * Render model selection field.
     */
    public function render_model_field() {
        $current_model = get_option( 'acf_ai_default_model', 'gpt-3.5-turbo' );
        $models = array(
            'gpt-3.5-turbo' => __( 'GPT-3.5 Turbo (Fast & Affordable)', 'acf-ai-assistant' ),
            'gpt-4'         => __( 'GPT-4 (Most Capable)', 'acf-ai-assistant' ),
            'gpt-4-turbo'   => __( 'GPT-4 Turbo (Latest Version)', 'acf-ai-assistant' ),
        );
        ?>
        <select name="acf_ai_default_model">
            <?php foreach ( $models as $model => $label ) : ?>
                <option value="<?php echo esc_attr( $model ); ?>" <?php selected( $current_model, $model ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render max tokens field.
     */
    public function render_max_tokens_field() {
        $max_tokens = get_option( 'acf_ai_max_tokens', 2000 );
        ?>
        <input
            type="number"
            name="acf_ai_max_tokens"
            value="<?php echo esc_attr( $max_tokens ); ?>"
            class="small-text"
            min="100"
            max="8000"
            step="100"
        />
        <p class="description">
            <?php esc_html_e( 'Maximum number of tokens to generate. Higher values may result in higher costs.', 'acf-ai-assistant' ); ?>
        </p>
        <?php
    }

    /**
     * Sanitize API key.
     *
     * @param string $value API key value.
     * @return string
     */
    public function sanitize_api_key( $value ) {
        $value = sanitize_text_field( $value );
        
        if ( ! empty( $value ) && ! preg_match( '/^sk-[a-zA-Z0-9]+$/', $value ) ) {
            add_settings_error(
                'acf_ai_openai_key',
                'invalid_api_key',
                __( 'Invalid OpenAI API key format.', 'acf-ai-assistant' )
            );
            return get_option( 'acf_ai_openai_key' );
        }

        return $value;
    }

    /**
     * Get analytics data
     */
    private function get_stats(): array {
        if ($this->is_local()) {
            return [
                'tokens' => rand(1000, 10000),
                'calls' => rand(100, 1000),
                'top' => ['Headlines', 'Social', 'Email'][rand(0,2)]
            ];
        }

        return [
            'tokens' => $this->get_token_usage(),
            'calls' => $this->get_api_calls(),
            'top' => $this->get_top_feature()
        ];
    }

    /**
     * Get token usage from DB
     */
    private function get_token_usage(): int {
        global $wpdb;
        // TODO: Implement token tracking
        return 0;
    }

    /**
     * Get API call count from DB
     */
    private function get_api_calls(): int {
        global $wpdb;
        // TODO: Implement call tracking
        return 0;
    }

    /**
     * Get most used feature from DB
     */
    private function get_top_feature(): string {
        global $wpdb;
        // TODO: Implement feature tracking
        return '';
    }

    /**
     * Render admin pages
     */
    public function render_main(): void {
        require plugin_dir_path(__FILE__) . 'views/main.php';
    }

    public function render_features(): void {
        require plugin_dir_path(__FILE__) . 'views/features.php';
    }

    public function render_stats(): void {
        $stats = $this->get_stats();
        require plugin_dir_path(__FILE__) . 'views/stats.php';
    }

}