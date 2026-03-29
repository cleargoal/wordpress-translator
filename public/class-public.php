<?php
namespace WPSTE\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PublicFrontend {

	public function init(): void {
		add_filter( 'the_content', array( $this, 'filter_content' ), 10 );
		add_filter( 'the_title', array( $this, 'filter_title' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'add_language_meta' ) );
	}

	public function filter_content( $content ) {
		// TODO: Implement language-specific content filtering
		return $content;
	}

	public function filter_title( $title, $post_id = 0 ) {
		// TODO: Implement language-specific title filtering
		return $title;
	}

	public function add_language_meta(): void {
		if ( is_singular() ) {
			global $post;
			$lang = get_post_meta( $post->ID, '_wpste_lang_code', true );
			if ( $lang ) {
				echo '<meta property="og:locale" content="' . esc_attr( $lang ) . '_' . strtoupper( $lang ) . '" />' . "\n";
			}
		}
	}
}
