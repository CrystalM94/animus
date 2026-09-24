/* Render verification QR codes client-side from data-animus-qr URLs. */
(function () {
	'use strict';

	if (typeof window.qrcode !== 'function') {
		return;
	}

	document.querySelectorAll('[data-animus-qr]').forEach(function (el) {
		var url = el.getAttribute('data-animus-qr');
		if (!url) {
			return;
		}
		// Type 0 = auto-size, 'M' = medium error correction.
		var qr = window.qrcode(0, 'M');
		qr.addData(url);
		qr.make();
		el.innerHTML = qr.createImgTag(4, 0);
		var img = el.querySelector('img');
		if (img) {
			img.setAttribute('alt', 'QR code linking to ' + url);
		}
	});
})();
