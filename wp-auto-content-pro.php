<?php
/**
 * Plugin Name: WP Auto Content Pro
 * Plugin URI:  https://github.com/ybngf/WP-Auto-Content-Pro
 * Description: Professional AI-powered content generation and auto-posting to WordPress with multi-platform social media distribution. Supports OpenAI, Google Gemini, Anthropic Claude, and DeepSeek.
 * Version:     2.0.0
 * Author:      Autometa
 * Author URI:  https://autometa.com.br
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-auto-content-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 6.7
 *
 * @package WPAutoContentPro
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Plugin constants.
 */
define( 'WPAC_VERSION', '2.0.0' );
define( 'WPAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPAC_TEXT_DOMAIN', 'wp-auto-content-pro' );

/**
 * Load required files.
 */
function wpac_load_dependencies() {
    require_once WPAC_PLUGIN_DIR . 'includes/class-wpac-database.php';
    require_once WPAC_PLUGIN_DIR . 'includes/class-wpac-ai-generator.php';
    require_once WPAC_PLUGIN_DIR . 'includes/class-wpac-post-creator.php';
    require_once WPAC_PLUGIN_DIR . 'includes/class-wpac-scheduler.php';
    require_once WPAC_PLUGIN_DIR . 'includes/class-wpac-social-media.php';
    require_once WPAC_PLUGIN_DIR . 'includes/social/class-wpac-twitter.php';
    require_once WPAC_PLUGIN_DIR . 'includes/social/class-wpac-threads.php';
    require_once WPAC_PLUGIN_DIR . 'includes/social/class-wpac-instagram.php';
    require_once WPAC_PLUGIN_DIR . 'includes/social/class-wpac-facebook.php';
    require_once WPAC_PLUGIN_DIR . 'includes/social/class-wpac-tiktok.php';
    require_once WPAC_PLUGIN_DIR . 'includes/social/class-wpac-linkedin.php';

    if ( is_admin() ) {
        require_once WPAC_PLUGIN_DIR . 'admin/class-wpac-admin.php';
    }
}
wpac_load_dependencies();

/**
 * Activation hook.
 *
 * @return void
 */
function wpac_activate() {
    $database = new WPAC_Database();
    $database->create_tables();
    $database->update_db_version();

    $scheduler = new WPAC_Scheduler();
    $scheduler->register_schedules();
    $scheduler->schedule_events();

    // Set default options.
    $defaults = array(
        'wpac_ai_provider'          => 'openai',
        'wpac_openai_model'         => 'gpt-4o',
        'wpac_gemini_model'         => 'gemini-1.5-pro',
        'wpac_claude_model'         => 'claude-opus-4-6',
        'wpac_deepseek_model'       => 'deepseek-chat',
        'wpac_content_language'     => 'en',
        'wpac_article_length'       => 'medium',
        'wpac_include_images'       => '1',
        'wpac_schedule_enabled'     => '0',
        'wpac_posts_per_day'        => '3',
        'wpac_posting_time_from'    => '08',
        'wpac_posting_time_to'      => '20',
        'wpac_default_post_status'  => 'publish',
        'wpac_debug_mode'           => '0',
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wpac_activate' );

/**
 * Deactivation hook.
 *
 * @return void
 */
function wpac_deactivate() {
    $scheduler = new WPAC_Scheduler();
    $scheduler->clear_scheduled_events();
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wpac_deactivate' );

/**
 * Initialize admin panel.
 *
 * @return void
 */
function wpac_init_admin() {
    if ( is_admin() ) {
        $admin = new WPAC_Admin();
        $admin->init();
    }
}
add_action( 'plugins_loaded', 'wpac_init_admin' );

/**
 * Initialize scheduler cron hooks.
 *
 * @return void
 */
function wpac_init_scheduler() {
    $scheduler = new WPAC_Scheduler();
    $scheduler->init_hooks();
}
add_action( 'plugins_loaded', 'wpac_init_scheduler' );

/**
 * Enqueue admin scripts and styles.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function wpac_admin_enqueue_scripts( $hook ) {
    if ( strpos( $hook, 'wpac' ) === false ) {
        return;
    }

    wp_enqueue_style(
        'wpac-admin-css',
        WPAC_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        WPAC_VERSION
    );

    wp_enqueue_script(
        'wpac-admin-js',
        WPAC_PLUGIN_URL . 'assets/js/admin.js',
        array( 'jquery' ),
        WPAC_VERSION,
        true
    );

    wp_localize_script(
        'wpac-admin-js',
        'wpac_ajax',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wpac_ajax_nonce' ),
            'strings'  => array(
                'generating'     => __( 'Generating content...', 'wp-auto-content-pro' ),
                'testing'        => __( 'Testing connection...', 'wp-auto-content-pro' ),
                'saving'         => __( 'Saving...', 'wp-auto-content-pro' ),
                'success'        => __( 'Success!', 'wp-auto-content-pro' ),
                'error'          => __( 'An error occurred. Please try again.', 'wp-auto-content-pro' ),
                'confirm_delete' => __( 'Are you sure you want to delete this item?', 'wp-auto-content-pro' ),
                'add_topic'      => __( 'Add Topic', 'wp-auto-content-pro' ),
                'edit_topic'     => __( 'Edit Topic', 'wp-auto-content-pro' ),
                'topic_required' => __( 'Topic text is required.', 'wp-auto-content-pro' ),
                'select_action'  => __( 'Please select a bulk action.', 'wp-auto-content-pro' ),
                'select_topics'  => __( 'Please select at least one topic.', 'wp-auto-content-pro' ),
                'time_error'     => __( 'Posting end time must be later than start time.', 'wp-auto-content-pro' ),
            ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'wpac_admin_enqueue_scripts' );

/**
 * Load plugin text domain.
 *
 * @return void
 */
function wpac_load_textdomain() {
    load_plugin_textdomain(
        'wp-auto-content-pro',
        false,
        dirname( WPAC_PLUGIN_BASENAME ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'wpac_load_textdomain' );

/**
 * Add settings link on plugin list page.
 *
 * @param array $links Existing action links.
 * @return array Modified action links.
 */
function wpac_plugin_action_links( $links ) {
    $settings_link  = '<a href="' . admin_url( 'admin.php?page=wpac-settings' ) . '">' . __( 'Settings', 'wp-auto-content-pro' ) . '</a>';
    $dashboard_link = '<a href="' . admin_url( 'admin.php?page=wpac-dashboard' ) . '">' . __( 'Dashboard', 'wp-auto-content-pro' ) . '</a>';
    array_unshift( $links, $settings_link, $dashboard_link );
    return $links;
}
add_filter( 'plugin_action_links_' . WPAC_PLUGIN_BASENAME, 'wpac_plugin_action_links' );

/**
 * Add plugin meta row links (documentation, support).
 *
 * @param array  $links Plugin meta links.
 * @param string $file  Plugin file path.
 * @return array Modified meta links.
 */
function wpac_plugin_row_meta( $links, $file ) {
    if ( WPAC_PLUGIN_BASENAME === $file ) {
        $links[] = '<a href="https://github.com/ybngf/WP-Auto-Content-Pro" target="_blank">' . __( 'Documentation', 'wp-auto-content-pro' ) . '</a>';
        $links[] = '<a href="https://autometa.com.br" target="_blank">' . __( 'Support', 'wp-auto-content-pro' ) . '</a>';
    }
    return $links;
}
add_filter( 'plugin_row_meta', 'wpac_plugin_row_meta', 10, 2 );
