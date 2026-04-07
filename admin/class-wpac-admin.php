<?php
/**
 * Admin panel management class.
 *
 * Registers admin menus, handles AJAX requests, and orchestrates
 * all admin functionality for the plugin.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Admin
 *
 * Manages the WordPress admin interface for the plugin.
 */
class WPAC_Admin {

    /**
     * Initialize admin hooks and AJAX handlers.
     *
     * @return void
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menus' ) );
        add_action( 'admin_post_wpac_save_settings', array( $this, 'handle_save_settings' ) );
        add_action( 'admin_post_wpac_save_topic', array( $this, 'handle_save_topic' ) );
        add_action( 'admin_post_wpac_delete_topic', array( $this, 'handle_delete_topic' ) );
        add_action( 'admin_post_wpac_bulk_topics', array( $this, 'handle_bulk_topics' ) );
        add_action( 'admin_post_wpac_import_topics', array( $this, 'handle_import_topics' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_wpac_generate_now', array( $this, 'ajax_generate_now' ) );
        add_action( 'wp_ajax_wpac_test_ai', array( $this, 'ajax_test_ai' ) );
        add_action( 'wp_ajax_wpac_test_social', array( $this, 'ajax_test_social' ) );
        add_action( 'wp_ajax_wpac_get_topic', array( $this, 'ajax_get_topic' ) );
        add_action( 'wp_ajax_wpac_save_topic_ajax', array( $this, 'ajax_save_topic' ) );
        add_action( 'wp_ajax_wpac_delete_topic_ajax', array( $this, 'ajax_delete_topic' ) );

        // Settings save via AJAX.
        add_action( 'wp_ajax_wpac_save_settings_ajax', array( $this, 'ajax_save_settings' ) );
    }

    /**
     * Register admin menu pages.
     *
     * @return void
     */
    public function register_menus() {
        add_menu_page(
            __( 'WP Auto Content Pro', 'wp-auto-content-pro' ),
            __( 'WPAC Pro', 'wp-auto-content-pro' ),
            'manage_options',
            'wpac-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-rss',
            30
        );

        add_submenu_page(
            'wpac-dashboard',
            __( 'Dashboard', 'wp-auto-content-pro' ),
            __( 'Dashboard', 'wp-auto-content-pro' ),
            'manage_options',
            'wpac-dashboard',
            array( $this, 'render_dashboard' )
        );

        add_submenu_page(
            'wpac-dashboard',
            __( 'Topics', 'wp-auto-content-pro' ),
            __( 'Topics', 'wp-auto-content-pro' ),
            'manage_options',
            'wpac-topics',
            array( $this, 'render_topics' )
        );

        add_submenu_page(
            'wpac-dashboard',
            __( 'Settings', 'wp-auto-content-pro' ),
            __( 'Settings', 'wp-auto-content-pro' ),
            'manage_options',
            'wpac-settings',
            array( $this, 'render_settings' )
        );

        add_submenu_page(
            'wpac-dashboard',
            __( 'Logs', 'wp-auto-content-pro' ),
            __( 'Logs', 'wp-auto-content-pro' ),
            'manage_options',
            'wpac-logs',
            array( $this, 'render_logs' )
        );
    }

