<?php
/**
 * PHPStan Bootstrap File
 *
 * Defines WordPress constants and stubs for PHPStan analysis.
 */

// WordPress core constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// Plugin constants
if (!defined('WPSTE_VERSION')) {
    define('WPSTE_VERSION', '1.0.0');
}

if (!defined('WPSTE_PLUGIN_DIR')) {
    define('WPSTE_PLUGIN_DIR', __DIR__ . '/');
}

if (!defined('WPSTE_PLUGIN_URL')) {
    define('WPSTE_PLUGIN_URL', 'https://example.com/wp-content/plugins/wp-smart-translation-engine/');
}

if (!defined('WPSTE_PLUGIN_BASENAME')) {
    define('WPSTE_PLUGIN_BASENAME', 'wp-smart-translation-engine/wp-smart-translation-engine.php');
}

// WordPress auth constants (for encryption functions)
if (!defined('AUTH_KEY')) {
    define('AUTH_KEY', 'test-auth-key');
}

if (!defined('AUTH_SALT')) {
    define('AUTH_SALT', 'test-auth-salt');
}

if (!defined('SECURE_AUTH_KEY')) {
    define('SECURE_AUTH_KEY', 'test-secure-auth-key');
}

if (!defined('SECURE_AUTH_SALT')) {
    define('SECURE_AUTH_SALT', 'test-secure-auth-salt');
}
