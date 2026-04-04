<?php
/**
 * Language Switcher Class
 *
 * Handles language switching functionality for frontend users.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Language Switcher class
 */
class Language_Switcher {

	/**
	 * Current language
	 *
	 * @var string
	 */
	protected $current_lang;

	/**
	 * Language names
	 *
	 * @var array
	 */
	protected $language_names = array(
		'en' => 'English',
		'uk' => 'Українська',
		'de' => 'Deutsch',
		'fr' => 'Français',
		'es' => 'Español',
		'it' => 'Italiano',
		'pt' => 'Português',
		'pl' => 'Polski',
		'ru' => 'Русский',
		'ja' => '日本語',
		'zh' => '中文',
		'nl' => 'Nederlands',
		'sv' => 'Svenska',
		'da' => 'Dansk',
		'fi' => 'Suomi',
		'no' => 'Norsk',
		'cs' => 'Čeština',
		'el' => 'Ελληνικά',
		'ar' => 'العربية',
		'tr' => 'Türkçe',
		'ko' => '한국어',
		'he' => 'עברית',
		'hi' => 'हिन्दी',
	);

	/**
	 * Language flags (emoji flags)
	 *
	 * @var array
	 */
	protected $language_flags = array(
		'en' => '🇬🇧',
		'uk' => '🇺🇦',
		'de' => '🇩🇪',
		'fr' => '🇫🇷',
		'es' => '🇪🇸',
		'it' => '🇮🇹',
		'pt' => '🇵🇹',
		'pl' => '🇵🇱',
		'ru' => '🇷🇺',
		'ja' => '🇯🇵',
		'zh' => '🇨🇳',
		'nl' => '🇳🇱',
		'sv' => '🇸🇪',
		'da' => '🇩🇰',
		'fi' => '🇫🇮',
		'no' => '🇳🇴',
		'cs' => '🇨🇿',
		'el' => '🇬🇷',
		'ar' => '🇸🇦',
		'tr' => '🇹🇷',
		'ko' => '🇰🇷',
		'he' => '🇮🇱',
		'hi' => '🇮🇳',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->current_lang = $this->get_current_language();

		// Enqueue assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Auto-inject switcher in header (if enabled)
		add_action( 'wp_body_open', array( $this, 'auto_inject_switcher' ) );
	}

	/**
	 * Auto-inject language switcher after body tag
	 */
	public function auto_inject_switcher(): void {
		$settings = get_option( 'wpste_settings', array() );
		$auto_inject = $settings['auto_inject_switcher'] ?? true; // Default: enabled

		if ( ! $auto_inject ) {
			return;
		}

		echo '<div class="wpste-auto-switcher" style="position: fixed; top: 10px; right: 10px; z-index: 9999; background: white; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render() returns escaped HTML
		echo $this->render( array( 'style' => 'flags', 'show_names' => false, 'show_flags' => true ) );
		echo '</div>';
	}

	/**
	 * Enqueue CSS and JS
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'wpste-language-switcher',
			plugins_url( 'css/language-switcher.css', __FILE__ ),
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'wpste-language-switcher',
			plugins_url( 'js/language-switcher.js', __FILE__ ),
			array( 'jquery' ),
			'1.0.0',
			true
		);

		// Localize script with AJAX URL
		wp_localize_script(
			'wpste-language-switcher',
			'wpsteLangSwitcher',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpste_set_language' ),
			)
		);
	}

	/**
	 * Get current language from various sources
	 *
	 * @return string Language code.
	 */
	protected function get_current_language(): string {
		// Start session if needed
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		// Check URL parameter first
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only language detection from URL parameter
		if ( isset( $_GET['lang'] ) ) {
			return sanitize_text_field( wp_unslash( $_GET['lang'] ) );
		}

		// Check session (global switching)
		if ( ! empty( $_SESSION['wpste_lang'] ) ) {
			return sanitize_text_field( $_SESSION['wpste_lang'] );
		}

		// Check cookie
		if ( isset( $_COOKIE['wpste_lang'] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE['wpste_lang'] ) );
		}

		// Check subdirectory in URL
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( preg_match( '#^/([a-z]{2})/#', $uri, $matches ) ) {
			return $matches[1];
		}

