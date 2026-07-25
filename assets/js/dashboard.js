/**
 * Newsblenda Dashboard JS - Phase 3
 */
(function($) {
    'use strict';

    function workflowNonce() {
        return (window.NBAccounts && (NBAccounts.workflow_nonce || NBAccounts.nonce)) || '';
    }

    function openModal(modalId) {
        $('#' + modalId).addClass('active');
        $('body').addClass('nba-modal-open');
    }

    function closeModal(modalId) {
        $('#' + modalId).removeClass('active');
        $('body').removeClass('nba-modal-open');
    }

    $(document).on('click', '[data-nba-modal]', function(e) {
        e.preventDefault();
        var modalId = $(this).data('nba-modal');
        var postId = $(this).data('post-id');
        $('#' + modalId).find('[name="post_id"]').val(postId);
        openModal(modalId);
    });

    $(document).on('click', '.nba-modal-close, .nba-modal-cancel', function(e) {
        e.preventDefault();
        closeModal($(this).closest('.nba-modal-overlay').attr('id'));
    });

    $(document).on('click', '.nba-modal-overlay', function(e) {
        if ($(e.target).is('.nba-modal-overlay')) {
            closeModal($(this).attr('id'));
        }
    });

    function showToast(message, type) {
        type = type || 'info';
        var $toast = $('<div class="nba-toast nba-toast-' + type + '"></div>').text(message);
        $('body').append($toast);
        setTimeout(function() { $toast.addClass('show'); }, 10);
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() { $toast.remove(); }, 300);
        }, 4000);
    }

    function workflowAction(action, data, $btn) {
        var originalText = $btn.text();
        $btn.prop('disabled', true).text((NBAccounts.i18n && NBAccounts.i18n.processing) || 'Processing...');

        $.post(NBAccounts.ajax_url, $.extend({
            action: 'nb_' + action,
            nonce: workflowNonce(),
            security: workflowNonce()
        }, data), function(response) {
            if (response.success) {
                showToast((response.data && response.data.message) || 'Done!', 'success');
                setTimeout(function() { window.location.reload(); }, 1200);
            } else {
                showToast((response.data && response.data.message) || 'An error occurred.', 'error');
                $btn.prop('disabled', false).text(originalText);
            }
        }).fail(function(xhr) {
            var message = 'Request failed. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                message = xhr.responseJSON.data.message;
            }
            showToast(message, 'error');
            $btn.prop('disabled', false).text(originalText);
        });
    }

    $(document).on('submit', '#nba-approve-form', function(e) {
        e.preventDefault();
        var publishNow = $(this).find('[name="publish_now"]').val();
        var data = {
            post_id: $(this).find('[name="post_id"]').val(),
            publish_now: publishNow,
            scheduled_at: $(this).find('[name="scheduled_at"]').val()
        };
        workflowAction('approve_article', data, $(this).find('[type="submit"]'));
    });

    $(document).on('submit', '#nba-reject-form', function(e) {
        e.preventDefault();
        var data = {
            post_id: $(this).find('[name="post_id"]').val(),
            reason: $(this).find('[name="reason"]').val(),
            comments: $(this).find('[name="comments"]').val()
        };
        workflowAction('reject_article', data, $(this).find('[type="submit"]'));
    });

    $(document).on('submit', '#nba-revision-form', function(e) {
        e.preventDefault();
        var data = {
            post_id: $(this).find('[name="post_id"]').val(),
            feedback: $(this).find('[name="feedback"]').val(),
            severity: $(this).find('[name="severity"]').val()
        };
        workflowAction('request_revision', data, $(this).find('[type="submit"]'));
    });

    var autosaveTimer = null;
    var currentDraftId = 0;

    function editorContent(htmlMode) {
        var content = '';
        if (typeof tinyMCE !== 'undefined') {
            var editor = tinyMCE.get('nb_article_content');
            if (editor) {
                content = htmlMode ? editor.getContent() : editor.getContent({ format: 'text' });
            }
        }
        if (!content) {
            content = $('#nb_article_content').val() || '';
        }
        return content;
    }

    function autosave() {
        var $form = $('#nba-submit-form');
        if (!$form.length) {
            return;
        }

        var $status = $('.nba-autosave-status');
        $status.removeClass('saved error').addClass('saving').text('Saving...');

        $.post(NBAccounts.ajax_url, {
            action: 'nb_autosave_article',
            security: workflowNonce(),
            post_id: currentDraftId,
            post_title: $('#post_title').val(),
            article_content: editorContent(true),
            seo_title: $('#seo_title').val(),
            meta_description: $('#meta_description').val(),
            sources: $('#sources').val(),
            content_type: $('#content_type').val()
        }, function(response) {
            if (response.success) {
                currentDraftId = (response.data && response.data.post_id) || currentDraftId;
                $('#nba-submit-form').find('[name="post_id"]').val(currentDraftId);
                $status.removeClass('saving error').addClass('saved').text('Saved ✓');
            } else {
                $status.removeClass('saving saved').addClass('error').text('Save failed');
            }
        }).fail(function() {
            $status.removeClass('saving saved').addClass('error').text('Save failed');
        });
    }

    $(document).on('input', '#post_title, #seo_title, #meta_description, #sources', function() {
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(autosave, 30000);
    });

    function countWords(text) {
        var stripped = (text || '').trim();
        return stripped === '' ? 0 : stripped.split(/\s+/).length;
    }

    function updateWordCount() {
        var words = countWords(editorContent(false));
        var $counter = $('.nba-word-counter');
        $counter.text(words + ' words');
        $counter.toggleClass('under-min', words < 300);
    }

    if (typeof tinyMCE !== 'undefined') {
        $(document).on('tinymce-editor-init', function(event, editor) {
            if (editor.id === 'nb_article_content') {
                editor.on('input keyup change', function() {
                    updateWordCount();
                    clearTimeout(autosaveTimer);
                    autosaveTimer = setTimeout(autosave, 30000);
                });
            }
        });
    }

    function updateCheckItem(id, pass) {
        var $item = $('#' + id);
        if (!$item.length) {
            return;
        }
        $item.find('.nba-check-icon').toggleClass('pass', pass).toggleClass('fail', !pass).text(pass ? '✓' : '✗');
    }

    function updateChecklist() {
        var title = $('#post_title').val() || '';
        var category = $('#category').val();
        var meta = $('#meta_description').val() || '';
        updateCheckItem('check-title', title.length >= 5);
        updateCheckItem('check-category', !!category);
        updateCheckItem('check-meta', meta.length >= 20);
    }

    $(document).on('input change', '#post_title, #category, #meta_description', updateChecklist);

    function addCharCounter($input, max) {
        if (!$input.length) {
            return;
        }
        var $counter = $('<span class="nba-char-counter"></span>');
        $input.after($counter);
        $input.on('input', function() {
            var len = ($(this).val() || '').length;
            $counter.text(len + '/' + max);
            $counter.toggleClass('nba-over-limit', len > max);
        }).trigger('input');
    }

    $(document).ready(function() {
        currentDraftId = parseInt($('#nba-submit-form').find('[name="post_id"]').val(), 10) || 0;
        addCharCounter($('#post_title'), 200);
        addCharCounter($('#seo_title'), 60);
        addCharCounter($('#meta_description'), 160);
        updateChecklist();
        updateWordCount();
    });
}(jQuery));
