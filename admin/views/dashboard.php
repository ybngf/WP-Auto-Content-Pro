<?php
/**
 * Admin Dashboard view.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Check if a social platform has configured credentials (used in this view).
 *
 * @param string $platform Platform slug.
 * @return bool
 */
function wpac_dashboard_platform_has_credentials( $platform ) {
    $cred_keys = array(
        'twitter'   => 'wpac_twitter_api_key',
        'threads'   => 'wpac_threads_access_token',
        'instagram' => 'wpac_instagram_access_token',
        'facebook'  => 'wpac_facebook_page_access_token',
        'tiktok'    => 'wpac_tiktok_access_token',
        'linkedin'  => 'wpac_linkedin_access_token',
    );
    $key = $cred_keys[ $platform ] ?? '';
    return ! empty( $key ) && ! empty( get_option( $key, '' ) );
}

$admin    = new WPAC_Admin();
$stats    = $admin->get_dashboard_stats();
$database = new WPAC_Database();
$activity = $database->get_recent_activity( 10 );

$providers = array( 'openai', 'gemini', 'claude', 'deepseek' );
$platforms = array( 'twitter', 'threads', 'instagram', 'facebook', 'tiktok', 'linkedin' );

$provider_labels = array(
    'openai'   => 'OpenAI',
    'gemini'   => 'Google Gemini',
    'claude'   => 'Claude AI',
    'deepseek' => 'DeepSeek',
);

$platform_labels = array(
    'twitter'   => 'Twitter / X',
    'threads'   => 'Threads',
    'instagram' => 'Instagram',
    'facebook'  => 'Facebook',
    'tiktok'    => 'TikTok',
    'linkedin'  => 'LinkedIn',
);

