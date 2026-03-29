/**
 * Taxonomy Translation Admin JavaScript
 *
 * Handles AJAX translation requests for taxonomy terms.
 *
 * @package WP_Smart_Translation_Engine
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		// Translate Term Button
		$('.wpste-translate-term-btn').on('click', function() {
			var $button = $(this);
			var $spinner = $('.wpste-translation-spinner');
			var $message = $('.wpste-translation-message');
			var $select = $('#wpste_target_lang');

			var termId = $button.data('term-id');
			var targetLang = $select.val();

			if (!targetLang) {
				alert(wpste_taxonomy.strings.select_language);
				return;
			}

			// Disable button and show spinner
			$button.prop('disabled', true);
			$spinner.addClass('is-active');
			$message.hide();

			// AJAX request
			$.ajax({
				url: wpste_taxonomy.ajax_url,
				type: 'POST',
				data: {
					action: 'wpste_translate_term',
					nonce: wpste_taxonomy.nonce,
					term_id: termId,
					target_lang: targetLang
				},
				success: function(response) {
					if (response.success) {
						$message
							.removeClass('error')
							.addClass('success')
							.html('<strong>' + wpste_taxonomy.strings.success + '</strong> ' + response.data.message)
							.fadeIn();

						// Remove translated language from select
						$select.find('option[value="' + targetLang + '"]').remove();

						// Reload page after 2 seconds to show new translation
						setTimeout(function() {
							location.reload();
						}, 2000);
					} else {
						$message
							.removeClass('success')
							.addClass('error')
							.html('<strong>' + wpste_taxonomy.strings.error + '</strong> ' + response.data.message)
							.fadeIn();
					}
				},
				error: function(xhr, status, error) {
					$message
						.removeClass('success')
						.addClass('error')
						.html('<strong>' + wpste_taxonomy.strings.error + '</strong> ' + error)
						.fadeIn();
				},
				complete: function() {
					$button.prop('disabled', false);
					$spinner.removeClass('is-active');
				}
			});
		});

		// Delete Term Translation Button
		$(document).on('click', '.wpste-delete-term-translation', function() {
			var $button = $(this);
			var termId = $button.data('term-id');
			var lang = $button.data('lang');

			if (!confirm(wpste_taxonomy.strings.confirm_delete)) {
				return;
			}

			$button.prop('disabled', true).text(wpste_taxonomy.strings.deleting);

			// AJAX request
			$.ajax({
				url: wpste_taxonomy.ajax_url,
				type: 'POST',
				data: {
					action: 'wpste_delete_term_translation',
					nonce: wpste_taxonomy.nonce,
					term_id: termId,
					lang: lang
				},
				success: function(response) {
					if (response.success) {
						// Remove row from table
						$button.closest('tr').fadeOut(function() {
							$(this).remove();
						});

						// Reload page to update select options
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						alert(wpste_taxonomy.strings.delete_error + ' ' + response.data.message);
						$button.prop('disabled', false).text(wpste_taxonomy.strings.delete);
					}
				},
				error: function() {
					alert(wpste_taxonomy.strings.delete_error);
					$button.prop('disabled', false).text(wpste_taxonomy.strings.delete);
				}
			});
		});

	});

})(jQuery);
