/**
 * Newsblenda Accounts Modal Manager
 */

(function ($) {
	'use strict';

	const Modal = {

		/**
		 * Active modal.
		 *
		 * @type {jQuery|null}
		 */
		active: null,

		/**
		 * Initialise.
		 */
		init: function () {

			this.bindOpen();
			this.bindClose();
			this.bindOverlay();
			this.bindEscape();

		},

		/**
		 * Open modal.
		 *
		 * @param {jQuery} modal
		 */
		open: function (modal) {

			if (!modal.length) {
				return;
			}

			this.closeAll();

			this.active = modal;

			$('body')
				.addClass('nba-modal-open')
				.css('overflow', 'hidden');

			modal
				.attr('aria-hidden', 'false')
				.fadeIn(200);

			window.setTimeout(function () {

				modal.find(
					'input, textarea, select, button'
				).filter(':visible').first().trigger('focus');

			}, 210);

		},

		/**
		 * Close modal.
		 *
		 * @param {jQuery} modal
		 */
		close: function (modal) {

			if (!modal.length) {
				return;
			}

			modal
				.attr('aria-hidden', 'true')
				.fadeOut(200);

			$('body')
				.removeClass('nba-modal-open')
				.css('overflow', '');

			this.active = null;

		},

		/**
		 * Close every modal.
		 */
		closeAll: function () {

			const self = this;

			$('.nba-modal:visible').each(function () {

				self.close($(this));

			});

		},

		/**
		 * Bind open buttons.
		 */
		bindOpen: function () {

			const self = this;

			$(document).on(
				'click',
				'[data-modal]',
				function (e) {

					e.preventDefault();

					const target = $(this).data('modal');

					if (!target) {
						return;
					}

					self.open($(target));

				}
			);

		},

		/**
		 * Bind close buttons.
		 */
		bindClose: function () {

			const self = this;

			$(document).on(
				'click',
				'.nba-modal-close,[data-modal-close]',
				function (e) {

					e.preventDefault();

					self.close(
						$(this).closest('.nba-modal')
					);

				}
			);

		},

		/**
		 * Close when clicking overlay.
		 */
		bindOverlay: function () {

			const self = this;

			$(document).on(
				'click',
				'.nba-modal',
				function (e) {

					if ($(e.target).is('.nba-modal')) {

						self.close($(this));

					}

				}
			);

		},

		/**
		 * Close with Escape.
		 */
		bindEscape: function () {

			const self = this;

			$(document).on(
				'keydown',
				function (e) {

					if (e.key === 'Escape') {

						self.closeAll();

					}

				}
			);

		}

	};

	$(function () {

		Modal.init();

		window.NBAccountsModal = Modal;

	});

})(jQuery);