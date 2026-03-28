<?php
/**
 * Translation Provider Interface
 *
 * Defines the contract all translation providers must implement.
 *
 * @package WP_Smart_Translation_Engine
 */

namespace WPSTE\Providers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translation Provider Interface
 */
interface Translation_Provider_Interface
{
    /**
     * Translate text
     *
     * @param string $text Text to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @param array $options Additional options
     * @return array Translation result with 'text' key or 'error'
     */
    public function translate(string $text, string $source_lang, string $target_lang, array $options = []): array;

    /**
     * Translate batch of texts
     *
     * @param array $texts Array of texts to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @param array $options Additional options
     * @return array Array of translation results
     */
    public function translate_batch(array $texts, string $source_lang, string $target_lang, array $options = []): array;

    /**
     * Detect language of text
     *
     * @param string $text Text to analyze
     * @return array Result with 'language' code or 'error'
     */
    public function detect_language(string $text): array;

    /**
     * Get supported languages
     *
     * @return array Array of supported language codes
     */
    public function get_supported_languages(): array;

    /**
     * Check if provider is available
     *
     * @return bool True if provider has valid API keys
     */
    public function is_available(): bool;

    /**
     * Get provider name
     *
     * @return string Provider name
     */
    public function get_name(): string;

    /**
     * Get usage statistics
     *
     * @return array Usage stats with character counts
     */
    public function get_usage_stats(): array;
}
