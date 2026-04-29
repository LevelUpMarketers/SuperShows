(function ($) {
	'use strict';

	function parseIds(value) {
		return String(value || '')
			.split(',')
			.map(function (part) {
				return parseInt(part.trim(), 10);
			})
			.filter(function (id) {
				return Number.isInteger(id) && id > 0;
			});
	}

	function getPreviewContainer(input) {
		return $('.supershows-media-preview[data-preview-for="' + input.attr('id') + '"]');
	}

	function renderMediaPreview(input) {
		const container = getPreviewContainer(input);
		if (!container.length) {
			return;
		}

		container.empty();
		const ids = parseIds(input.val());
		if (!ids.length || typeof wp === 'undefined' || !wp.media) {
			return;
		}

		ids.forEach(function (id) {
			const attachment = wp.media.attachment(id);
			attachment.fetch().done(function () {
				const data = attachment.toJSON() || {};
				const imageUrl =
					(data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url) ||
					data.url ||
					'';

				const item = $('<div/>', { class: 'supershows-media-item', 'data-id': id });
				if (imageUrl) {
					item.append($('<img/>', { src: imageUrl, alt: '' }));
				} else {
					item.append($('<div/>', { class: 'supershows-media-item-fallback', text: '#' + id }));
				}

				item.append(
					$('<button/>', {
						type: 'button',
						class: 'supershows-media-remove',
						'data-target': input.attr('id'),
						'data-id': id,
						text: '×',
						'aria-label': 'Remove image'
					})
				);
				container.append(item);
			});
		});
	}

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

		frame.on('open', function () {
			const ids = parseIds(target.val());
			if (!ids.length) {
				return;
			}

			const selection = frame.state().get('selection');
			ids.forEach(function (id) {
				const attachment = wp.media.attachment(id);
				attachment.fetch();
				if (attachment) {
					selection.add(attachment);
				}
			});
		});

		frame.on('select', function () {
			const selection = frame.state().get('selection');
			const ids = [];

			selection.each(function (attachment) {
				ids.push(attachment.get('id'));
			});

			target.val(ids.join(','));
			renderMediaPreview(target);
		});

		frame.open();
	});

	$(document).on('click', '.supershows-media-remove', function (event) {
		event.preventDefault();

		const button = $(this);
		const target = $('#' + button.data('target'));
		if (!target.length) {
			return;
		}

		const removeId = parseInt(button.data('id'), 10);
		const updatedIds = parseIds(target.val()).filter(function (id) {
			return id !== removeId;
		});
		target.val(updatedIds.join(','));
		renderMediaPreview(target);
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

	$('.supershows-media-input').each(function () {
		renderMediaPreview($(this));
	});
})(jQuery);