    /**
     * Render the Dashboard page.
     *
     * @return void
     */
    public function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'wp-auto-content-pro' ) );
        }
        require_once WPAC_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render the Topics page.
     *
     * @return void
     */
    public function render_topics() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'wp-auto-content-pro' ) );
        }
        require_once WPAC_PLUGIN_DIR . 'admin/views/topics.php';
    }

    /**
     * Render the Settings page.
     *
     * @return void
     */
    public function render_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'wp-auto-content-pro' ) );
        }
        require_once WPAC_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Render the Logs page.
     *
     * @return void
     */
    public function render_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'wp-auto-content-pro' ) );
        }
        require_once WPAC_PLUGIN_DIR . 'admin/views/logs.php';
    }

    /**
     * Handle settings form submission.
     *
     * @return void
     */
    public function handle_save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'wp-auto-content-pro' ) );
        }

        check_admin_referer( 'wpac_save_settings', 'wpac_settings_nonce' );

        $this->save_settings_from_post( $_POST );

        wp_redirect( add_query_arg(
            array( 'page' => 'wpac-settings', 'updated' => '1' ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Save all plugin settings from POST data.
     *
     * @param array $data POST data array.
     * @return void
     */
    private function save_settings_from_post( $data ) {
        // AI Settings.
        $string_options = array(
            'wpac_ai_provider', 'wpac_openai_api_key', 'wpac_openai_model',
            'wpac_gemini_api_key', 'wpac_gemini_model', 'wpac_claude_api_key',
            'wpac_claude_model', 'wpac_deepseek_api_key', 'wpac_deepseek_model',
            'wpac_content_language', 'wpac_article_length', 'wpac_dalle_model',
            'wpac_dalle_size', 'wpac_unsplash_access_key', 'wpac_image_source',
        );

        foreach ( $string_options as $option ) {
            if ( isset( $data[ $option ] ) ) {
                update_option( $option, sanitize_text_field( $data[ $option ] ) );
            }
        }

        // Toggle options.
        $toggle_options = array(
            'wpac_include_images', 'wpac_schedule_enabled', 'wpac_debug_mode',
            'wpac_twitter_enabled', 'wpac_threads_enabled', 'wpac_instagram_enabled',
            'wpac_facebook_enabled', 'wpac_tiktok_enabled', 'wpac_linkedin_enabled',
        );

        foreach ( $toggle_options as $option ) {
            update_option( $option, isset( $data[ $option ] ) && '1' === $data[ $option ] ? '1' : '0' );
        }

        // Social media credentials.
        $social_options = array(
            'wpac_twitter_api_key', 'wpac_twitter_api_secret', 'wpac_twitter_access_token',
            'wpac_twitter_access_secret', 'wpac_twitter_username',
            'wpac_twitter_template', 'wpac_threads_access_token', 'wpac_threads_user_id',
            'wpac_instagram_access_token', 'wpac_instagram_account_id',
            'wpac_facebook_page_access_token', 'wpac_facebook_page_id',
            'wpac_tiktok_access_token', 'wpac_linkedin_access_token', 'wpac_linkedin_author_urn',
        );

        foreach ( $social_options as $option ) {
            if ( isset( $data[ $option ] ) ) {
                update_option( $option, sanitize_text_field( $data[ $option ] ) );
            }
        }

        // Schedule settings.
        $numeric_options = array( 'wpac_posts_per_day', 'wpac_posting_time_from', 'wpac_posting_time_to', 'wpac_default_category' );
        foreach ( $numeric_options as $option ) {
            if ( isset( $data[ $option ] ) ) {
                update_option( $option, absint( $data[ $option ] ) );
            }
        }

        $status_options = array( 'wpac_default_post_status' );
        foreach ( $status_options as $option ) {
            if ( isset( $data[ $option ] ) ) {
                $valid = array( 'publish', 'draft', 'pending' );
                $val   = in_array( $data[ $option ], $valid, true ) ? $data[ $option ] : 'publish';
                update_option( $option, $val );
            }
        }

        // Advanced settings.
        if ( isset( $data['wpac_timezone'] ) ) {
            update_option( 'wpac_timezone', sanitize_text_field( $data['wpac_timezone'] ) );
        }
        if ( isset( $data['wpac_webhook_url'] ) ) {
            update_option( 'wpac_webhook_url', esc_url_raw( $data['wpac_webhook_url'] ) );
        }

        // Reschedule cron if schedule settings changed.
        $scheduler = new WPAC_Scheduler();
        $scheduler->reschedule();
    }

    /**
     * Handle topic save (add/edit) form submission.
     *
     * @return void
     */
    public function handle_save_topic() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'wp-auto-content-pro' ) );
        }

        check_admin_referer( 'wpac_save_topic', 'wpac_topic_nonce' );

        $database = new WPAC_Database();
        $id       = absint( $_POST['topic_id'] ?? 0 );

        $data = array(
            'topic'      => sanitize_text_field( $_POST['topic'] ?? '' ),
            'type'       => sanitize_text_field( $_POST['type'] ?? 'article' ),
            'frequency'  => sanitize_text_field( $_POST['frequency'] ?? 'daily' ),
            'categories' => sanitize_text_field( $_POST['categories'] ?? '' ),
            'status'     => sanitize_text_field( $_POST['status'] ?? 'active' ),
        );

        if ( empty( $data['topic'] ) ) {
            wp_redirect( add_query_arg(
                array( 'page' => 'wpac-topics', 'error' => 'empty_topic' ),
                admin_url( 'admin.php' )
            ) );
            exit;
        }

        $result = $database->save_topic( $data, $id );

        $redirect_args = array( 'page' => 'wpac-topics' );
        if ( $result ) {
            $redirect_args['saved'] = '1';
        } else {
            $redirect_args['error'] = 'save_failed';
        }

        wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Handle topic deletion form submission.
     *
     * @return void
     */
    public function handle_delete_topic() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'wp-auto-content-pro' ) );
        }

        $id = absint( $_GET['id'] ?? 0 );
        check_admin_referer( 'wpac_delete_topic_' . $id );

        $database = new WPAC_Database();
        $database->delete_topic( $id );

        wp_redirect( add_query_arg(
            array( 'page' => 'wpac-topics', 'deleted' => '1' ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Handle bulk topic actions.
     *
     * @return void
     */
    public function handle_bulk_topics() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'wp-auto-content-pro' ) );
        }

        check_admin_referer( 'wpac_bulk_topics', 'wpac_bulk_nonce' );

        $action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
        $ids    = array_map( 'absint', $_POST['topic_ids'] ?? array() );

        if ( empty( $ids ) || empty( $action ) ) {
            wp_redirect( add_query_arg( array( 'page' => 'wpac-topics' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        $database = new WPAC_Database();

        switch ( $action ) {
            case 'activate':
                $database->bulk_update_status( $ids, 'active' );
                break;
            case 'pause':
                $database->bulk_update_status( $ids, 'paused' );
                break;
            case 'delete':
                $database->bulk_delete( $ids );
                break;
        }

        wp_redirect( add_query_arg(
            array( 'page' => 'wpac-topics', 'bulk_done' => '1' ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Handle CSV topic import.
     *
     * @return void
     */
    public function handle_import_topics() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'wp-auto-content-pro' ) );
        }

        check_admin_referer( 'wpac_import_topics', 'wpac_import_nonce' );

        if ( empty( $_FILES['topics_csv']['tmp_name'] ) ) {
            wp_redirect( add_query_arg(
                array( 'page' => 'wpac-topics', 'error' => 'no_file' ),
                admin_url( 'admin.php' )
            ) );
            exit;
        }

        // Validate file type.
        $file_info = wp_check_filetype( $_FILES['topics_csv']['name'] );
        if ( ! in_array( $file_info['ext'], array( 'csv', 'txt' ), true ) ) {
            wp_redirect( add_query_arg(
                array( 'page' => 'wpac-topics', 'error' => 'invalid_file' ),
                admin_url( 'admin.php' )
            ) );
            exit;
        }

        // Validate file size (max 2MB).
        if ( $_FILES['topics_csv']['size'] > 2 * 1024 * 1024 ) {
            wp_redirect( add_query_arg(
                array( 'page' => 'wpac-topics', 'error' => 'file_too_large' ),
                admin_url( 'admin.php' )
            ) );
            exit;
        }

        $file     = $_FILES['topics_csv']['tmp_name'];
        $handle   = fopen( $file, 'r' );
        $database = new WPAC_Database();
        $imported = 0;

        if ( $handle ) {
            // Skip header row.
            fgetcsv( $handle );

            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                if ( empty( $row[0] ) ) {
                    continue;
                }

                $data = array(
                    'topic'      => sanitize_text_field( $row[0] ),
                    'type'       => isset( $row[1] ) ? sanitize_text_field( $row[1] ) : 'article',
                    'frequency'  => isset( $row[2] ) ? sanitize_text_field( $row[2] ) : 'daily',
                    'categories' => isset( $row[3] ) ? sanitize_text_field( $row[3] ) : '',
                    'status'     => 'active',
                );

                if ( $database->save_topic( $data ) ) {
                    $imported++;
                }
            }

            fclose( $handle );
        }

        wp_redirect( add_query_arg(
            array( 'page' => 'wpac-topics', 'imported' => $imported ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * AJAX: Manually trigger content generation.
     *
     * @return void
     */
    public function ajax_generate_now() {
        check_ajax_referer( 'wpac_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-auto-content-pro' ) ) );
        }

        $scheduler = new WPAC_Scheduler();
        $result    = $scheduler->trigger_manual();

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX: Test AI provider connection.
     *
     * @return void
     */
    public function ajax_test_ai() {
        check_ajax_referer( 'wpac_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-auto-content-pro' ) ) );
        }

        $provider = sanitize_text_field( $_POST['provider'] ?? '' );

        $generator = new WPAC_AI_Generator();
        $result    = $generator->test_connection( $provider );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX: Test social media platform connection.
     *
     * @return void
     */
    public function ajax_test_social() {
        check_ajax_referer( 'wpac_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-auto-content-pro' ) ) );
        }

        $platform = sanitize_text_field( $_POST['platform'] ?? '' );

        $social = new WPAC_Social_Media();
        $result = $social->test_platform( $platform );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX: Get a topic for editing.
     *
     * @return void
     */
    public function ajax_get_topic() {
        check_ajax_referer( 'wpac_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-auto-content-pro' ) ) );
        }

        $id       = absint( $_POST['id'] ?? 0 );
        $database = new WPAC_Database();
        $topic    = $database->get_topic( $id );

        if ( $topic ) {
            wp_send_json_success( $topic );
        } else {
            wp_send_json_error( array( 'message' => __( 'Topic not found.', 'wp-auto-content-pro' ) ) );
        }
    }

    /**
     * AJAX: Save a topic (add/edit).
     *
     * @return void
     */
    public function ajax_save_topic() {
        check_ajax_referer( 'wpac_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-auto-content-pro' ) ) );
        }

        $id   = absint( $_POST['id'] ?? 0 );
        $data = array(
            'topic'      => sanitize_text_field( $_POST['topic'] ?? '' ),
            'type'       => sanitize_text_field( $_POST['type'] ?? 'article' ),
            'frequency'  => sanitize_text_field( $_POST['frequency'] ?? 'daily' ),
            'categories' => sanitize_text_field( $_POST['categories'] ?? '' ),
            'status'     => sanitize_text_field( $_POST['status'] ?? 'active' ),
        );

        if ( empty( $data['topic'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Topic text is required.', 'wp-auto-content-pro' ) ) );
        }

        $database = new WPAC_Database();
        $result   = $database->save_topic( $data, $id );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => $id ? __( 'Topic updated.', 'wp-auto-content-pro' ) : __( 'Topic added.', 'wp-auto-content-pro' ),
                'id'      => $result,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to save topic.', 'wp-auto-content-pro' ) ) );
        }
    }

    /**
     * AJAX: Delete a topic.
     *
     * @return void
     */
    public function ajax_delete_topic() {
        check_ajax_referer( 'wpac_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-auto-content-pro' ) ) );
        }

        $id       = absint( $_POST['id'] ?? 0 );
        $database = new WPAC_Database();
        $result   = $database->delete_topic( $id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Topic deleted.', 'wp-auto-content-pro' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete topic.', 'wp-auto-content-pro' ) ) );
        }
    }

    /**
     * AJAX: Save settings.
     *
     * @return void
     */
    public function ajax_save_settings() {
        check_ajax_referer( 'wpac_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-auto-content-pro' ) ) );
        }

        $this->save_settings_from_post( $_POST );

        wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'wp-auto-content-pro' ) ) );
    }

    /**
     * Get dashboard statistics.
     *
     * @return array Stats data.
     */
    public function get_dashboard_stats() {
        $database = new WPAC_Database();

        $total_posts   = wp_count_posts( 'post' );
        $today_count   = $database->get_today_posts_count();
        $social_shares = $database->get_total_social_shares();
        $total_topics  = $database->count_topics();
        $active_topics = $database->count_topics( 'active' );

        return array(
            'total_posts'    => ( $total_posts->publish ?? 0 ),
            'today_posts'    => $today_count,
            'social_shares'  => $social_shares,
            'total_topics'   => $total_topics,
            'active_topics'  => $active_topics,
            'schedule_on'    => get_option( 'wpac_schedule_enabled', '0' ) === '1',
            'next_run'       => wp_next_scheduled( WPAC_Scheduler::CRON_HOOK ),
        );
    }
}
