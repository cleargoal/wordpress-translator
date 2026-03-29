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
$license = get_option( 'wpste_license', array( 'tier' => 'free' ) );
$current_tier = $license['tier'] ?? 'free';

// Define all tiers with features
$tiers = array(
	'free'       => array(
		'name'       => __( 'Free', 'wp-smart-translation-engine' ),
		'price'      => __( 'Free Forever', 'wp-smart-translation-engine' ),
		'languages'  => 3,
		'api_keys'   => 1,
		'features'   => array(
			__( '3 languages', 'wp-smart-translation-engine' ),
			__( '1 API key (no rotation)', 'wp-smart-translation-engine' ),
			__( '1 provider choice', 'wp-smart-translation-engine' ),
			__( 'Post & page translation', 'wp-smart-translation-engine' ),
			__( 'Basic support', 'wp-smart-translation-engine' ),
		),
		'cta'        => '',
		'recommended' => false,
	),
	'starter'    => array(
		'name'       => __( 'Starter', 'wp-smart-translation-engine' ),
		'price'      => '$9/month',
		'languages'  => 5,
		'api_keys'   => 3,
		'features'   => array(
			__( '5 languages', 'wp-smart-translation-engine' ),
			__( 'Up to 3 API keys with rotation', 'wp-smart-translation-engine' ),
			__( 'Multiple providers', 'wp-smart-translation-engine' ),
			__( 'Post, page & custom post types', 'wp-smart-translation-engine' ),
			__( 'Priority support', 'wp-smart-translation-engine' ),
		),
		'cta'        => 'Buy Starter',
		'recommended' => false,
	),
	'basic'      => array(
		'name'       => __( 'Basic', 'wp-smart-translation-engine' ),
		'price'      => '$19/month',
		'languages'  => 10,
		'api_keys'   => 5,
		'features'   => array(
			__( '10 languages', 'wp-smart-translation-engine' ),
			__( 'Up to 5 API keys with smart rotation', 'wp-smart-translation-engine' ),
			__( 'All providers with fallback', 'wp-smart-translation-engine' ),
			__( 'Menu & widget translation', 'wp-smart-translation-engine' ),
			__( 'Basic SEO support', 'wp-smart-translation-engine' ),
		),
		'cta'        => 'Buy Basic',
		'recommended' => false,
	),
	'plus'       => array(
		'name'       => __( 'Plus', 'wp-smart-translation-engine' ),
		'price'      => '$39/month',
		'languages'  => 15,
		'api_keys'   => 10,
		'features'   => array(
			__( '15 languages', 'wp-smart-translation-engine' ),
			__( 'Up to 10 API keys with smart rotation', 'wp-smart-translation-engine' ),
			__( 'Custom fields translation (ACF, etc)', 'wp-smart-translation-engine' ),
			__( 'Taxonomy translation', 'wp-smart-translation-engine' ),
			__( 'Advanced SEO integration', 'wp-smart-translation-engine' ),
		),
		'cta'        => 'Buy Plus',
		'recommended' => true,
	),
	'pro'        => array(
		'name'       => __( 'Pro', 'wp-smart-translation-engine' ),
		'price'      => '$79/month',
		'languages'  => 25,
		'api_keys'   => -1,
		'features'   => array(
			__( '25 languages', 'wp-smart-translation-engine' ),
			__( 'Unlimited API keys', 'wp-smart-translation-engine' ),
			__( 'Translation Memory', 'wp-smart-translation-engine' ),
			__( 'Glossary management', 'wp-smart-translation-engine' ),
			__( 'Bulk operations', 'wp-smart-translation-engine' ),
			__( 'Premium support', 'wp-smart-translation-engine' ),
		),
		'cta'        => 'Buy Pro',
		'recommended' => false,
	),
	'agency'     => array(
		'name'       => __( 'Agency', 'wp-smart-translation-engine' ),
		'price'      => '$149/month',
		'languages'  => -1,
		'api_keys'   => -1,
		'features'   => array(
			__( 'Unlimited languages', 'wp-smart-translation-engine' ),
			__( 'Unlimited API keys', 'wp-smart-translation-engine' ),
			__( 'White-label branding', 'wp-smart-translation-engine' ),
			__( 'SEO integration (Yoast, Rank Math, AIOSEO)', 'wp-smart-translation-engine' ),
			__( 'Client management', 'wp-smart-translation-engine' ),
			__( 'Priority support', 'wp-smart-translation-engine' ),
		),
		'cta'        => 'Buy Agency',
		'recommended' => false,
	),
	'enterprise' => array(
		'name'       => __( 'Enterprise', 'wp-smart-translation-engine' ),
		'price'      => '$299/month',
		'languages'  => -1,
		'api_keys'   => -1,
		'features'   => array(
			__( 'Everything in Agency', 'wp-smart-translation-engine' ),
			__( 'Team management & roles', 'wp-smart-translation-engine' ),
			__( 'Approval workflows', 'wp-smart-translation-engine' ),
			__( 'Advanced analytics & reporting', 'wp-smart-translation-engine' ),
			__( 'Dedicated support', 'wp-smart-translation-engine' ),
			__( 'Custom development available', 'wp-smart-translation-engine' ),
		),
		'cta'        => 'Contact Sales',
		'recommended' => false,
	),
);

