/**
 * Language Switcher JavaScript
 *
 * Handles language switching with cookie setting and page navigation.
 *
 * @package WP_Smart_Translation_Engine
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		// Dropdown Language Switcher
		$('.wpste-language-select').on('change', function() {
			var url = $(this).val();
			var lang = $(this).find('option:selected').data('lang');

			if (url && lang) {
				// Set language via AJAX (saves to session + cookie)
				setLanguageSession(lang, url);
			}
		});

		// Flag/Link Language Switcher
		$('.wpste-lang-flag').on('click', function(e) {
			e.preventDefault();

			var url = $(this).attr('href');
			var lang = $(this).data('lang');

			if (url && lang) {
				// Set language via AJAX (saves to session + cookie)
				setLanguageSession(lang, url);
			}
		});

		/**
		 * Set language session via AJAX
		 *
		 * @param {string} lang Language code
		 * @param {string} url URL to redirect to after setting language
		 */
		function setLanguageSession(lang, url) {
			// Set cookie immediately as fallback
			setCookie('wpste_lang', lang, 365);

			// Set session via AJAX
			$.ajax({
				url: wpsteLangSwitcher.ajaxurl,
				type: 'POST',
				data: {
					action: 'wpste_set_language',
					lang: lang
				},
				success: function(response) {
					// Redirect after session is set
					window.location.href = url;
				},
				error: function() {
					// If AJAX fails, still redirect (cookie will work)
					window.location.href = url;
				}
			});
		}

		/**
		 * Set cookie
		 *
		 * @param {string} name Cookie name
		 * @param {string} value Cookie value
		 * @param {number} days Days until expiration
		 */
		function setCookie(name, value, days) {
			var expires = '';
			if (days) {
				var date = new Date();
				date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
				expires = '; expires=' + date.toUTCString();
			}
			document.cookie = name + '=' + (value || '') + expires + '; path=/';
		}

		/**
		 * Get cookie
		 *
		 * @param {string} name Cookie name
		 * @return {string|null} Cookie value
		 */
		function getCookie(name) {
			var nameEQ = name + '=';
			var ca = document.cookie.split(';');
			for (var i = 0; i < ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0) === ' ') {
					c = c.substring(1, c.length);
				}
				if (c.indexOf(nameEQ) === 0) {
					return c.substring(nameEQ.length, c.length);
				}
			}
			return null;
		}

		/**
		 * Erase cookie
		 *
		 * @param {string} name Cookie name
		 */
		function eraseCookie(name) {
			document.cookie = name + '=; Max-Age=-99999999; path=/';
		}

	});

})(jQuery);