$platform_icons = array(
    'twitter'   => '&#120143;',
    'threads'   => '&#9679;',
    'instagram' => '&#128247;',
    'facebook'  => 'f',
    'tiktok'    => '&#9836;',
    'linkedin'  => 'in',
);
?>
<div class="wrap wpac-wrap">
    <div class="wpac-page-header">
        <h1 class="wpac-page-title">
            <span class="wpac-logo-icon">&#9881;</span>
            <?php esc_html_e( 'WP Auto Content Pro', 'wp-auto-content-pro' ); ?>
        </h1>
        <div class="wpac-header-actions">
            <button id="wpac-generate-now" class="wpac-btn wpac-btn-primary wpac-btn-lg">
                <span class="wpac-btn-icon">&#9889;</span>
                <?php esc_html_e( 'Generate Now', 'wp-auto-content-pro' ); ?>
            </button>
        </div>
    </div>

    <div id="wpac-toast-container"></div>

    <?php if ( isset( $_GET['updated'] ) ) : ?>
    <div class="wpac-notice wpac-notice-success">
        <p><?php esc_html_e( 'Settings saved successfully.', 'wp-auto-content-pro' ); ?></p>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="wpac-stats-grid">
        <div class="wpac-stat-card wpac-stat-blue">
            <div class="wpac-stat-icon">&#128196;</div>
            <div class="wpac-stat-content">
                <div class="wpac-stat-number"><?php echo esc_html( number_format( $stats['total_posts'] ) ); ?></div>
                <div class="wpac-stat-label"><?php esc_html_e( 'Total Posts', 'wp-auto-content-pro' ); ?></div>
            </div>
        </div>
        <div class="wpac-stat-card wpac-stat-green">
            <div class="wpac-stat-icon">&#128336;</div>
            <div class="wpac-stat-content">
                <div class="wpac-stat-number"><?php echo esc_html( $stats['today_posts'] ); ?></div>
                <div class="wpac-stat-label"><?php esc_html_e( 'Posts Today', 'wp-auto-content-pro' ); ?></div>
            </div>
        </div>
        <div class="wpac-stat-card wpac-stat-purple">
            <div class="wpac-stat-icon">&#128279;</div>
            <div class="wpac-stat-content">
                <div class="wpac-stat-number"><?php echo esc_html( number_format( $stats['social_shares'] ) ); ?></div>
                <div class="wpac-stat-label"><?php esc_html_e( 'Social Shares', 'wp-auto-content-pro' ); ?></div>
            </div>
        </div>
        <div class="wpac-stat-card wpac-stat-orange">
            <div class="wpac-stat-icon">&#128203;</div>
            <div class="wpac-stat-content">
                <div class="wpac-stat-number"><?php echo esc_html( $stats['active_topics'] ); ?> / <?php echo esc_html( $stats['total_topics'] ); ?></div>
                <div class="wpac-stat-label"><?php esc_html_e( 'Active Topics', 'wp-auto-content-pro' ); ?></div>
            </div>
        </div>
    </div>

    <div class="wpac-dashboard-grid">

        <!-- Schedule Status -->
        <div class="wpac-card">
            <div class="wpac-card-header">
                <h2><?php esc_html_e( 'Automation Status', 'wp-auto-content-pro' ); ?></h2>
            </div>
            <div class="wpac-card-body">
                <div class="wpac-status-row">
                    <span class="wpac-status-label"><?php esc_html_e( 'Automation Schedule', 'wp-auto-content-pro' ); ?></span>
                    <?php if ( $stats['schedule_on'] ) : ?>
                        <span class="wpac-badge wpac-badge-success"><?php esc_html_e( 'Active', 'wp-auto-content-pro' ); ?></span>
                    <?php else : ?>
                        <span class="wpac-badge wpac-badge-danger"><?php esc_html_e( 'Disabled', 'wp-auto-content-pro' ); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ( $stats['next_run'] ) : ?>
                <div class="wpac-status-row">
                    <span class="wpac-status-label"><?php esc_html_e( 'Next Run', 'wp-auto-content-pro' ); ?></span>
                    <span class="wpac-status-value"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $stats['next_run'] ) ); ?></span>
                </div>
                <?php endif; ?>
                <div class="wpac-status-row">
                    <span class="wpac-status-label"><?php esc_html_e( 'Posts per Day', 'wp-auto-content-pro' ); ?></span>
                    <span class="wpac-status-value"><?php echo esc_html( get_option( 'wpac_posts_per_day', 3 ) ); ?></span>
                </div>
                <div class="wpac-status-row">
                    <span class="wpac-status-label"><?php esc_html_e( 'Posting Window', 'wp-auto-content-pro' ); ?></span>
                    <span class="wpac-status-value"><?php echo esc_html( get_option( 'wpac_posting_time_from', 8 ) . ':00 - ' . get_option( 'wpac_posting_time_to', 20 ) . ':00' ); ?></span>
                </div>
            </div>
        </div>

        <!-- API Status -->
        <div class="wpac-card">
            <div class="wpac-card-header">
                <h2><?php esc_html_e( 'AI Provider Status', 'wp-auto-content-pro' ); ?></h2>
            </div>
            <div class="wpac-card-body">
                <?php foreach ( $providers as $provider ) :
                    $api_key      = get_option( 'wpac_' . $provider . '_api_key', '' );
                    $is_configured = ! empty( $api_key );
                    $is_active     = get_option( 'wpac_ai_provider', 'openai' ) === $provider;
                ?>
                <div class="wpac-status-row">
                    <span class="wpac-status-label">
                        <?php echo esc_html( $provider_labels[ $provider ] ); ?>
                        <?php if ( $is_active ) : ?>
                            <span class="wpac-badge wpac-badge-info wpac-badge-sm"><?php esc_html_e( 'Primary', 'wp-auto-content-pro' ); ?></span>
                        <?php endif; ?>
                    </span>
                    <div class="wpac-status-actions">
                        <?php if ( $is_configured ) : ?>
                            <span class="wpac-badge wpac-badge-success"><?php esc_html_e( 'Configured', 'wp-auto-content-pro' ); ?></span>
                        <?php else : ?>
                            <span class="wpac-badge wpac-badge-warning"><?php esc_html_e( 'Not Set', 'wp-auto-content-pro' ); ?></span>
                        <?php endif; ?>
                        <?php if ( $is_configured ) : ?>
                        <button class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-test-ai" data-provider="<?php echo esc_attr( $provider ); ?>">
                            <?php esc_html_e( 'Test', 'wp-auto-content-pro' ); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Social Media Status -->
        <div class="wpac-card">
            <div class="wpac-card-header">
                <h2><?php esc_html_e( 'Social Media Status', 'wp-auto-content-pro' ); ?></h2>
            </div>
            <div class="wpac-card-body">
                <?php foreach ( $platforms as $platform ) :
                    $enabled      = get_option( 'wpac_' . $platform . '_enabled', '0' ) === '1';
                    $has_creds    = wpac_dashboard_platform_has_credentials( $platform );
                ?>
                <div class="wpac-status-row">
                    <span class="wpac-status-label">
                        <span class="wpac-platform-icon"><?php echo $platform_icons[ $platform ]; ?></span>
                        <?php echo esc_html( $platform_labels[ $platform ] ); ?>
                    </span>
                    <div class="wpac-status-actions">
                        <?php if ( $enabled && $has_creds ) : ?>
                            <span class="wpac-badge wpac-badge-success"><?php esc_html_e( 'Active', 'wp-auto-content-pro' ); ?></span>
                            <button class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-test-social" data-platform="<?php echo esc_attr( $platform ); ?>">
                                <?php esc_html_e( 'Test', 'wp-auto-content-pro' ); ?>
                            </button>
                        <?php elseif ( $enabled ) : ?>
                            <span class="wpac-badge wpac-badge-warning"><?php esc_html_e( 'No Credentials', 'wp-auto-content-pro' ); ?></span>
                        <?php else : ?>
                            <span class="wpac-badge wpac-badge-secondary"><?php esc_html_e( 'Disabled', 'wp-auto-content-pro' ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="wpac-card">
            <div class="wpac-card-header">
                <h2><?php esc_html_e( 'Quick Actions', 'wp-auto-content-pro' ); ?></h2>
            </div>
            <div class="wpac-card-body">
                <div class="wpac-quick-actions">
                    <button id="wpac-generate-now-2" class="wpac-action-btn wpac-action-primary">
                        <span class="wpac-action-icon">&#9889;</span>
                        <span><?php esc_html_e( 'Generate Post Now', 'wp-auto-content-pro' ); ?></span>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpac-topics' ) ); ?>" class="wpac-action-btn wpac-action-secondary">
                        <span class="wpac-action-icon">&#43;</span>
                        <span><?php esc_html_e( 'Add New Topic', 'wp-auto-content-pro' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpac-settings' ) ); ?>" class="wpac-action-btn wpac-action-secondary">
                        <span class="wpac-action-icon">&#9881;</span>
                        <span><?php esc_html_e( 'Settings', 'wp-auto-content-pro' ); ?></span>
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpac-logs' ) ); ?>" class="wpac-action-btn wpac-action-secondary">
                        <span class="wpac-action-icon">&#128196;</span>
                        <span><?php esc_html_e( 'View Logs', 'wp-auto-content-pro' ); ?></span>
                    </a>
                </div>
            </div>
        </div>

    </div><!-- .wpac-dashboard-grid -->

    <!-- Recent Activity -->
    <div class="wpac-card wpac-card-full">
        <div class="wpac-card-header">
            <h2><?php esc_html_e( 'Recent Activity', 'wp-auto-content-pro' ); ?></h2>
        </div>
        <div class="wpac-card-body">
            <?php if ( empty( $activity ) ) : ?>
                <div class="wpac-empty-state">
                    <div class="wpac-empty-icon">&#128203;</div>
                    <p><?php esc_html_e( 'No activity yet. Add topics and enable the schedule or click Generate Now.', 'wp-auto-content-pro' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpac-topics' ) ); ?>" class="wpac-btn wpac-btn-primary">
                        <?php esc_html_e( 'Add Topics', 'wp-auto-content-pro' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <table class="wpac-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Post Title', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Topic', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'AI Provider', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Social', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Generated', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'wp-auto-content-pro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $activity as $item ) :
                            $post = $item->wp_post_id ? get_post( $item->wp_post_id ) : null;
                            $social_platforms = $item->social_platforms ? explode( ',', $item->social_platforms ) : array();
                            $social_statuses  = $item->social_statuses ? explode( ',', $item->social_statuses ) : array();
                        ?>
                        <tr>
                            <td>
                                <?php if ( $post ) : ?>
                                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank">
                                        <?php echo esc_html( $item->post_title ?: $post->post_title ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $item->post_title ?: __( 'N/A', 'wp-auto-content-pro' ) ); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( wp_trim_words( $item->topic_text, 8 ) ); ?></td>
                            <td>
                                <span class="wpac-badge wpac-badge-info">
                                    <?php echo esc_html( strtoupper( $item->ai_provider ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( $item->error_message ) : ?>
                                    <span class="wpac-badge wpac-badge-danger" title="<?php echo esc_attr( $item->error_message ); ?>">
                                        <?php esc_html_e( 'Failed', 'wp-auto-content-pro' ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="wpac-badge wpac-badge-success">
                                        <?php echo esc_html( $item->post_status ?: __( 'Published', 'wp-auto-content-pro' ) ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="wpac-social-icons">
                                    <?php foreach ( $social_platforms as $idx => $sp ) :
                                        $ss = $social_statuses[ $idx ] ?? 'skipped';
                                        $icon_class = 'success' === $ss ? 'wpac-social-success' : ( 'failed' === $ss ? 'wpac-social-failed' : 'wpac-social-skipped' );
                                    ?>
                                        <span class="wpac-social-dot <?php echo esc_attr( $icon_class ); ?>" title="<?php echo esc_attr( ucfirst( $sp ) . ': ' . $ss ); ?>">
                                            <?php echo esc_html( substr( $sp, 0, 1 ) ); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if ( empty( $social_platforms ) ) : ?>
                                        <span class="wpac-text-muted"><?php esc_html_e( 'None', 'wp-auto-content-pro' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="wpac-text-muted wpac-text-sm">
                                    <?php echo esc_html( wp_date( 'M j, Y H:i', strtotime( $item->generated_at ) ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( $post ) : ?>
                                    <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="wpac-btn wpac-btn-xs wpac-btn-outline">
                                        <?php esc_html_e( 'Edit', 'wp-auto-content-pro' ); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="wpac-loading-overlay" class="wpac-loading-overlay" style="display:none;">
        <div class="wpac-loading-box">
            <div class="wpac-spinner"></div>
            <p id="wpac-loading-message"><?php esc_html_e( 'Generating content...', 'wp-auto-content-pro' ); ?></p>
        </div>
    </div>

</div><!-- .wpac-wrap -->

<script>
jQuery(document).ready(function($) {
    // Generate Now buttons.
    $('#wpac-generate-now, #wpac-generate-now-2').on('click', function() {
        $('#wpac-loading-message').text(wpac_ajax.strings.generating);
        $('#wpac-loading-overlay').fadeIn(200);

        $.post(wpac_ajax.ajax_url, {
            action: 'wpac_generate_now',
            nonce: wpac_ajax.nonce
        }, function(response) {
            $('#wpac-loading-overlay').fadeOut(200);
            if (response.success) {
                wpacShowToast('success', response.data.message + ' <a href="' + response.data.post_url + '" target="_blank">View Post</a>');
                setTimeout(function() { location.reload(); }, 3000);
            } else {
                wpacShowToast('error', response.data.message);
            }
        }).fail(function() {
            $('#wpac-loading-overlay').fadeOut(200);
            wpacShowToast('error', wpac_ajax.strings.error);
        });
    });

    // Test AI connections.
    $('.wpac-test-ai').on('click', function() {
        var btn = $(this);
        var provider = btn.data('provider');
        btn.text('Testing...').prop('disabled', true);

        $.post(wpac_ajax.ajax_url, {
            action: 'wpac_test_ai',
            nonce: wpac_ajax.nonce,
            provider: provider
        }, function(response) {
            btn.text('Test').prop('disabled', false);
            if (response.success) {
                wpacShowToast('success', response.data.message);
            } else {
                wpacShowToast('error', response.data.message);
            }
        });
    });

    // Test social connections.
    $('.wpac-test-social').on('click', function() {
        var btn = $(this);
        var platform = btn.data('platform');
        btn.text('Testing...').prop('disabled', true);

        $.post(wpac_ajax.ajax_url, {
            action: 'wpac_test_social',
            nonce: wpac_ajax.nonce,
            platform: platform
        }, function(response) {
            btn.text('Test').prop('disabled', false);
            if (response.success) {
                wpacShowToast('success', response.data.message);
            } else {
                wpacShowToast('error', response.data.message);
            }
        });
    });
});
</script>
<?php

