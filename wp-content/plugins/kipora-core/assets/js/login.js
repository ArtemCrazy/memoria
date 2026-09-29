/**
 * Smart-ID / Mobile-ID login widget: start a session, show the control
 * code, poll until the phone answers, then reload into the logged-in page.
 */
(function () {
	'use strict';

	var cfg = window.kiporaLogin;
	if (!cfg) {
		return;
	}

	document.querySelectorAll('[data-kp-login]').forEach(function (box) {
		var method = 'smartid';
		var form = box.querySelector('form');
		var phoneField = box.querySelector('[data-kp-phone]');
		var message = box.querySelector('[data-kp-message]');
		var pending = box.querySelector('[data-kp-pending]');
		var codeEl = box.querySelector('[data-kp-code]');
		var submit = form.querySelector('[type="submit"]');
		var active = null;

		box.querySelectorAll('[data-kp-method]').forEach(function (tab) {
			tab.addEventListener('click', function () {
				method = tab.getAttribute('data-kp-method');
				box.querySelectorAll('[data-kp-method]').forEach(function (other) {
					var on = other === tab;
					other.classList.toggle('kp-login__tab--active', on);
					other.setAttribute('aria-selected', on ? 'true' : 'false');
				});
				phoneField.hidden = method !== 'mobileid';
				say('');
			});
		});

		box.querySelector('[data-kp-cancel]').addEventListener('click', function () {
			active = null;
			pending.hidden = true;
			form.hidden = false;
			submit.disabled = false;
		});

		function say(text, isError) {
			message.textContent = text;
			message.classList.toggle('kp-login__message--error', !!isError);
		}

		function post(url, body) {
			body.lang = cfg.lang;
			return fetch(url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(body)
			}).then(function (r) {
				return r.json();
			});
		}

		function fail(data) {
			active = null;
			pending.hidden = true;
			form.hidden = false;
			submit.disabled = false;
			say(data && data.message ? data.message : cfg.strings.network, true);
		}

		function poll(key) {
			if (active !== key) {
				return;
			}
			post(cfg.statusUrl, { key: key })
				.then(function (data) {
					if (active !== key) {
						return;
					}
					if (data.state === 'pending') {
						setTimeout(function () { poll(key); }, 1000);
					} else if (data.state === 'ok') {
						codeEl.textContent = '✓';
						box.querySelector('.kp-login__hint').textContent = cfg.strings.success;
						window.location.reload();
					} else {
						fail(data);
					}
				})
				.catch(function () { fail(null); });
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var idcode = form.idcode.value.replace(/\D/g, '');
			if (idcode.length !== 11) {
				form.idcode.focus();
				say(form.idcode.validationMessage || '11', true);
				return;
			}
			submit.disabled = true;
			say('');
			post(cfg.startUrl, { method: method, idcode: idcode, phone: form.phone.value })
				.then(function (data) {
					if (!data.key) {
						fail(data);
						return;
					}
					active = data.key;
					codeEl.textContent = data.code;
					form.hidden = true;
					pending.hidden = false;
					pending.querySelector('[data-kp-cancel]').focus();
					poll(data.key);
				})
				.catch(function () { fail(null); });
		});
	});
})();
