<?php
/**
 * Upgrade/Pricing Page
 *
 * @package WP_Smart_Translation_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current tier
$wpste_license = get_option( 'wpste_license', array( 'tier' => 'free' ) );
$wpste_current_tier = $wpste_license['tier'] ?? 'free';

// Define all tiers with features (prices: monthly / yearly with discount - TBD by user)
$wpste_tiers = array(
	'free'       => array(
		'name'          => __( 'Free', 'smart-translation-engine' ),
		'price_monthly' => 0,
		'price_yearly'  => 0,
		'price_display' => __( 'Free Forever', 'smart-translation-engine' ),
		'languages'     => 3,
		'api_keys'      => 1,
		'features'      => array(
			__( '3 languages', 'smart-translation-engine' ),
			__( '1 API key (no rotation)', 'smart-translation-engine' ),
			__( '1 provider choice', 'smart-translation-engine' ),
			__( 'Post & page translation', 'smart-translation-engine' ),
			__( 'Community support', 'smart-translation-engine' ),
		),
		'cta'           => '',
		'recommended'   => false,
	),
	'starter'    => array(
		'name'          => __( 'Starter', 'smart-translation-engine' ),
		'price_monthly' => 3,
		'price_yearly'  => 27, // Save 3 months (25% discount)
		'savings_text'  => __( 'Save 3 months!', 'smart-translation-engine' ),
		'price_display' => '$3/month',
		'languages'     => 4,
		'api_keys'      => 2,
		'features'      => array(
			__( '4 languages', 'smart-translation-engine' ),
			__( 'Up to 2 API keys with rotation', 'smart-translation-engine' ),
			__( 'Multiple providers', 'smart-translation-engine' ),
			__( 'Post, page & custom post types', 'smart-translation-engine' ),
			__( 'Priority support', 'smart-translation-engine' ),
		),
		'cta'           => __( 'Buy Starter', 'smart-translation-engine' ),
		'recommended'   => false,
	),
	'basic'      => array(
		'name'          => __( 'Basic', 'smart-translation-engine' ),
		'price_monthly' => 5,
		'price_yearly'  => 45, // Save 3 months (25% discount)
		'savings_text'  => __( 'Save 3 months!', 'smart-translation-engine' ),
		'price_display' => '$5/month',
		'languages'     => 5,
		'api_keys'      => 3,
		'features'      => array(
			__( '5 languages', 'smart-translation-engine' ),
			__( 'Up to 3 API keys with smart rotation', 'smart-translation-engine' ),
			__( 'All providers with fallback', 'smart-translation-engine' ),
			__( 'Menu & widget translation', 'smart-translation-engine' ),
			__( 'Basic SEO support', 'smart-translation-engine' ),
		),
		'cta'           => __( 'Buy Basic', 'smart-translation-engine' ),
		'recommended'   => false,
	),
	'plus'       => array(
		'name'          => __( 'Plus', 'smart-translation-engine' ),
		'price_monthly' => 8,
		'price_yearly'  => 72, // Save 3 months (25% discount)
		'savings_text'  => __( 'Save 3 months!', 'smart-translation-engine' ),
		'price_display' => '$8/month',
		'languages'     => 8,
		'api_keys'      => 5,
		'features'      => array(
			__( '8 languages', 'smart-translation-engine' ),
			__( 'Up to 5 API keys with smart rotation', 'smart-translation-engine' ),
			__( 'Custom fields translation (ACF, etc)', 'smart-translation-engine' ),
			__( 'Taxonomy translation', 'smart-translation-engine' ),
			__( 'Advanced SEO integration', 'smart-translation-engine' ),
		),
		'cta'           => __( 'Buy Plus', 'smart-translation-engine' ),
		'recommended'   => true,
	),
	'pro'        => array(
		'name'          => __( 'Pro', 'smart-translation-engine' ),
		'price_monthly' => 19,
		'price_yearly'  => 190, // Save 2 months (17% discount)
		'savings_text'  => __( 'Save 2 months!', 'smart-translation-engine' ),
		'price_display' => '$19/month',
		'languages'     => 12,
		'api_keys'      => -1,
		'features'      => array(
			__( '12 languages', 'smart-translation-engine' ),
			__( 'Unlimited API keys', 'smart-translation-engine' ),
			__( 'Translation Memory', 'smart-translation-engine' ),
			__( 'Glossary management', 'smart-translation-engine' ),
			__( 'Bulk operations', 'smart-translation-engine' ),
			__( 'Premium support', 'smart-translation-engine' ),
		),
		'cta'           => __( 'Buy Pro', 'smart-translation-engine' ),
		'recommended'   => false,
	),
	'agency'     => array(
		'name'          => __( 'Agency', 'smart-translation-engine' ),
		'price_monthly' => 49,
		'price_yearly'  => 539, // Save 1 month (8% discount)
		'savings_text'  => __( 'Save 1 month!', 'smart-translation-engine' ),
		'price_display' => '$49/month',
		'languages'     => -1,
		'api_keys'      => -1,
		'features'      => array(
			__( 'Unlimited languages', 'smart-translation-engine' ),
			__( 'Unlimited API keys', 'smart-translation-engine' ),
			__( 'White-label branding', 'smart-translation-engine' ),
			__( 'SEO integration (Yoast, Rank Math, AIOSEO)', 'smart-translation-engine' ),
			__( 'Client management', 'smart-translation-engine' ),
			__( 'Priority support', 'smart-translation-engine' ),
		),
		'cta'           => __( 'Buy Agency', 'smart-translation-engine' ),
		'recommended'   => false,
	),
	'enterprise' => array(
		'name'          => __( 'Enterprise', 'smart-translation-engine' ),
		'price_monthly' => 100,
		'price_yearly'  => 1200, // No discount
		'savings_text'  => '', // No savings message
		'price_display' => '$100/month',
		'languages'     => -1,
		'api_keys'      => -1,
		'features'      => array(
			__( 'Everything in Agency', 'smart-translation-engine' ),
			__( 'Team management & roles', 'smart-translation-engine' ),
			__( 'Approval workflows', 'smart-translation-engine' ),
			__( 'Advanced analytics & reporting', 'smart-translation-engine' ),
			__( 'Dedicated support', 'smart-translation-engine' ),
			__( 'Custom development available', 'smart-translation-engine' ),
		),
		'cta'           => __( 'Contact Sales', 'smart-translation-engine' ),
		'recommended'   => false,
	),
);

?>

<div class="wrap wpste-upgrade-page">
	<h1><?php echo esc_html__( 'Upgrade Your Plan', 'smart-translation-engine' ); ?></h1>

	<?php if ( $wpste_current_tier === 'free' ) : ?>
		<div class="notice notice-info">
			<p>
				<strong><?php echo esc_html__( 'You are currently on the Free plan.', 'smart-translation-engine' ); ?></strong>
				<?php echo esc_html__( 'Upgrade to unlock more languages, API key rotation, and premium features.', 'smart-translation-engine' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-success">
			<p>
				<strong>
					<?php
					/* translators: %s: Tier name (Free, Pro, Agency, or Enterprise) */
					echo sprintf( esc_html__( 'Current Plan: %s', 'smart-translation-engine' ), esc_html( ucfirst( $wpste_current_tier ) ) );
					?>
				</strong>
			</p>
		</div>
	<?php endif; ?>

	<!-- Billing Toggle -->
	<div class="wpste-billing-toggle">
		<label class="wpste-toggle-label">
			<span class="monthly-label active"><?php echo esc_html__( 'Monthly', 'smart-translation-engine' ); ?></span>
			<div class="wpste-toggle-switch">
				<input type="checkbox" id="wpste-billing-period" />
				<span class="slider"></span>
			</div>
			<span class="yearly-label">
				<?php echo esc_html__( 'Yearly', 'smart-translation-engine' ); ?>
				<span class="discount-badge"><?php echo esc_html__( 'Save up to 25%', 'smart-translation-engine' ); ?></span>
			</span>
		</label>
	</div>

	<div class="wpste-pricing-table">
		<?php foreach ( $wpste_tiers as $wpste_tier_key => $wpste_tier ) : ?>
			<div class="wpste-pricing-card <?php echo $wpste_tier['recommended'] ? 'recommended' : ''; ?> <?php echo $wpste_tier_key === $wpste_current_tier ? 'current' : ''; ?>" data-tier="<?php echo esc_attr( $wpste_tier_key ); ?>">

				<?php if ( $wpste_tier['recommended'] ) : ?>
					<div class="recommended-badge"><?php echo esc_html__( 'Most Popular', 'smart-translation-engine' ); ?></div>
				<?php endif; ?>

				<?php if ( $wpste_tier_key === $wpste_current_tier ) : ?>
					<div class="current-badge"><?php echo esc_html__( 'Current Plan', 'smart-translation-engine' ); ?></div>
				<?php endif; ?>

				<h2><?php echo esc_html( $wpste_tier['name'] ); ?></h2>

				<div class="price-container">
					<?php if ( $wpste_tier['price_monthly'] > 0 ) : ?>
						<div class="price monthly-price active">
							<span class="amount">$<?php echo esc_html( $wpste_tier['price_monthly'] ); ?></span>
							<span class="period">/month</span>
						</div>
						<div class="price yearly-price">
							<span class="amount">$<?php echo esc_html( $wpste_tier['price_yearly'] ); ?></span>
							<span class="period">/year</span>
							<?php if ( ! empty( $wpste_tier['savings_text'] ) ) : ?>
								<div class="savings"><?php echo esc_html( $wpste_tier['savings_text'] ); ?></div>
							<?php endif; ?>
						</div>
					<?php else : ?>
						<div class="price free-price"><?php echo esc_html( $wpste_tier['price_display'] ); ?></div>
					<?php endif; ?>
				</div>

				<ul class="features-list">
					<?php foreach ( $wpste_tier['features'] as $wpste_feature ) : ?>
						<li><span class="dashicons dashicons-yes"></span> <?php echo esc_html( $wpste_feature ); ?></li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $wpste_tier_key === $wpste_current_tier ) : ?>
					<button class="button button-disabled" disabled><?php echo esc_html__( 'Current Plan', 'smart-translation-engine' ); ?></button>
				<?php elseif ( ! empty( $wpste_tier['cta'] ) ) : ?>
					<button
						class="button button-primary wpste-upgrade-button"
						data-tier="<?php echo esc_attr( $wpste_tier_key ); ?>"
						data-price-monthly="<?php echo esc_attr( $wpste_tier['price_monthly'] ); ?>"
						data-price-yearly="<?php echo esc_attr( $wpste_tier['price_yearly'] ); ?>"
					>
						<?php echo esc_html( $wpste_tier['cta'] ); ?>
					</button>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="wpste-upgrade-notice">
		<h3><?php echo esc_html__( 'All Plans Include:', 'smart-translation-engine' ); ?></h3>
		<ul>
			<li><?php echo esc_html__( 'Support for DeepL, Azure Translator, and AWS Translate', 'smart-translation-engine' ); ?></li>
			<li><?php echo esc_html__( 'Automatic quota tracking and management', 'smart-translation-engine' ); ?></li>
			<li><?php echo esc_html__( 'REST API and WP-CLI access', 'smart-translation-engine' ); ?></li>
			<li><?php echo esc_html__( 'Regular updates and security patches', 'smart-translation-engine' ); ?></li>
			<li><?php echo esc_html__( '30-day money-back guarantee', 'smart-translation-engine' ); ?></li>
		</ul>
	</div>

	<div class="wpste-help-section">
		<h3><?php echo esc_html__( 'Need Help Choosing?', 'smart-translation-engine' ); ?></h3>
		<p><?php echo esc_html__( 'Contact us at', 'smart-translation-engine' ); ?> <a href="mailto:cleargoal01@gmail.com">cleargoal01@gmail.com</a></p>
	</div>
