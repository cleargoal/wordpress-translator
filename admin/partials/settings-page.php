<?php
/**
 * Settings Page Template
 *
 * @package WP_Smart_Translation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle form submission
if ( isset( $_POST['wpste_settings_submit'] ) && check_admin_referer( 'wpste_settings', 'wpste_settings_nonce' ) ) {
	// Handle enabled_languages (array from checkboxes)
	$wpste_enabled_languages = isset( wp_unslash( $_POST['enabled_languages'] ) ) && is_array( wp_unslash( $_POST['enabled_languages'] ) )
		? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_languages'] ) )
		: array( 'en' );

	$wpste_settings = array(
		'default_language'    => sanitize_text_field( wp_unslash( $_POST['default_language'] ) ?? 'en' ),
		'enabled_languages'   => $wpste_enabled_languages,
		'primary_provider'    => sanitize_text_field( wp_unslash( $_POST['primary_provider'] ) ?? 'deepl' ),
		'fallback_providers'  => array_map( 'sanitize_text_field', wp_unslash( $_POST['fallback_providers'] ) ?? array() ),
		'post_types'          => array_map( 'sanitize_text_field', wp_unslash( $_POST['post_types'] ) ?? array( 'post', 'page' ) ),
		'url_structure'       => sanitize_text_field( wp_unslash( $_POST['url_structure'] ) ?? 'subdirectory' ),
		'cache_ttl'           => absint( $_POST['cache_ttl'] ?? 300 ),
	);

	update_option( 'wpste_settings', $wpste_settings );

	echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'smart-translation-engine' ) . '</p></div>';
}

$wpste_settings = get_option( 'wpste_settings', array() );
$wpste_factory = new \WPSTE\Core\Provider_Factory();
$wpste_available_providers = $wpste_factory->get_registered_providers();

// Check current tier
$wpste_license = get_option( 'wpste_license', array( 'tier' => 'free' ) );
$wpste_current_tier = $wpste_license['tier'] ?? 'free';
$wpste_show_fallback = ( $wpste_current_tier !== 'free' ); // Hide fallback providers for free tier

?>

<div class="wrap">
	<h1><?php echo esc_html__( 'WP Smart Translation Engine - Settings', 'smart-translation-engine' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'wpste_settings', 'wpste_settings_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Primary Provider', 'smart-translation-engine' ); ?></th>
				<td>
					<select name="primary_provider">
						<?php foreach ( $wpste_available_providers as $wpste_provider ) : ?>
							<option value="<?php echo esc_attr( $wpste_provider ); ?>" <?php selected( $wpste_settings['primary_provider'] ?? 'deepl', $wpste_provider ); ?>>
								<?php echo esc_html( ucfirst( $wpste_provider ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Free tier: Choose 1 translation provider (DeepL, Azure, or AWS)', 'smart-translation-engine' ); ?>
					</p>
				</td>
			</tr>

			<?php if ( $wpste_show_fallback ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Fallback Providers', 'smart-translation-engine' ); ?></th>
				<td>
					<?php foreach ( $wpste_available_providers as $wpste_provider ) : ?>
						<label>
							<input type="checkbox" name="fallback_providers[]" value="<?php echo esc_attr( $wpste_provider ); ?>"
								<?php checked( in_array( $wpste_provider, $wpste_settings['fallback_providers'] ?? array() ) ); ?>>
							<?php echo esc_html( ucfirst( $wpste_provider ) ); ?>
						</label><br>
					<?php endforeach; ?>
					<p class="description">
						<?php esc_html_e( 'If primary provider fails, these providers will be tried in order.', 'smart-translation-engine' ); ?>
					</p>
				</td>
			</tr>
			<?php endif; ?>

			<tr>
				<th scope="row"><?php esc_html_e( 'Languages', 'smart-translation-engine' ); ?></th>
				<td>
					<?php
					$wpste_all_languages = array(
						'en' => 'English',
						'uk' => 'Ukrainian',
						'de' => 'German',
						'fr' => 'French',
						'es' => 'Spanish',
						'it' => 'Italian',
						'pt' => 'Portuguese',
						'pl' => 'Polish',
						'ru' => 'Russian',
						'ja' => 'Japanese',
						'zh' => 'Chinese',
						'ar' => 'Arabic',
						'nl' => 'Dutch',
						'sv' => 'Swedish',
						'da' => 'Danish',
						'fi' => 'Finnish',
						'no' => 'Norwegian',
						'cs' => 'Czech',
						'el' => 'Greek',
						'he' => 'Hebrew',
						'hi' => 'Hindi',
						'ko' => 'Korean',
						'tr' => 'Turkish',
					);
					$wpste_enabled = $wpste_settings['enabled_languages'] ?? array( 'en', 'uk', 'de' );
					$wpste_default = $wpste_settings['default_language'] ?? 'en';
					?>
					<div id="wpste-language-selector">
						<table class="widefat" style="max-width: 500px;">
							<thead>
								<tr>
									<th style="width: 50px;"><?php esc_html_e( 'Enable', 'smart-translation-engine' ); ?></th>
									<th><?php esc_html_e( 'Language', 'smart-translation-engine' ); ?></th>
									<th style="width: 80px; text-align: center;"><?php esc_html_e( 'Default', 'smart-translation-engine' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $wpste_all_languages as $wpste_code => $wpste_name ) : ?>
									<tr>
										<td>
											<input type="checkbox"
												   name="enabled_languages[]"
												   value="<?php echo esc_attr( $wpste_code ); ?>"
												   id="lang_<?php echo esc_attr( $wpste_code ); ?>"
												   class="wpste-language-checkbox"
												   data-lang="<?php echo esc_attr( $wpste_code ); ?>"
												   <?php checked( in_array( $wpste_code, $wpste_enabled ) ); ?>>
										</td>
										<td>
											<label for="lang_<?php echo esc_attr( $wpste_code ); ?>">
												<?php echo esc_html( $wpste_name . ' (' . strtoupper( $wpste_code ) . ')' ); ?>
											</label>
										</td>
										<td style="text-align: center;">
											<input type="radio"
												   name="default_language"
												   value="<?php echo esc_attr( $wpste_code ); ?>"
												   class="wpste-default-radio"
												   data-lang="<?php echo esc_attr( $wpste_code ); ?>"
												   <?php checked( $wpste_default, $wpste_code ); ?>>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<p class="description">
						<span id="wpste-lang-counter">
							<strong><?php echo count( $wpste_enabled ); ?> of 3</strong> languages selected (Free tier limit: 3)
						</span>
					</p>
					<p class="description">
						<?php esc_html_e( 'Select which languages to enable, and choose one as the default (source) language.', 'smart-translation-engine' ); ?>
					</p>
					<p class="description wpste-error" id="wpste-lang-limit-error" style="color: #dc3232; display: none;">
						<?php esc_html_e( 'Free tier allows maximum 3 languages. Please deselect some languages or upgrade.', 'smart-translation-engine' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Post Types', 'smart-translation-engine' ); ?></th>
				<td>
					<?php foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) : ?>
						<label>
							<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>"
								<?php checked( in_array( $post_type->name, $wpste_settings['post_types'] ?? array( 'post', 'page' ) ) ); ?>>
							<?php echo esc_html( $post_type->label ); ?>
						</label><br>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save Settings', 'smart-translation-engine' ), 'primary', 'wpste_settings_submit' ); ?>
	</form>

	<!-- Language Switcher Documentation -->
	<div class="wrap" style="margin-top: 30px;">
		<h2><?php esc_html_e( 'Language Switcher', 'smart-translation-engine' ); ?></h2>
		<div class="card">
			<h3><?php esc_html_e( 'How to Add Language Switcher to Your Site', 'smart-translation-engine' ); ?></h3>

			<h4><?php esc_html_e( 'Method 1: Using Gutenberg Block', 'smart-translation-engine' ); ?></h4>
			<ol>
				<li><?php esc_html_e( 'Edit any post, page, or template in the block editor', 'smart-translation-engine' ); ?></li>
				<li><?php esc_html_e( 'Click the + (Add block) button', 'smart-translation-engine' ); ?></li>
				<li><?php esc_html_e( 'Search for "Language Switcher"', 'smart-translation-engine' ); ?></li>
				<li><?php esc_html_e( 'Insert the block and configure the display style', 'smart-translation-engine' ); ?></li>
			</ol>

			<h4><?php esc_html_e( 'Method 2: Using Shortcode', 'smart-translation-engine' ); ?></h4>
			<p><?php esc_html_e( 'Add this shortcode anywhere in your content:', 'smart-translation-engine' ); ?></p>
			<p>
				<code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; font-size: 14px;">[wpste_language_switcher]</code>
				<button type="button" class="button button-small" onclick="navigator.clipboard.writeText('[wpste_language_switcher]')">
					<?php esc_html_e( 'Copy', 'smart-translation-engine' ); ?>
				</button>
			</p>

			<h4><?php esc_html_e( 'Shortcode Options:', 'smart-translation-engine' ); ?></h4>
			<ul>
				<li>
					<code style="background: #f0f0f1; padding: 3px 8px; border-radius: 3px;">style="dropdown"</code> - <?php esc_html_e( 'Display as dropdown (default)', 'smart-translation-engine' ); ?>
				</li>
				<li>
					<code style="background: #f0f0f1; padding: 3px 8px; border-radius: 3px;">style="flags"</code> - <?php esc_html_e( 'Display as flag links', 'smart-translation-engine' ); ?>
				</li>
				<li>
					<code style="background: #f0f0f1; padding: 3px 8px; border-radius: 3px;">show_flags="yes"</code> - <?php esc_html_e( 'Show emoji flags (default: yes)', 'smart-translation-engine' ); ?>
				</li>
				<li>
					<code style="background: #f0f0f1; padding: 3px 8px; border-radius: 3px;">show_names="yes"</code> - <?php esc_html_e( 'Show language names (default: yes)', 'smart-translation-engine' ); ?>
				</li>
			</ul>

			<h4><?php esc_html_e( 'Examples:', 'smart-translation-engine' ); ?></h4>
			<p>
				<code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0;">[wpste_language_switcher style="dropdown"]</code>
			</p>
			<p>
				<code style="background: #f0f0f1; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0;">[wpste_language_switcher style="flags" show_names="no"]</code>
			</p>

			<h4><?php esc_html_e( 'Method 3: For Classic Themes (with Widget Areas)', 'smart-translation-engine' ); ?></h4>
			<ol>
				<li><?php esc_html_e( 'Go to Appearance → Widgets', 'smart-translation-engine' ); ?></li>
				<li><?php esc_html_e( 'Drag "Language Switcher" widget to your sidebar', 'smart-translation-engine' ); ?></li>
				<li><?php esc_html_e( 'Configure and save', 'smart-translation-engine' ); ?></li>
			</ol>

			<h4><?php esc_html_e( 'Method 4: In Theme Template Files (PHP)', 'smart-translation-engine' ); ?></h4>
			<pre style="background: #f0f0f1; padding: 15px; border-radius: 3px; overflow-x: auto;"><code>&lt;?php
if (class_exists('WPSTE\Frontend\Language_Switcher')) {
    $switcher = new WPSTE\Frontend\Language_Switcher();
    echo $switcher->render(array(
        'style' => 'dropdown',
        'show_flags' => true,
        'show_names' => true
    ));
}
?&gt;</code></pre>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var maxLanguages = 3; // Free tier limit
	var $checkboxes = $('.wpste-language-checkbox');
	var $radios = $('.wpste-default-radio');
	var $counter = $('#wpste-lang-counter');
	var $error = $('#wpste-lang-limit-error');
	var $submitBtn = $('input[type="submit"][name="wpste_settings_submit"]');

	// Debug: Log initial state
	console.log('WPSTE: Found ' + $checkboxes.length + ' language checkboxes');
	console.log('WPSTE: Found submit button:', $submitBtn.length > 0);
	console.log('WPSTE: Initially checked:', $checkboxes.filter(':checked').length);

	function updateLanguageSelection() {
		var checked = $checkboxes.filter(':checked').length;
		$counter.html('<strong>' + checked + ' of ' + maxLanguages + '</strong> languages selected (Free tier limit: ' + maxLanguages + ')');

		if (checked > maxLanguages) {
			$error.show();
			$submitBtn.prop('disabled', true).addClass('disabled');
			$counter.css('color', '#dc3232');
			$counter.find('strong').css('color', '#dc3232');
		} else if (checked === 0) {
			$error.hide();
			$submitBtn.prop('disabled', true).addClass('disabled');
			$counter.html('<strong style="color: #dc3232;">Please select at least 1 language</strong>');
		} else {
			$error.hide();
			$submitBtn.prop('disabled', false).removeClass('disabled');
			$counter.css('color', '#000');
			$counter.find('strong').css('color', checked === maxLanguages ? '#d63638' : '#2271b1');
		}

		// Update default language radio states
		var defaultLang = $radios.filter(':checked').data('lang');
		$radios.each(function() {
			var lang = $(this).data('lang');
			var checkbox = $checkboxes.filter('[data-lang="' + lang + '"]');

			// Disable radio if language is not enabled
			if (!checkbox.is(':checked')) {
				$(this).prop('disabled', true);
			} else {
				$(this).prop('disabled', false);
			}
		});

		// If default language is unchecked, select first checked language as default
		var defaultChecked = $checkboxes.filter('[data-lang="' + defaultLang + '"]').is(':checked');
		if (!defaultChecked && checked > 0) {
			var firstChecked = $checkboxes.filter(':checked').first().data('lang');
			$radios.filter('[data-lang="' + firstChecked + '"]').prop('checked', true);
		}
	}

	// When a radio is selected, ensure its checkbox is checked
	$radios.on('change', function() {
		var lang = $(this).data('lang');
		$checkboxes.filter('[data-lang="' + lang + '"]').prop('checked', true);
		updateLanguageSelection();
	});

	// When checkbox changes
	$checkboxes.on('change', function() {
		var lang = $(this).data('lang');

		// If unchecking the default language, prevent it
		var isDefault = $radios.filter('[data-lang="' + lang + '"]').is(':checked');
		if (isDefault && !$(this).is(':checked')) {
			alert('Cannot disable the default language. Please select a different default language first.');
			$(this).prop('checked', true);
			return;
		}

		updateLanguageSelection();
	});

	// Initial check - run after a short delay to ensure DOM is fully ready
	setTimeout(function() {
		console.log('WPSTE: Running initial validation...');
		updateLanguageSelection();
	}, 100);

	// Also check on window load as backup
	$(window).on('load', function() {
		updateLanguageSelection();
	});
});
</script>
