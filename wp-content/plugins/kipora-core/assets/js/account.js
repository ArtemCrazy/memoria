/**
 * Small enhancements for the account and checkout forms.
 * Everything works without it; this only hides fields that do not apply.
 */
(function () {
	'use strict';

	// Checkout: new card fields only when "New memorial card" is chosen.
	document.querySelectorAll('[data-kp-checkout]').forEach(function (form) {
		var fresh = form.querySelector('[data-kp-new-card]');
		var radios = form.querySelectorAll('[data-kp-card]');
		function sync() {
			var picked = form.querySelector('[data-kp-card]:checked');
			fresh.hidden = !!(picked && picked.value !== '0');
		}
		radios.forEach(function (r) { r.addEventListener('change', sync); });
		if (radios.length) {
			sync();
		}
	});

	// New card: cemetery fields only for a person.
	document.querySelectorAll('[data-kp-kind]').forEach(function (radio) {
		radio.addEventListener('change', function () {
			var place = radio.form.querySelector('[data-kp-place]');
			if (place) {
				place.hidden = radio.value === 'pet';
			}
		});
	});

	// Deleting a file cannot be undone: ask first.
	document.querySelectorAll('[data-kp-confirm]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			if (!window.confirm(form.getAttribute('data-kp-confirm'))) {
				e.preventDefault();
			}
		});
	});

	// Show which files were chosen before upload.
	document.querySelectorAll('[data-kp-files]').forEach(function (input) {
		var out = input.closest('label').querySelector('[data-kp-chosen]');
		input.addEventListener('change', function () {
			out.textContent = Array.prototype.map.call(input.files, function (f) { return f.name; }).join(', ');
		});
	});
})();
