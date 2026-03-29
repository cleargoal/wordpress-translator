<?php
/**
 * API Keys Management Page
 *
 * @package WP_Smart_Translation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Get tier manager to check limits
$tier_manager = new \WPSTE\Licensing\Tier_Manager();
$max_keys = $tier_manager->get_max_api_keys();
$current_tier = $tier_manager->get_tier();

// Handle form submissions
$message = '';
$message_type = '';

if ( isset( $_POST['wpste_add_key_nonce'] ) && wp_verify_nonce( $_POST['wpste_add_key_nonce'], 'wpste_add_key' ) ) {
	$provider = sanitize_text_field( $_POST['provider'] );
	$api_key = sanitize_text_field( $_POST['api_key'] );
	$label = sanitize_text_field( $_POST['label'] );
	$quota_limit = ! empty( $_POST['quota_limit'] ) ? absint( $_POST['quota_limit'] ) : null;

	if ( empty( $provider ) || empty( $api_key ) ) {
		$message = __( 'Provider and API Key are required.', 'wp-smart-translation-engine' );
		$message_type = 'error';
	} else {
		// Check tier limit before adding
		$existing_keys_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpste_api_keys" );

		if ( $max_keys !== -1 && $existing_keys_count >= $max_keys ) {
			$message = sprintf(
				__( 'Free tier is limited to %d API key. Please upgrade to add more keys for multi-key rotation.', 'wp-smart-translation-engine' ),
				$max_keys
			);
			$message_type = 'error';
		} else {
			// Encrypt API key before storing
			$encrypted_key = wpste_encrypt_api_key( $api_key );

			$result = $wpdb->insert(
				$wpdb->prefix . 'wpste_api_keys',
				array(
					'provider' => $provider,
					'api_key' => $encrypted_key,
					'label' => $label,
					'quota_limit' => $quota_limit,
					'is_active' => 1,
				),
				array( '%s', '%s', '%s', '%d', '%d' )
			);

			if ( $result ) {
				// Clear transient cache for this provider
				delete_transient( 'wpste_api_keys_' . $provider );
				$message = __( 'API Key added successfully.', 'wp-smart-translation-engine' );
				$message_type = 'success';
			} else {
				$message = __( 'Failed to add API Key.', 'wp-smart-translation-engine' );
				$message_type = 'error';
			}
		}
	}
}

// Handle delete
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['key_id'] ) ) {
	check_admin_referer( 'wpste_delete_key_' . absint( $_GET['key_id'] ) );

	$key_id = absint( $_GET['key_id'] );

	// Get provider before deleting to clear cache
	$key_data = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT provider FROM {$wpdb->prefix}wpste_api_keys WHERE id = %d",
			$key_id
		)
	);

	if ( $key_data ) {
		$deleted = $wpdb->delete(
			$wpdb->prefix . 'wpste_api_keys',
			array( 'id' => $key_id ),
			array( '%d' )
		);

		if ( $deleted ) {
			delete_transient( 'wpste_api_keys_' . $key_data->provider );
			$message = __( 'API Key deleted successfully.', 'wp-smart-translation-engine' );
			$message_type = 'success';
		}
	}
}

// Handle toggle active status
if ( isset( $_GET['action'] ) && $_GET['action'] === 'toggle' && isset( $_GET['key_id'] ) ) {
	check_admin_referer( 'wpste_toggle_key_' . absint( $_GET['key_id'] ) );

	$key_id = absint( $_GET['key_id'] );

	// Get current status and provider
	$key_data = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT is_active, provider FROM {$wpdb->prefix}wpste_api_keys WHERE id = %d",
			$key_id
		)
	);

	if ( $key_data ) {
		$new_status = $key_data->is_active ? 0 : 1;
		$updated = $wpdb->update(
			$wpdb->prefix . 'wpste_api_keys',
			array( 'is_active' => $new_status ),
			array( 'id' => $key_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( $updated !== false ) {
			delete_transient( 'wpste_api_keys_' . $key_data->provider );
			$status_text = $new_status ? __( 'activated', 'wp-smart-translation-engine' ) : __( 'deactivated', 'wp-smart-translation-engine' );
			$message = sprintf( __( 'API Key %s successfully.', 'wp-smart-translation-engine' ), $status_text );
			$message_type = 'success';
		}
	}
}

// Get all API keys grouped by provider
$api_keys = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}wpste_api_keys ORDER BY provider, created_at DESC"
);

// Group by provider
$keys_by_provider = array(
	'deepl' => array(),
	'azure' => array(),
	'aws' => array(),
);

foreach ( $api_keys as $key ) {
	if ( isset( $keys_by_provider[ $key->provider ] ) ) {
		$keys_by_provider[ $key->provider ][] = $key;
	}
}

$provider_names = array(
	'deepl' => 'DeepL',
	'azure' => 'Azure Translator',
	'aws' => 'AWS Translate',
);

// Check if limit reached
$existing_keys_count = count( $api_keys );
$limit_reached = ( $max_keys !== -1 && $existing_keys_count >= $max_keys );

?>

<div class="wrap wpste-api-keys-page">
	<h1><?php echo esc_html__( 'API Keys Management', 'wp-smart-translation-engine' ); ?></h1>

	<?php if ( $message ) : ?>
		<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wpste-api-keys-container">

		<!-- Add New Key Form -->
		<div class="wpste-add-key-section">
			<h2><?php echo esc_html__( 'Add New API Key', 'wp-smart-translation-engine' ); ?></h2>

			<?php if ( $limit_reached ) : ?>
				<div class="notice notice-warning inline">
					<p>
						<strong><?php echo esc_html__( 'Free Tier Limit Reached', 'wp-smart-translation-engine' ); ?></strong><br>
						<?php
						printf(
							esc_html__( 'Free tier is limited to %d API key. Upgrade to a paid plan to add multiple keys for smart quota rotation.', 'wp-smart-translation-engine' ),
							$max_keys
						);
						?>
					</p>
					<p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpste-upgrade' ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Upgrade Plan', 'wp-smart-translation-engine' ); ?></a>
					</p>
				</div>
			<?php else : ?>

			<form method="post" action="" class="wpste-add-key-form">
				<?php wp_nonce_field( 'wpste_add_key', 'wpste_add_key_nonce' ); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="provider"><?php echo esc_html__( 'Provider', 'wp-smart-translation-engine' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<select name="provider" id="provider" required>
									<option value=""><?php echo esc_html__( 'Select Provider', 'wp-smart-translation-engine' ); ?></option>
									<option value="deepl">DeepL</option>
									<option value="azure">Azure Translator</option>
									<option value="aws">AWS Translate</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_key"><?php echo esc_html__( 'API Key', 'wp-smart-translation-engine' ); ?> <span class="required">*</span></label>
							</th>
							<td>
								<input type="text" name="api_key" id="api_key" class="regular-text" required>
								<p class="description"><?php echo esc_html__( 'Your API key will be encrypted before storage.', 'wp-smart-translation-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="label"><?php echo esc_html__( 'Label', 'wp-smart-translation-engine' ); ?></label>
							</th>
							<td>
								<input type="text" name="label" id="label" class="regular-text" placeholder="<?php echo esc_attr__( 'e.g., Account 1, Production Key', 'wp-smart-translation-engine' ); ?>">
								<p class="description"><?php echo esc_html__( 'Optional label to help identify this key.', 'wp-smart-translation-engine' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="quota_limit"><?php echo esc_html__( 'Quota Limit (characters)', 'wp-smart-translation-engine' ); ?></label>
							</th>
							<td>
								<input type="number" name="quota_limit" id="quota_limit" class="regular-text" min="0" placeholder="500000">
								<p class="description"><?php echo esc_html__( 'Maximum characters allowed for this key. DeepL Free: 500,000/month.', 'wp-smart-translation-engine' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php echo esc_html__( 'Add API Key', 'wp-smart-translation-engine' ); ?></button>
				</p>
			</form>

			<?php endif; // End limit_reached check ?>
		</div>

		<hr>

		<!-- Existing Keys -->
		<div class="wpste-existing-keys-section">
			<h2><?php echo esc_html__( 'Existing API Keys', 'wp-smart-translation-engine' ); ?></h2>

			<?php if ( empty( $api_keys ) ) : ?>
				<p><?php echo esc_html__( 'No API keys added yet. Add your first key above to start translating.', 'wp-smart-translation-engine' ); ?></p>
			<?php else : ?>

				<?php foreach ( $keys_by_provider as $provider => $keys ) : ?>
					<?php if ( ! empty( $keys ) ) : ?>
						<div class="wpste-provider-section">
							<h3><?php echo esc_html( $provider_names[ $provider ] ); ?></h3>

							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php echo esc_html__( 'Label', 'wp-smart-translation-engine' ); ?></th>
										<th><?php echo esc_html__( 'API Key', 'wp-smart-translation-engine' ); ?></th>
										<th><?php echo esc_html__( 'Usage', 'wp-smart-translation-engine' ); ?></th>
										<th><?php echo esc_html__( 'Quota Limit', 'wp-smart-translation-engine' ); ?></th>
										<th><?php echo esc_html__( 'Status', 'wp-smart-translation-engine' ); ?></th>
										<th><?php echo esc_html__( 'Actions', 'wp-smart-translation-engine' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $keys as $key ) : ?>
										<tr>
											<td>
												<strong><?php echo esc_html( $key->label ?: __( 'Unnamed', 'wp-smart-translation-engine' ) ); ?></strong>
											</td>
											<td>
												<code class="wpste-api-key-preview">
													<?php
													$decrypted = wpste_decrypt_api_key( $key->api_key );
													if ( strlen( $decrypted ) > 12 ) {
														echo esc_html( substr( $decrypted, 0, 8 ) . '...' . substr( $decrypted, -4 ) );
													} else {
														echo esc_html( $decrypted );
													}
													?>
												</code>
											</td>
											<td>
												<?php
												$usage = number_format( $key->characters_used );
												printf(
													esc_html__( '%s chars', 'wp-smart-translation-engine' ),
													$usage
												);
												?>
											</td>
											<td>
												<?php
												if ( $key->quota_limit ) {
													$limit = number_format( $key->quota_limit );
													$percentage = ( $key->characters_used / $key->quota_limit ) * 100;
													printf(
														esc_html__( '%1$s chars (%2$d%%)', 'wp-smart-translation-engine' ),
														$limit,
														round( $percentage )
													);
												} else {
													echo esc_html__( 'Unlimited', 'wp-smart-translation-engine' );
												}
												?>
											</td>
											<td>
												<?php if ( $key->is_active ) : ?>
													<span class="wpste-status-badge wpste-status-active"><?php echo esc_html__( 'Active', 'wp-smart-translation-engine' ); ?></span>
												<?php else : ?>
													<span class="wpste-status-badge wpste-status-inactive"><?php echo esc_html__( 'Inactive', 'wp-smart-translation-engine' ); ?></span>
												<?php endif; ?>
											</td>
											<td>
												<a href="
												<?php
												echo esc_url(
													wp_nonce_url(
														admin_url( 'admin.php?page=wpste-keys&action=toggle&key_id=' . $key->id ),
														'wpste_toggle_key_' . $key->id
													)
												);
												?>
															" class="button button-small">
													<?php echo $key->is_active ? esc_html__( 'Deactivate', 'wp-smart-translation-engine' ) : esc_html__( 'Activate', 'wp-smart-translation-engine' ); ?>
												</a>

												<a href="
												<?php
												echo esc_url(
													wp_nonce_url(
														admin_url( 'admin.php?page=wpste-keys&action=delete&key_id=' . $key->id ),
														'wpste_delete_key_' . $key->id
													)
												);
												?>
															"
												   class="button button-small button-link-delete"
												   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this API key?', 'wp-smart-translation-engine' ) ); ?>');">
													<?php echo esc_html__( 'Delete', 'wp-smart-translation-engine' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>

			<?php endif; ?>
		</div>

	</div>
</div>

<style>
.wpste-api-keys-page {
	max-width: 1200px;
}

.wpste-api-keys-container {
	background: #fff;
	padding: 20px;
	margin-top: 20px;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.wpste-add-key-section {
	margin-bottom: 30px;
}

.wpste-provider-section {
	margin-bottom: 30px;
}

.wpste-provider-section h3 {
	margin-bottom: 10px;
	padding: 10px;
	background: #f0f0f1;
	border-left: 4px solid #2271b1;
}

.wpste-api-key-preview {
	font-family: monospace;
	background: #f0f0f1;
	padding: 2px 6px;
	border-radius: 3px;
}

.wpste-status-badge {
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: 600;
}

.wpste-status-active {
	background: #00a32a;
	color: #fff;
}

.wpste-status-inactive {
	background: #dba617;
	color: #fff;
}

.required {
	color: #d63638;
}
</style>
