<?php
/**
 * Term Translation Metabox
 *
 * Displays translation options on term edit screens.
 *
 * @package WP_Smart_Translation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get term info
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading term ID from URL for display purposes
$wpste_term_id = isset( $_GET['tag_ID'] ) ? absint( $_GET['tag_ID'] ) : 0;
if ( ! $wpste_term_id ) {
	return;
}

// Get settings
$wpste_settings = get_option( 'wpste_settings', array() );
$wpste_enabled_langs = $wpste_settings['enabled_languages'] ?? array( 'en' );

// Get existing translations
$wpste_translator = new \WPSTE\Core\Taxonomy_Translator();
$wpste_translations = $wpste_translator->get_all_term_translations( $wpste_term_id );

// Language names
$wpste_language_names = array(
	'en' => __( 'English', 'smart-translation-engine' ),
	'uk' => __( 'Ukrainian', 'smart-translation-engine' ),
	'de' => __( 'German', 'smart-translation-engine' ),
	'fr' => __( 'French', 'smart-translation-engine' ),
	'es' => __( 'Spanish', 'smart-translation-engine' ),
	'it' => __( 'Italian', 'smart-translation-engine' ),
	'pt' => __( 'Portuguese', 'smart-translation-engine' ),
	'pl' => __( 'Polish', 'smart-translation-engine' ),
	'ru' => __( 'Russian', 'smart-translation-engine' ),
	'ja' => __( 'Japanese', 'smart-translation-engine' ),
	'zh' => __( 'Chinese', 'smart-translation-engine' ),
	'ar' => __( 'Arabic', 'smart-translation-engine' ),
	'nl' => __( 'Dutch', 'smart-translation-engine' ),
	'sv' => __( 'Swedish', 'smart-translation-engine' ),
	'da' => __( 'Danish', 'smart-translation-engine' ),
	'fi' => __( 'Finnish', 'smart-translation-engine' ),
	'no' => __( 'Norwegian', 'smart-translation-engine' ),
	'cs' => __( 'Czech', 'smart-translation-engine' ),
	'el' => __( 'Greek', 'smart-translation-engine' ),
	'he' => __( 'Hebrew', 'smart-translation-engine' ),
	'hi' => __( 'Hindi', 'smart-translation-engine' ),
	'ko' => __( 'Korean', 'smart-translation-engine' ),
	'tr' => __( 'Turkish', 'smart-translation-engine' ),
);

// Build translations map
$wpste_translations_map = array();
foreach ( $wpste_translations as $wpste_trans ) {
	$wpste_translations_map[ $wpste_trans['lang_code'] ] = $wpste_trans;
}

?>

<tr class="form-field wpste-term-translation-field">
	<th scope="row">
		<label><?php echo esc_html__( 'Translation', 'smart-translation-engine' ); ?></label>
	</th>
	<td>
		<div class="wpste-term-translation-box">
			<h4><?php echo esc_html__( 'Translate this term', 'smart-translation-engine' ); ?></h4>

			<?php if ( count( $wpste_enabled_langs ) > 1 ) : ?>

				<p class="description">
					<?php echo esc_html__( 'Select a language and click "Translate" to create a translation.', 'smart-translation-engine' ); ?>
				</p>

				<div class="wpste-translation-controls">
					<label for="wpste_target_lang"><?php echo esc_html__( 'Target Language:', 'smart-translation-engine' ); ?></label>
					<select id="wpste_target_lang" name="wpste_target_lang">
						<option value=""><?php echo esc_html__( 'Select language', 'smart-translation-engine' ); ?></option>
						<?php foreach ( $wpste_enabled_langs as $wpste_lang ) : ?>
							<?php if ( ! isset( $wpste_translations_map[ $wpste_lang ] ) ) : ?>
								<option value="<?php echo esc_attr( $wpste_lang ); ?>">
									<?php echo esc_html( $wpste_language_names[ $wpste_lang ] ?? strtoupper( $wpste_lang ) ); ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>

					<button type="button" class="button button-primary wpste-translate-term-btn" data-term-id="<?php echo esc_attr( $wpste_term_id ); ?>">
						<?php echo esc_html__( 'Translate', 'smart-translation-engine' ); ?>
					</button>

					<span class="wpste-translation-spinner spinner"></span>
				</div>

				<div class="wpste-translation-message" style="display:none; margin-top: 10px;"></div>

				<?php if ( ! empty( $wpste_translations ) ) : ?>
					<h4 style="margin-top: 20px;"><?php echo esc_html__( 'Existing Translations', 'smart-translation-engine' ); ?></h4>
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Language', 'smart-translation-engine' ); ?></th>
								<th><?php echo esc_html__( 'Translated Name', 'smart-translation-engine' ); ?></th>
								<th><?php echo esc_html__( 'Translated At', 'smart-translation-engine' ); ?></th>
								<th><?php echo esc_html__( 'Actions', 'smart-translation-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $wpste_translations as $wpste_trans ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $wpste_language_names[ $wpste_trans['lang_code'] ] ?? strtoupper( $wpste_trans['lang_code'] ) ); ?></strong></td>
									<td><?php echo esc_html( $wpste_trans['translated_name'] ); ?></td>
									<td><?php echo esc_html( $wpste_trans['translated_at'] ); ?></td>
									<td>
										<button type="button" class="button button-small wpste-delete-term-translation"
												data-term-id="<?php echo esc_attr( $wpste_term_id ); ?>"
												data-lang="<?php echo esc_attr( $wpste_trans['lang_code'] ); ?>">
											<?php echo esc_html__( 'Delete', 'smart-translation-engine' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

			<?php else : ?>
				<p class="description">
					<?php
					printf(
						/* translators: %s: Settings page URL */
						esc_html__( 'Please enable more languages in %s to use translation feature.', 'smart-translation-engine' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wpste-settings' ) ) . '">' . esc_html__( 'Settings', 'smart-translation-engine' ) . '</a>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</td>
</tr>

<style>
.wpste-term-translation-box {
	padding: 15px;
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.wpste-translation-controls {
	margin: 15px 0;
	display: flex;
	align-items: center;
	gap: 10px;
}

.wpste-translation-controls label {
	font-weight: 600;
}

.wpste-translation-controls select {
	min-width: 200px;
}

.wpste-translation-message {
	padding: 10px;
	border-radius: 4px;
}

.wpste-translation-message.success {
	background: #d4edda;
	border: 1px solid #c3e6cb;
	color: #155724;
}

.wpste-translation-message.error {
	background: #f8d7da;
	border: 1px solid #f5c6cb;
	color: #721c24;
}

.wpste-term-translation-box table {
	margin-top: 10px;
}
</style>
