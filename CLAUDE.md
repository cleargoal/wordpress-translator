# WP Smart Translation Engine - Claude Code Instructions

## Project Overview

**Location**: `/home/yefrem/projects/wordpress-translator`

Multi-provider AI translation WordPress plugin supporting DeepL, Azure Translator, and AWS Translate with smart key rotation and quota management.

**Status**: Core functionality restored after accidental deletion (2026-03-28)

## Quick Facts

- **Plugin Name**: WP Smart Translation Engine
- **Plugin Slug**: `wp-smart-translation-engine`
- **Prefix**: `wpste_`
- **Text Domain**: `wp-smart-translation-engine`
- **PHP**: 7.4+
- **WordPress**: 6.0+
- **License**: GPL v2+

## Essential Commands

```bash
# Navigate
cd /home/yefrem/projects/wordpress-translator

# Dependencies
composer install

# Testing (when tests are implemented)
composer test

# Git
git status
git log --oneline
```

## Documentation Structure

**All detailed info is in `docs/` folder:**

- **[docs/ROADMAP.md](docs/ROADMAP.md)** - Feature roadmap, next steps
- **[RESTORATION_NOTES.md](RESTORATION_NOTES.md)** - What was lost and restored

## Current Implementation Status

**✅ Restored & Working**:
- Database layer (Installer, Database)
- Provider system (interfaces, abstracts, factory)
- Core translation classes (Manager, Post_Translator)
- DeepL provider (full implementation from Laravel reference)
- Azure/AWS providers (stubs - TODO)
- Key management (DeepL complete, Azure/AWS stubs)
- Licensing framework (basic stubs)
- Admin UI (settings page, metabox, AJAX)
- Public frontend (basic)
- REST API endpoints
- WP-CLI commands
- Configuration files (composer.json, .gitignore, README.md)

**⚠️ Needs Implementation**:
- Azure Provider (API integration)
- AWS Provider (SDK integration)
- Full licensing features (external server, downloads)
- Admin API keys management page
- Taxonomy translation
- Custom fields translation
- Full test suite

## Laravel Reference Package

**Location**: `/home/yefrem/projects/laravel-deepl/`

**Key Files**:
- `src/Services/DeepLApiKeyManager.php` - Reference for key rotation logic
- `src/Services/DeepLTranslationService.php` - Reference for API integration

**Adaptations Made**:
```php
// Laravel → WordPress
DB::table()     → $wpdb->get_results()
Cache::put()    → set_transient()
Http::post()    → wp_remote_post()
event()         → do_action()
config()        → get_option()
Carbon::now()   → current_time('mysql')
Log::error()    → error_log()
```

## Provider Architecture

**Interface**: `Translation_Provider_Interface`
- `translate()`, `translate_batch()`, `detect_language()`
- `get_supported_languages()`, `is_available()`

**Base Class**: `Abstract_Translation_Provider`
- HTTP request handling via `wp_remote_request()`
- Usage logging
- Character counting

**Implementations**:
- `DeepL_Provider` ✅ - Full multi-key rotation
- `Azure_Provider` ⚠️ - Stub only
- `AWS_Provider` ⚠️ - Stub only

## Database Schema

**Tables**:
- `wp_wpste_api_keys` - API keys for all providers
- `wp_wpste_translations` - Translation status & relationships
- `wp_wpste_licenses` - License data (not yet used)
- `wp_wpste_features` - Downloaded features (not yet used)
- `wp_wpste_quota_alerts` - Quota notifications

**Options**:
- `wpste_settings` - Plugin configuration
- `wpste_license` - License info (defaults to 'free')
- `wpste_db_version` - Schema version

**Transients** (5-min cache):
- `wpste_api_keys_deepl`, `wpste_api_keys_azure`, `wpste_api_keys_aws`

**Post Meta**:
- `_wpste_lang_code` - Post language
- `_wpste_translation_group` - UUID linking translations
- `_wpste_source_post_id` - Original post ID

## WordPress Integration

**Actions**:
- `wpste_post_translated` - After successful translation
- `wpste_translation_failed` - Translation error
- `wpste_quota_exhausted` - API key quota exceeded

**Filters**:
- `wpste_translatable_post_types`
- `wpste_language_codes`
- `wpste_available_providers`
- `wpste_provider_list`

**REST API**: `/wp-json/wpste/v1/translate`, `/wp-json/wpste/v1/languages`

**WP-CLI**: `wp wpste translate-post`, `wp wpste languages`

## Development Guidelines

1. **Follow WordPress Coding Standards**: Use WordPress APIs, nonces, capabilities
2. **Security First**: Sanitize input, escape output, validate permissions
3. **No Auto-Commit**: User reviews changes before committing
4. **Reference Laravel Package**: For DeepL implementation patterns
5. **Keep CLAUDE.md Compact**: Detailed docs go in `docs/` folder
6. **Test Locally**: Symlinked to Local by Flywheel at `http://localhost:10003`

## File Structure

```
/
├── wp-smart-translation-engine.php  # Main plugin file
├── uninstall.php                    # Cleanup on deletion
├── composer.json                    # Dependencies
├── CLAUDE.md                       # This file
├── RESTORATION_NOTES.md             # Recovery documentation
├── README.md                        # Public readme
├── includes/
│   ├── core/                       # Translation_Manager, Post_Translator, Provider_Factory
│   ├── providers/                  # DeepL_Provider, Azure_Provider, AWS_Provider
│   ├── key-management/             # Key managers for each provider
│   ├── database/                   # Installer, Database operations
│   ├── licensing/                  # Tier management, feature loading
│   └── integrations/               # REST_API
├── admin/
│   ├── class-admin.php             # Admin functionality
│   ├── partials/                   # Admin templates
│   └── js/                         # Admin JavaScript
├── public/
│   └── class-public.php            # Frontend functionality
├── cli/
│   └── class-cli.php               # WP-CLI commands
└── docs/
    └── ROADMAP.md                  # Feature planning
```

## Testing Workflow

**Local Development Site**:
- URL: `http://localhost:10003`
- Path: `/home/yefrem/Local Sites/translation-test/app/public`
- Plugin symlinked from `/home/yefrem/projects/wordpress-translator`

**Test Translation**:
1. Create a post in WordPress
2. Use Translation metabox to translate
3. Check draft post created
4. Verify Azure API key usage (current test setup)

## Important Notes

- **Symlink Deletion Issue**: WordPress delete button tries to delete symlink target - use manual deactivation instead
- **Free Tier**: 3 languages, 1 provider (currently allows all 3 for testing)
- **API Keys**: Stored encrypted in database, cached 5 minutes
- **Multi-Key Rotation**: DeepL can use multiple keys from different accounts
- **Laravel Reference**: Use `/home/yefrem/projects/laravel-deepl/` as implementation guide

## Key Reminders

- Don't commit without user review
- Azure quota tracking via response headers (X-Metering headers)
- Post language metadata critical for proper translation linking
- Translation groups use UUID4
- Default language in fresh installs: English only
- Settings page hides fallback providers for free tier

---

**Last Updated**: 2026-03-28 (after restoration)
**Version**: 1.0.0-dev
**Status**: Core functional, Azure/AWS providers need implementation
