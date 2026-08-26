(function () {
	'use strict';

	if (typeof hdspinParams === 'undefined') {
		return;
	}

	var COOKIE_NAME = 'hdspin_dismissed';
	var FULL_SPINS = 6;
	var SEGMENT_DEGREES = 45;

	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
		return match ? decodeURIComponent(match[1]) : null;
	}

	function setCookie(name, value, days) {
		var expires = new Date(Date.now() + days * 86400000).toUTCString();
		document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/';
	}

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		if (getCookie(COOKIE_NAME)) {
			return;
		}

		var overlay = document.getElementById('hdspin-overlay');
		var wheel = document.getElementById('hdspin-wheel');
		var form = document.getElementById('hdspin-form');
		var closeBtn = overlay ? overlay.querySelector('.hdspin-close') : null;
		var message = form ? form.querySelector('.hdspin-form__message') : null;
		var submit = form ? form.querySelector('.hdspin-form__submit') : null;
		var resultBox = overlay ? overlay.querySelector('.hdspin-result') : null;

		if (!overlay || !wheel || !form) {
			return;
		}

		function dismiss() {
			overlay.style.display = 'none';
			setCookie(COOKIE_NAME, '1', hdspinParams.frequency_cap_days);
		}

		function showPopup() {
			overlay.style.display = 'flex';
		}

		closeBtn.addEventListener('click', dismiss);

		if ('delay' === hdspinParams.trigger_mode) {
			setTimeout(showPopup, hdspinParams.trigger_delay_seconds * 1000);
		} else if ('exit_intent' === hdspinParams.trigger_mode) {
			document.addEventListener('mouseout', function (e) {
				if (e.clientY <= 0 && !e.relatedTarget) {
					showPopup();
				}
			});
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();

			var email = form.querySelector('.hdspin-form__email').value;
			var honeypot = form.querySelector('.hdspin-honeypot').value;

			message.textContent = '';
			submit.disabled = true;

			var body = new URLSearchParams();
			body.append('action', 'hdspin_spin');
			body.append('nonce', hdspinParams.nonce);
			body.append('email', email);
			body.append('hdspin_hp', honeypot);

			fetch(hdspinParams.ajax_url, {
				method: 'POST',
				credentials: 'same-origin',
				body: body,
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (response) {
					if (!response || !response.success) {
						var msg = (response && response.data && response.data.message) ? response.data.message : hdspinParams.i18n.error;
						message.textContent = msg;
						submit.disabled = false;
						return;
					}

					spinToSegment(response.data);
				})
				.catch(function () {
					message.textContent = hdspinParams.i18n.error;
					submit.disabled = false;
				});
		});

		function spinToSegment(data) {
			var segmentCenter = data.segment_index * SEGMENT_DEGREES + (SEGMENT_DEGREES / 2);
			var finalAngle = (360 * FULL_SPINS) + (360 - segmentCenter);

			wheel.style.transform = 'rotate(' + finalAngle + 'deg)';

			wheel.addEventListener('transitionend', function onEnd() {
				wheel.removeEventListener('transitionend', onEnd);
				revealResult(data);
			});
		}

		function revealResult(data) {
			form.style.display = 'none';

			var labelEl = resultBox.querySelector('.hdspin-result__label');
			var codeRowEl = resultBox.querySelector('.hdspin-result__code-row');
			var codeEl = resultBox.querySelector('.hdspin-result__code');
			var copyBtn = resultBox.querySelector('.hdspin-result__copy');
			var copiedEl = resultBox.querySelector('.hdspin-result__copied');
			var expiryEl = resultBox.querySelector('.hdspin-result__expiry');

			copiedEl.textContent = '';

			if (data.code) {
				labelEl.textContent = data.label;
				codeEl.textContent = data.code;
				codeRowEl.style.display = 'flex';
				expiryEl.textContent = data.expiry_date ? (hdspinParams.i18n.use_by + ' ' + data.expiry_date) : '';
			} else {
				labelEl.textContent = hdspinParams.i18n.no_prize;
				codeEl.textContent = '';
				codeRowEl.style.display = 'none';
				expiryEl.textContent = '';
			}

			resultBox.style.display = 'block';
			setCookie(COOKIE_NAME, '1', hdspinParams.frequency_cap_days);

			copyBtn.onclick = function () {
				copyToClipboard(data.code, copiedEl);
			};
		}

		function copyToClipboard(text, feedbackEl) {
			if (!text) {
				return;
			}

			function showCopied() {
				feedbackEl.textContent = hdspinParams.i18n.copied;
				setTimeout(function () {
					feedbackEl.textContent = '';
				}, 2000);
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(showCopied).catch(function () {
					legacyCopy(text);
					showCopied();
				});
			} else {
				legacyCopy(text);
				showCopied();
			}
		}

		function legacyCopy(text) {
			var temp = document.createElement('textarea');
			temp.value = text;
			temp.style.position = 'fixed';
			temp.style.left = '-9999px';
			document.body.appendChild(temp);
			temp.select();
			try {
				document.execCommand('copy');
			} catch (e) {
				// Nothing further we can do -- the code is still visible on screen to copy manually.
			}
			document.body.removeChild(temp);
		}
	});

})();
