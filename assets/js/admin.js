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

	$(document).on('click', '.supershows-accordion-toggle', function (event) {
		event.preventDefault();

		const toggle = $(this);
		const sectionId = toggle.attr('aria-controls');
		const panel = $('#' + sectionId);
		if (!panel.length) {
			return;
		}

		const isExpanded = toggle.attr('aria-expanded') === 'true';
		toggle.attr('aria-expanded', isExpanded ? 'false' : 'true');
		panel.attr('hidden', isExpanded);
	});
})(jQuery);
