/**
 * Newsblenda Accounts Dashboard
 */

(function ($) {
	'use strict';

	const Dashboard = {

		refreshTimer: null,

		init: function () {

			this.navigation();
			this.tabs();
			this.refreshCards();
			this.tooltips();
			this.cardActions();
			this.tableSearch();
			this.loadingButtons();
			this.sidebarToggle();

		},

		navigation: function () {

			$(document).on(
				'click',
				'.nba-dashboard-menu a',
				function () {

					$('.nba-dashboard-menu a').removeClass('active');

					$(this).addClass('active');

				}
			);

		},

		tabs: function () {

			$(document).on(
				'click',
				'[data-dashboard-tab]',
				function (e) {

					e.preventDefault();

					const target = $(this).data('dashboard-tab');

					$('[data-dashboard-tab]').removeClass('active');
					$(this).addClass('active');

					$('.nba-dashboard-tab')
						.removeClass('active')
						.hide();

					$(target)
						.addClass('active')
						.fadeIn(150);

				}
			);

		},

		refreshCards: function () {

			const refresh = parseInt(
				$('.nba-dashboard').data('refresh'),
				10
			);

			if (!refresh || refresh <= 0) {
				return;
			}

			if (this.refreshTimer) {
				clearInterval(this.refreshTimer);
			}

			this.refreshTimer = setInterval(function () {

				$(document).trigger('nba_dashboard_refresh');

			}, refresh * 1000);

		},

		tooltips: function () {

			$('[data-tooltip]').each(function () {

				$(this).attr(
					'title',
					$(this).data('tooltip')
				);

			});

		},

		cardActions: function () {

			$(document).on(
				'click',
				'.nba-card-toggle',
				function (e) {

					e.preventDefault();

					const card = $(this).closest('.nba-card');

					card
						.find('.nba-card-body')
						.stop(true, true)
						.slideToggle(200);

					$(this).toggleClass('open');

				}
			);

		},

		tableSearch: function () {

			$(document).on(
				'keyup',
				'.nba-table-search',
				function () {

					const value = $(this)
						.val()
						.toLowerCase();

					$('.nba-table tbody tr').each(function () {

						$(this).toggle(
							$(this)
								.text()
								.toLowerCase()
								.indexOf(value) > -1
						);

					});

				}
			);

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

		sidebarToggle: function () {

			$(document).on(
				'click',
				'.nba-dashboard-toggle',
				function (e) {

					e.preventDefault();

					$('.nba-dashboard-sidebar')
						.toggleClass('is-open');

				}
			);

		}

	};

	$(function () {

		Dashboard.init();

	});

})(jQuery);