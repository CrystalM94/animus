/* Animus Labs — restricted-access gate + cookie notice.
   Visibility is decided client-side so pages stay fully cacheable. */
(function () {
	'use strict';

	var GATE_KEY = 'animusGateAccepted';
	var COOKIE_KEY = 'animusCookieAck';

	function store(key, value) {
		try {
			window.localStorage.setItem(key, value);
		} catch (e) {
			// Storage unavailable (private mode); the notice simply reappears.
		}
	}

	function read(key) {
		try {
			return window.localStorage.getItem(key);
		} catch (e) {
			return null;
		}
	}

	// ---- Restricted access gate ----
	var gate = document.querySelector('[data-animus-gate]');
	if (gate) {
		if (read(GATE_KEY) !== 'yes') {
			gate.hidden = false;
			document.body.style.overflow = 'hidden';
			var accept = gate.querySelector('[data-animus-gate-accept]');
			if (accept) {
				accept.focus();
				accept.addEventListener('click', function () {
					store(GATE_KEY, 'yes');
					gate.hidden = true;
					document.body.style.overflow = '';
				});
			}
		}
	}

	// ---- Cookie notice ----
	var cookie = document.querySelector('[data-animus-cookie]');
	if (cookie && read(COOKIE_KEY) !== 'yes') {
		cookie.hidden = false;
		cookie.querySelectorAll('[data-animus-cookie-dismiss]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				store(COOKIE_KEY, 'yes');
				cookie.hidden = true;
			});
		});
	}
})();
