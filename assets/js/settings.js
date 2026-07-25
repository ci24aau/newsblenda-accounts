/**
 * Newsblenda Accounts Settings
 */

(function ($) {
	'use strict';

	const Settings = {

		init: function () {

			this.tabs();
			this.dependencies();
			this.numberValidation();
			this.trackChanges();
			this.loadingButtons();
			this.resetConfirmation();
			this.preventDoubleSubmit();

		},

		/**
		 * Settings tabs.
		 */
		tabs: function () {

			const tabs = $('.nba-settings-tabs a');

			if (!tabs.length) {
				return;
			}

			const savedTab = sessionStorage.getItem('nba-settings-tab');

			if (savedTab && $(savedTab).length) {

				tabs.removeClass('active');
				$('.nba-settings-panel').hide();

				$('.nba-settings-tabs a[href="' + savedTab + '"]')
					.addClass('active');

				$(savedTab).show();

			} else {

				tabs.first().addClass('active');

				$('.nba-settings-panel').hide();

				$(
					tabs.first().attr('href')
				).show();

			}

			$(document).on(
				'click',
				'.nba-settings-tabs a',
				function (e) {

					e.preventDefault();

					const target = $(this).attr('href');

					sessionStorage.setItem(
						'nba-settings-tab',
						target
					);

					tabs.removeClass('active');

					$(this).addClass('active');

					$('.nba-settings-panel').hide();

					$(target).fadeIn(150);

				}
			);

		},

		/**
		 * Toggle dependent settings.
		 */
		dependencies: function () {

			$(document).on(
				'change',
				'[data-toggle]',
				function () {

					const target = $(this).data('toggle');

					if (!target) {
						return;
					}

					let visible = false;

					if ($(this).is(':checkbox')) {

						visible = $(this).is(':checked');

					} else {

						visible = $(this).val() !== '';

					}

					if (visible) {

						$(target).stop(true, true).slideDown(200);

					} else {

						$(target).stop(true, true).slideUp(200);

					}

				}
			);

			$('[data-toggle]').trigger('change');

		},

		/**
		 * Validate number fields.
		 */
		numberValidation: function () {

			$(document).on(
				'input',
				'input[type="number"]',
				function () {

					const min = parseFloat($(this).attr('min'));
					const max = parseFloat($(this).attr('max'));

					let value = parseFloat($(this).val());

					if (isNaN(value)) {
						return;
					}

					if (!isNaN(min) && value < min) {
						value = min;
					}

					if (!isNaN(max) && value > max) {
						value = max;
					}

					$(this).val(value);

				}
			);

		},

		/**
		 * Track changed fields.
		 */
		trackChanges: function () {

			$('.nba-settings-form :input').on(
				'change input',
				function () {

					$(this)
						.closest('.form-table, .nba-form-group')
						.addClass('nba-dirty');

				}
			);

		},

		/**
		 * Loading state.
		 */
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

		/**
		 * Confirm reset buttons.
		 */
		resetConfirmation: function () {

			$(document).on(
				'click',
				'.nba-settings-reset',
				function (e) {

					if (
						!window.confirm(
							'Reset all settings to their default values?'
						)
					) {

						e.preventDefault();

					}

				}
			);

		},

		/**
		 * Prevent duplicate submissions.
		 */
		preventDoubleSubmit: function () {

			$(document).on(
				'submit',
				'form',
				function () {

					$(this)
						.find(
							'button[type="submit"], input[type="submit"]'
						)
						.prop('disabled', true);

				}
			);

		}

	};

	$(function () {

		Settings.init();

		window.NBSettings = Settings;

	});

})(jQuery);