/**
 * Newsblenda Accounts REST API Helper
 */

(function (window, $) {
	'use strict';

	if (window.NBRest) {
		return;
	}

	const request = function (method, endpoint, data) {

		if (typeof nbaData === 'undefined') {

			return $.Deferred()
				.reject('REST configuration missing')
				.promise();

		}

		const options = {

			url: nbaData.restUrl + endpoint,

			method: method,

			timeout: 30000,

			dataType: 'json',

			beforeSend: function (xhr) {

				xhr.setRequestHeader(
					'X-WP-Nonce',
					nbaData.restNonce
				);

				$(document).trigger(
					'nba_rest_before_request',
					[method, endpoint, data]
				);

			},

			complete: function () {

				$(document).trigger(
					'nba_rest_complete',
					[method, endpoint]
				);

			}

		};

		if (data !== undefined) {

			options.data = data;

		}

		const ajax = $.ajax(options);

		ajax.done(function (response) {

			$(document).trigger(
				'nba_rest_success',
				[method, endpoint, response]
			);

		});

		ajax.fail(function (xhr) {

			$(document).trigger(
				'nba_rest_error',
				[method, endpoint, xhr]
			);

		});

		return ajax;

	};

	window.NBRest = {

		request: request,

		get: function (endpoint, data) {

			return request(
				'GET',
				endpoint,
				data
			);

		},

		post: function (endpoint, data) {

			return request(
				'POST',
				endpoint,
				data
			);

		},

		put: function (endpoint, data) {

			return request(
				'PUT',
				endpoint,
				data
			);

		},

		patch: function (endpoint, data) {

			return request(
				'PATCH',
				endpoint,
				data
			);

		},

		delete: function (endpoint, data) {

			return request(
				'DELETE',
				endpoint,
				data
			);

		}

	};

})(window, jQuery);