</div>

<!-- Checkout Modal -->
<div id="wpste-checkout-modal" class="wpste-modal" style="display: none;">
	<div class="wpste-modal-content">
		<span class="wpste-modal-close">&times;</span>
		<div class="wpste-modal-header">
			<h2><?php echo esc_html__( 'Complete Your Upgrade', 'smart-translation-engine' ); ?></h2>
			<p class="wpste-selected-plan"></p>
		</div>
		<div class="wpste-modal-body">
			<div id="wpste-checkout-container">
				<div class="wpste-loading">
					<span class="spinner is-active"></span>
					<p><?php echo esc_html__( 'Loading checkout...', 'smart-translation-engine' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.wpste-upgrade-page {
	max-width: 1400px;
}

/* Billing Toggle */
.wpste-billing-toggle {
	text-align: center;
	margin: 30px 0;
}

.wpste-toggle-label {
	display: inline-flex;
	align-items: center;
	gap: 15px;
	font-size: 16px;
	cursor: pointer;
}

.wpste-toggle-label span {
	transition: color 0.3s;
}

.wpste-toggle-label .active {
	color: #2271b1;
	font-weight: 600;
}

.wpste-toggle-switch {
	position: relative;
	display: inline-block;
	width: 50px;
	height: 26px;
}

.wpste-toggle-switch input {
	opacity: 0;
	width: 0;
	height: 0;
}

.slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: #ccc;
	transition: 0.4s;
	border-radius: 26px;
}

