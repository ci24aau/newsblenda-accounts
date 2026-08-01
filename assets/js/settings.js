/**
 * Newsblenda Accounts — Settings
 */

(function ($) {
	'use strict';

	const Settings = {

		init: function () {
			this.colorPickers();
			this.smtpDependencies();
			this.numberValidation();
			this.trackChanges();
			this.sendTestEmail();
			this.smtpTest();
			this.resetSection();
			this.preventDoubleSubmit();
			this.highlightActiveTab();
		},

		/**
		 * Initialise WordPress colour pickers.
		 */
		colorPickers: function () {

			if ($.fn.wpColorPicker) {

				$('.nba-color-picker').wpColorPicker();

			}

		},

		/**
		 * Show/hide SMTP fields based on the "Enable SMTP" checkbox.
		 */
		smtpDependencies: function () {

			const $smtp    = $('#nba_smtp_enabled');
			const $fields  = $('.nba-smtp-fields');

			if (!$smtp.length || !$fields.length) {
				return;
			}

			const toggle = function () {

				if ($smtp.is(':checked')) {
					$fields.slideDown(200);
				} else {
					$fields.slideUp(200);
				}

			};

			toggle();

			$smtp.on('change', toggle);

		},

		/**
		 * Clamp number inputs to their min/max.
		 */
		numberValidation: function () {

			$(document).on('input', 'input[type="number"]', function () {

				const min   = parseFloat($(this).attr('min'));
				const max   = parseFloat($(this).attr('max'));
				let   value = parseFloat($(this).val());

				if (isNaN(value)) {
					return;
				}

				if (!isNaN(min) && value < min) {
					$(this).val(min);
				}

				if (!isNaN(max) && value > max) {
					$(this).val(max);
				}

			});

		},

		/**
		 * Mark rows as dirty when values change.
		 */
		trackChanges: function () {

			$('.nba-settings-form :input').on('change input', function () {

				$(this)
					.closest('tr, .nba-form-group')
					.addClass('nba-dirty');

			});

		},

		/**
		 * Send a test email via AJAX.
		 */
		sendTestEmail: function () {

			$(document).on('click', '.nba-send-test-email', function (e) {

				e.preventDefault();

				const $btn    = $(this);
				const $input  = $btn.prev('input[type="email"]');
				const $result = $btn.next('.nba-test-email-result');
				const email   = $input.val().trim();
				const nonce   = $btn.data('nonce') || (window.nbaSettings && window.nbaSettings.testEmailNonce);

				if (!email) {
					$result
						.text(
							window.nbaSettings
								? window.nbaSettings.i18n.sending
								: 'Please enter an email address.'
						)
						.css('color', '#d63638')
						.show();
					return;
				}

				$btn.prop('disabled', true).text(
					window.nbaSettings ? window.nbaSettings.i18n.sending : 'Sending…'
				);

				$result.hide().text('');

				$.post(
					window.nbaSettings ? window.nbaSettings.ajaxUrl : ajaxurl,
					{
						action : 'nba_send_test_email',
						nonce  : nonce,
						email  : email,
					},
					function (response) {

						$btn.prop('disabled', false).text('Send Test Email');

						if (response.success) {
							$result
								.text(response.data.message)
								.css('color', '#46b450')
								.show();
						} else {
							$result
								.text(response.data.message)
								.css('color', '#d63638')
								.show();
						}

					}
				).fail(function () {

					$btn.prop('disabled', false).text('Send Test Email');

					$result
						.text('Request failed. Check your network connection.')
						.css('color', '#d63638')
						.show();

				});

			});

		},

		/**
		 * SMTP connection test.
		 */
		smtpTest: function () {

			$(document).on('click', '.nba-smtp-test', function (e) {

				e.preventDefault();

				const $btn        = $(this);
				const $result     = $('#nba-smtp-test-result');
				const host        = $('#nba_smtp_host').val().trim();
				const port        = $('#nba_smtp_port').val();
				const encryption  = $('#nba_smtp_encryption').val();
				const nonce       = window.nbaSettings ? window.nbaSettings.smtpTestNonce : '';

				if (!host) {
					$result.text('Please enter an SMTP host.').css('color', '#d63638').show();
					return;
				}

				$btn.prop('disabled', true).text(
					window.nbaSettings ? window.nbaSettings.i18n.testing : 'Testing…'
				);

				$result.hide().text('');

				$.post(
					window.nbaSettings ? window.nbaSettings.ajaxUrl : ajaxurl,
					{
						action     : 'nba_smtp_test',
						nonce      : nonce,
						host       : host,
						port       : port,
						encryption : encryption,
					},
					function (response) {

						$btn.prop('disabled', false).text('Test Connection');

						if (response.success) {
							$result
								.text(response.data.message)
								.css('color', '#46b450')
								.show();
						} else {
							$result
								.text(response.data.message)
								.css('color', '#d63638')
								.show();
						}

					}
				).fail(function () {

					$btn.prop('disabled', false).text('Test Connection');

					$result
						.text('Request failed.')
						.css('color', '#d63638')
						.show();

				});

			});

		},

		/**
		 * Reset a settings section to defaults.
		 */
		resetSection: function () {

			$(document).on('click', '.nba-reset-section', function (e) {

				e.preventDefault();

				const confirmMsg = window.nbaSettings
					? window.nbaSettings.i18n.confirmReset
					: 'Reset all settings in this tab to their default values? This cannot be undone.';

				if (!window.confirm(confirmMsg)) {
					return;
				}

				const $btn    = $(this);
				const section = $btn.data('section');
				const nonce   = $btn.data('nonce');

				$btn.prop('disabled', true);

				$.post(
					window.nbaSettings ? window.nbaSettings.ajaxUrl : ajaxurl,
					{
						action  : 'nba_reset_settings',
						nonce   : nonce,
						section : section,
					},
					function (response) {

						$btn.prop('disabled', false);

						if (response.success) {
							window.location.reload();
						} else {
							alert(response.data.message || 'Reset failed.');
						}

					}
				).fail(function () {

					$btn.prop('disabled', false);
					alert('Request failed.');

				});

			});

		},

		/**
		 * Prevent double form submissions.
		 */
		preventDoubleSubmit: function () {

			$(document).on('submit', '.nba-settings-form', function () {

				$(this)
					.find('button[type="submit"], input[type="submit"]')
					.prop('disabled', true);

			});

		},

		/**
		 * Add active class to the current tab link.
		 */
		highlightActiveTab: function () {

			const current = window.location.href;

			$('.nba-settings-tabs .nba-settings-tab').each(function () {

				if ($(this).attr('href') === current) {
					$(this).addClass('active');
				}

			});

		}

	};

	$(function () {

		Settings.init();

		window.NBSettings = Settings;

	});

})(jQuery);
