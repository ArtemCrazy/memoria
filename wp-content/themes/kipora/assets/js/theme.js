/**
 * Sticky header shadow once the page is scrolled; phone menu: the button
 * opens and closes the menu panel, Escape closes it.
 */
(function () {
	'use strict';

	var header = document.querySelector('.site-header');
	if (header) {
		var onScroll = function () {
			header.classList.toggle('is-scrolled', window.scrollY > 4);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	initReveal();

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

	/**
	 * Entrance animations. <html class="has-reveal"> is set in <head> (unless the
	 * visitor prefers reduced motion); here headings are split into words and
	 * every [data-reveal] block gets .is-revealed when it scrolls into view.
	 */
	function initReveal() {
		window.kpRevealReady = true;
		var root = document.documentElement;
		var items = document.querySelectorAll('[data-reveal]');
		if (!root.classList.contains('has-reveal') || !items.length) {
			return;
		}

		document.querySelectorAll('[data-reveal="heading"]').forEach(splitWords);
		document.querySelectorAll('[data-reveal="stagger"]').forEach(function (group) {
			Array.prototype.forEach.call(group.children, function (child, i) {
				child.style.setProperty('--reveal-index', i);
			});
		});

		if (!('IntersectionObserver' in window)) {
			items.forEach(function (el) { el.classList.add('is-revealed'); });
			return;
		}
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-revealed');
					observer.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
		items.forEach(function (el) { observer.observe(el); });
	}

	// Wraps each word of a heading (also inside <em>) in a mask + inner span.
	function splitWords(heading) {
		var index = 0;
		var walker = document.createTreeWalker(heading, NodeFilter.SHOW_TEXT);
		var nodes = [];
		while (walker.nextNode()) {
			nodes.push(walker.currentNode);
		}
		nodes.forEach(function (node) {
			var frag = document.createDocumentFragment();
			node.nodeValue.split(/(\s+)/).forEach(function (part) {
				if (!part) {
					return;
				}
				if (/^\s+$/.test(part)) {
					frag.appendChild(document.createTextNode(part));
					return;
				}
				var mask = document.createElement('span');
				mask.className = 'reveal-word';
				var inner = document.createElement('span');
				inner.className = 'reveal-word__inner';
				inner.style.setProperty('--reveal-index', index++);
				inner.textContent = part;
				mask.appendChild(inner);
				frag.appendChild(mask);
			});
			node.parentNode.replaceChild(frag, node);
		});
	}
})();
