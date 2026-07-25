/**
 * Newsblenda Accounts Table Utilities
 */

(function ($) {
	'use strict';

	const Tables = {

		init: function () {

			this.sortable();
			this.search();
			this.selectAll();
			this.rowActions();
			this.syncSelectAll();
			this.highlightRows();

		},

		/**
		 * Sort table rows.
		 */
		sortable: function () {

			$(document).on(
				'click',
				'.nba-table-sort',
				function (e) {

					e.preventDefault();

					const header = $(this);
					const table = header.closest('table');
					const tbody = table.find('tbody');

					if (!tbody.length) {
						return;
					}

					const index = header.closest('th').index();

					const ascending = !header.hasClass('asc');

					header
						.toggleClass('asc', ascending)
						.toggleClass('desc', !ascending);

					const rows = tbody.find('tr').get();

					rows.sort(function (a, b) {

						const aText = $(a)
							.children()
							.eq(index)
							.text()
							.trim()
							.toLowerCase();

						const bText = $(b)
							.children()
							.eq(index)
							.text()
							.trim()
							.toLowerCase();

						if ($.isNumeric(aText) && $.isNumeric(bText)) {

							return ascending
								? Number(aText) - Number(bText)
								: Number(bText) - Number(aText);

						}

						if (ascending) {
							return aText.localeCompare(bText);
						}

						return bText.localeCompare(aText);

					});

					$.each(rows, function (_, row) {

						tbody.append(row);

					});

				}
			);

		},

		/**
		 * Live table search.
		 */
		search: function () {

			$(document).on(
				'keyup',
				'.nba-table-search',
				function () {

					const value = $(this)
						.val()
						.toLowerCase();

					$('.nba-table tbody tr').each(function () {

						const row = $(this)
							.text()
							.toLowerCase();

						$(this).toggle(
							row.indexOf(value) !== -1
						);

					});

				}
			);

		},

		/**
		 * Select all rows.
		 */
		selectAll: function () {

			$(document).on(
				'change',
				'.nba-select-all',
				function () {

					const checked = $(this).is(':checked');

					$('.nba-row-checkbox')
						.prop('checked', checked)
						trigger('change');

				}
			);

		},

		/**
		 * Keep master checkbox synchronized.
		 */
		syncSelectAll: function () {

			$(document).on(
				'change',
				'.nba-row-checkbox',
				function () {

					const all = $('.nba-row-checkbox').length;

					const checked = $('.nba-row-checkbox:checked').length;

					$('.nba-select-all').prop(
						'checked',
						all > 0 && all === checked
					);

				}
			);

		},

		/**
		 * Row actions.
		 */
		rowActions: function () {

			$(document).on(
				'click',
				'.nba-row-action',
				function (e) {

					e.preventDefault();

					$(this)
						.closest('tr')
						.toggleClass('selected');

				}
			);

		},

		/**
		 * Highlight selected rows.
		 */
		highlightRows: function () {

			$(document).on(
				'change',
				'.nba-row-checkbox',
				function () {

					$(this)
						.closest('tr')
						.toggleClass(
							'selected',
							$(this).is(':checked')
						);

				}
			);

		}

	};

	$(function () {

		Tables.init();

		window.NBTables = Tables;

	});

})(jQuery);