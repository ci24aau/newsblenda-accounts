/**
 * Newsblenda Accounts Authentication
 */

(function ($) {
	'use strict';

	const Auth = {

		init: function () {

			this.passwordToggle();
			this.passwordStrength();
			this.confirmPassword();
			this.emailValidation();
			this.capsLockWarning();
			this.disableDoubleSubmit();
			this.loadingButtons();
			this.trimInputs();

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

		passwordStrength: function () {

			$(document).on(
				'keyup',
				'input[name="password"]',
				function () {

					const password = $(this).val();

					let strength = 0;

					if (password.length >= 8) {
						strength++;
					}

					if (/[A-Z]/.test(password)) {
						strength++;
					}

					if (/[a-z]/.test(password)) {
						strength++;
					}

					if (/[0-9]/.test(password)) {
						strength++;
					}

					if (/[^A-Za-z0-9]/.test(password)) {
						strength++;
					}

					const meter = $('.nba-password-strength');

					if (!meter.length) {
						return;
					}

					meter.removeClass(
						'weak medium strong very-strong'
					);

					switch (strength) {

						case 0:
						case 1:
						case 2:

							meter
								.addClass('weak')
								.text('Weak');

							break;

						case 3:

							meter
								.addClass('medium')
								.text('Medium');

							break;

						case 4:

							meter
								.addClass('strong')
								.text('Strong');

							break;

						default:

							meter
								.addClass('very-strong')
								.text('Very Strong');

					}

				}
			);

		},

		confirmPassword: function () {

			$(document).on(
				'keyup',
				'input[name="confirm_password"]',
				function () {

					const password = $('input[name="password"]').val();

					const confirm = $(this).val();

					const status = $('.nba-password-match');

					if (!status.length) {
						return;
					}

					if (confirm === '') {

						status.text('');

						return;

					}

					if (password === confirm) {

						status
							.removeClass('error')
							.addClass('success')
							.text('Passwords match');

					} else {

						status
							.removeClass('success')
							.addClass('error')
							.text('Passwords do not match');

					}

				}
			);

		},

		emailValidation: function () {

			$(document).on(
				'blur',
				'input[type="email"]',
				function () {

					const value = $(this).val().trim();

					const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

					if (!value.length) {
						return;
					}

					$(this).toggleClass(
						'nba-invalid',
						!regex.test(value)
					);

				}
			);

		},

		capsLockWarning: function () {

			$(document).on(
				'keyup',
				'input[type="password"]',
				function (e) {

					const warning = $('.nba-caps-lock');

					if (!warning.length) {
						return;
					}

					if (
						e.originalEvent &&
						e.originalEvent.getModifierState &&
						e.originalEvent.getModifierState('CapsLock')
					) {

						warning.show();

					} else {

						warning.hide();

					}

				}
			);

		},

		trimInputs: function () {

			$(document).on(
				'blur',
				'input[type="text"],input[type="email"]',
				function () {

					$(this).val(
						$(this).val().trim()
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

		},

		disableDoubleSubmit: function () {

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

		Auth.init();

	});

})(jQuery);