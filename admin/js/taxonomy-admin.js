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

		// Check for success message after page reload
		var successMessage = sessionStorage.getItem('wpste_translation_success');
		if (successMessage) {
			// Clear the stored message
			sessionStorage.removeItem('wpste_translation_success');

			// Show success message
			var $message = $('.wpste-translation-message');
			if ($message.length) {
				$message
					.removeClass('error')
					.addClass('success')
					.html('<strong>' + wpste_taxonomy.strings.success + '</strong> ' + successMessage)
					.fadeIn();
			}
		}

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
						// Store success message in sessionStorage
						sessionStorage.setItem('wpste_translation_success', response.data.message);

						// Reload page immediately to show new translation
						location.reload();
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
