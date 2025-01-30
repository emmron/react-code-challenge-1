<?php

/**
 * ACF AI Assistant CLI Commands
 *
 * @package ACF_AI_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load critical classes first
require_once __DIR__ . '/includes/class-acf-ai-response.php';
require_once __DIR__ . '/includes/class-acf-ai-security.php';
require_once __DIR__ . '/includes/class-acf-ai-license.php';

// Then load other classes
add_action('plugins_loaded', function() {
    // Load remaining classes
});

/**
 * WP-CLI commands for ACF AI Assistant.
 */
class ACF_AI_CLI {

    /**
     * Initialize CLI commands.
     */
    public static function init() {
        if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
            return;
        }

        WP_CLI::add_command( 'acf-ai', array( __CLASS__, 'test_plugin' ) );
    }

    /**
     * Test plugin functionality.
     *
     * ## OPTIONS
     *
     * [--component=<component>]
     * : Specific component to test (license|api|field)
     *
     * ## EXAMPLES
     *
     *     # Test all components
     *     $ wp acf-ai test
     *
     *     # Test specific component
     *     $ wp acf-ai test --component=license
     *
     * @param array $args Command arguments.
     * @param array $assoc_args Command options.
     */
    public static function test_plugin( $args, $assoc_args ) {
        $component = isset( $assoc_args['component'] ) ? $assoc_args['component'] : 'all';

        WP_CLI::line( 'Testing ACF AI Assistant Plugin...' );

        // Test plugin initialization
        if ( ! class_exists( 'ACF_AI_Assistant' ) ) {
            WP_CLI::error( 'Plugin class not found.' );
        }

        $plugin = ACF_AI_Assistant::get_instance();
        if ( ! $plugin ) {
            WP_CLI::error( 'Failed to initialize plugin.' );
        }

        WP_CLI::success( 'Plugin initialized successfully.' );

        // Test specific components
        switch ( $component ) {
            case 'license':
                self::test_license();
                break;
            case 'api':
                self::test_api();
                break;
            case 'field':
                self::test_field();
                break;
            case 'all':
                self::test_license();
                self::test_api();
                self::test_field();
                break;
            default:
                WP_CLI::error( 'Invalid component specified.' );
        }
    }

    /**
     * Test license functionality.
     */
    private static function test_license() {
        WP_CLI::line( 'Testing license component...' );

        if ( ! class_exists( 'ACF_AI_License' ) ) {
            WP_CLI::error( 'License class not found.' );
        }

        $license = ACF_AI_License::get_instance();
        if ( ! $license ) {
            WP_CLI::error( 'Failed to initialize license component.' );
        }

        // Test development environment detection
        if ( $license->is_development() ) {
            WP_CLI::line( '✓ Development environment detected.' );
        } else {
            WP_CLI::line( '✓ Production environment detected.' );
        }

        WP_CLI::success( 'License component tests completed.' );
    }

    /**
     * Test API functionality.
     */
    private static function test_api() {
        WP_CLI::line( 'Testing API component...' );

        if ( ! class_exists( 'ACF_AI_API' ) ) {
            WP_CLI::error( 'API class not found.' );
        }

        $api = ACF_AI_API::get_instance();
        if ( ! $api ) {
            WP_CLI::error( 'Failed to initialize API component.' );
        }

        WP_CLI::success( 'API component tests completed.' );
    }

    /**
     * Test field functionality.
     */
    private static function test_field() {
        WP_CLI::line( 'Testing field component...' );

        if ( ! class_exists( 'ACF_AI_Field' ) ) {
            WP_CLI::error( 'Field class not found.' );
        }

        // Test field registration
        if ( ! function_exists( 'acf_get_field_type' ) ) {
            WP_CLI::warning( 'ACF plugin not active - skipping field tests.' );
            return;
        }

        $field = acf_get_field_type( 'ai_assistant' );
        if ( ! $field ) {
            WP_CLI::error( 'AI Assistant field type not registered.' );
        }

        WP_CLI::success( 'Field component tests completed.' );
    }

    /**
     * Generate field groups from a text description
     * 
     * ## OPTIONS
     * 
     * <description>
     * : Natural language description of the fields you want to create
     * 
     * [--name=<name>]
     * : Name for the field group (optional)
     * 
     * [--location=<location>]
     * : Where to show the field group (post_type, page, etc.)
     * 
     * [--model=<model>]
     * : AI model to use (default: gpt-3.5-turbo)
     * 
     * ## EXAMPLES
     * 
     *     wp acf-ai generate "Create fields for a real estate listing with price, location, and features"
     *     wp acf-ai generate "Build a team member profile with name, photo, bio, and social links" --name="Team Member" --location=post_type:team
     * 
     * @when after_wp_load
     */
    public function generate($args, $assoc_args) {
        list($description) = $args;
        
        $name = $assoc_args['name'] ?? '';
        $location = $assoc_args['location'] ?? '';
        $model = $assoc_args['model'] ?? 'gpt-3.5-turbo';

        WP_CLI::line("🤖 Generating fields from description...");
        
        try {
            $api = ACF_AI_API::get_instance();
            $response = $api->generate_fields($description, $model);
            
            if (is_wp_error($response)) {
                WP_CLI::error($response->get_error_message());
                return;
            }

            $fields = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                WP_CLI::error("Invalid response from AI: " . json_last_error_msg());
                return;
            }

            // Create field group
            $field_group = array(
                'key' => 'group_' . uniqid(),
                'title' => $name ?: 'AI Generated Fields - ' . date('Y-m-d H:i'),
                'fields' => $fields,
                'location' => $this->parse_location($location),
                'active' => true
            );

            acf_add_local_field_group($field_group);
            
            WP_CLI::success("Field group created successfully! 🎉");
            WP_CLI::line("Fields generated:");
            
            foreach ($fields as $field) {
                WP_CLI::line(sprintf(
                    "✓ %s (%s)",
                    $field['label'],
                    $field['type']
                ));
            }

        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Analyze existing field groups and suggest improvements
     * 
     * ## OPTIONS
     * 
     * [--group=<group>]
     * : Specific field group to analyze (optional)
     * 
     * ## EXAMPLES
     * 
     *     wp acf-ai analyze
     *     wp acf-ai analyze --group="Team Member Fields"
     * 
     * @when after_wp_load
     */
    public function analyze($args, $assoc_args) {
        WP_CLI::line("🔍 Analyzing field groups...");
        
        try {
            $api = ACF_AI_API::get_instance();
            $field_groups = acf_get_field_groups();
            
            if (isset($assoc_args['group'])) {
                $field_groups = array_filter($field_groups, function($group) use ($assoc_args) {
                    return $group['title'] === $assoc_args['group'];
                });
            }

            if (empty($field_groups)) {
                WP_CLI::error("No field groups found to analyze.");
                return;
            }

            foreach ($field_groups as $group) {
                WP_CLI::line(sprintf("\nAnalyzing: %s", $group['title']));
                
                $fields = acf_get_fields($group);
                $analysis = $api->analyze_fields($fields);
                
                if (is_wp_error($analysis)) {
                    WP_CLI::warning($analysis->get_error_message());
                    continue;
                }

                $suggestions = json_decode($analysis, true);
                
                if (!empty($suggestions)) {
                    WP_CLI::line("\nSuggestions:");
                    foreach ($suggestions as $suggestion) {
                        WP_CLI::line("• " . $suggestion);
                    }
                } else {
                    WP_CLI::line("✓ No improvements suggested.");
                }
            }

        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Export field groups as code
     * 
     * ## OPTIONS
     * 
     * [--format=<format>]
     * : Output format (php, json) [default: php]
     * 
     * [--group=<group>]
     * : Specific field group to export (optional)
     * 
     * [--output=<output>]
     * : Output file (optional, defaults to stdout)
     * 
     * ## EXAMPLES
     * 
     *     wp acf-ai export
     *     wp acf-ai export --format=json --group="Team Member Fields"
     *     wp acf-ai export --output=field-groups.php
     * 
     * @when after_wp_load
     */
    public function export($args, $assoc_args) {
        $format = $assoc_args['format'] ?? 'php';
        $output = $assoc_args['output'] ?? '';
        
        try {
            $field_groups = acf_get_field_groups();
            
            if (isset($assoc_args['group'])) {
                $field_groups = array_filter($field_groups, function($group) use ($assoc_args) {
                    return $group['title'] === $assoc_args['group'];
                });
            }

            if (empty($field_groups)) {
                WP_CLI::error("No field groups found to export.");
                return;
            }

            $export_data = array();
            foreach ($field_groups as $group) {
                $group['fields'] = acf_get_fields($group);
                $export_data[] = $group;
            }

            $content = '';
            if ($format === 'json') {
                $content = json_encode($export_data, JSON_PRETTY_PRINT);
            } else {
                $content = "<?php\n\n";
                $content .= "if( function_exists('acf_add_local_field_group') ):\n\n";
                
                foreach ($export_data as $group) {
                    $content .= "acf_add_local_field_group(" . var_export($group, true) . ");\n\n";
                }
                
                $content .= "endif;";
            }

            if ($output) {
                file_put_contents($output, $content);
                WP_CLI::success(sprintf("Exported to %s", $output));
            } else {
                WP_CLI::line($content);
            }

        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }

    /**
     * Parse location rules from string
     */
    private function parse_location($location) {
        if (empty($location)) {
            return array(array(array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'post',
            )));
        }

        $parts = explode(':', $location);
        return array(array(array(
            'param' => $parts[0],
            'operator' => '==',
            'value' => $parts[1] ?? 'post',
        )));
    }

    public function handle_field_creation() {
        if (!method_exists('ACF_AI_Response', 'error')) {
            error_log('ACF AI Response class missing');
            return;
        }
        // Rest of method
    }
}
