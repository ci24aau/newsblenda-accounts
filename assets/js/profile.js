/**
 * Newsblenda Accounts Profile
 */

(function ($) {
	'use strict';

	const Profile = {

		changed: false,

		init: function () {

			this.trackChanges();
			this.preventDuplicateSubmission();
			this.previewAvatar();
			this.validateAvatar();
			this.passwordToggle();
			this.confirmPassword();
			this.characterCounters();
			this.trimInputs();
			this.loadingState();

		},

		trackChanges: function () {

			const self = this;

			$(document).on(
				'change input',
				'.nba-profile-form :input',
				function () {

					self.changed = true;

				}
			);

			$(window).on(
				'beforeunload',
				function () {

					if (!self.changed) {
						return;
					}

					if (
						typeof nbaProfile !== 'undefined' &&
						nbaProfile.unsavedChanges
					) {
						return nbaProfile.unsavedChanges;
					}

					return 'You have unsaved changes.';

				}
			);

			$(document).on(
				'submit',
				'.nba-profile-form',
				function () {

					self.changed = false;

				}
			);

		},

		preventDuplicateSubmission: function () {

			$(document).on(
				'submit',
				'.nba-profile-form',
				function () {

					$(this)
						.find(
							'button[type="submit"], input[type="submit"]'
						)
						.prop('disabled', true);

				}
			);

		},

		previewAvatar: function () {

			$(document).on(
				'change',
				'#nba-profile-avatar',
				function () {

					const file = this.files[0];

					if (!file) {
						return;
					}

					const reader = new FileReader();

					reader.onload = function (e) {

						$('.nba-avatar-preview')
							.attr('src', e.target.result);

					};

					reader.readAsDataURL(file);

				}
			);

		},

		validateAvatar: function () {

			$(document).on(
				'change',
				'#nba-profile-avatar',
				function () {

					const file = this.files[0];

					if (!file) {
						return;
					}

					const allowed = [
						'image/jpeg',
						'image/png',
						'image/webp',
						'image/gif'
					];

					if ($.inArray(file.type, allowed) === -1) {

						alert('Please select a valid image.');

						$(this).val('');

						return;

					}

					if (file.size > (5 * 1024 * 1024)) {

						alert('Maximum avatar size is 5 MB.');

						$(this).val('');

					}

				}
			);

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

				}
			);

		},

		confirmPassword: function () {

			$(document).on(
				'keyup',
				'input[name="confirm_password"]',
				function () {

					const password = $('input[name="password"]').val();

					if (!password.length) {
						return;
					}

					$(this).toggleClass(
						'nba-invalid',
						$(this).val() !== password
					);

				}
			);

		},

		characterCounters: function () {

			$(document).on(
				'input',
				'textarea[maxlength]',
				function () {

					const max = parseInt(
						$(this).attr('maxlength'),
						10
					);

					const count = $(this).val().length;

					$(this)
						.next('.nba-character-count')
						.text(count + ' / ' + max);

				}
			);

		},

		trimInputs: function () {

			$(document).on(
				'blur',
				'input[type="text"], input[type="email"], textarea',
				function () {

					$(this).val($.trim($(this).val()));

				}
			);

		},

		loadingState: function () {

			$(document).on(
				'click',
				'.nba-loading-button',
				function () {

					$(this)
						.prop('disabled', true)
						.addClass('nba-loading');

				}
			);

		}

	};

	$(function () {

		Profile.init();

		window.NBProfile = Profile;

	});

})(jQuery);