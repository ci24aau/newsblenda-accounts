/**
 * Newsblenda Accounts Media Library
 */

(function ($) {
	'use strict';

	const Media = {

		/**
		 * Initialise.
		 */
		init: function () {

			this.openMedia();
			this.removeMedia();

		},

		/**
		 * Open WordPress Media Library.
		 */
		openMedia: function () {

			$(document).on(
				'click',
				'.nba-media-upload',
				function (e) {

					e.preventDefault();

					if (typeof wp === 'undefined' || !wp.media) {
						return;
					}

					const button = $(this);

					const target = $(button.data('target'));
					const preview = $(button.data('preview'));
					const filename = $(button.data('filename'));

					const frame = wp.media({

						title: button.data('title') || 'Select Media',

						button: {
							text: button.data('button') || 'Use Selected'
						},

						library: {
							type: button.data('type') || ''
						},

						multiple: false

					});

					frame.on(
						'select',
						function () {

							const attachment = frame
								.state()
								.get('selection')
								.first()
								.toJSON();

							if (target.length) {
								target.val(attachment.id).trigger('change');
							}

							if (preview.length) {

								if (
									attachment.type === 'image' &&
									attachment.url
								) {

									preview
										.attr('src', attachment.url)
										show();

								}

							}

							if (filename.length) {

								filename.text(
									attachment.filename || ''
								);

							}

						}
					);

					frame.open();

				}
			);

		},

		/**
		 * Remove selected media.
		 */
		removeMedia: function () {

			$(document).on(
				'click',
				'.nba-media-remove',
				function (e) {

					e.preventDefault();

					const button = $(this);

					const target = $(button.data('target'));
					const preview = $(button.data('preview'));
					const filename = $(button.data('filename'));

					if (target.length) {
						target.val('').trigger('change');
					}

					if (preview.length) {

						preview
							.attr('src', '')
							.hide();

					}

					if (filename.length) {
						filename.text('');
					}

				}
			);

		}

	};

	$(function () {

		if (
			typeof wp !== 'undefined' &&
			typeof wp.media !== 'undefined'
		) {

			Media.init();

		}

	});

})(jQuery);