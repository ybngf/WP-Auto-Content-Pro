<?php
/**
 * Scheduler class.
 *
 * Manages WordPress cron schedules and automated content generation events.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Scheduler
 *
 * Registers custom cron schedules and orchestrates automated content generation.
 */
class WPAC_Scheduler {

    /**
     * Main automation cron hook name.
     *
     * @var string
     */
    const CRON_HOOK = 'wpac_run_automation';

    /**
     * Maximum number of retry attempts for failed generation.
     *
     * @var int
     */
    const MAX_RETRIES = 2;

    /**
     * Register custom WP-Cron interval schedules.
     *
     * @return void
     */
    public function register_schedules() {
        add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
    }

    /**
     * Add custom cron schedule intervals.
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public function add_custom_schedules( $schedules ) {
        $schedules['wpac_every_30min'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 30 Minutes', 'wp-auto-content-pro' ),
        );

        $schedules['wpac_every_2h'] = array(
            'interval' => 2 * HOUR_IN_SECONDS,
            'display'  => __( 'Every 2 Hours', 'wp-auto-content-pro' ),
        );

        $schedules['wpac_every_6h'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __( 'Every 6 Hours', 'wp-auto-content-pro' ),
        );

        return $schedules;
    }

    /**
     * Schedule cron events based on current settings.
     *
     * @return void
     */
    public function schedule_events() {
        $enabled = get_option( 'wpac_schedule_enabled', '0' );

        if ( '1' !== $enabled ) {
            $this->clear_scheduled_events();
            return;
        }

        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            $schedule    = $this->determine_cron_schedule();
            $start_time  = $this->get_next_run_time();
            wp_schedule_event( $start_time, $schedule, self::CRON_HOOK );
        }
    }

    /**
     * Clear all scheduled cron events for this plugin.
     *
     * @return void
     */
    public function clear_scheduled_events() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    /**
     * Re-schedule cron events (used when settings change).
     *
     * @return void
     */
    public function reschedule() {
        $this->clear_scheduled_events();
        $this->schedule_events();
    }

    /**
     * Register cron action hooks.
     *
     * @return void
     */
    public function init_hooks() {
        add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
        add_action( self::CRON_HOOK, array( $this, 'run_automation' ) );
    }

    /**
     * Main automation routine executed by WP-Cron.
     *
     * Picks a pending topic, generates content, creates post, shares on social media.
     *
     * @return void
     */
    public function run_automation() {
        $enabled = get_option( 'wpac_schedule_enabled', '0' );
        if ( '1' !== $enabled ) {
            return;
        }

        // Check posting time window.
        if ( ! $this->is_within_posting_window() ) {
            return;
        }

        // Check daily post limit.
        if ( ! $this->is_under_daily_limit() ) {
            return;
        }

        // Prevent concurrent runs using a transient lock.
        if ( get_transient( 'wpac_automation_running' ) ) {
            return;
        }
        set_transient( 'wpac_automation_running', 1, 5 * MINUTE_IN_SECONDS );

        try {
            $database = new WPAC_Database();
            $pending  = $database->get_pending_topics();

            if ( empty( $pending ) ) {
                delete_transient( 'wpac_automation_running' );
                return;
            }

            $topic = $pending[0];
            $this->process_topic( $topic, $database );

        } catch ( Exception $e ) {
            if ( get_option( 'wpac_debug_mode' ) === '1' ) {
                error_log( 'WPAC Scheduler Error: ' . $e->getMessage() );
            }
        }

        delete_transient( 'wpac_automation_running' );
    }

    /**
     * Process a single topic: generate content, create post, share on social.
     *
     * Includes retry logic for AI generation failures.
     *
     * @param object       $topic    Topic database object.
     * @param WPAC_Database $database Database instance.
     * @return void
     */
    private function process_topic( $topic, $database ) {
        $ai_generator = new WPAC_AI_Generator();
        $article_data = null;
        $last_error   = null;

        // Retry loop for AI generation.
        for ( $attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++ ) {
            $article_data = $ai_generator->generate_article( $topic->topic, $topic->type );

            if ( ! is_wp_error( $article_data ) ) {
                break;
            }

            $last_error = $article_data;
            $this->log_debug( sprintf( 'AI generation attempt %d/%d failed: %s', $attempt, self::MAX_RETRIES, $article_data->get_error_message() ) );

            if ( $attempt < self::MAX_RETRIES ) {
                sleep( 2 ); // Brief delay before retry.
            }
        }

        if ( is_wp_error( $article_data ) ) {
            $database->log_post( array(
                'topic_id'      => $topic->id,
                'topic_text'    => $topic->topic,
                'ai_provider'   => get_option( 'wpac_ai_provider', 'openai' ),
                'error_message' => $last_error->get_error_message(),
            ) );
            return;
        }

        $post_creator = new WPAC_Post_Creator();
        $post_id      = $post_creator->create_post( $article_data );

        if ( is_wp_error( $post_id ) ) {
            $database->log_post( array(
                'topic_id'      => $topic->id,
                'topic_text'    => $topic->topic,
                'ai_provider'   => $article_data['provider'],
                'error_message' => $post_id->get_error_message(),
            ) );
            return;
        }

        // Log the successful post creation.
        $log_id = $database->log_post( array(
            'topic_id'    => $topic->id,
            'wp_post_id'  => $post_id,
            'topic_text'  => $topic->topic,
            'ai_provider' => $article_data['provider'],
            'post_title'  => $article_data['title'],
            'post_status' => get_option( 'wpac_default_post_status', 'publish' ),
        ) );

        // Update topic last generated timestamp.
        $database->update_topic_generated( $topic->id );

        // Share on social media.
        $enabled_platforms = $this->get_enabled_platforms();
        if ( ! empty( $enabled_platforms ) ) {
            $social_media = new WPAC_Social_Media();
            $social_media->share_post( $post_id, $enabled_platforms, $log_id );
        }

        // Send webhook notification if configured.
        $this->send_webhook_notification( $post_id, $article_data );
    }

    /**
     * Determine the appropriate WP-Cron schedule based on posts-per-day setting.
     *
     * @return string Schedule key.
     */
    private function determine_cron_schedule() {
        $posts_per_day = (int) get_option( 'wpac_posts_per_day', 3 );

        if ( $posts_per_day >= 24 ) {
            return 'hourly';
        } elseif ( $posts_per_day >= 12 ) {
            return 'wpac_every_2h';
        } elseif ( $posts_per_day >= 4 ) {
            return 'wpac_every_6h';
        } else {
            return 'daily';
        }
    }

    /**
     * Calculate the next run time respecting the posting window.
     *
     * @return int Unix timestamp for next run.
     */
    private function get_next_run_time() {
        $from_hour = (int) get_option( 'wpac_posting_time_from', 8 );
        $timezone  = get_option( 'wpac_timezone', get_option( 'timezone_string', 'UTC' ) );

        try {
            $tz   = new DateTimeZone( $timezone ?: 'UTC' );
            $now  = new DateTime( 'now', $tz );
            $hour = (int) $now->format( 'G' );

            if ( $hour < $from_hour ) {
                $now->setTime( $from_hour, 0, 0 );
            }

            return $now->getTimestamp();
        } catch ( Exception $e ) {
            return time();
        }
    }

    /**
     * Check whether current time is within the configured posting window.
     *
     * @return bool True if current time is within window.
     */
    private function is_within_posting_window() {
        $from_hour = (int) get_option( 'wpac_posting_time_from', 8 );
        $to_hour   = (int) get_option( 'wpac_posting_time_to', 20 );
        $timezone  = get_option( 'wpac_timezone', get_option( 'timezone_string', 'UTC' ) );

        try {
            $tz   = new DateTimeZone( $timezone ?: 'UTC' );
            $now  = new DateTime( 'now', $tz );
            $hour = (int) $now->format( 'G' );
            return $hour >= $from_hour && $hour < $to_hour;
        } catch ( Exception $e ) {
            return true;
        }
    }

    /**
     * Check whether the daily post limit has been reached.
     *
     * @return bool True if still under the daily limit.
     */
    private function is_under_daily_limit() {
        $limit    = (int) get_option( 'wpac_posts_per_day', 3 );
        $database = new WPAC_Database();
        $today    = $database->get_today_posts_count();
        return $today < $limit;
    }

    /**
     * Get list of enabled social media platform slugs.
     *
     * @return array Array of enabled platform slugs.
     */
    private function get_enabled_platforms() {
        $all_platforms = array( 'twitter', 'threads', 'instagram', 'facebook', 'tiktok', 'linkedin' );
        $enabled       = array();

        foreach ( $all_platforms as $platform ) {
            if ( '1' === get_option( 'wpac_' . $platform . '_enabled', '0' ) ) {
                $enabled[] = $platform;
            }
        }

        return $enabled;
    }

    /**
     * Send a webhook notification after content is published.
     *
     * @param int   $post_id      WordPress post ID.
     * @param array $article_data Article data array.
     * @return void
     */
    private function send_webhook_notification( $post_id, $article_data ) {
        $webhook_url = get_option( 'wpac_webhook_url', '' );
        if ( empty( $webhook_url ) || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
            return;
        }

        $payload = wp_json_encode( array(
            'event'      => 'post_published',
            'post_id'    => $post_id,
            'post_url'   => get_permalink( $post_id ),
            'post_title' => $article_data['title'],
            'provider'   => $article_data['provider'],
            'timestamp'  => current_time( 'c' ),
            'site_url'   => home_url(),
        ) );

        $headers = array(
            'Content-Type' => 'application/json',
            'X-WPAC-Event' => 'post_published',
            'User-Agent'   => 'WPAutoContentPro/' . WPAC_VERSION,
        );

        // Add HMAC signature if a webhook secret is configured.
        $webhook_secret = get_option( 'wpac_webhook_secret', '' );
        if ( ! empty( $webhook_secret ) ) {
            $headers['X-WPAC-Signature'] = hash_hmac( 'sha256', $payload, $webhook_secret );
        }

        wp_remote_post(
            $webhook_url,
            array(
                'timeout'  => 10,
                'blocking' => false,
                'headers'  => $headers,
                'body'     => $payload,
            )
        );
    }

    /**
     * Log debug messages when debug mode is enabled.
     *
     * @param string $message Debug message.
     * @return void
     */
    private function log_debug( $message ) {
        if ( '1' === get_option( 'wpac_debug_mode', '0' ) ) {
            error_log( 'WPAC Scheduler: ' . $message );
        }
    }
    /**
     * Manually trigger automation (for admin use).
     *
     * @return array Result with 'success' and 'message' keys.
     */
    public function trigger_manual( ) {
        $database = new WPAC_Database();
        $pending  = $database->get_pending_topics();

        if ( empty( $pending ) ) {
            // If no pending topics, get any active topic.
            $topics = $database->get_topics( array( 'status' => 'active', 'limit' => 1 ) );
            if ( empty( $topics ) ) {
                return array(
                    'success' => false,
                    'message' => __( 'No active topics found. Please add topics first.', 'wp-auto-content-pro' ),
                );
            }
            $topic = $topics[0];
        } else {
            $topic = $pending[0];
        }

        $ai_generator = new WPAC_AI_Generator();
        $article_data = $ai_generator->generate_article( $topic->topic, $topic->type );

        if ( is_wp_error( $article_data ) ) {
            return array(
                'success' => false,
                'message' => $article_data->get_error_message(),
            );
        }

        $post_creator = new WPAC_Post_Creator();
        $post_id      = $post_creator->create_post( $article_data );

        if ( is_wp_error( $post_id ) ) {
            return array(
                'success' => false,
                'message' => $post_id->get_error_message(),
            );
        }

        $log_id = $database->log_post( array(
            'topic_id'    => $topic->id,
            'wp_post_id'  => $post_id,
            'topic_text'  => $topic->topic,
            'ai_provider' => $article_data['provider'],
            'post_title'  => $article_data['title'],
            'post_status' => get_option( 'wpac_default_post_status', 'publish' ),
        ) );

        $database->update_topic_generated( $topic->id );

        $enabled_platforms = $this->get_enabled_platforms();
        if ( ! empty( $enabled_platforms ) ) {
            $social_media = new WPAC_Social_Media();
            $social_media->share_post( $post_id, $enabled_platforms, $log_id );
        }

        return array(
            'success'  => true,
            'message'  => sprintf(
                /* translators: 1: post title */
                __( 'Post "%s" created successfully!', 'wp-auto-content-pro' ),
                $article_data['title']
            ),
            'post_id'  => $post_id,
            'post_url' => get_permalink( $post_id ),
            'edit_url' => get_edit_post_link( $post_id ),
        );
    }
}
