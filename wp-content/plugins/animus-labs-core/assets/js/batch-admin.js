/* Batch admin: COA / SDS document pickers via the WordPress media modal. */
jQuery(function ($) {
	'use strict';

	var frames = {};

	function labelFor(kind) {
		return kind === 'coa' ? animusBatchAdmin.chooseCoa : animusBatchAdmin.chooseSds;
	}

	$('[data-animus-upload]').on('click', function (e) {
		e.preventDefault();
		var kind = $(this).data('animus-upload');

		if (!frames[kind]) {
			frames[kind] = wp.media({
				title: labelFor(kind),
				button: { text: animusBatchAdmin.use },
				library: { type: 'application/pdf' },
				multiple: false
			});

			frames[kind].on('select', function () {
				var doc = frames[kind].state().get('selection').first().toJSON();
				$('#animus_batch_' + kind + '_id').val(doc.id);
				$('[data-animus-name="' + kind + '"]').text(doc.title || doc.filename);
			});
		}

		frames[kind].open();
	});

	$('.animus-doc-clear').on('click', function (e) {
		e.preventDefault();
		var kind = $(this).data('animus-clear');
		$('#animus_batch_' + kind + '_id').val('');
		$('[data-animus-name="' + kind + '"]').text('—');
	});
});
