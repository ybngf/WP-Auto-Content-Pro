/**
 * WP Auto Content Pro - Admin JavaScript
 *
 * Handles all admin interface interactions including AJAX calls,
 * form validation, modal management, and toast notifications.
 *
 * @package WPAutoContentPro
 * @since   1.0.0
 */

/* global wpac_ajax, jQuery */
(function ($) {
    'use strict';

    // =========================================================
    // Toast Notification System
    // =========================================================

    /**
     * Show a toast notification.
     *
     * @param {string} type    Notification type: 'success', 'error', 'warning', 'info'.
     * @param {string} message HTML message to display.
     * @param {number} duration Duration in milliseconds before auto-dismiss (default 5000).
     */
    window.wpacShowToast = function (type, message, duration) {
        duration = duration || 5000;

        var $container = $('#wpac-toast-container');
        if (!$container.length) {
            $container = $('<div id="wpac-toast-container"></div>').appendTo('body');
        }

        var $toast = $(
            '<div class="wpac-toast wpac-toast-' + type + '">' +
                '<span>' + message + '</span>' +
            '</div>'
        );

        $container.append($toast);

        setTimeout(function () {
            $toast.css({ opacity: 0, transform: 'translateX(100%)' });
            setTimeout(function () { $toast.remove(); }, 300);
        }, duration);
    };

    // =========================================================
    // DOM Ready
    // =========================================================

    $(document).ready(function () {

        // =====================================================
        // Tab Switching
        // =====================================================
        $(document).on('click', '.wpac-tab-btn', function () {
            var tab = $(this).data('tab');

            if (!tab) return;

            // Update button states.
            $(this).closest('.wpac-tab-nav').find('.wpac-tab-btn').removeClass('wpac-tab-active');
            $(this).addClass('wpac-tab-active');

            // Update content visibility.
            $('.wpac-tab-content').removeClass('wpac-tab-active').hide();
            $('#tab-' + tab).addClass('wpac-tab-active').show();

            // Persist tab in URL hash without page jump.
            if (history.replaceState) {
                history.replaceState(null, null, '#' + tab);
            }
        });

        // Restore active tab from hash.
        var hash = window.location.hash.replace('#', '');
        if (hash) {
            var $targetBtn = $('[data-tab="' + hash + '"]');
            if ($targetBtn.length) {
                $targetBtn.trigger('click');
            }
        }

        // Show first tab by default if none active.
        if (!$('.wpac-tab-content.wpac-tab-active:visible').length) {
            $('.wpac-tab-content').hide();
            $('.wpac-tab-content.wpac-tab-active').show();
        }

        // =====================================================
        // Provider Card Radio Selection
        // =====================================================
        $(document).on('change', 'input[name="wpac_ai_provider"]', function () {
            $('.wpac-provider-card').removeClass('wpac-provider-selected');
            $(this).closest('.wpac-provider-card').addClass('wpac-provider-selected');
        });

        // =====================================================
        // Posts-Per-Day Slider
        // =====================================================
        $(document).on('input', '#wpac-posts-per-day', function () {
            $('#wpac-ppd-val').text($(this).val());
        });

        // =====================================================
        // Generate Now (Dashboard)
        // =====================================================
        $(document).on('click', '#wpac-generate-now, #wpac-generate-now-2', function () {
            var $overlay = $('#wpac-loading-overlay');
            var $msg     = $('#wpac-loading-message');

            if ($msg.length) $msg.text(wpac_ajax.strings.generating);
            if ($overlay.length) $overlay.fadeIn(200);

            $.ajax({
                url:  wpac_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpac_generate_now',
                    nonce:  wpac_ajax.nonce
                },
                success: function (response) {
                    if ($overlay.length) $overlay.fadeOut(200);

                    if (response.success) {
                        var msg = response.data.message;
                        if (response.data.post_url) {
                            msg += ' <a href="' + response.data.post_url + '" target="_blank" class="wpac-text-link">View Post</a>';
                        }
                        wpacShowToast('success', msg, 8000);

                        // Reload to update activity table after a delay.
                        setTimeout(function () {
                            window.location.reload();
                        }, 3000);
                    } else {
                        wpacShowToast('error', response.data.message);
                    }
                },
                error: function () {
                    if ($overlay.length) $overlay.fadeOut(200);
                    wpacShowToast('error', wpac_ajax.strings.error);
                }
            });
        });

        // =====================================================
        // Test AI Connection
        // =====================================================
        $(document).on('click', '.wpac-test-ai', function () {
            var $btn     = $(this);
            var provider = $btn.data('provider');
            var origText = $btn.text();

            $btn.text(wpac_ajax.strings.testing).prop('disabled', true);

            $.ajax({
                url:  wpac_ajax.ajax_url,
                type: 'POST',
                data: {
                    action:   'wpac_test_ai',
                    nonce:    wpac_ajax.nonce,
                    provider: provider
                },
                success: function (response) {
                    $btn.text(origText).prop('disabled', false);
                    if (response.success) {
                        wpacShowToast('success', response.data.message);
                    } else {
                        wpacShowToast('error', response.data.message);
                    }
                },
                error: function () {
                    $btn.text(origText).prop('disabled', false);
                    wpacShowToast('error', wpac_ajax.strings.error);
                }
            });
        });

        // =====================================================
        // Test Social Media Connection
        // =====================================================
        $(document).on('click', '.wpac-test-social', function () {
            var $btn     = $(this);
            var platform = $btn.data('platform');
            var origText = $btn.html();

            $btn.text(wpac_ajax.strings.testing).prop('disabled', true);

            $.ajax({
                url:  wpac_ajax.ajax_url,
                type: 'POST',
                data: {
                    action:   'wpac_test_social',
                    nonce:    wpac_ajax.nonce,
                    platform: platform
                },
                success: function (response) {
                    $btn.html(origText).prop('disabled', false);
                    if (response.success) {
                        wpacShowToast('success', response.data.message);
                    } else {
                        wpacShowToast('error', response.data.message);
                    }
                },
                error: function () {
                    $btn.html(origText).prop('disabled', false);
                    wpacShowToast('error', wpac_ajax.strings.error);
                }
            });
        });

        // =====================================================
        // AJAX Save Settings
        // =====================================================
        $(document).on('click', '#wpac-save-ajax', function () {
            var $btn     = $(this);
            var origHtml = $btn.html();

            $btn.text(wpac_ajax.strings.saving).prop('disabled', true);

            var $form    = $('#wpac-settings-form');
            var formData = $form.serialize();

            $.ajax({
                url:  wpac_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=wpac_save_settings_ajax&nonce=' + encodeURIComponent(wpac_ajax.nonce),
                success: function (response) {
                    $btn.html(origHtml).prop('disabled', false);
                    if (response.success) {
                        wpacShowToast('success', response.data.message);
                    } else {
                        wpacShowToast('error', response.data.message);
                    }
                },
                error: function () {
                    $btn.html(origHtml).prop('disabled', false);
                    wpacShowToast('error', wpac_ajax.strings.error);
                }
            });
        });

        // =====================================================
        // Topic Modal - Add
        // =====================================================
        $(document).on('click', '#wpac-add-topic, #wpac-add-topic-empty', function () {
            resetTopicForm();
            $('#wpac-modal-title').text(wpac_ajax.strings.add_topic || 'Add Topic');
            $('#wpac-topic-modal').fadeIn(200);
        });

        // =====================================================
        // Topic Modal - Edit
        // =====================================================
        $(document).on('click', '.wpac-edit-topic', function () {
            var id = $(this).data('id');

            $.ajax({
                url:  wpac_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpac_get_topic',
                    nonce:  wpac_ajax.nonce,
                    id:     id
                },
                success: function (response) {
                    if (response.success) {
                        var t = response.data;
                        resetTopicForm();
                        $('#wpac-modal-title').text(wpac_ajax.strings.edit_topic || 'Edit Topic');
                        $('#wpac-topic-id').val(t.id);
                        $('#wpac-topic-text').val(t.topic);
                        $('#wpac-topic-type').val(t.type);
                        $('#wpac-topic-frequency').val(t.frequency);
                        $('#wpac-topic-categories').val(t.categories);
                        $('#wpac-topic-status').val(t.status);
                        $('#wpac-topic-modal').fadeIn(200);
                    } else {
                        wpacShowToast('error', response.data.message);
                    }
                },
                error: function () {
                    wpacShowToast('error', wpac_ajax.strings.error);
                }
            });
        });

        // =====================================================
        // Topic Modal - Save
        // =====================================================
        $(document).on('click', '#wpac-save-topic', function () {
            var $btn    = $(this);
            var topic   = $('#wpac-topic-text').val().trim();

            if (!topic) {
                wpacShowToast('error', 'Topic text is required.');
                $('#wpac-topic-text').focus();
                return;
            }

            var origText = $btn.text();
            $btn.text(wpac_ajax.strings.saving).prop('disabled', true);

            $.ajax({
                url:  wpac_ajax.ajax_url,
                type: 'POST',
                data: {
                    action:     'wpac_save_topic_ajax',
                    nonce:      wpac_ajax.nonce,
                    id:         $('#wpac-topic-id').val(),
                    topic:      topic,
                    type:       $('#wpac-topic-type').val(),
                    frequency:  $('#wpac-topic-frequency').val(),
                    categories: $('#wpac-topic-categories').val(),
                    status:     $('#wpac-topic-status').val()
                },
                success: function (response) {
                    $btn.text(origText).prop('disabled', false);
                    if (response.success) {
                        wpacShowToast('success', response.data.message);
                        $('#wpac-topic-modal').fadeOut(200);
                        setTimeout(function () { window.location.reload(); }, 1200);
                    } else {
                        wpacShowToast('error', response.data.message);
                    }
                },
                error: function () {
                    $btn.text(origText).prop('disabled', false);
                    wpacShowToast('error', wpac_ajax.strings.error);
                }
            });
        });

        // =====================================================
        // Topic Delete
        // =====================================================
        $(document).on('click', '.wpac-delete-topic', function () {
            var id    = $(this).data('id');
            var topic = $(this).data('topic') || 'this topic';

            if (!confirm(wpac_ajax.strings.confirm_delete + '\n\n"' + topic + '"')) {
                return;
            }

            var $row = $(this).closest('tr');

            $.ajax({
                url:  wpac_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpac_delete_topic_ajax',
                    nonce:  wpac_ajax.nonce,
                    id:     id
                },
                success: function (response) {
                    if (response.success) {
                        $row.fadeOut(300, function () { $(this).remove(); });
                        wpacShowToast('success', response.data.message);
                    } else {
                        wpacShowToast('error', response.data.message);
                    }
                },
                error: function () {
                    wpacShowToast('error', wpac_ajax.strings.error);
                }
            });
        });

        // =====================================================
        // Bulk Actions
        // =====================================================
        $(document).on('click', '#wpac-bulk-apply', function () {
            var action    = $('#wpac-bulk-action').val();
            var $checked  = $('.wpac-topic-checkbox:checked');

            if (!action) {
                wpacShowToast('warning', 'Please select a bulk action.');
                return;
            }

            if ($checked.length === 0) {
                wpacShowToast('warning', 'Please select at least one topic.');
                return;
            }

            if ('delete' === action && !confirm(wpac_ajax.strings.confirm_delete)) {
                return;
            }

            $('#wpac-bulk-form').submit();
        });

        // =====================================================
        // Check All Checkbox
        // =====================================================
        $(document).on('change', '#wpac-check-all', function () {
            $('.wpac-topic-checkbox').prop('checked', $(this).is(':checked'));
        });

        // Individual checkbox state management.
        $(document).on('change', '.wpac-topic-checkbox', function () {
            var total   = $('.wpac-topic-checkbox').length;
            var checked = $('.wpac-topic-checkbox:checked').length;
            $('#wpac-check-all').prop({
                checked:       checked === total,
                indeterminate: checked > 0 && checked < total
            });
        });

        // =====================================================
        // Import CSV Modal
        // =====================================================
        $(document).on('click', '#wpac-show-import', function () {
            $('#wpac-import-modal').fadeIn(200);
        });

        $(document).on('click', '#wpac-do-import', function () {
            $('#wpac-import-form').submit();
        });

        // =====================================================
        // Error Detail Modal
        // =====================================================
        $(document).on('click', '.wpac-show-error', function () {
            var error = $(this).data('error');
            $('#wpac-error-text').text(error);
            $('#wpac-error-modal').fadeIn(200);
        });

        // =====================================================
        // Modal Close
        // =====================================================
        $(document).on('click', '.wpac-modal-close, .wpac-modal-overlay', function () {
            $(this).closest('.wpac-modal').fadeOut(200);
        });

        // Close modal on Escape key.
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('.wpac-modal:visible').fadeOut(200);
            }
        });

        // =====================================================
        // Settings Form Validation
        // =====================================================
        $('#wpac-settings-form').on('submit', function (e) {
            var $form = $(this);
            var valid = true;

            // Clear previous error states.
            $form.find('.wpac-form-control.is-error').removeClass('is-error');

            // Validate posting window.
            var fromHour = parseInt($('select[name="wpac_posting_time_from"]').val(), 10);
            var toHour   = parseInt($('select[name="wpac_posting_time_to"]').val(), 10);

            if (fromHour >= toHour) {
                wpacShowToast('error', 'Posting end time must be later than start time.');
                $('select[name="wpac_posting_time_from"]').addClass('is-error');
                $('select[name="wpac_posting_time_to"]').addClass('is-error');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                // Scroll to first error.
                var $firstError = $form.find('.is-error').first();
                if ($firstError.length) {
                    $('html, body').animate({ scrollTop: $firstError.offset().top - 100 }, 300);
                }
            }
        });

        // =====================================================
        // Image settings toggle.
        // =====================================================
        function toggleImageSettings() {
            if ($('#wpac-include-images').is(':checked')) {
                $('#wpac-image-settings').slideDown(200);
            } else {
                $('#wpac-image-settings').slideUp(200);
            }
        }

        $(document).on('change', '#wpac-include-images', toggleImageSettings);
        toggleImageSettings();

        // =====================================================
        // Auto-dismiss admin notices.
        // =====================================================
        setTimeout(function () {
            $('.wpac-notice').fadeOut(500);
        }, 5000);

        // =====================================================
        // Password field reveal toggle.
        // =====================================================
        $(document).on('dblclick', 'input[type="password"].wpac-form-control', function () {
            var $input = $(this);
            var type   = $input.attr('type') === 'password' ? 'text' : 'password';
            $input.attr('type', type);

            // Revert after 2 seconds.
            if ('text' === type) {
                setTimeout(function () {
                    $input.attr('type', 'password');
                }, 2000);
            }
        });

        // =====================================================
        // Character counter for text inputs with limits.
        // =====================================================
        $(document).on('input', 'textarea[maxlength]', function () {
            var max     = parseInt($(this).attr('maxlength'), 10);
            var current = $(this).val().length;
            var $counter = $(this).siblings('.wpac-char-counter');

            if (!$counter.length) {
                $counter = $('<small class="wpac-char-counter wpac-text-muted"></small>');
                $(this).after($counter);
            }

            $counter.text(current + ' / ' + max + ' characters');

            if (current > max * 0.9) {
                $counter.css('color', 'var(--wpac-warning)');
            } else {
                $counter.css('color', '');
            }
        });

    }); // end document.ready

    // =========================================================
    // Helper Functions
    // =========================================================

    /**
     * Reset the topic form to its default empty state.
     */
    function resetTopicForm() {
        var $form = $('#wpac-topic-form');
        if ($form.length) {
            $form[0].reset();
        }
        $('#wpac-topic-id').val(0);
        $('#wpac-topic-text').val('');
        $('#wpac-topic-type').val('article');
        $('#wpac-topic-frequency').val('daily');
        $('#wpac-topic-categories').val('');
        $('#wpac-topic-status').val('active');
    }

    /**
     * Serialize a form including unchecked checkboxes.
     *
     * @param  {jQuery} $form jQuery form object.
     * @return {string} Serialized form data string.
     */
    function serializeFormAll($form) {
        var data = $form.serializeArray();

        // Ensure all checkboxes have a value even if unchecked.
        $form.find('input[type="checkbox"]').each(function () {
            var name  = $(this).attr('name');
            var found = false;

            for (var i = 0; i < data.length; i++) {
                if (data[i].name === name) {
                    found = true;
                    break;
                }
            }

            if (!found) {
                data.push({ name: name, value: '0' });
            }
        });

        return $.param(data);
    }

}(jQuery));
