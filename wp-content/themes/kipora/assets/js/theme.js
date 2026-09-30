/**
 * Phone menu: the button opens and closes the menu panel, Escape closes it.
 */
(function () {
	'use strict';

	var toggle = document.querySelector('[data-site-menu-toggle]');
	var menu = document.querySelector('[data-site-menu]');
	if (!toggle || !menu) {
		return;
	}

	function set(open) {
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		menu.classList.toggle('is-open', open);
	}

	toggle.addEventListener('click', function () {
		set(toggle.getAttribute('aria-expanded') !== 'true');
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
			set(false);
			toggle.focus();
		}
	});
})();
