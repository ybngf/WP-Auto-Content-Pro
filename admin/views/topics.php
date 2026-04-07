<?php
/**
 * Admin Topics view.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$database  = new WPAC_Database();
$topics    = $database->get_topics( array( 'limit' => 200 ) );
$total     = $database->count_topics();
$active    = $database->count_topics( 'active' );
$paused    = $database->count_topics( 'paused' );

$frequency_labels = array(
    'hourly'    => __( 'Hourly', 'wp-auto-content-pro' ),
    'every_2h'  => __( 'Every 2 Hours', 'wp-auto-content-pro' ),
    'every_6h'  => __( 'Every 6 Hours', 'wp-auto-content-pro' ),
    'daily'     => __( 'Daily', 'wp-auto-content-pro' ),
    'weekly'    => __( 'Weekly', 'wp-auto-content-pro' ),
);
?>
<div class="wrap wpac-wrap">
    <div class="wpac-page-header">
        <h1 class="wpac-page-title"><?php esc_html_e( 'Content Topics', 'wp-auto-content-pro' ); ?></h1>
        <div class="wpac-header-actions">
            <button id="wpac-add-topic" class="wpac-btn wpac-btn-primary">
                <span>&#43;</span> <?php esc_html_e( 'Add Topic', 'wp-auto-content-pro' ); ?>
            </button>
        </div>
    </div>

    <div id="wpac-toast-container"></div>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="wpac-notice wpac-notice-success"><p><?php esc_html_e( 'Topic saved successfully.', 'wp-auto-content-pro' ); ?></p></div>
    <?php elseif ( isset( $_GET['deleted'] ) ) : ?>
        <div class="wpac-notice wpac-notice-success"><p><?php esc_html_e( 'Topic deleted.', 'wp-auto-content-pro' ); ?></p></div>
    <?php elseif ( isset( $_GET['imported'] ) ) : ?>
        <div class="wpac-notice wpac-notice-success">
            <p><?php printf( esc_html__( '%d topics imported successfully.', 'wp-auto-content-pro' ), intval( $_GET['imported'] ) ); ?></p>
        </div>
    <?php elseif ( isset( $_GET['bulk_done'] ) ) : ?>
        <div class="wpac-notice wpac-notice-success"><p><?php esc_html_e( 'Bulk action completed.', 'wp-auto-content-pro' ); ?></p></div>
    <?php elseif ( isset( $_GET['error'] ) ) : ?>
        <div class="wpac-notice wpac-notice-error"><p><?php esc_html_e( 'An error occurred. Please try again.', 'wp-auto-content-pro' ); ?></p></div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="wpac-topics-stats">
        <div class="wpac-topics-stat">
            <strong><?php echo esc_html( $total ); ?></strong>
            <span><?php esc_html_e( 'Total Topics', 'wp-auto-content-pro' ); ?></span>
        </div>
        <div class="wpac-topics-stat wpac-text-green">
            <strong><?php echo esc_html( $active ); ?></strong>
            <span><?php esc_html_e( 'Active', 'wp-auto-content-pro' ); ?></span>
        </div>
        <div class="wpac-topics-stat wpac-text-orange">
            <strong><?php echo esc_html( $paused ); ?></strong>
            <span><?php esc_html_e( 'Paused', 'wp-auto-content-pro' ); ?></span>
        </div>
    </div>

    <!-- Bulk Actions + Import -->
    <div class="wpac-topics-toolbar">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wpac-bulk-form">
            <?php wp_nonce_field( 'wpac_bulk_topics', 'wpac_bulk_nonce' ); ?>
            <input type="hidden" name="action" value="wpac_bulk_topics">

            <div class="wpac-toolbar-left">
                <select name="bulk_action" id="wpac-bulk-action">
                    <option value=""><?php esc_html_e( 'Bulk Actions', 'wp-auto-content-pro' ); ?></option>
                    <option value="activate"><?php esc_html_e( 'Activate', 'wp-auto-content-pro' ); ?></option>
                    <option value="pause"><?php esc_html_e( 'Pause', 'wp-auto-content-pro' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'Delete', 'wp-auto-content-pro' ); ?></option>
                </select>
                <button type="button" id="wpac-bulk-apply" class="wpac-btn wpac-btn-secondary">
                    <?php esc_html_e( 'Apply', 'wp-auto-content-pro' ); ?>
                </button>
            </div>

            <div class="wpac-toolbar-right">
                <button type="button" id="wpac-show-import" class="wpac-btn wpac-btn-outline">
                    &#128196; <?php esc_html_e( 'Import CSV', 'wp-auto-content-pro' ); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Topics Table -->
    <?php if ( empty( $topics ) ) : ?>
        <div class="wpac-card">
            <div class="wpac-card-body">
                <div class="wpac-empty-state">
                    <div class="wpac-empty-icon">&#128203;</div>
                    <h3><?php esc_html_e( 'No Topics Yet', 'wp-auto-content-pro' ); ?></h3>
                    <p><?php esc_html_e( 'Add your first topic to start generating content automatically.', 'wp-auto-content-pro' ); ?></p>
                    <button id="wpac-add-topic-empty" class="wpac-btn wpac-btn-primary">
                        <?php esc_html_e( 'Add Your First Topic', 'wp-auto-content-pro' ); ?>
                    </button>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="wpac-card">
            <div class="wpac-card-body wpac-p-0">
                <table class="wpac-table wpac-topics-table" id="wpac-topics-table">
                    <thead>
                        <tr>
                            <th class="wpac-col-check"><input type="checkbox" id="wpac-check-all"></th>
                            <th><?php esc_html_e( 'Topic', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Type', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Frequency', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Categories', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Last Generated', 'wp-auto-content-pro' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'wp-auto-content-pro' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $topics as $topic ) : ?>
                        <tr data-id="<?php echo esc_attr( $topic->id ); ?>">
                            <td><input type="checkbox" name="topic_ids[]" value="<?php echo esc_attr( $topic->id ); ?>" class="wpac-topic-checkbox" form="wpac-bulk-form"></td>
                            <td class="wpac-topic-text"><?php echo esc_html( $topic->topic ); ?></td>
                            <td>
                                <span class="wpac-badge <?php echo 'tutorial' === $topic->type ? 'wpac-badge-purple' : 'wpac-badge-info'; ?>">
                                    <?php echo esc_html( ucfirst( $topic->type ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $frequency_labels[ $topic->frequency ] ?? $topic->frequency ); ?></td>
                            <td><?php echo $topic->categories ? esc_html( $topic->categories ) : '<span class="wpac-text-muted">' . esc_html__( 'Auto', 'wp-auto-content-pro' ) . '</span>'; ?></td>
                            <td>
                                <?php if ( 'active' === $topic->status ) : ?>
                                    <span class="wpac-badge wpac-badge-success"><?php esc_html_e( 'Active', 'wp-auto-content-pro' ); ?></span>
                                <?php else : ?>
                                    <span class="wpac-badge wpac-badge-secondary"><?php esc_html_e( 'Paused', 'wp-auto-content-pro' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $topic->last_generated ) : ?>
                                    <span class="wpac-text-sm">
                                        <?php echo esc_html( wp_date( 'M j, Y', strtotime( $topic->last_generated ) ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="wpac-text-muted wpac-text-sm"><?php esc_html_e( 'Never', 'wp-auto-content-pro' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="wpac-row-actions">
                                    <button class="wpac-btn wpac-btn-xs wpac-btn-outline wpac-edit-topic"
                                            data-id="<?php echo esc_attr( $topic->id ); ?>">
                                        <?php esc_html_e( 'Edit', 'wp-auto-content-pro' ); ?>
                                    </button>
                                    <button class="wpac-btn wpac-btn-xs wpac-btn-danger wpac-delete-topic"
                                            data-id="<?php echo esc_attr( $topic->id ); ?>"
                                            data-topic="<?php echo esc_attr( $topic->topic ); ?>">
                                        <?php esc_html_e( 'Delete', 'wp-auto-content-pro' ); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div><!-- .wpac-wrap -->

<!-- Add/Edit Topic Modal -->
<div id="wpac-topic-modal" class="wpac-modal" style="display:none;">
    <div class="wpac-modal-overlay"></div>
    <div class="wpac-modal-dialog">
        <div class="wpac-modal-header">
            <h3 id="wpac-modal-title"><?php esc_html_e( 'Add Topic', 'wp-auto-content-pro' ); ?></h3>
            <button class="wpac-modal-close">&times;</button>
        </div>
        <div class="wpac-modal-body">
            <form id="wpac-topic-form">
                <input type="hidden" id="wpac-topic-id" name="id" value="0">

                <div class="wpac-form-group">
                    <label for="wpac-topic-text"><?php esc_html_e( 'Topic *', 'wp-auto-content-pro' ); ?></label>
                    <input type="text" id="wpac-topic-text" name="topic" class="wpac-form-control" placeholder="<?php esc_attr_e( 'e.g. Best practices for WordPress security', 'wp-auto-content-pro' ); ?>" required>
                    <small><?php esc_html_e( 'Enter the topic or subject for AI to write about.', 'wp-auto-content-pro' ); ?></small>
                </div>

                <div class="wpac-form-row">
                    <div class="wpac-form-group">
                        <label for="wpac-topic-type"><?php esc_html_e( 'Content Type', 'wp-auto-content-pro' ); ?></label>
                        <select id="wpac-topic-type" name="type" class="wpac-form-control">
                            <option value="article"><?php esc_html_e( 'Article', 'wp-auto-content-pro' ); ?></option>
                            <option value="tutorial"><?php esc_html_e( 'Tutorial', 'wp-auto-content-pro' ); ?></option>
                        </select>
                    </div>

                    <div class="wpac-form-group">
                        <label for="wpac-topic-frequency"><?php esc_html_e( 'Frequency', 'wp-auto-content-pro' ); ?></label>
                        <select id="wpac-topic-frequency" name="frequency" class="wpac-form-control">
                            <option value="daily"><?php esc_html_e( 'Daily', 'wp-auto-content-pro' ); ?></option>
                            <option value="hourly"><?php esc_html_e( 'Hourly', 'wp-auto-content-pro' ); ?></option>
                            <option value="every_2h"><?php esc_html_e( 'Every 2 Hours', 'wp-auto-content-pro' ); ?></option>
                            <option value="every_6h"><?php esc_html_e( 'Every 6 Hours', 'wp-auto-content-pro' ); ?></option>
                            <option value="weekly"><?php esc_html_e( 'Weekly', 'wp-auto-content-pro' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="wpac-form-row">
                    <div class="wpac-form-group">
                        <label for="wpac-topic-categories"><?php esc_html_e( 'Category (Optional)', 'wp-auto-content-pro' ); ?></label>
                        <input type="text" id="wpac-topic-categories" name="categories" class="wpac-form-control" placeholder="<?php esc_attr_e( 'e.g. Technology, Tips', 'wp-auto-content-pro' ); ?>">
                        <small><?php esc_html_e( 'Leave blank to let AI decide.', 'wp-auto-content-pro' ); ?></small>
                    </div>

                    <div class="wpac-form-group">
                        <label for="wpac-topic-status"><?php esc_html_e( 'Status', 'wp-auto-content-pro' ); ?></label>
                        <select id="wpac-topic-status" name="status" class="wpac-form-control">
                            <option value="active"><?php esc_html_e( 'Active', 'wp-auto-content-pro' ); ?></option>
                            <option value="paused"><?php esc_html_e( 'Paused', 'wp-auto-content-pro' ); ?></option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="wpac-modal-footer">
            <button type="button" class="wpac-btn wpac-btn-secondary wpac-modal-close"><?php esc_html_e( 'Cancel', 'wp-auto-content-pro' ); ?></button>
            <button type="button" id="wpac-save-topic" class="wpac-btn wpac-btn-primary">
                <?php esc_html_e( 'Save Topic', 'wp-auto-content-pro' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div id="wpac-import-modal" class="wpac-modal" style="display:none;">
    <div class="wpac-modal-overlay"></div>
    <div class="wpac-modal-dialog">
        <div class="wpac-modal-header">
            <h3><?php esc_html_e( 'Import Topics from CSV', 'wp-auto-content-pro' ); ?></h3>
            <button class="wpac-modal-close">&times;</button>
        </div>
        <div class="wpac-modal-body">
            <p><?php esc_html_e( 'CSV format: topic, type, frequency, categories', 'wp-auto-content-pro' ); ?></p>
            <p class="wpac-text-muted"><?php esc_html_e( 'Example: "WordPress Security Tips", "article", "daily", "Security"', 'wp-auto-content-pro' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="wpac-import-form">
                <?php wp_nonce_field( 'wpac_import_topics', 'wpac_import_nonce' ); ?>
                <input type="hidden" name="action" value="wpac_import_topics">
                <div class="wpac-form-group">
                    <label><?php esc_html_e( 'Select CSV File', 'wp-auto-content-pro' ); ?></label>
                    <input type="file" name="topics_csv" accept=".csv" class="wpac-form-control" required>
                </div>
            </form>
        </div>
        <div class="wpac-modal-footer">
            <button class="wpac-btn wpac-btn-secondary wpac-modal-close"><?php esc_html_e( 'Cancel', 'wp-auto-content-pro' ); ?></button>
            <button type="button" id="wpac-do-import" class="wpac-btn wpac-btn-primary"><?php esc_html_e( 'Import', 'wp-auto-content-pro' ); ?></button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Open add topic modal.
    $('#wpac-add-topic, #wpac-add-topic-empty').on('click', function() {
        $('#wpac-modal-title').text('<?php esc_html_e( 'Add Topic', 'wp-auto-content-pro' ); ?>');
        $('#wpac-topic-form')[0].reset();
        $('#wpac-topic-id').val(0);
        $('#wpac-topic-modal').fadeIn(200);
    });

    // Open edit topic modal.
    $(document).on('click', '.wpac-edit-topic', function() {
        var id = $(this).data('id');
        $.post(wpac_ajax.ajax_url, {
            action: 'wpac_get_topic',
            nonce: wpac_ajax.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                var t = response.data;
                $('#wpac-modal-title').text('<?php esc_html_e( 'Edit Topic', 'wp-auto-content-pro' ); ?>');
                $('#wpac-topic-id').val(t.id);
                $('#wpac-topic-text').val(t.topic);
                $('#wpac-topic-type').val(t.type);
                $('#wpac-topic-frequency').val(t.frequency);
                $('#wpac-topic-categories').val(t.categories);
                $('#wpac-topic-status').val(t.status);
                $('#wpac-topic-modal').fadeIn(200);
            }
        });
    });

    // Save topic.
    $('#wpac-save-topic').on('click', function() {
        var data = {
            action: 'wpac_save_topic_ajax',
            nonce: wpac_ajax.nonce,
            id: $('#wpac-topic-id').val(),
            topic: $('#wpac-topic-text').val(),
            type: $('#wpac-topic-type').val(),
            frequency: $('#wpac-topic-frequency').val(),
            categories: $('#wpac-topic-categories').val(),
            status: $('#wpac-topic-status').val()
        };

        if (!data.topic.trim()) {
            wpacShowToast('error', 'Topic text is required.');
            return;
        }

        $(this).text('Saving...').prop('disabled', true);

        $.post(wpac_ajax.ajax_url, data, function(response) {
            $('#wpac-save-topic').text('Save Topic').prop('disabled', false);
            if (response.success) {
                wpacShowToast('success', response.data.message);
                $('#wpac-topic-modal').fadeOut(200);
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                wpacShowToast('error', response.data.message);
            }
        });
    });

    // Delete topic.
    $(document).on('click', '.wpac-delete-topic', function() {
        var id = $(this).data('id');
        var topic = $(this).data('topic');
        if (!confirm(wpac_ajax.strings.confirm_delete + '\n\n"' + topic + '"')) return;

        $.post(wpac_ajax.ajax_url, {
            action: 'wpac_delete_topic_ajax',
            nonce: wpac_ajax.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                $('tr[data-id="' + id + '"]').fadeOut(300, function() { $(this).remove(); });
                wpacShowToast('success', response.data.message);
            } else {
                wpacShowToast('error', response.data.message);
            }
        });
    });

    // Check all.
    $('#wpac-check-all').on('change', function() {
        $('.wpac-topic-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Bulk apply.
    $('#wpac-bulk-apply').on('click', function() {
        var action = $('#wpac-bulk-action').val();
        if (!action) { alert('Please select a bulk action.'); return; }
        if (action === 'delete' && !confirm(wpac_ajax.strings.confirm_delete)) return;
        if ($('.wpac-topic-checkbox:checked').length === 0) { alert('Please select at least one topic.'); return; }
        $('#wpac-bulk-form').submit();
    });

    // Import modal.
    $('#wpac-show-import').on('click', function() {
        $('#wpac-import-modal').fadeIn(200);
    });

    $('#wpac-do-import').on('click', function() {
        $('#wpac-import-form').submit();
    });

    // Close modals.
    $(document).on('click', '.wpac-modal-close, .wpac-modal-overlay', function() {
        $('.wpac-modal').fadeOut(200);
    });
});
</script>