		// Default to English
		return 'en';
	}

	/**
	 * Get enabled languages from settings
	 *
	 * @return array Array of language codes.
	 */
	protected function get_enabled_languages(): array {
		$settings = get_option( 'wpste_settings', array() );
		$enabled_languages = $settings['enabled_languages'] ?? array( 'en' );

		// Ensure it's an array
		if ( ! is_array( $enabled_languages ) ) {
			$enabled_languages = array( 'en' );
		}

		return $enabled_languages;
	}

	/**
	 * Generate language URL
	 *
	 * @param string $lang Language code.
	 * @return string URL for the language.
	 */
	protected function get_language_url( string $lang ): string {
		$settings = get_option( 'wpste_settings', array() );
		$url_structure = $settings['url_structure'] ?? 'parameter';

		$http_host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $http_host . $request_uri;

		// Remove existing language parameter or subdirectory
		$current_url = remove_query_arg( 'lang', $current_url );

		switch ( $url_structure ) {
			case 'subdirectory':
				// Remove existing language subdirectory
				$current_url = preg_replace( '#^(https?://[^/]+)/[a-z]{2}/#', '$1/', $current_url );

				// Add new language subdirectory (unless English)
				if ( $lang !== 'en' ) {
					$current_url = preg_replace( '#^(https?://[^/]+)/#', '$1/' . $lang . '/', $current_url );
				}
				break;

			case 'parameter':
			default:
				// Add language parameter (unless English)
				if ( $lang !== 'en' ) {
					$current_url = add_query_arg( 'lang', $lang, $current_url );
				}
				break;
		}

		return $current_url;
	}

	/**
	 * Render language switcher
	 *
	 * @param array $args Switcher arguments.
	 * @return string HTML output.
	 */
	public function render( array $args = array() ): string {
		$defaults = array(
			'style'      => 'dropdown', // 'dropdown' or 'flags'
			'show_flags' => true,
			'show_names' => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$enabled_languages = $this->get_enabled_languages();

		// Don't show if only one language
		if ( count( $enabled_languages ) < 2 ) {
			return '';
		}

		ob_start();

		if ( $args['style'] === 'dropdown' ) {
			$this->render_dropdown( $enabled_languages, $args );
		} else {
			$this->render_flags( $enabled_languages, $args );
		}

		return ob_get_clean();
	}

	/**
	 * Render dropdown style switcher
	 *
	 * @param array $languages Enabled languages.
	 * @param array $args Arguments.
	 */
	protected function render_dropdown( array $languages, array $args ): void {
		?>
		<div class="wpste-language-switcher wpste-dropdown">
			<select class="wpste-language-select" data-current="<?php echo esc_attr( $this->current_lang ); ?>">
				<?php foreach ( $languages as $lang_code ) : ?>
					<?php
					$name = $this->language_names[ $lang_code ] ?? strtoupper( $lang_code );
					$flag = $this->language_flags[ $lang_code ] ?? '';
					$url  = $this->get_language_url( $lang_code );
					?>
					<option
						value="<?php echo esc_attr( $url ); ?>"
						<?php selected( $lang_code, $this->current_lang ); ?>
						data-lang="<?php echo esc_attr( $lang_code ); ?>"
					>
						<?php if ( $args['show_flags'] && $flag ) : ?>
							<?php echo esc_html( $flag ); ?>&nbsp;
						<?php endif; ?>
						<?php if ( $args['show_names'] ) : ?>
							<?php echo esc_html( $name ); ?>
						<?php endif; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Render flags style switcher
	 *
	 * @param array $languages Enabled languages.
	 * @param array $args Arguments.
	 */
	protected function render_flags( array $languages, array $args ): void {
		?>
		<div class="wpste-language-switcher wpste-flags">
			<?php foreach ( $languages as $lang_code ) : ?>
				<?php
				$name = $this->language_names[ $lang_code ] ?? strtoupper( $lang_code );
				$flag = $this->language_flags[ $lang_code ] ?? '';
				$url  = $this->get_language_url( $lang_code );
				$is_current = ( $lang_code === $this->current_lang );
				?>
				<a
					href="<?php echo esc_url( $url ); ?>"
					class="wpste-lang-flag <?php echo $is_current ? 'wpste-current' : ''; ?>"
					data-lang="<?php echo esc_attr( $lang_code ); ?>"
					title="<?php echo esc_attr( $name ); ?>"
				>
					<?php if ( $args['show_flags'] && $flag ) : ?>
						<span class="wpste-flag"><?php echo esc_html( $flag ); ?></span>
					<?php endif; ?>
					<?php if ( $args['show_names'] ) : ?>
						<span class="wpste-lang-name"><?php echo esc_html( $name ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Shortcode handler
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'style'      => 'dropdown',
				'show_flags' => 'yes',
				'show_names' => 'yes',
			),
			$atts,
			'wpste_language_switcher'
		);

		// Convert yes/no to boolean
		$atts['show_flags'] = ( $atts['show_flags'] === 'yes' );
		$atts['show_names'] = ( $atts['show_names'] === 'yes' );

		return $this->render( $atts );
	}
}
