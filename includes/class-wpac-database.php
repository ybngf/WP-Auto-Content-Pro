<?php
/**
 * Database management class.
 *
 * Handles creation and interaction with custom database tables
 * for topics, post logs, and social media logs.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WPAC_Database
 *
 * Manages all database operations for the plugin.
 */
class WPAC_Database {

    /**
     * Topics table name (without prefix).
     *
     * @var string
     */
    const TABLE_TOPICS = 'wpac_topics';

    /**
     * Posts log table name (without prefix).
     *
     * @var string
     */
    const TABLE_POSTS_LOG = 'wpac_posts_log';

    /**
     * Social log table name (without prefix).
     *
     * @var string
     */
    const TABLE_SOCIAL_LOG = 'wpac_social_log';

    /**
     * Database schema version for migration tracking.
     *
     * @var string
     */
    const DB_VERSION = '2.0.0';

    /**
     * WordPress database instance.
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Get full table name with WordPress prefix.
     *
     * @param string $table Table constant name.
     * @return string Full table name.
     */
    public function get_table( $table ) {
        return $this->wpdb->prefix . $table;
    }

    /**
     * Create all custom database tables.
     *
     * @return void
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $topics_table = $this->get_table( self::TABLE_TOPICS );
        $posts_log_table = $this->get_table( self::TABLE_POSTS_LOG );
        $social_log_table = $this->get_table( self::TABLE_SOCIAL_LOG );

        $sql_topics = "CREATE TABLE IF NOT EXISTS {$topics_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            topic VARCHAR(500) NOT NULL,
            type ENUM('article','tutorial') NOT NULL DEFAULT 'article',
            frequency VARCHAR(50) NOT NULL DEFAULT 'daily',
            categories TEXT DEFAULT NULL,
            status ENUM('active','paused') NOT NULL DEFAULT 'active',
            last_generated DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY last_generated (last_generated)
        ) {$charset_collate};";

        $sql_posts_log = "CREATE TABLE IF NOT EXISTS {$posts_log_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            topic_id BIGINT(20) UNSIGNED DEFAULT NULL,
            wp_post_id BIGINT(20) UNSIGNED DEFAULT NULL,
            topic_text VARCHAR(500) NOT NULL,
            ai_provider VARCHAR(50) NOT NULL,
            post_title VARCHAR(500) DEFAULT NULL,
            post_status VARCHAR(20) DEFAULT NULL,
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            error_message TEXT DEFAULT NULL,
            PRIMARY KEY (id),
            KEY topic_id (topic_id),
            KEY wp_post_id (wp_post_id),
            KEY generated_at (generated_at)
        ) {$charset_collate};";

        $sql_social_log = "CREATE TABLE IF NOT EXISTS {$social_log_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_log_id BIGINT(20) UNSIGNED DEFAULT NULL,
            wp_post_id BIGINT(20) UNSIGNED DEFAULT NULL,
            platform VARCHAR(50) NOT NULL,
            status ENUM('success','failed','skipped') NOT NULL DEFAULT 'skipped',
            platform_post_id VARCHAR(255) DEFAULT NULL,
            platform_url VARCHAR(500) DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            shared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY wp_post_id (wp_post_id),
            KEY platform (platform),
            KEY status (status),
            KEY shared_at (shared_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_topics );
        dbDelta( $sql_posts_log );
        dbDelta( $sql_social_log );
    }

    /**
     * Save a topic to the database.
     *
     * @param array $data Topic data with keys: topic, type, frequency, categories, status.
     * @param int   $id   Optional existing topic ID for update.
     * @return int|false Inserted/updated ID or false on failure.
     */
    public function save_topic( $data, $id = 0 ) {
        $table = $this->get_table( self::TABLE_TOPICS );

        $sanitized = array(
            'topic'      => sanitize_text_field( $data['topic'] ),
            'type'       => in_array( $data['type'], array( 'article', 'tutorial' ), true ) ? $data['type'] : 'article',
            'frequency'  => sanitize_text_field( $data['frequency'] ),
            'categories' => isset( $data['categories'] ) ? sanitize_text_field( $data['categories'] ) : '',
            'status'     => in_array( $data['status'], array( 'active', 'paused' ), true ) ? $data['status'] : 'active',
        );

        if ( $id > 0 ) {
            $result = $this->wpdb->update(
                $table,
                $sanitized,
                array( 'id' => absint( $id ) ),
                array( '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
            return ( false !== $result ) ? $id : false;
        } else {
            $result = $this->wpdb->insert(
                $table,
                $sanitized,
                array( '%s', '%s', '%s', '%s', '%s' )
            );
            return ( false !== $result ) ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Get topics from the database.
     *
     * @param array $args Query arguments: status, limit, offset, orderby, order.
     * @return array Array of topic objects.
     */
    public function get_topics( $args = array() ) {
        $table = $this->get_table( self::TABLE_TOPICS );

        $defaults = array(
            'status'  => '',
            'limit'   => 100,
            'offset'  => 0,
            'orderby' => 'created_at',
            'order'   => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        $allowed_orderby = array( 'id', 'topic', 'type', 'frequency', 'status', 'last_generated', 'created_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $where = '1=1';
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where   .= ' AND status = %s';
            $params[] = $args['status'];
        }

        $params[] = absint( $args['limit'] );
        $params[] = absint( $args['offset'] );

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            ...$params
        );

        return $this->wpdb->get_results( $query );
    }

    /**
     * Get a single topic by ID.
     *
     * @param int $id Topic ID.
     * @return object|null Topic object or null if not found.
     */
    public function get_topic( $id ) {
        $table = $this->get_table( self::TABLE_TOPICS );
        return $this->wpdb->get_row(
            $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) )
        );
    }

    /**
     * Delete a topic by ID.
     *
     * @param int $id Topic ID.
     * @return bool True on success, false on failure.
     */
    public function delete_topic( $id ) {
        $table = $this->get_table( self::TABLE_TOPICS );
        $result = $this->wpdb->delete(
            $table,
            array( 'id' => absint( $id ) ),
            array( '%d' )
        );
        return false !== $result;
    }

    /**
     * Get total count of topics.
     *
     * @param string $status Filter by status ('active', 'paused', or '' for all).
     * @return int Count of topics.
     */
    public function count_topics( $status = '' ) {
        $table = $this->get_table( self::TABLE_TOPICS );

        if ( ! empty( $status ) ) {
            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status )
            );
        }

        return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Update the last_generated timestamp for a topic.
     *
     * @param int $topic_id Topic ID.
     * @return void
     */
    public function update_topic_generated( $topic_id ) {
        $table = $this->get_table( self::TABLE_TOPICS );
        $this->wpdb->update(
            $table,
            array( 'last_generated' => current_time( 'mysql' ) ),
            array( 'id' => absint( $topic_id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Log a post generation event.
     *
     * @param array $data Log data with keys: topic_id, wp_post_id, topic_text, ai_provider, post_title, post_status, error_message.
     * @return int|false Inserted log ID or false on failure.
     */
    public function log_post( $data ) {
        $table = $this->get_table( self::TABLE_POSTS_LOG );

        $insert = array(
            'topic_id'      => isset( $data['topic_id'] ) ? absint( $data['topic_id'] ) : null,
            'wp_post_id'    => isset( $data['wp_post_id'] ) ? absint( $data['wp_post_id'] ) : null,
            'topic_text'    => sanitize_text_field( $data['topic_text'] ),
            'ai_provider'   => sanitize_text_field( $data['ai_provider'] ),
            'post_title'    => isset( $data['post_title'] ) ? sanitize_text_field( $data['post_title'] ) : null,
            'post_status'   => isset( $data['post_status'] ) ? sanitize_text_field( $data['post_status'] ) : null,
            'error_message' => isset( $data['error_message'] ) ? sanitize_textarea_field( $data['error_message'] ) : null,
        );

        $formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' );

        $result = $this->wpdb->insert( $table, $insert, $formats );
        return ( false !== $result ) ? $this->wpdb->insert_id : false;
    }

    /**
     * Log a social media share event.
     *
     * @param array $data Log data with keys: post_log_id, wp_post_id, platform, status, platform_post_id, platform_url, error_message.
     * @return int|false Inserted log ID or false on failure.
     */
    public function log_social( $data ) {
        $table = $this->get_table( self::TABLE_SOCIAL_LOG );

        $valid_statuses = array( 'success', 'failed', 'skipped' );
        $status = in_array( $data['status'], $valid_statuses, true ) ? $data['status'] : 'skipped';

        $insert = array(
            'post_log_id'      => isset( $data['post_log_id'] ) ? absint( $data['post_log_id'] ) : null,
            'wp_post_id'       => isset( $data['wp_post_id'] ) ? absint( $data['wp_post_id'] ) : null,
            'platform'         => sanitize_text_field( $data['platform'] ),
            'status'           => $status,
            'platform_post_id' => isset( $data['platform_post_id'] ) ? sanitize_text_field( $data['platform_post_id'] ) : null,
            'platform_url'     => isset( $data['platform_url'] ) ? esc_url_raw( $data['platform_url'] ) : null,
            'error_message'    => isset( $data['error_message'] ) ? sanitize_textarea_field( $data['error_message'] ) : null,
        );

        $formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' );

        $result = $this->wpdb->insert( $table, $insert, $formats );
        return ( false !== $result ) ? $this->wpdb->insert_id : false;
    }

    /**
     * Get post logs.
     *
     * @param array $args Query arguments: limit, offset, orderby, order.
     * @return array Array of log objects.
     */
    public function get_logs( $args = array() ) {
        $table = $this->get_table( self::TABLE_POSTS_LOG );

        $defaults = array(
            'limit'   => 50,
            'offset'  => 0,
            'orderby' => 'generated_at',
            'order'   => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        $allowed_orderby = array( 'id', 'topic_text', 'ai_provider', 'post_status', 'generated_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'generated_at';
        $order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            absint( $args['limit'] ),
            absint( $args['offset'] )
        );

        return $this->wpdb->get_results( $query );
    }

    /**
     * Get social logs for a specific WordPress post.
     *
     * @param int $wp_post_id WordPress post ID.
     * @return array Array of social log objects.
     */
    public function get_social_logs_for_post( $wp_post_id ) {
        $table = $this->get_table( self::TABLE_SOCIAL_LOG );
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE wp_post_id = %d ORDER BY shared_at DESC",
                absint( $wp_post_id )
            )
        );
    }

    /**
     * Get total posts generated today.
     *
     * @return int Count of posts generated today.
     */
    public function get_today_posts_count() {
        $table = $this->get_table( self::TABLE_POSTS_LOG );
        $today = current_time( 'Y-m-d' );
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE DATE(generated_at) = %s AND error_message IS NULL",
                $today
            )
        );
    }

    /**
     * Get total social shares count.
     *
     * @return int Count of successful social shares.
     */
    public function get_total_social_shares() {
        $table = $this->get_table( self::TABLE_SOCIAL_LOG );
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'success'"
        );
    }

    /**
     * Get recent activity combining posts and social logs.
     *
     * @param int $limit Number of records to return.
     * @return array Array of activity objects.
     */
    public function get_recent_activity( $limit = 10 ) {
        $posts_table  = $this->get_table( self::TABLE_POSTS_LOG );
        $social_table = $this->get_table( self::TABLE_SOCIAL_LOG );

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT p.*,
                    GROUP_CONCAT(s.platform ORDER BY s.platform SEPARATOR ',') as social_platforms,
                    GROUP_CONCAT(s.status ORDER BY s.platform SEPARATOR ',') as social_statuses
                FROM {$posts_table} p
                LEFT JOIN {$social_table} s ON p.wp_post_id = s.wp_post_id
                GROUP BY p.id
                ORDER BY p.generated_at DESC
                LIMIT %d",
                absint( $limit )
            )
        );
    }

    /**
     * Get active topics that are due for content generation.
     *
     * @return array Array of topic objects that need content generated.
     */
    public function get_pending_topics() {
        $table = $this->get_table( self::TABLE_TOPICS );
        $now   = current_time( 'mysql' );

        return $this->wpdb->get_results(
            "SELECT * FROM {$table}
            WHERE status = 'active'
            AND (
                last_generated IS NULL
                OR (frequency = 'hourly' AND last_generated < DATE_SUB('{$now}', INTERVAL 1 HOUR))
                OR (frequency = 'every_2h' AND last_generated < DATE_SUB('{$now}', INTERVAL 2 HOUR))
                OR (frequency = 'every_6h' AND last_generated < DATE_SUB('{$now}', INTERVAL 6 HOUR))
                OR (frequency = 'daily' AND last_generated < DATE_SUB('{$now}', INTERVAL 1 DAY))
                OR (frequency = 'weekly' AND last_generated < DATE_SUB('{$now}', INTERVAL 7 DAY))
            )
            ORDER BY RAND()
            LIMIT 1"
        );
    }

    /**
     * Bulk update topic status.
     *
     * @param array  $ids    Array of topic IDs.
     * @param string $status New status ('active' or 'paused').
     * @return int|false Number of rows updated or false on failure.
     */
    public function bulk_update_status( $ids, $status ) {
        if ( empty( $ids ) ) {
            return false;
        }

        $valid_statuses = array( 'active', 'paused' );
        if ( ! in_array( $status, $valid_statuses, true ) ) {
            return false;
        }

        $table       = $this->get_table( self::TABLE_TOPICS );
        $ids_cleaned = array_map( 'absint', $ids );
        $ids_in      = implode( ',', $ids_cleaned );

        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$table} SET status = %s WHERE id IN ({$ids_in})",
                $status
            )
        );
    }

    /**
     * Bulk delete topics.
     *
     * @param array $ids Array of topic IDs to delete.
     * @return int|false Number of rows deleted or false on failure.
     */
    public function bulk_delete( $ids ) {
        if ( empty( $ids ) ) {
            return false;
        }

        $table       = $this->get_table( self::TABLE_TOPICS );
        $ids_cleaned = array_map( 'absint', $ids );
        $ids_in      = implode( ',', $ids_cleaned );

        return $this->wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ids_in})" );
    }

    /**
     * Get total count of post generation logs.
     *
     * @return int Count of log entries.
     */
    public function get_log_count() {
        $table = $this->get_table( self::TABLE_POSTS_LOG );
        return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Get success rate of content generation.
     *
     * @return float Success rate percentage (0-100).
     */
    public function get_success_rate() {
        $table = $this->get_table( self::TABLE_POSTS_LOG );
        $total = (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        if ( 0 === $total ) {
            return 0.0;
        }

        $success = (int) $this->wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE error_message IS NULL AND wp_post_id IS NOT NULL"
        );

        return round( ( $success / $total ) * 100, 1 );
    }

    /**
     * Check if database schema needs updating.
     *
     * @return bool True if update is needed.
     */
    public function needs_upgrade() {
        $current = get_option( 'wpac_db_version', '0' );
        return version_compare( $current, self::DB_VERSION, '<' );
    }

    /**
     * Mark database schema version as current.
     *
     * @return void
     */
    public function update_db_version() {
        update_option( 'wpac_db_version', self::DB_VERSION );
    }
}
