<?php

if (!defined('ABSPATH')) {
    exit;
}

// Ensure WordPress is loaded
if (!function_exists('add_action')) {
    return;
}

// Require WordPress plugin API
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

class ACF_AI_Stripe {
    private static $instance = null;
    private $stripe_pk;
    private $stripe_sk; 
    private $stripe_initialized = false;
    private $has_dependencies = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Check WordPress dependencies
        if (!function_exists('add_action') || !function_exists('add_filter')) {
            return;
        }

        // Check ACF dependency
        if (!class_exists('ACF')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>ACF AI Assistant requires Advanced Custom Fields to be installed and activated.</p></div>';
            });
            return;
        }

        // Get Stripe keys from constants or environment
        $this->stripe_pk = defined('ACF_AI_STRIPE_PK') ? ACF_AI_STRIPE_PK : getenv('ACF_AI_STRIPE_PK');
        $this->stripe_sk = defined('ACF_AI_STRIPE_SK') ? ACF_AI_STRIPE_SK : getenv('ACF_AI_STRIPE_SK');

        if (empty($this->stripe_pk) || empty($this->stripe_sk)) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>ACF AI Assistant: Stripe API keys not configured. Please set ACF_AI_STRIPE_PK and ACF_AI_STRIPE_SK constants or environment variables.</p></div>';
            });
            return;
        }

        // Register ACF fields for Stripe settings
        add_action('acf/init', array($this, 'register_stripe_fields'));
        
        // Add menu page for Stripe settings
        add_action('admin_menu', array($this, 'add_stripe_menu'));

        // Handle Stripe webhooks
        add_action('init', array($this, 'handle_stripe_webhook'));

        // Initialize Stripe with error handling
        $this->init_stripe();
    }

    private function init_stripe() {
        $autoload_path = ACF_AI_PATH . 'vendor/autoload.php';
        
        // Validate path
        if (empty(ACF_AI_PATH) || !is_string(ACF_AI_PATH)) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>ACF AI Assistant: Invalid plugin path configuration.</p></div>';
            });
            return;
        }

        // Check if composer dependencies exist
        if (!file_exists($autoload_path) || !is_readable($autoload_path)) {
            add_action('admin_notices', function() {
                $message = 'Stripe integration requires Composer dependencies. Please run: <code>composer install</code> in the plugin directory.';
                if (current_user_can('activate_plugins')) {
                    echo '<div class="error"><p>' . wp_kses_post($message) . '</p></div>';
                }
            });
            return;
        }

        try {
            require_once $autoload_path;
            
            if (!class_exists('Stripe\Stripe')) {
                throw new Exception('Stripe PHP SDK not found after loading autoloader.');
            }

            \Stripe\Stripe::setApiKey($this->stripe_sk);
            $this->stripe_initialized = true;
            $this->has_dependencies = true;

        } catch (Exception $e) {
            add_action('admin_notices', function() use ($e) {
                if (current_user_can('activate_plugins')) {
                    $message = sprintf(
                        'Failed to initialize Stripe: %s',
                        esc_html($e->getMessage())
                    );
                    echo '<div class="error"><p>' . wp_kses_post($message) . '</p></div>';
                }
            });
            error_log('ACF AI Assistant Stripe initialization error: ' . $e->getMessage());
        }
    }

    public function register_stripe_fields() {
        if (!function_exists('acf_add_local_field_group') || !$this->has_dependencies) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_acf_ai_stripe_settings',
            'title' => 'ACF AI Stripe Settings',
            'fields' => array(
                array(
                    'key' => 'field_stripe_monthly_price',
                    'label' => 'Monthly Subscription Price',
                    'name' => 'stripe_monthly_price',
                    'type' => 'number',
                    'instructions' => 'Enter the monthly subscription price in USD',
                    'required' => 1,
                    'default_value' => 29,
                    'min' => 1,
                ),
                array(
                    'key' => 'field_stripe_tokens_per_month',
                    'label' => 'Tokens Per Month',
                    'name' => 'stripe_tokens_per_month',
                    'type' => 'number',
                    'instructions' => 'Number of AI tokens included in monthly subscription',
                    'required' => 1,
                    'default_value' => 1000,
                    'min' => 100,
                ),
                array(
                    'key' => 'field_stripe_success_url',
                    'label' => 'Success URL',
                    'name' => 'stripe_success_url',
                    'type' => 'url',
                    'instructions' => 'URL to redirect after successful payment',
                    'required' => 1,
                    'default_value' => home_url('/payment-success'),
                ),
                array(
                    'key' => 'field_stripe_cancel_url',
                    'label' => 'Cancel URL',
                    'name' => 'stripe_cancel_url',
                    'type' => 'url',
                    'instructions' => 'URL to redirect after cancelled payment',
                    'required' => 1,
                    'default_value' => home_url('/payment-cancelled'),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-ai-settings',
                    ),
                ),
            ),
        ));
    }

    public function create_subscription($email) {
        if (!$this->stripe_initialized) {
            return new WP_Error('stripe_not_initialized', 'Stripe is not properly initialized');
        }

        try {
            if (!is_email($email)) {
                throw new Exception('Invalid email address provided');
            }

            // Create customer
            $customer = \Stripe\Customer::create([
                'email' => sanitize_email($email),
            ]);

            // Create subscription
            $subscription = \Stripe\Subscription::create([
                'customer' => $customer->id,
                'items' => [[
                    'price' => $this->get_stripe_price_id(),
                ]],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            return array(
                'subscriptionId' => $subscription->id,
                'clientSecret' => $subscription->latest_invoice->payment_intent->client_secret,
            );
        } catch (Exception $e) {
            error_log('Stripe subscription creation error: ' . $e->getMessage());
            return new WP_Error('stripe_error', $e->getMessage());
        }
    }

    private function get_stripe_price_id() {
        if (!$this->stripe_initialized) {
            throw new Exception('Stripe is not properly initialized');
        }

        $monthly_price = absint(get_field('stripe_monthly_price', 'option'));
        $tokens_per_month = absint(get_field('stripe_tokens_per_month', 'option'));
        
        if (!$monthly_price || !$tokens_per_month) {
            throw new Exception('Invalid price or token configuration');
        }

        try {
            // Check if price already exists
            $prices = \Stripe\Price::all([
                'lookup_keys' => ['acf_ai_monthly_' . $monthly_price . '_' . $tokens_per_month],
                'expand' => ['data.product'],
                'active' => true
            ]);

            if (!empty($prices->data)) {
                return $prices->data[0]->id;
            }

            // Create new product if needed
            $product = \Stripe\Product::create([
                'name' => 'ACF AI Assistant Monthly Subscription',
                'description' => $tokens_per_month . ' tokens per month',
            ]);

            // Create new price
            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => $monthly_price * 100, // Convert to cents
                'currency' => 'usd',
                'recurring' => [
                    'interval' => 'month',
                ],
                'lookup_key' => 'acf_ai_monthly_' . $monthly_price . '_' . $tokens_per_month,
            ]);

            return $price->id;
        } catch (Exception $e) {
            error_log('Stripe price creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function handle_stripe_webhook() {
        if (!$this->stripe_initialized || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['acf_ai_stripe_webhook'])) {
            return;
        }

        $payload = file_get_contents('php://input');
        if (!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
            http_response_code(400);
            exit('Missing signature header');
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $_SERVER['HTTP_STRIPE_SIGNATURE'],
                $this->get_webhook_secret()
            );
        } catch (Exception $e) {
            error_log('Stripe webhook error: ' . $e->getMessage());
            http_response_code(400);
            exit('Invalid payload');
        }

        try {
            switch ($event->type) {
                case 'customer.subscription.created':
                    $this->handle_subscription_created($event->data->object);
                    break;
                case 'customer.subscription.deleted':
                    $this->handle_subscription_cancelled($event->data->object);
                    break;
                case 'invoice.payment_succeeded':
                    $this->handle_payment_succeeded($event->data->object);
                    break;
            }
        } catch (Exception $e) {
            error_log('Stripe webhook handling error: ' . $e->getMessage());
            http_response_code(500);
            exit('Error processing webhook');
        }

        http_response_code(200);
    }

    private function handle_subscription_created($subscription) {
        if (!$subscription || !isset($subscription->customer)) {
            throw new Exception('Invalid subscription data');
        }

        $user = $this->get_user_by_stripe_customer($subscription->customer);
        if ($user && $user instanceof WP_User) {
            update_user_meta($user->ID, 'acf_ai_stripe_subscription_id', sanitize_text_field($subscription->id));
            update_user_meta($user->ID, 'acf_ai_subscription_status', 'active');
            update_user_meta($user->ID, 'acf_ai_tokens_reset_date', date('Y-m-d H:i:s', $subscription->current_period_end));
        }
    }

    private function handle_subscription_cancelled($subscription) {
        if (!$subscription || !isset($subscription->customer)) {
            throw new Exception('Invalid subscription data');
        }

        $user = $this->get_user_by_stripe_customer($subscription->customer);
        if ($user && $user instanceof WP_User) {
            update_user_meta($user->ID, 'acf_ai_subscription_status', 'cancelled');
        }
    }

    private function handle_payment_succeeded($invoice) {
        if (!$invoice || !isset($invoice->customer)) {
            throw new Exception('Invalid invoice data');
        }

        $user = $this->get_user_by_stripe_customer($invoice->customer);
        if ($user && $user instanceof WP_User) {
            $tokens_per_month = absint(get_field('stripe_tokens_per_month', 'option'));
            if ($tokens_per_month > 0) {
                update_user_meta($user->ID, 'acf_ai_tokens_remaining', $tokens_per_month);
                update_user_meta($user->ID, 'acf_ai_tokens_reset_date', date('Y-m-d H:i:s', strtotime('+1 month')));
            }
        }
    }

    private function get_user_by_stripe_customer($customer_id) {
        if (empty($customer_id)) {
            return null;
        }

        $users = get_users([
            'meta_key' => 'acf_ai_stripe_customer_id',
            'meta_value' => sanitize_text_field($customer_id),
            'number' => 1,
        ]);

        return !empty($users) ? $users[0] : null;
    }

    private function get_webhook_secret() {
        $secret = defined('ACF_AI_STRIPE_WEBHOOK_SECRET') ? ACF_AI_STRIPE_WEBHOOK_SECRET : getenv('ACF_AI_STRIPE_WEBHOOK_SECRET');
        if (empty($secret)) {
            throw new Exception('Webhook secret is not configured');
        }
        return $secret;
    }

    /**
     * Add Stripe settings menu page
     */
    public function add_stripe_menu() {
        add_submenu_page(
            'acf-ai-settings',
            __('Stripe Settings', 'acf-ai-assistant'),
            __('Stripe Settings', 'acf-ai-assistant'),
            'manage_options',
            'acf-ai-stripe-settings',
            array($this, 'render_stripe_settings')
        );
    }

    /**
     * Render Stripe settings page
     */
    public function render_stripe_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('ACF AI Assistant - Stripe Settings', 'acf-ai-assistant'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acf_ai_stripe_settings');
                do_settings_sections('acf_ai_stripe_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
