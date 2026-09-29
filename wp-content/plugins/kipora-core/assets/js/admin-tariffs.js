/**
 * Prices screen: add and remove table rows.
 */
(function () {
	'use strict';

	var counter = Date.now();

	document.querySelectorAll('[data-kp-table]').forEach(function (table) {
		var template = table.nextElementSibling;
		var add = template.nextElementSibling.querySelector('[data-kp-add]');

		add.addEventListener('click', function () {
			var html = template.innerHTML.replace(/__i__/g, 'n' + (counter++));
			var body = table.querySelector('tbody');
			body.insertAdjacentHTML('beforeend', html);
			var first = body.lastElementChild.querySelector('input[type="text"]');
			if (first) {
				first.focus();
			}
		});

		table.addEventListener('click', function (e) {
			if (e.target.matches('[data-kp-remove]')) {
				e.target.closest('tr').remove();
			}
		});
	});
})();
