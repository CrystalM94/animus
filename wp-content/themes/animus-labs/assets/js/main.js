/* Animus Labs — storefront interactions */
(function () {
	'use strict';

	var toggle = document.querySelector('[data-nav-toggle]');
	var nav = document.querySelector('.animus-nav');
	if (toggle && nav) {
		toggle.addEventListener('click', function () {
			var open = nav.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	}

	// Sticky header shadow on scroll.
	var header = document.getElementById('site-header');
	if (header) {
		var onScroll = function () {
			header.style.borderBottomColor = window.scrollY > 8 ? 'var(--al-accent-dim)' : 'var(--al-line)';
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}
})();
