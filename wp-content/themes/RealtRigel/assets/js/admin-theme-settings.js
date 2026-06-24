(function ($) {
	'use strict';

	$(function () {
		$('[data-theme-icon-field]').each(function () {
			var field = $(this);
			var input = field.find('[data-theme-icon-input]');
			var preview = field.find('[data-theme-icon-preview]');
			var removeButton = field.find('[data-theme-icon-remove]');
			var frame = null;

			field.find('[data-theme-icon-upload]').on('click', function () {
				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: (window.RRTThemeSettings && window.RRTThemeSettings.title) || 'Select icon',
					button: {
						text: (window.RRTThemeSettings && window.RRTThemeSettings.buttonLabel) || 'Use icon'
					},
					multiple: false,
					library: {
						type: 'image'
					}
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var previewUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

					input.val(attachment.id);
					preview.attr('src', previewUrl).show();
					removeButton.prop('hidden', false);
				});

				frame.open();
			});

			removeButton.on('click', function () {
				input.val('');
				preview.attr('src', '').hide();
				removeButton.prop('hidden', true);
			});
		});
	});
}(jQuery));