?>

<div class="wrap wpste-upgrade-page">
	<h1><?php echo esc_html__( 'Upgrade Your Plan', 'wp-smart-translation-engine' ); ?></h1>

	<?php if ( $current_tier === 'free' ) : ?>
		<div class="notice notice-info">
			<p>
				<strong><?php echo esc_html__( 'You are currently on the Free plan.', 'wp-smart-translation-engine' ); ?></strong>
				<?php echo esc_html__( 'Upgrade to unlock more languages, API key rotation, and premium features.', 'wp-smart-translation-engine' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-success">
			<p>
				<strong><?php echo sprintf( esc_html__( 'Current Plan: %s', 'wp-smart-translation-engine' ), esc_html( ucfirst( $current_tier ) ) ); ?></strong>
			</p>
		</div>
	<?php endif; ?>

	<div class="wpste-pricing-table">
		<?php foreach ( $tiers as $tier_key => $tier ) : ?>
			<div class="wpste-pricing-card <?php echo $tier['recommended'] ? 'recommended' : ''; ?> <?php echo $tier_key === $current_tier ? 'current' : ''; ?>">

				<?php if ( $tier['recommended'] ) : ?>
					<div class="recommended-badge"><?php echo esc_html__( 'Most Popular', 'wp-smart-translation-engine' ); ?></div>
				<?php endif; ?>

				<?php if ( $tier_key === $current_tier ) : ?>
					<div class="current-badge"><?php echo esc_html__( 'Current Plan', 'wp-smart-translation-engine' ); ?></div>
				<?php endif; ?>

				<h2><?php echo esc_html( $tier['name'] ); ?></h2>
				<div class="price"><?php echo esc_html( $tier['price'] ); ?></div>

				<ul class="features-list">
					<?php foreach ( $tier['features'] as $feature ) : ?>
						<li><span class="dashicons dashicons-yes"></span> <?php echo esc_html( $feature ); ?></li>
					<?php endforeach; ?>
				</ul>

				<?php if ( $tier_key === $current_tier ) : ?>
					<button class="button button-disabled" disabled><?php echo esc_html__( 'Current Plan', 'wp-smart-translation-engine' ); ?></button>
				<?php elseif ( ! empty( $tier['cta'] ) ) : ?>
					<a href="#" class="button button-primary wpste-upgrade-button" data-tier="<?php echo esc_attr( $tier_key ); ?>">
						<?php echo esc_html( $tier['cta'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="wpste-upgrade-notice">
		<h3><?php echo esc_html__( 'All Plans Include:', 'wp-smart-translation-engine' ); ?></h3>
		<ul>
			<li><?php echo esc_html__( 'Support for DeepL, Azure Translator, and AWS Translate', 'wp-smart-translation-engine' ); ?></li>
			<li><?php echo esc_html__( 'Automatic quota tracking and management', 'wp-smart-translation-engine' ); ?></li>
			<li><?php echo esc_html__( 'REST API and WP-CLI access', 'wp-smart-translation-engine' ); ?></li>
			<li><?php echo esc_html__( 'Regular updates and security patches', 'wp-smart-translation-engine' ); ?></li>
		</ul>
	</div>

	<div class="wpste-help-section">
		<h3><?php echo esc_html__( 'Need Help Choosing?', 'wp-smart-translation-engine' ); ?></h3>
		<p><?php echo esc_html__( 'Contact us at', 'wp-smart-translation-engine' ); ?> <a href="mailto:cleargoal01@gmail.com">cleargoal01@gmail.com</a></p>
	</div>
</div>

<style>
.wpste-upgrade-page {
	max-width: 1400px;
}

.wpste-pricing-table {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.wpste-pricing-card .price {
	font-size: 32px;
	font-weight: bold;
	color: #2271b1;
	margin: 15px 0;
}

.wpste-pricing-card .features-list {
	list-style: none;
	padding: 0;
	margin: 20px 0;
	text-align: left;
}

.wpste-pricing-card .features-list li {
	padding: 8px 0;
	display: flex;
	align-items: center;
	gap: 8px;
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
	$('.wpste-upgrade-button').on('click', function(e) {
		e.preventDefault();
		var tier = $(this).data('tier');
		alert('Upgrade functionality coming soon!\n\nSelected plan: ' + tier + '\n\nPlease contact cleargoal01@gmail.com for now.');
	});
});
</script>
