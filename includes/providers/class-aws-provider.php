<?php
namespace WPSTE\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AWS_Provider extends Abstract_Translation_Provider {

	protected $name = 'aws';

	public function translate( string $text, string $source_lang, string $target_lang, array $options = array() ): array {
		// TODO: Implement AWS Translate SDK integration
		return array( 'error' => 'AWS provider not yet implemented' );
	}

	public function translate_batch( array $texts, string $source_lang, string $target_lang, array $options = array() ): array {
		return array( 'error' => 'AWS batch not yet implemented' );
	}

	public function detect_language( string $text ): array {
		return array( 'error' => 'AWS detection not yet implemented' );
	}

	public function get_supported_languages(): array {
		return array( 'en', 'uk', 'de', 'fr', 'es' );
	}

	public function is_available(): bool {
		$keys = $this->key_manager->get_all_keys( true );
		return ! empty( $keys );
	}
}
