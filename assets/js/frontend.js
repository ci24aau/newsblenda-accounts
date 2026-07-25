/**
 * Newsblenda Accounts Frontend
 */

(function ($) {
	'use strict';

	const NBAccounts = {

		init: function () {

			this.passwordToggle();
			this.confirmActions();
			this.autoDismiss();
			this.disableDoubleSubmit();
			this.loadingButtons();
			this.smoothScroll();
			this.copyToClipboard();
			this.toggleSections();
			this.inputTrim();
			this.tooltips();
			this.registerNotices();

		},

		passwordToggle: function () {

			$(document).on(
				'click',
				'.nba-password-toggle',
				function (e) {

					e.preventDefault();

					const input = $(this).siblings('input');

					if (!input.length) {
						return;
					}

					input.attr(
						'type',
						input.attr('type') === 'password'
							? 'text'
							: 'password'
					);

					$(this).toggleClass('visible');

				}
			);

		},

		confirmActions: function () {

			$(document).on(
				'click',
				'[data-confirm]',
				function (e) {

					const message = $(this).data('confirm');

					if (message && !window.confirm(message)) {
						e.preventDefault();
					}

				}
			);

		},

		autoDismiss: function () {

			window.setTimeout(function () {

				$('.nba-notice').fadeOut(300);

			}, 5000);

		},

		disableDoubleSubmit: function () {

			$(document).on(
				'submit',
				'form[data-nb-lock-submit="1"]',
				function () {
					const form = $(this);
					if (form.data('nb-submitting')) {
						return false;
					}

					form.data('nb-submitting', true).addClass('nba-loading');

					form
						.find('button[type="submit"], input[type="submit"]')
						.prop('disabled', true);

					window.setTimeout(function () {
						if (form.data('nb-submitting')) {
							form.data('nb-submitting', false).removeClass('nba-loading');
							form
								.find('button[type="submit"], input[type="submit"]')
								.prop('disabled', false);
						}
					}, 10000);

				}
			);

		},

		registerNotices: function () {
			$(document).on('submit', '.nba-register-form', function () {
				$(this).closest('.nba-auth-card').find('.nba-message').remove();
			});
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

		smoothScroll: function () {

			$(document).on(
				'click',
				'[data-scroll]',
				function (e) {

					e.preventDefault();

					const target = $(this).data('scroll');

					if (!$(target).length) {
						return;
					}

					$('html, body').animate(
						{
							scrollTop: $(target).offset().top - 20
						},
						300
					);

				}
			);

		},

		copyToClipboard: function () {

			$(document).on(
				'click',
				'.nba-copy',
				function (e) {

					e.preventDefault();

					const selector = $(this).data('copy');

					if (!selector) {
						return;
					}

					const input = document.querySelector(selector);

					if (!input) {
						return;
					}

					if (navigator.clipboard && window.isSecureContext) {

						navigator.clipboard.writeText(input.value);

					} else {

						input.select();
						document.execCommand('copy');

					}

				}
			);

		},

		toggleSections: function () {

			$(document).on(
				'click',
				'.nba-toggle',
				function (e) {

					e.preventDefault();

					const target = $(this).data('target');

					if (!target) {
						return;
					}

					$(target).stop(true, true).slideToggle(200);

					$(this).toggleClass('open');

				}
			);

		},

		inputTrim: function () {

			$(document).on(
				'blur',
				'input[type="text"], input[type="email"], textarea',
				function () {

					$(this).val($.trim($(this).val()));

				}
			);

		},

		tooltips: function () {

			$('[data-tooltip]').each(function () {

				$(this).attr(
					'title',
					$(this).data('tooltip')
				);

			});

		}

	};

	$(function () {

		NBAccounts.init();

	});

})(jQuery);