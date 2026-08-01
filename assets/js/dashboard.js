/**
 * Newsblenda Accounts Dashboard
 */

(function ($) {
	'use strict';

	/*
	|--------------------------------------------------------------------------
	| Workflow Actions (Editor & Author)
	|--------------------------------------------------------------------------
	*/

	const Workflow = {

		/**
		 * Active post ID for modal-based actions.
		 */
		activePostId: 0,

		/**
		 * Initialise workflow action handlers.
		 */
		init: function () {

			this.bindApprove();
			this.bindReject();
			this.bindRevision();
			this.bindPublish();
			this.bindResubmit();
			this.bindDeleteDraft();
			this.bindModals();

		},

		/**
		 * AJAX helper.
		 */
		request: function (action, data, callback) {

			if (!window.NBAccounts) {
				return;
			}

			$.ajax({
				url: NBAccounts.ajax_url,
				type: 'POST',
				data: $.extend({ action: action }, data),
				success: function (res) {
					callback(null, res);
				},
				error: function (xhr, status, error) {
					callback(error, null);
				}
			});

		},

		/**
		 * Show an inline notice after a table row.
		 */
		rowNotice: function ($btn, message, type) {

			const $row = $btn.closest('tr');

			$row.find('.nba-action-notice').remove();

			const $notice = $(
				'<td colspan="20" class="nba-action-notice nba-notice nba-notice-' + type + '">' +
				$('<span>').text(message).html() +
				'</td>'
			);

			$row.after('<tr class="nba-notice-row">' + $notice.prop('outerHTML') + '</tr>');

		},

		/**
		 * Approve article.
		 */
		bindApprove: function () {

			const self = this;

			$(document).on('click', '.nba-approve-btn', function () {

				const $btn    = $(this);
				const postId  = parseInt($btn.data('post-id'), 10);
				const title   = $btn.data('title') || '';

				if (!postId) { return; }

				if (!window.confirm(
					(NBAccounts.i18n && NBAccounts.i18n.confirmApprove)
						? NBAccounts.i18n.confirmApprove
						: 'Approve "' + title + '"?'
				)) {
					return;
				}

				$btn.addClass('is-loading').prop('disabled', true);

				self.request(
					'nb_approve_article',
					{
						post_id: postId,
						nonce: NBAccounts.editor_nonce || ''
					},
					function (err, res) {

						$btn.removeClass('is-loading').prop('disabled', false);

						if (err || !res || !res.success) {
							const msg = res && res.data && res.data.message
								? res.data.message
								: 'An error occurred.';
							self.rowNotice($btn, msg, 'error');
							return;
						}

						const $row = $btn.closest('tr');
						$row.addClass('nba-row-removed');
						setTimeout(function () { $row.remove(); }, 320);

					}
				);

			});

		},

		/**
		 * Open reject modal and bind confirm.
		 */
		bindReject: function () {

			const self = this;

			$(document).on('click', '.nba-reject-btn', function () {

				const $btn   = $(this);
				const postId = parseInt($btn.data('post-id'), 10);
				const title  = $btn.data('title') || '';

				if (!postId) { return; }

				self.activePostId = postId;

				$('#nba-modal-reject-title').text(title);
				$('#nba-reject-reason').val('');
				$('#nba-modal-reject').attr('aria-hidden', 'false').fadeIn(200);
				$('body').addClass('nba-modal-open').css('overflow', 'hidden');

			});

			$(document).on('click', '#nba-reject-confirm', function () {

				const postId = self.activePostId;
				const reason = $('#nba-reject-reason').val().trim();
				const $btn   = $(this);

				if (!postId) { return; }

				$btn.addClass('is-loading').prop('disabled', true);

				self.request(
					'nb_reject_article',
					{
						post_id: postId,
						reason:  reason,
						nonce:   NBAccounts.editor_nonce || ''
					},
					function (err, res) {

						$btn.removeClass('is-loading').prop('disabled', false);

						if (err || !res || !res.success) {
							const msg = res && res.data && res.data.message
								? res.data.message
								: 'An error occurred.';
							alert(msg);
							return;
						}

						self.closeModals();

						const $row = $('[data-post-id="' + postId + '"]').first().closest('tr');
						$row.addClass('nba-row-removed');
						setTimeout(function () { $row.remove(); }, 320);

					}
				);

			});

		},

		/**
		 * Open revision modal and bind confirm.
		 */
		bindRevision: function () {

			const self = this;

			$(document).on('click', '.nba-revision-btn', function () {

				const $btn   = $(this);
				const postId = parseInt($btn.data('post-id'), 10);
				const title  = $btn.data('title') || '';

				if (!postId) { return; }

				self.activePostId = postId;

				$('#nba-modal-revision-title').text(title);
				$('#nba-revision-feedback').val('');
				$('#nba-modal-revision').attr('aria-hidden', 'false').fadeIn(200);
				$('body').addClass('nba-modal-open').css('overflow', 'hidden');

			});

			$(document).on('click', '#nba-revision-confirm', function () {

				const postId   = self.activePostId;
				const feedback = $('#nba-revision-feedback').val().trim();
				const $btn     = $(this);

				if (!postId) { return; }

				if (feedback === '') {
					$('#nba-revision-feedback').focus();
					return;
				}

				$btn.addClass('is-loading').prop('disabled', true);

				self.request(
					'nb_request_revision',
					{
						post_id:  postId,
						feedback: feedback,
						nonce:    NBAccounts.editor_nonce || ''
					},
					function (err, res) {

						$btn.removeClass('is-loading').prop('disabled', false);

						if (err || !res || !res.success) {
							const msg = res && res.data && res.data.message
								? res.data.message
								: 'An error occurred.';
							alert(msg);
							return;
						}

						self.closeModals();

						const $row = $('[data-post-id="' + postId + '"]').first().closest('tr');
						$row.addClass('nba-row-removed');
						setTimeout(function () { $row.remove(); }, 320);

					}
				);

			});

		},

		/**
		 * Publish an approved article.
		 */
		bindPublish: function () {

			const self = this;

			$(document).on('click', '.nba-publish-btn', function () {

				const $btn   = $(this);
				const postId = parseInt($btn.data('post-id'), 10);
				const title  = $btn.data('title') || '';

				if (!postId) { return; }

				if (!window.confirm(
					(NBAccounts.i18n && NBAccounts.i18n.confirmPublish)
						? NBAccounts.i18n.confirmPublish
						: 'Publish "' + title + '" now?'
				)) {
					return;
				}

				$btn.addClass('is-loading').prop('disabled', true);

				self.request(
					'nb_publish_article',
					{
						post_id: postId,
						nonce:   NBAccounts.editor_nonce || ''
					},
					function (err, res) {

						$btn.removeClass('is-loading').prop('disabled', false);

						if (err || !res || !res.success) {
							const msg = res && res.data && res.data.message
								? res.data.message
								: 'An error occurred.';
							self.rowNotice($btn, msg, 'error');
							return;
						}

						const $row = $btn.closest('tr');
						$row.addClass('nba-row-removed');
						setTimeout(function () { $row.remove(); }, 320);

					}
				);

			});

		},

		/**
		 * Author resubmits an article.
		 */
		bindResubmit: function () {

			const self = this;

			$(document).on('click', '.nba-resubmit-btn', function () {

				const $btn   = $(this);
				const postId = parseInt($btn.data('post-id'), 10);

				if (!postId) { return; }

				if (!window.confirm(
					(NBAccounts.i18n && NBAccounts.i18n.confirmResubmit)
						? NBAccounts.i18n.confirmResubmit
						: 'Resubmit this article for review?'
				)) {
					return;
				}

				$btn.addClass('is-loading').prop('disabled', true);

				self.request(
					'nb_resubmit_article',
					{
						post_id: postId,
						nonce:   NBAccounts.author_nonce || ''
					},
					function (err, res) {

						$btn.removeClass('is-loading').prop('disabled', false);

						if (err || !res || !res.success) {
							const msg = res && res.data && res.data.message
								? res.data.message
								: 'An error occurred.';
							self.rowNotice($btn, msg, 'error');
							return;
						}

						const $row = $btn.closest('tr');
						$row.addClass('nba-row-removed');
						setTimeout(function () {
							$row.remove();
						}, 320);

					}
				);

			});

		},

		/**
		 * Author deletes a draft.
		 */
		bindDeleteDraft: function () {

			const self = this;

			$(document).on('click', '.nba-delete-draft-btn', function () {

				const $btn   = $(this);
				const postId = parseInt($btn.data('post-id'), 10);

				if (!postId) { return; }

				if (!window.confirm(
					(NBAccounts.i18n && NBAccounts.i18n.confirmDelete)
						? NBAccounts.i18n.confirmDelete
						: 'Permanently delete this draft?'
				)) {
					return;
				}

				$btn.addClass('is-loading').prop('disabled', true);

				self.request(
					'nb_delete_draft',
					{
						post_id: postId,
						nonce:   NBAccounts.author_nonce || ''
					},
					function (err, res) {

						$btn.removeClass('is-loading').prop('disabled', false);

						if (err || !res || !res.success) {
							const msg = res && res.data && res.data.message
								? res.data.message
								: 'An error occurred.';
							self.rowNotice($btn, msg, 'error');
							return;
						}

						const $row = $btn.closest('tr');
						$row.addClass('nba-row-removed');
						setTimeout(function () { $row.remove(); }, 320);

					}
				);

			});

		},

		/**
		 * Bind modal close buttons and overlay clicks.
		 */
		bindModals: function () {

			const self = this;

			$(document).on('click', '.nba-modal-close', function () {
				self.closeModals();
			});

			$(document).on('click', '.nba-modal', function (e) {
				if ($(e.target).hasClass('nba-modal')) {
					self.closeModals();
				}
			});

			$(document).on('keydown', function (e) {
				if (e.key === 'Escape') {
					self.closeModals();
				}
			});

		},

		/**
		 * Close all modals.
		 */
		closeModals: function () {

			$('.nba-modal').attr('aria-hidden', 'true').fadeOut(200);
			$('body').removeClass('nba-modal-open').css('overflow', '');
			this.activePostId = 0;

		}

	};

	/*
	|--------------------------------------------------------------------------
	| General Dashboard UI
	|--------------------------------------------------------------------------
	*/

	const Dashboard = {

		refreshTimer: null,

		init: function () {

			this.navigation();
			this.tabs();
			this.refreshCards();
			this.tooltips();
			this.cardActions();
			this.tableSearch();
			this.loadingButtons();
			this.sidebarToggle();

		},

		navigation: function () {

			$(document).on(
				'click',
				'.nba-dashboard-menu a',
				function () {

					$('.nba-dashboard-menu a').removeClass('active');

					$(this).addClass('active');

				}
			);

		},

		tabs: function () {

			$(document).on(
				'click',
				'[data-dashboard-tab]',
				function (e) {

					e.preventDefault();

					const target = $(this).data('dashboard-tab');

					$('[data-dashboard-tab]').removeClass('active');
					$(this).addClass('active');

					$('.nba-dashboard-tab')
						.removeClass('active')
						.hide();

					$(target)
						.addClass('active')
						.fadeIn(150);

				}
			);

		},

		refreshCards: function () {

			const refresh = parseInt(
				$('.nba-dashboard').data('refresh'),
				10
			);

			if (!refresh || refresh <= 0) {
				return;
			}

			if (this.refreshTimer) {
				clearInterval(this.refreshTimer);
			}

			this.refreshTimer = setInterval(function () {

				$(document).trigger('nba_dashboard_refresh');

			}, refresh * 1000);

		},

		tooltips: function () {

			$('[data-tooltip]').each(function () {

				$(this).attr(
					'title',
					$(this).data('tooltip')
				);

			});

		},

		cardActions: function () {

			$(document).on(
				'click',
				'.nba-card-toggle',
				function (e) {

					e.preventDefault();

					const card = $(this).closest('.nba-card');

					card
						.find('.nba-card-body')
						.stop(true, true)
						.slideToggle(200);

					$(this).toggleClass('open');

				}
			);

		},

		tableSearch: function () {

			$(document).on(
				'keyup',
				'.nba-table-search',
				function () {

					const value = $(this)
						.val()
						.toLowerCase();

					$('.nba-table tbody tr').each(function () {

						$(this).toggle(
							$(this)
								.text()
								.toLowerCase()
								.indexOf(value) > -1
						);

					});

				}
			);

		},

		loadingButtons: function () {

			$(document).on(
				'click',
				'.nba-loading-button',
				function () {

					$(this)
						.prop('disabled', true)
						.addClass('nba-loading');

				}
			);

		},

		sidebarToggle: function () {

			$(document).on(
				'click',
				'.nba-dashboard-toggle',
				function (e) {

					e.preventDefault();

					$('.nba-dashboard-sidebar')
						.toggleClass('is-open');

				}
			);

		}

	};

	$(function () {

		Dashboard.init();
		Workflow.init();

	});

})(jQuery);