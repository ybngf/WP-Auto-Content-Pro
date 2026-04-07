<?php
/**
 * Admin Logs view.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$database    = new WPAC_Database();
$per_page    = 25;
$current_page = max( 1, absint( $_GET['paged'] ?? 1 ) );
$offset      = ( $current_page - 1 ) * $per_page;

$logs = $database->get_logs( array(
    'limit'  => $per_page,
    'offset' => $offset,
) );

// Get total for pagination.
global $wpdb;
$total_logs = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpac_posts_log" );
$total_pages = ceil( $total_logs / $per_page );

$platform_labels = array(
    'twitter'   => 'X',
    'threads'   => 'Threads',
    'instagram' => 'IG',
    'facebook'  => 'FB',
    'tiktok'    => 'TT',
    'linkedin'  => 'LI',
);
?>
<div class="wrap wpac-wrap">
    <div class="wpac-page-header">
        <h1 class="wpac-page-title"><?php esc_html_e( 'Activity Logs', 'wp-auto-content-pro' ); ?></h1>
        <div class="wpac-header-actions">
            <span class="wpac-text-muted">
                <?php printf(
                    esc_html__( '%d total records', 'wp-auto-content-pro' ),
                    $total_logs
                ); ?>
            </span>
        </div>
    </div>

    <?php if ( empty( $logs ) ) : ?>
        <div class="wpac-card">
            <div class="wpac-card-body">
                <div class="wpac-empty-state">
                    <div class="wpac-empty-icon">&#128196;</div>
                    <h3><?php esc_html_e( 'No Activity Yet', 'wp-auto-content-pro' ); ?></h3>
                    <p><?php esc_html_e( 'Logs will appear here after content is generated.', 'wp-auto-content-pro' ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpac-dashboard' ) ); ?>" class="wpac-btn wpac-btn-primary">
                        <?php esc_html_e( 'Go to Dashboard', 'wp-auto-content-pro' ); ?>
                    </a>
                </div>
            </div>
        </div>
    <?php else : ?>

        <div class="wpac-card">
            <div class="wpac-card-body wpac-p-0">
                <table class="wpac-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php esc_html_e( 'Post Title', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Topic', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'AI Provider', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Post Status', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Social Shares', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Generated At', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Result', 'wp-auto-content-pro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log ) :
                            $post        = $log->wp_post_id ? get_post( $log->wp_post_id ) : null;
                            $social_logs = $log->wp_post_id ? $database->get_social_logs_for_post( $log->wp_post_id ) : array();
                        ?>
                        <tr>
                            <td class="wpac-text-muted wpac-text-sm"><?php echo esc_html( $log->id ); ?></td>
                            <td>
                                <?php if ( $post ) : ?>
                                    <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank" class="wpac-text-link">
                                        <?php echo esc_html( wp_trim_words( $log->post_title ?: $post->post_title, 10 ) ); ?>
                                    </a>
                                    <div class="wpac-log-actions">
                                        <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="wpac-text-link wpac-text-sm">
                                            <?php esc_html_e( 'Edit', 'wp-auto-content-pro' ); ?>
                                        </a>
                                        &nbsp;&middot;&nbsp;
                                        <a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank" class="wpac-text-link wpac-text-sm">
                                            <?php esc_html_e( 'View', 'wp-auto-content-pro' ); ?>
                                        </a>
                                    </div>
                                <?php else : ?>
                                    <span class="wpac-text-muted"><?php echo esc_html( $log->post_title ?: __( 'N/A', 'wp-auto-content-pro' ) ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="wpac-text-sm"><?php echo esc_html( wp_trim_words( $log->topic_text, 8 ) ); ?></td>
                            <td>
                                <span class="wpac-badge wpac-badge-info">
                                    <?php echo esc_html( strtoupper( $log->ai_provider ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( $log->post_status ) : ?>
                                    <span class="wpac-badge <?php echo 'publish' === $log->post_status ? 'wpac-badge-success' : 'wpac-badge-secondary'; ?>">
                                        <?php echo esc_html( ucfirst( $log->post_status ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="wpac-text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="wpac-social-log-icons">
                                    <?php if ( ! empty( $social_logs ) ) :
                                        foreach ( $social_logs as $sl ) :
                                            $short = $platform_labels[ $sl->platform ] ?? substr( $sl->platform, 0, 2 );
                                            $cls   = 'success' === $sl->status ? 'wpac-social-success' : ( 'failed' === $sl->status ? 'wpac-social-failed' : 'wpac-social-skipped' );
                                            $title = ucfirst( $sl->platform ) . ': ' . $sl->status;
                                            if ( $sl->error_message ) {
                                                $title .= ' — ' . $sl->error_message;
                                            }
                                    ?>
                                        <span class="wpac-social-dot <?php echo esc_attr( $cls ); ?>" title="<?php echo esc_attr( $title ); ?>">
                                            <?php echo esc_html( $short ); ?>
                                        </span>
                                    <?php endforeach;
                                    else : ?>
                                        <span class="wpac-text-muted wpac-text-sm"><?php esc_html_e( 'None', 'wp-auto-content-pro' ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="wpac-text-sm wpac-text-muted">
                                    <?php echo esc_html( wp_date( 'M j, Y', strtotime( $log->generated_at ) ) ); ?><br>
                                    <small><?php echo esc_html( wp_date( 'H:i:s', strtotime( $log->generated_at ) ) ); ?></small>
                                </span>
                            </td>
                            <td>
                                <?php if ( $log->error_message ) : ?>
                                    <span class="wpac-badge wpac-badge-danger" title="<?php echo esc_attr( $log->error_message ); ?>">
                                        <?php esc_html_e( 'Error', 'wp-auto-content-pro' ); ?>
                                    </span>
                                    <button type="button" class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-show-error"
                                            data-error="<?php echo esc_attr( $log->error_message ); ?>">
                                        <?php esc_html_e( 'Details', 'wp-auto-content-pro' ); ?>
                                    </button>
                                <?php else : ?>
                                    <span class="wpac-badge wpac-badge-success"><?php esc_html_e( 'Success', 'wp-auto-content-pro' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ( $total_pages > 1 ) : ?>
            <div class="wpac-card-footer">
                <div class="wpac-pagination">
                    <?php if ( $current_page > 1 ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>" class="wpac-page-btn">
                            &laquo; <?php esc_html_e( 'Previous', 'wp-auto-content-pro' ); ?>
                        </a>
                    <?php endif; ?>

                    <?php
                    $start = max( 1, $current_page - 2 );
                    $end   = min( $total_pages, $current_page + 2 );
                    for ( $p = $start; $p <= $end; $p++ ) :
                    ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $p ) ); ?>"
                           class="wpac-page-btn <?php echo $p === $current_page ? 'wpac-page-active' : ''; ?>">
                            <?php echo esc_html( $p ); ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ( $current_page < $total_pages ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>" class="wpac-page-btn">
                            <?php esc_html_e( 'Next', 'wp-auto-content-pro' ); ?> &raquo;
                        </a>
                    <?php endif; ?>
                </div>
                <div class="wpac-pagination-info">
                    <?php printf(
                        esc_html__( 'Page %1$d of %2$d', 'wp-auto-content-pro' ),
                        $current_page,
                        $total_pages
                    ); ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- .wpac-card -->

    <?php endif; ?>

</div><!-- .wpac-wrap -->

<!-- Error Detail Modal -->
<div id="wpac-error-modal" class="wpac-modal" style="display:none;">
    <div class="wpac-modal-overlay"></div>
    <div class="wpac-modal-dialog wpac-modal-sm">
        <div class="wpac-modal-header">
            <h3><?php esc_html_e( 'Error Details', 'wp-auto-content-pro' ); ?></h3>
            <button class="wpac-modal-close">&times;</button>
        </div>
        <div class="wpac-modal-body">
            <pre id="wpac-error-text" class="wpac-error-pre"></pre>
        </div>
        <div class="wpac-modal-footer">
            <button class="wpac-btn wpac-btn-secondary wpac-modal-close"><?php esc_html_e( 'Close', 'wp-auto-content-pro' ); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show error details modal.
    $(document).on('click', '.wpac-show-error', function() {
        var error = $(this).data('error');
        $('#wpac-error-text').text(error);
        $('#wpac-error-modal').fadeIn(200);
    });

    // Close modals.
    $(document).on('click', '.wpac-modal-close, .wpac-modal-overlay', function() {
        $('.wpac-modal').fadeOut(200);
    });
});
</script>
