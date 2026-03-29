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
$term_id = isset( $_GET['tag_ID'] ) ? absint( $_GET['tag_ID'] ) : 0;
if ( ! $term_id ) {
	return;
}

// Get settings
$settings = get_option( 'wpste_settings', array() );
$enabled_langs = $settings['enabled_languages'] ?? array( 'en' );

// Get existing translations
$translator = new \WPSTE\Core\Taxonomy_Translator();
$translations = $translator->get_all_term_translations( $term_id );

// Language names
$language_names = array(
	'en' => __( 'English', 'wp-smart-translation-engine' ),
	'uk' => __( 'Ukrainian', 'wp-smart-translation-engine' ),
	'de' => __( 'German', 'wp-smart-translation-engine' ),
	'fr' => __( 'French', 'wp-smart-translation-engine' ),
	'es' => __( 'Spanish', 'wp-smart-translation-engine' ),
	'it' => __( 'Italian', 'wp-smart-translation-engine' ),
	'pt' => __( 'Portuguese', 'wp-smart-translation-engine' ),
	'pl' => __( 'Polish', 'wp-smart-translation-engine' ),
	'ru' => __( 'Russian', 'wp-smart-translation-engine' ),
	'ja' => __( 'Japanese', 'wp-smart-translation-engine' ),
	'zh' => __( 'Chinese', 'wp-smart-translation-engine' ),
	'ar' => __( 'Arabic', 'wp-smart-translation-engine' ),
	'nl' => __( 'Dutch', 'wp-smart-translation-engine' ),
	'sv' => __( 'Swedish', 'wp-smart-translation-engine' ),
	'da' => __( 'Danish', 'wp-smart-translation-engine' ),
	'fi' => __( 'Finnish', 'wp-smart-translation-engine' ),
	'no' => __( 'Norwegian', 'wp-smart-translation-engine' ),
	'cs' => __( 'Czech', 'wp-smart-translation-engine' ),
	'el' => __( 'Greek', 'wp-smart-translation-engine' ),
	'he' => __( 'Hebrew', 'wp-smart-translation-engine' ),
	'hi' => __( 'Hindi', 'wp-smart-translation-engine' ),
	'ko' => __( 'Korean', 'wp-smart-translation-engine' ),
	'tr' => __( 'Turkish', 'wp-smart-translation-engine' ),
);

// Build translations map
$translations_map = array();
foreach ( $translations as $trans ) {
	$translations_map[ $trans['lang_code'] ] = $trans;
}

?>

<tr class="form-field wpste-term-translation-field">
	<th scope="row">
		<label><?php echo esc_html__( 'Translation', 'wp-smart-translation-engine' ); ?></label>
	</th>
	<td>
		<div class="wpste-term-translation-box">
			<h4><?php echo esc_html__( 'Translate this term', 'wp-smart-translation-engine' ); ?></h4>

			<?php if ( count( $enabled_langs ) > 1 ) : ?>

				<p class="description">
					<?php echo esc_html__( 'Select a language and click "Translate" to create a translation.', 'wp-smart-translation-engine' ); ?>
				</p>

				<div class="wpste-translation-controls">
					<label for="wpste_target_lang"><?php echo esc_html__( 'Target Language:', 'wp-smart-translation-engine' ); ?></label>
					<select id="wpste_target_lang" name="wpste_target_lang">
						<option value=""><?php echo esc_html__( 'Select language', 'wp-smart-translation-engine' ); ?></option>
						<?php foreach ( $enabled_langs as $lang ) : ?>
							<?php if ( ! isset( $translations_map[ $lang ] ) ) : ?>
								<option value="<?php echo esc_attr( $lang ); ?>">
									<?php echo esc_html( $language_names[ $lang ] ?? strtoupper( $lang ) ); ?>
								</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>

					<button type="button" class="button button-primary wpste-translate-term-btn" data-term-id="<?php echo esc_attr( $term_id ); ?>">
						<?php echo esc_html__( 'Translate', 'wp-smart-translation-engine' ); ?>
					</button>

					<span class="wpste-translation-spinner spinner"></span>
				</div>

				<div class="wpste-translation-message" style="display:none; margin-top: 10px;"></div>

				<?php if ( ! empty( $translations ) ) : ?>
					<h4 style="margin-top: 20px;"><?php echo esc_html__( 'Existing Translations', 'wp-smart-translation-engine' ); ?></h4>
					<table class="widefat fixed striped">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Language', 'wp-smart-translation-engine' ); ?></th>
								<th><?php echo esc_html__( 'Translated Name', 'wp-smart-translation-engine' ); ?></th>
								<th><?php echo esc_html__( 'Translated At', 'wp-smart-translation-engine' ); ?></th>
								<th><?php echo esc_html__( 'Actions', 'wp-smart-translation-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $translations as $trans ) : ?>
								<tr>
									<td><strong><?php echo esc_html( $language_names[ $trans['lang_code'] ] ?? strtoupper( $trans['lang_code'] ) ); ?></strong></td>
									<td><?php echo esc_html( $trans['translated_name'] ); ?></td>
									<td><?php echo esc_html( $trans['translated_at'] ); ?></td>
									<td>
										<button type="button" class="button button-small wpste-delete-term-translation"
												data-term-id="<?php echo esc_attr( $term_id ); ?>"
												data-lang="<?php echo esc_attr( $trans['lang_code'] ); ?>">
											<?php echo esc_html__( 'Delete', 'wp-smart-translation-engine' ); ?>
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
						esc_html__( 'Please enable more languages in %s to use translation feature.', 'wp-smart-translation-engine' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wpste-settings' ) ) . '">' . esc_html__( 'Settings', 'wp-smart-translation-engine' ) . '</a>'
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