.slider:before {
	position: absolute;
	content: "";
	height: 18px;
	width: 18px;
	left: 4px;
	bottom: 4px;
	background-color: white;
	transition: 0.4s;
	border-radius: 50%;
}

input:checked + .slider {
	background-color: #2271b1;
}

input:checked + .slider:before {
	transform: translateX(24px);
}

.discount-badge {
	background: #00a32a;
	color: white;
	padding: 2px 8px;
	border-radius: 10px;
	font-size: 12px;
	font-weight: 600;
	margin-left: 5px;
}

/* Pricing Cards */
.wpste-pricing-table {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 20px;
	margin: 30px 0;
}

.wpste-pricing-card {
	background: #fff;
	border: 2px solid #ddd;
	border-radius: 8px;
	padding: 30px 20px;
	text-align: center;
	position: relative;
	transition: transform 0.2s, box-shadow 0.2s;
}

.wpste-pricing-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.wpste-pricing-card.recommended {
	border-color: #2271b1;
	border-width: 3px;
}

.wpste-pricing-card.current {
	border-color: #00a32a;
	background: #f0f9f5;
}

.recommended-badge {
	position: absolute;
	top: -12px;
	left: 50%;
	transform: translateX(-50%);
	background: #2271b1;
	color: #fff;
	padding: 4px 12px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
}

