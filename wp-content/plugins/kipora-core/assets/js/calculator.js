/**
 * KIPORA price calculator.
 * Renders from window.kiporaCalculator, asks the server for the price
 * (the server formula is the only source of truth) and passes the
 * selection to the checkout page as ?sel=base64url(JSON).
 */
(function () {
	'use strict';

	var cfg = window.kiporaCalculator;
	var root = document.querySelector('[data-kp-calculator]');
	if (!cfg || !root) {
		return;
	}

	var t = cfg.strings;
	var tariffs = cfg.tariffs;
	var state = restore() || { direction: '' };
	var quoteTimer = null;
	var quoteSeq = 0;
	var lastQuote = null;

	function restore() {
		var raw = new URLSearchParams(window.location.search).get('sel');
		if (!raw) {
			return null;
		}
		try {
			return JSON.parse(atob(raw.replace(/-/g, '+').replace(/_/g, '/')));
		} catch (e) {
			return null;
		}
	}

	function encode(obj) {
		return btoa(JSON.stringify(obj)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
	}

	function money(cents) {
		var euros = Math.floor(cents / 100);
		var rest = cents % 100;
		var int = String(euros).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
		return (rest ? int + ',' + String(rest).padStart(2, '0') : int) + ' €';
	}

	function fmt(str) {
		var args = Array.prototype.slice.call(arguments, 1);
		var i = 0;
		return str.replace(/%(\d\$)?[sd]/g, function (m, pos) {
			return pos ? args[parseInt(pos, 10) - 1] : args[i++];
		});
	}

	function el(tag, attrs, children) {
		var node = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (key) {
			if (key === 'text') {
				node.textContent = attrs[key];
			} else if (key === 'class') {
				node.className = attrs[key];
			} else if (attrs[key] === true) {
				node.setAttribute(key, '');
			} else if (attrs[key] !== false && attrs[key] !== null && attrs[key] !== undefined) {
				node.setAttribute(key, attrs[key]);
			}
		});
		(children || []).forEach(function (child) {
			if (child) {
				node.appendChild(child);
			}
		});
		return node;
	}

	var uid = 0;

	/** One option: radio or checkbox styled as a choice card. */
	function choice(type, name, item, checked, priceText, onChange) {
		var id = 'kp-calc-' + (++uid);
		var input = el('input', { class: 'kp-choice__input', type: type, name: name, id: id, value: item.id, checked: !!checked });
		input.addEventListener('change', onChange);
		return el('label', { class: 'kp-choice', for: id }, [
			input,
			el('span', { class: 'kp-choice__body' }, [
				el('span', { class: 'kp-choice__title', text: item.label }),
				item.hint ? el('span', { class: 'kp-choice__meta', text: item.hint }) : null,
				priceText ? el('span', { class: 'kp-choice__price', text: priceText }) : null
			])
		]);
	}

	function section(title, body, note) {
		return el('fieldset', { class: 'kp-calc__section' }, [
			el('legend', { class: 'kp-calc__legend', text: title }),
			note ? el('p', { class: 'kp-calc__note', text: note }) : null,
			body
		]);
	}

	function group(nodes, inline) {
		return el('div', { class: 'kp-choices' + (inline ? ' kp-choices--inline' : '') }, nodes);
	}

	function find(list, id) {
		for (var i = 0; i < list.length; i++) {
			if (list[i].id === id) {
				return list[i];
			}
		}
		return null;
	}

	function sizeCoef() {
		var size = find(tariffs.grave.sizes, state.size);
		return size ? size.coef : 1;
	}

	function priceFor(item) {
		if (item.per_size) {
			return state.size ? money(Math.round(item.price * sizeCoef())) : fmt(t.from, money(item.price));
		}
		return money(item.price);
	}

	function setDirection(direction) {
		state = { direction: direction };
		if (direction === 'grave') {
			state.extras = [];
		} else {
			state.services = [];
			state.options = [];
			var none = tariffs.pet.urns.filter(function (u) { return u.price === 0; })[0];
			state.urn = none ? none.id : '';
		}
		render();
	}

	function toggle(list, id, on) {
		var idx = list.indexOf(id);
		if (on && idx === -1) {
			list.push(id);
		}
		if (!on && idx !== -1) {
			list.splice(idx, 1);
		}
	}

	function renderGrave(form) {
		var g = tariffs.grave;

		form.appendChild(section(t.package, group(g.packages.map(function (p) {
			return choice('radio', 'package', p, state.package === p.id, priceFor(p), function () {
				state.package = p.id;
				if (!p.extras) {
					state.extras = [];
				}
				render();
			});
		}))));

		if (!state.package) {
			return;
		}
		var pkg = find(g.packages, state.package);

		form.appendChild(section(t.size, group(g.sizes.map(function (s) {
			return choice('radio', 'size', s, state.size === s.id, '', function () {
				state.size = s.id;
				render();
			});
		}), true)));

		if (!state.size) {
			return;
		}

		if (pkg && pkg.extras && g.extras.length) {
			form.appendChild(section(t.extras, group(g.extras.map(function (x) {
				return choice('checkbox', 'extras', x, state.extras.indexOf(x.id) !== -1, '+ ' + priceFor(x), function (e) {
					toggle(state.extras, x.id, e.target.checked);
					requestQuote();
				});
			}))));
		}

		var select = el('select', { class: 'kp-field__input', id: 'kp-calc-cemetery', name: 'cemetery' }, [
			el('option', { value: '', text: t.cemeteryPick })
		]);
		[['tallinn', t.tallinn], ['harjumaa', t.harjumaa]].forEach(function (zone) {
			var items = g.cemeteries.filter(function (c) { return c.zone === zone[0]; });
			if (!items.length) {
				return;
			}
			var og = el('optgroup', { label: zone[1] });
			items.forEach(function (c) {
				og.appendChild(el('option', {
					value: c.id,
					selected: state.cemetery === c.id,
					text: c.label + (c.zone === 'harjumaa' && c.km ? ' · ' + c.km + ' km' : '')
				}));
			});
			select.appendChild(og);
		});
		select.addEventListener('change', function () {
			state.cemetery = select.value;
			requestQuote();
		});
		form.appendChild(section(t.cemetery, el('div', { class: 'kp-field' }, [
			el('label', { class: 'kp-field__label kp-visually-hidden', for: 'kp-calc-cemetery', text: t.cemetery }),
			select,
			g.km_price ? el('p', { class: 'kp-field__hint', text: t.harjumaa + ': ' + fmt(t.kmNote, money(g.km_price)) }) : null
		]), t.otherCemetery));
	}

	function renderPet(form) {
		var p = tariffs.pet;

		form.appendChild(section(t.services, group(p.services.map(function (s) {
			return choice('checkbox', 'services', s, state.services.indexOf(s.id) !== -1, money(s.price), function (e) {
				toggle(state.services, s.id, e.target.checked);
				requestQuote();
			});
		}))));

		if (p.urns.length) {
			form.appendChild(section(t.urn, group(p.urns.map(function (u) {
				return choice('radio', 'urn', u, state.urn === u.id, u.price ? '+ ' + money(u.price) : '', function () {
					state.urn = u.id;
					requestQuote();
				});
			}))));
		}

		if (p.options.length) {
			form.appendChild(section(t.options, group(p.options.map(function (o) {
				return choice('checkbox', 'options', o, state.options.indexOf(o.id) !== -1, '+ ' + money(o.price), function (e) {
					toggle(state.options, o.id, e.target.checked);
					requestQuote();
				});
			}))));
		}
	}

	var summary;

	function renderSummary() {
		summary = el('aside', { class: 'kp-calc__summary', 'aria-live': 'polite' });
		updateSummary();
		return summary;
	}

	function updateSummary() {
		if (!summary) {
			return;
		}
		summary.innerHTML = '';
		summary.appendChild(el('h2', { class: 'kp-calc__summary-title', text: t.summary }));

		var q = lastQuote;
		if (!q || !q.valid) {
			summary.appendChild(el('p', { class: 'kp-calc__hint', text: q && q.failed ? t.error : (state.direction === 'pet' ? t.incompletePet : t.incomplete) }));
			return;
		}

		var dl = el('dl', { class: 'kp-summary' });
		q.lines.forEach(function (line) {
			dl.appendChild(el('div', { class: 'kp-summary__row' }, [
				el('dt', { class: 'kp-summary__label', text: line.label }),
				el('dd', { class: 'kp-summary__amount', text: line.formatted })
			]));
		});
		dl.appendChild(el('div', { class: 'kp-summary__row kp-summary__row--total' }, [
			el('dt', { class: 'kp-summary__label', text: t.total }),
			el('dd', { class: 'kp-summary__amount', text: q.total_formatted })
		]));
		summary.appendChild(dl);

		var go = el('a', { class: 'kp-button kp-button--primary kp-button--wide', href: cfg.checkout + (cfg.checkout.indexOf('?') === -1 ? '?' : '&') + 'sel=' + encode(q.summary), text: t.continue });
		summary.appendChild(go);
	}

	function requestQuote() {
		clearTimeout(quoteTimer);
		quoteTimer = setTimeout(fetchQuote, 150);
	}

	function fetchQuote() {
		var seq = ++quoteSeq;
		if (summary) {
			summary.classList.add('kp-calc__summary--loading');
		}
		fetch(cfg.quoteUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ selection: state, lang: cfg.lang })
		})
			.then(function (r) {
				if (!r.ok) {
					throw new Error(r.status);
				}
				return r.json();
			})
			.then(function (q) {
				if (seq === quoteSeq) {
					lastQuote = q;
				}
			})
			.catch(function () {
				if (seq === quoteSeq) {
					lastQuote = { valid: false, failed: true };
				}
			})
			.then(function () {
				if (seq === quoteSeq && summary) {
					summary.classList.remove('kp-calc__summary--loading');
					updateSummary();
				}
			});
	}

	function render() {
		// Re-rendering replaces the inputs: remember focus for keyboard users.
		var active = document.activeElement;
		var focusKey = active && root.contains(active) && active.name ? [active.name, active.value] : null;
		root.innerHTML = '';
		var form = el('form', { class: 'kp-calc__form', novalidate: true });
		form.addEventListener('submit', function (e) { e.preventDefault(); });

		var hasGrave = tariffs.grave.packages.length > 0;
		var hasPet = tariffs.pet.services.length > 0;
		var directions = [];
		if (hasGrave) {
			directions.push(choice('radio', 'direction', { id: 'grave', label: t.grave, hint: t.graveHint }, state.direction === 'grave', '', function () { setDirection('grave'); }));
		}
		if (hasPet) {
			directions.push(choice('radio', 'direction', { id: 'pet', label: t.pet, hint: t.petHint }, state.direction === 'pet', '', function () { setDirection('pet'); }));
		}
		form.appendChild(section(t.direction, group(directions, true)));

		if (state.direction === 'grave') {
			state.extras = state.extras || [];
			renderGrave(form);
		} else if (state.direction === 'pet') {
			state.services = state.services || [];
			state.options = state.options || [];
			renderPet(form);
		}

		root.appendChild(el('div', { class: 'kp-calc__layout' }, [form, state.direction ? renderSummary() : null]));
		if (!state.direction) {
			summary = null;
		}
		if (state.direction) {
			requestQuote();
		}
		if (focusKey) {
			var again = root.querySelector('[name="' + focusKey[0] + '"][value="' + focusKey[1] + '"]');
			if (again) {
				again.focus();
			}
		}
	}

	render();
})();
