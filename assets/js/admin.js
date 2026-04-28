(function ($) {
	'use strict';

	$(document).on('click', '.supershows-media-button', function (event) {
		event.preventDefault();

		const button = $(this);
		const targetId = button.data('target');
		const multiple = String(button.data('multiple')) === '1';
		const target = $('#' + targetId);

		if (!target.length || typeof wp === 'undefined' || !wp.media) {
			return;
		}

		const frame = wp.media({
			title: multiple ? 'Select Images' : 'Select Image',
			button: {
				text: multiple ? 'Use selected images' : 'Use this image'
			},
			multiple: multiple,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			const selection = frame.state().get('selection');
			const ids = [];

			selection.each(function (attachment) {
				ids.push(attachment.get('id'));
			});

			target.val(ids.join(','));
		});

		frame.open();
	});
})(jQuery);