.current-badge {
	position: absolute;
	top: -12px;
	left: 50%;
	transform: translateX(-50%);
	background: #00a32a;
	color: #fff;
	padding: 4px 12px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
}

.wpste-pricing-card h2 {
	margin: 10px 0;
	font-size: 24px;
}

.price-container {
	min-height: 90px;
	position: relative;
}

.price {
	display: none;
	margin: 15px 0;
}

.price.active {
	display: block;
}

.price .amount {
	font-size: 36px;
	font-weight: bold;
	color: #2271b1;
}

.price .period {
	font-size: 16px;
	color: #666;
}

.price .savings {
	font-size: 12px;
	color: #00a32a;
	font-weight: 600;
	margin-top: 5px;
}

.free-price {
	display: block !important;
	font-size: 20px;
	font-weight: bold;
	color: #2271b1;
	padding: 20px 0;
}

.wpste-pricing-card .features-list {
	list-style: none;
	padding: 0;
	margin: 20px 0;
	text-align: left;
	min-height: 180px;
}

.wpste-pricing-card .features-list li {
	padding: 8px 0;
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 14px;
}

.wpste-pricing-card .features-list .dashicons {
	color: #00a32a;
	font-size: 20px;
	flex-shrink: 0;
}

.wpste-pricing-card .button {
	width: 100%;
	margin-top: 20px;
}

/* Modal */
.wpste-modal {
	position: fixed;
	z-index: 100000;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	overflow: auto;
	background-color: rgba(0,0,0,0.5);
}

