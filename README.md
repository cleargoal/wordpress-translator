# WP Smart Translation Engine

Multi-provider AI translation for WordPress posts and pages. Supports DeepL, Azure Translator, and AWS Translate with smart key rotation and quota management.

## Features

- **Multiple Translation Providers**: DeepL, Azure Translator, AWS Translate
- **Smart Key Rotation**: Automatically rotates between API keys based on quota availability
- **Post/Page Translation**: Translate titles, content, and excerpts
- **Draft Status**: Translated posts saved as drafts for review
- **Translation Groups**: Link translations together
- **Language Management**: Easy language selection with tier-based limits
- **REST API**: Programmatic access to translation features
- **WP-CLI Support**: Command-line translation tools
- **Quota Tracking**: Monitor API usage per provider

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

### From Source

1. Clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/cleargoal/wordpress-translator.git wp-smart-translation-engine
   ```

2. Install dependencies:
   ```bash
   cd wp-smart-translation-engine
   composer install
   ```

3. Activate the plugin in WordPress Admin → Plugins

### From WordPress.org

Coming soon!

## Configuration

1. Go to WordPress Admin → Translation → Settings
2. Select your enabled languages (Free tier: 3 languages)
3. Choose your primary translation provider
4. Go to Translation → API Keys
5. Add your API keys for your chosen provider(s)

## Usage

### Translating Posts

1. Edit any post or page
2. In the sidebar, find the "Translation" metabox
3. Select target language
4. Click "Translate"
5. A new draft post will be created in the target language

### WP-CLI Commands

```bash
# Translate a post
wp wpste translate-post 123 uk

# List enabled languages
wp wpste languages
```

### REST API

```bash
# Translate text
curl -X POST https://yoursite.com/wp-json/wpste/v1/translate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"text":"Hello","source_lang":"en","target_lang":"uk"}'

# Get enabled languages
curl https://yoursite.com/wp-json/wpste/v1/languages
```

## Supported Languages

English, Ukrainian, German, French, Spanish, Italian, Portuguese, Polish, Russian, Japanese, Chinese, Arabic, Dutch, Swedish, Danish, Finnish, Norwegian, Czech, Greek, Hebrew, Hindi, Korean, Turkish

## License

GPL v2 or later

## Support

- Documentation: See `/docs` folder
- Issues: https://github.com/cleargoal/wordpress-translator/issues
- Email: cleargoal01@gmail.com

## Credits

Developed by Volodymyr Yefremov
