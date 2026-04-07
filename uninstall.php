<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all plugin data including database tables, options, and transients.
 * This file is triggered only when the user explicitly deletes the plugin
 * from the WordPress admin panel.
 *
 * @package WPAutoContentPro
 * @since   2.0.0
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

global $wpdb;

// =========================================================
// Remove custom database tables.
// =========================================================
$tables = array(
    $wpdb->prefix . 'wpac_topics',
    $wpdb->prefix . 'wpac_posts_log',
    $wpdb->prefix . 'wpac_social_log',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// =========================================================
// Remove all plugin options.
// =========================================================
$options = array(
    // AI Settings.
    'wpac_ai_provider',
    'wpac_openai_api_key',
    'wpac_openai_model',
    'wpac_gemini_api_key',
    'wpac_gemini_model',
    'wpac_claude_api_key',
    'wpac_claude_model',
    'wpac_deepseek_api_key',
    'wpac_deepseek_model',

    // Content Settings.
    'wpac_content_language',
    'wpac_article_length',
    'wpac_include_images',
    'wpac_image_source',
    'wpac_dalle_model',
    'wpac_dalle_size',
    'wpac_unsplash_access_key',

    // Schedule Settings.
    'wpac_schedule_enabled',
    'wpac_posts_per_day',
    'wpac_posting_time_from',
    'wpac_posting_time_to',
    'wpac_default_post_status',
    'wpac_default_category',
    'wpac_timezone',

    // Social Media - Twitter.
    'wpac_twitter_enabled',
    'wpac_twitter_api_key',
    'wpac_twitter_api_secret',
    'wpac_twitter_access_token',
    'wpac_twitter_access_secret',
    'wpac_twitter_username',
    'wpac_twitter_template',

    // Social Media - Threads.
    'wpac_threads_enabled',
    'wpac_threads_access_token',
    'wpac_threads_user_id',

    // Social Media - Instagram.
    'wpac_instagram_enabled',
    'wpac_instagram_access_token',
    'wpac_instagram_account_id',

    // Social Media - Facebook.
    'wpac_facebook_enabled',
    'wpac_facebook_page_access_token',
    'wpac_facebook_page_id',

    // Social Media - TikTok.
    'wpac_tiktok_enabled',
    'wpac_tiktok_access_token',

    // Social Media - LinkedIn.
    'wpac_linkedin_enabled',
    'wpac_linkedin_access_token',
    'wpac_linkedin_author_urn',

    // Advanced.
    'wpac_debug_mode',
    'wpac_webhook_url',
    'wpac_db_version',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// =========================================================
// Clear any scheduled cron events.
// =========================================================
$timestamp = wp_next_scheduled( 'wpac_cron_generate' );
if ( $timestamp ) {
    wp_unschedule_event( $timestamp, 'wpac_cron_generate' );
}

// Clear all transients.
delete_transient( 'wpac_generating' );

// =========================================================
// Clean up any remaining post meta.
// =========================================================
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_wpac_%'" );
