/**
 * Newsblenda Accounts Admin
 */

(function ($) {
	'use strict';

	const Admin = {

		init: function () {

			this.confirmActions();
			this.toggleSections();
			this.copyFields();
			this.autoDismissNotices();
			this.preventDoubleSubmit();

			this.settingsTabs();
			this.dependentFields();
			this.dirtyForms();
			this.selectAll();
			this.loadingButtons();

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

		copyFields: function () {

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

		autoDismissNotices: function () {

			window.setTimeout(function () {

				$('.notice.is-dismissible,.nba-notice')
					.fadeOut(300);

			}, 5000);

		},

		preventDoubleSubmit: function () {

			$(document).on(
				'submit',
				'form',
				function () {

					$(this)
						.find('button[type="submit"],input[type="submit"]')
						.prop('disabled', true);

				}
			);

		},

		settingsTabs: function () {

			const tabs = $('.nba-settings-tabs a');

			if (!tabs.length) {
				return;
			}

			tabs.on('click', function (e) {

				e.preventDefault();

				const target = $(this).attr('href');

				tabs.removeClass('active');

				$(this).addClass('active');

				$('.nba-settings-panel')
					.removeClass('active')
					.hide();

				$(target)
					.addClass('active')
					.show();

			});

			tabs.first().trigger('click');

		},

		dependentFields: function () {

			$(document).on(
				'change',
				'[data-toggle]',
				function () {

					const target = $(this).data('toggle');

					if (!target) {
						return;
					}

					if ($(this).is(':checked')) {

						$(target).slideDown(200);

					} else {

						$(target).slideUp(200);

					}

				}
			);

		},

		dirtyForms: function () {

			$('.nba-admin-form :input').on(
				'change input',
				function () {

					$(this)
						.closest('.nba-form-group')
						.addClass('nba-dirty');

				}
			);

		},

		selectAll: function () {

			$(document).on(
				'change',
				'.nba-select-all',
				function () {

					const checked = $(this).is(':checked');

					$('.nba-row-checkbox').prop(
						'checked',
						checked
					);

				}
			);

		},

		loadingButtons: function () {

			$(document).on(
				'click',
				'.nba-loading-button',
				function () {

					$(this)
						.addClass('nba-loading')
						.prop('disabled', true);

				}
			);

		}

	};

	$(function () {

		Admin.init();

	});

})(jQuery);