<?php
/**
 * Language Switcher Gutenberg Block
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Admin\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Language Switcher Block class
 */
class Language_Switcher_Block {

	/**
	 * Language Switcher instance
	 *
	 * @var \WPSTE\Frontend\Language_Switcher
	 */
	protected $switcher;

	/**
	 * Constructor
	 */
	public function __construct() {
		require_once WPSTE_PLUGIN_DIR . 'public/class-language-switcher.php';
		$this->switcher = new \WPSTE\Frontend\Language_Switcher();
	}

	/**
	 * Register the block
	 */
	public function register(): void {
		// Register block script
		wp_register_script(
			'wpste-language-switcher-block',
			WPSTE_PLUGIN_URL . 'admin/blocks/language-switcher-block.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
			),
			WPSTE_VERSION,
			false
		);

		// Register the block
		register_block_type(
			'wpste/language-switcher',
			array(
				'editor_script'   => 'wpste-language-switcher-block',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'style'     => array(
						'type'    => 'string',
						'default' => 'dropdown',
					),
					'showFlags' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showNames' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
	}

	/**
	 * Render block callback
	 *
	 * @param array $attributes Block attributes.
	 * @return string Block HTML.
	 */
	public function render_block( array $attributes ): string {
		$style      = $attributes['style'] ?? 'dropdown';
		$show_flags = $attributes['showFlags'] ?? true;
		$show_names = $attributes['showNames'] ?? true;

		return $this->switcher->render(
			array(
				'style'      => $style,
				'show_flags' => $show_flags,
				'show_names' => $show_names,
			)
		);
	}
}
