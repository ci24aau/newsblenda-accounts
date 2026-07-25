/**
 * Newsblenda Accounts Utilities
 */

(function (window, $) {
	'use strict';

	if (window.NBAccounts) {
		return;
	}

	const Utils = {

		/**
		 * AJAX request helper.
		 *
		 * @param {String} method
		 * @param {Object} data
		 * @param {Function|null} success
		 * @param {Function|null} error
		 * @returns {jqXHR|null}
		 */
		request: function (method, data, success, error) {

			if (typeof nbaData === 'undefined') {
				return null;
			}

			const request = $.ajax({

				url: nbaData.ajaxUrl,

				type: method,

				dataType: 'json',

				timeout: 30000,

				data: $.extend(
					{
						nonce: nbaData.nonce
					},
					data || {}
				)

			});

			request.done(function (response) {

				if (typeof success === 'function') {
					success(response);
				}

				$(document).trigger(
					'nba_ajax_success',
					[response]
				);

			});

			request.fail(function (xhr) {

				if (typeof error === 'function') {
					error(xhr);
				}

				$(document).trigger(
					'nba_ajax_error',
					[xhr]
				);

			});

			return request;

		},

		/**
		 * POST helper.
		 */
		post: function (data, success, error) {

			return this.request(
				'POST',
				data,
				success,
				error
			);

		},

		/**
		 * GET helper.
		 */
		get: function (data, success, error) {

			return this.request(
				'GET',
				data,
				success,
				error
			);

		},

		/**
		 * Show notice.
		 */
		notice: function (message, type) {

			type = type || 'success';

			const notice = $('<div>', {
				class: 'nba-notice nba-notice-' + type,
				text: message
			});

			$('.nba-notice').remove();

			$('body').prepend(notice);

			setTimeout(function () {

				notice.fadeOut(300, function () {

					$(this).remove();

				});

			}, 4000);

		},

		/**
		 * Serialize form to object.
		 */
		serialize: function (form) {

			const object = {};

			$(form)
				.serializeArray()
				.forEach(function (field) {

					object[field.name] = field.value;

				});

			return object;

		},

		/**
		 * Enable submit buttons.
		 */
		enableSubmit: function (form) {

			$(form)
				.find('button[type="submit"], input[type="submit"]')
				.prop('disabled', false)
				.removeClass('nba-loading');

		},

		/**
		 * Disable submit buttons.
		 */
		disableSubmit: function (form) {

			$(form)
				.find('button[type="submit"], input[type="submit"]')
				.prop('disabled', true)
				.addClass('nba-loading');

		},

		/**
		 * Trim all text inputs.
		 */
		trimForm: function (form) {

			$(form)
				.find('input[type="text"], input[type="email"], textarea')
				.each(function () {

					$(this).val(
						$.trim($(this).val())
					);

				});

		},

		/**
		 * Escape HTML.
		 */
		escapeHtml: function (string) {

			return $('<div>')
				.text(string)
				.html();

		},

		/**
		 * Scroll to element.
		 */
		scrollTo: function (selector) {

			const target = $(selector);

			if (!target.length) {
				return;
			}

			$('html, body').animate(
				{
					scrollTop: target.offset().top - 20
				},
				300
			);

		},

		/**
		 * Debounce helper.
		 */
		debounce: function (callback, delay) {

			let timer;

			return function () {

				const args = arguments;

				const context = this;

				clearTimeout(timer);

				timer = setTimeout(function () {

					callback.apply(context, args);

				}, delay);

			};

		},

		/**
		 * Generate random ID.
		 */
		uniqueId: function (prefix) {

			prefix = prefix || 'nba';

			return (
				prefix +
				'-' +
				Date.now() +
				'-' +
				Math.floor(Math.random() * 100000)
			);

		}

	};

	window.NBAccounts = Utils;

})(window, jQuery);