.wpste-modal-content {
	background-color: #fefefe;
	margin: 5% auto;
	padding: 0;
	border: 1px solid #888;
	border-radius: 8px;
	width: 90%;
	max-width: 600px;
	box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.wpste-modal-header {
	padding: 20px;
	border-bottom: 1px solid #ddd;
}

.wpste-modal-header h2 {
	margin: 0 0 10px 0;
}

.wpste-modal-header .wpste-selected-plan {
	margin: 0;
	color: #666;
}

.wpste-modal-close {
	color: #aaa;
	float: right;
	font-size: 28px;
	font-weight: bold;
	line-height: 20px;
	cursor: pointer;
}

.wpste-modal-close:hover,
.wpste-modal-close:focus {
	color: #000;
}

.wpste-modal-body {
	padding: 20px;
	min-height: 300px;
}

.wpste-loading {
	text-align: center;
	padding: 40px 20px;
}

.wpste-loading .spinner {
	float: none;
	margin: 0 auto 20px;
}

.wpste-upgrade-notice {
	background: #f0f0f1;
	padding: 20px;
	border-radius: 8px;
	margin: 30px 0;
}

.wpste-upgrade-notice ul {
	list-style: disc;
	padding-left: 20px;
}

.wpste-upgrade-notice li {
	padding: 5px 0;
}

.wpste-help-section {
	text-align: center;
	margin: 30px 0;
	padding: 20px;
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Billing period toggle
	$('#wpste-billing-period').on('change', function() {
		const isYearly = $(this).is(':checked');

		// Toggle active labels
		$('.monthly-label').toggleClass('active', !isYearly);
		$('.yearly-label').toggleClass('active', isYearly);

		// Toggle prices
		$('.monthly-price').toggleClass('active', !isYearly);
		$('.yearly-price').toggleClass('active', isYearly);
	});

	// Upgrade button click
	$('.wpste-upgrade-button').on('click', function(e) {
		e.preventDefault();

		const $button = $(this);
		const tier = $button.data('tier');
		const isYearly = $('#wpste-billing-period').is(':checked');
		const price = isYearly ? $button.data('price-yearly') : $button.data('price-monthly');
		const period = isYearly ? 'yearly' : 'monthly';
		const tierName = $button.closest('.wpste-pricing-card').find('h2').text();

		// Disable button
		$button.prop('disabled', true).text('<?php echo esc_js( __( 'Processing...', 'smart-translation-engine' ) ); ?>');

		// Generate/get UUID and start checkout
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'wpste_start_checkout',
				nonce: '<?php echo esc_js( wp_create_nonce( 'wpste_start_checkout' ) ); ?>',
				tier: tier,
				period: period,
				price: price
			},
			success: function(response) {
				if (response.success) {
					// Show modal with checkout info
					$('#wpste-checkout-modal .wpste-selected-plan').text(
						tierName + ' - $' + price + '/' + period
					);
					$('#wpste-checkout-modal').fadeIn();

					// Load Stripe checkout or redirect
					if (response.data.checkout_url) {
						// For now, redirect to external checkout
						window.location.href = response.data.checkout_url;
					} else {
						// Show embedded checkout (future implementation)
						$('#wpste-checkout-container').html(
							'<p>Checkout URL: ' + response.data.checkout_url + '</p>' +
							'<p>UUID: ' + response.data.uuid + '</p>'
						);
					}
				} else {
					// Show actual error message from server
					const errorMsg = response.data && response.data.message
						? response.data.message
						: '<?php echo esc_js( __( 'Error starting checkout. Please try again.', 'smart-translation-engine' ) ); ?>';

					console.error('Checkout error:', response);
					alert('Error: ' + errorMsg);
					$button.prop('disabled', false).html($button.data('original-text'));
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', xhr.responseText);
				console.error('Status:', status);
				console.error('Error:', error);

				alert('Network error: ' + error + '\n\nCheck browser console (F12) for details.');
				$button.prop('disabled', false).html($button.data('original-text'));
			}
		});

		// Store original button text for restoration
		$button.data('original-text', $button.html());
	});

	// Close modal
	$('.wpste-modal-close, .wpste-modal').on('click', function(e) {
		if (e.target === this) {
			$('#wpste-checkout-modal').fadeOut();
			$('.wpste-upgrade-button').prop('disabled', false).each(function() {
				const $btn = $(this);
				if ($btn.data('original-text')) {
					$btn.html($btn.data('original-text'));
				}
			});
		}
	});
});
</script>
