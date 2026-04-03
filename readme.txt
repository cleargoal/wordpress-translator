=== Smart Translation Engine ===
Contributors: cleargoal
Donate link: https://github.com/cleargoal/wordpress-translator
Tags: translation, multilingual, deepl, azure, aws
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Multi-provider AI translation for WordPress. Supports DeepL, Azure Translator, and AWS Translate with smart key rotation.

== Description ==

WP Smart Translation Engine makes translating your WordPress content simple and powerful. Connect your DeepL, Azure Translator, or AWS Translate account and start translating posts, pages, and custom content in minutes.

= Key Features =

* **Multiple Translation Providers** - Choose from DeepL, Azure Translator, or AWS Translate
* **Smart Key Rotation** - Automatically rotate between multiple API keys based on quota availability
* **Post & Page Translation** - Translate titles, content, and excerpts with one click
* **Draft Status** - All translations saved as drafts for review before publishing
* **Translation Groups** - Automatically link translations together
* **Language Management** - Easy checkbox-based language selector (Free tier: 3 languages)
* **REST API** - Programmatic access to translation features
* **WP-CLI Support** - Command-line tools for bulk operations
* **Quota Tracking** - Monitor API usage per provider
* **Provider Fallback** - Automatically try backup providers if primary fails

= Supported Languages =

English, Ukrainian, German, French, Spanish, Italian, Portuguese, Polish, Russian, Japanese, Chinese, Arabic, Dutch, Swedish, Danish, Finnish, Norwegian, Czech, Greek, Hebrew, Hindi, Korean, Turkish, and more.

= How It Works =

1. Install and activate the plugin
2. Go to Translation → Settings
3. Select your languages (up to 3 on free tier)
4. Choose your translation provider
5. Add your API key(s) in Translation → API Keys
6. Start translating! Click "Translate" in any post editor

= Free vs Premium =

**Free Version Includes:**
* 3 languages
* 1 translation provider (your choice: DeepL, Azure, or AWS)
* Unlimited translations (you pay API costs directly to provider)
* Post and page translation
* REST API access
* WP-CLI commands

**Premium Features** (coming soon):
* Unlimited languages
* All 3 providers simultaneously with fallback
* Translation Memory (reuse previous translations)
* Glossary management
* SEO plugin integration (Yoast, Rank Math)
* White-label options
* Team management
* And more!

= API Keys Required =

This plugin requires an API key from your chosen provider:
* **DeepL** - Get free API key at https://www.deepl.com/pro#developer (500,000 characters/month free)
* **Azure Translator** - Sign up at https://azure.microsoft.com/services/cognitive-services/translator/
* **AWS Translate** - Get credentials at https://aws.amazon.com/translate/

You are responsible for API costs. Most providers have generous free tiers.

= Documentation =

* [GitHub Repository](https://github.com/cleargoal/wordpress-translator)
* [Feature Roadmap](https://github.com/cleargoal/wordpress-translator/blob/master/docs/ROADMAP.md)
* [REST API Documentation](https://github.com/cleargoal/wordpress-translator/blob/master/docs/ARCHITECTURE.md)

= Support =

For bug reports and feature requests, please use our [GitHub Issues](https://github.com/cleargoal/wordpress-translator/issues).

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "WP Smart Translation Engine"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

= After Installation =

1. Go to Translation → Settings
2. Select which languages you want to enable (maximum 3 on free tier)
3. Choose your primary translation provider
4. Go to Translation → API Keys
5. Add your API key from DeepL, Azure, or AWS
6. Start translating posts from the editor!

== Frequently Asked Questions ==

= Do I need to pay for this plugin? =

The plugin itself is free. However, you need an API key from DeepL, Azure, or AWS, and they charge for translation services. Most providers have generous free tiers (DeepL offers 500,000 characters/month free).

= How many languages can I use? =

The free version supports up to 3 languages. Premium versions support more (up to unlimited).

= Can I use multiple translation providers? =

The free version lets you choose 1 provider. Premium versions allow using multiple providers with automatic fallback.

= What happens to my API keys? =

API keys are stored encrypted in your WordPress database and never shared with us or any third parties. They are only used to call the translation provider APIs directly from your server.

= Are translations automatic? =

No, you have full control. Translations are only created when you click the "Translate" button in the post editor. All translations are saved as drafts for you to review.

= Can I translate custom post types? =

Yes! Go to Settings and check which post types you want to enable for translation.

= Does this work with page builders? =

The plugin translates the core post content. Compatibility with page builders (Elementor, Divi, etc.) varies. We recommend testing with your specific builder.

= Can I translate categories and tags? =

Not in the current version. Taxonomy translation is planned for version 1.1.0. Check our [roadmap](https://github.com/cleargoal/wordpress-translator/blob/master/docs/ROADMAP.md).

= Is this compatible with WPML/Polylang? =

This is a standalone translation solution and doesn't integrate with WPML or Polylang. You should use one or the other, not both.

= Where are translations stored? =

Translations are stored as regular WordPress posts/pages with special metadata to link them together. You can edit them like any other post.

= Can I translate WooCommerce products? =

If you enable "product" post type in settings, yes. However, product-specific fields (price, SKU, etc.) are not automatically translated - only the title and description.

== Screenshots ==

1. Language management interface with checkbox selector and free tier limit validation
2. Translation metabox in post editor for one-click translation
3. Settings page showing provider selection and language configuration
4. Draft translations created and linked to original posts
5. API Keys management interface (coming soon)
6. WP-CLI command for batch translation

== Changelog ==

= 1.0.0 - 2026-03-28 =
* Initial public release
* Multi-provider support (DeepL, Azure, AWS)
* Smart API key rotation
* Post and page translation
* Translation metabox in editor
* Settings page with language selector
* Free tier: 3 languages, 1 provider
* REST API endpoints
* WP-CLI commands
* Quota tracking
* Provider fallback system
* Draft status for translations
* Translation groups (link posts together)

== Upgrade Notice ==

= 1.0.0 =
Initial release of WP Smart Translation Engine. Start translating your WordPress content with AI-powered translation providers!

== Developer Documentation ==

= REST API =

**Translate Text**
POST /wp-json/wpste/v1/translate { "text": "Hello world", "source_lang": "en", "target_lang": "uk" }

**Get Languages**
GET /wp-json/wpste/v1/languages

= WP-CLI =

**Translate a Post**
wp wpste translate-post 123 uk

**List Languages**
wp wpste translate-post 123 uk

**List Languages**
wp wpste languages

= Hooks =

**Actions**
* `wpste_post_translated` - Fired after successful translation
* `wpste_translation_failed` - Fired when translation fails
* `wpste_quota_exhausted` - Fired when API quota exceeded

**Filters**
* `wpste_translatable_post_types` - Filter post types available for translation
* `wpste_language_codes` - Modify available languages
* `wpste_available_providers` - Add custom providers

= Code Example =

```php
// Hook into successful translation
add_action('wpste_post_translated', function($data) {
    error_log('Post ' . $data['source_post_id'] . ' translated to ' . $data['target_lang']);
}, 10, 1);

For more documentation, visit GitHub.

