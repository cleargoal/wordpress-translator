# WP Smart Translation Engine - System Architecture

## Overview

Multi-provider translation plugin for WordPress with a split architecture: free version (public/GPL) and premium features (private/proprietary).

**Last Updated**: 2026-03-28

---

## Repository Structure

### Two-Repository Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ Repository 1: wordpress-translator (PUBLIC - GPL v2+)       │
│                                                               │
│ - Free version core functionality                            │
│ - WordPress.org compatible                                   │
│ - Basic translation features                                 │
│ - License validation client                                  │
│ - Feature download framework                                 │
│ - Does NOT contain premium feature implementations           │
│                                                               │
│ GitHub: cleargoal/wordpress-translator                       │
│ Distribution: WordPress.org + GitHub releases                │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Repository 2: wordpress-translator-premium (PRIVATE)        │
│                                                               │
│ - Premium feature implementations                            │
│ - Proprietary license (NOT GPL)                              │
│ - Never published to WordPress.org                           │
│ - Distributed only to paying customers                       │
│ - License prohibits redistribution                           │
│                                                               │
│ GitHub: cleargoal/wordpress-translator-premium (private)     │
│ Distribution: Direct download via license server/CDN         │
└─────────────────────────────────────────────────────────────┘
```

---

## System Architecture Diagram

```
┌───────────────────────────────────────────────────────────────────┐
│ User's WordPress Site                                              │
│                                                                     │
│  ┌──────────────────────────────────────────────────────┐         │
│  │ Free Plugin (wordpress-translator - PUBLIC)          │         │
│  │                                                       │         │
│  │  Core Features:                                      │         │
│  │  • Post/Page Translation (DeepL/Azure/AWS)          │         │
│  │  • Multi-language support (3 max on free)           │         │
│  │  • API key management                                │         │
│  │  • Smart key rotation                                │         │
│  │  • Translation metabox                               │         │
│  │  • REST API                                          │         │
│  │  • WP-CLI commands                                   │         │
│  │                                                       │         │
│  │  Licensing Framework:                                │         │
│  │  • License_Validator (client)                        │         │
│  │  • Feature_Downloader (client)                       │         │
│  │  • Tier_Manager (permission checks)                  │         │
│  └──────────────────────────────────────────────────────┘         │
│         │                                                           │
│         │ 1. User enters license key in admin                      │
│         ▼                                                           │
│  ┌──────────────────────────────────────────────────────┐         │
│  │ License Manager                                       │         │
│  │  • Validates key with external server                │         │
│  │  • Receives tier info (Free/Pro/Agency/Enterprise)   │         │
│  │  • Gets list of available features                   │         │
│  │  • Caches validation for 7 days                      │         │
│  └──────────────────────────────────────────────────────┘         │
│         │                                                           │
│         │ 2. If valid Pro/Agency/Enterprise license                │
│         ▼                                                           │
│  ┌──────────────────────────────────────────────────────┐         │
│  │ Feature Downloader                                    │         │
│  │  • Downloads premium feature ZIPs from CDN           │         │
│  │  • Verifies SHA-256 checksums                        │         │
│  │  • Extracts to includes/features/tier/               │         │
│  │  • Registers features in database                    │         │
│  └──────────────────────────────────────────────────────┘         │
│         │                                                           │
│         │ 3. Premium features loaded                               │
│         ▼                                                           │
│  ┌──────────────────────────────────────────────────────┐         │
│  │ includes/features/ (dynamically loaded)              │         │
│  │                                                       │         │
│  │  pro/                                                │         │
│  │    ├── translation-memory/                           │         │
│  │    ├── smart-rotation/                               │         │
│  │    └── glossary/                                     │         │
│  │                                                       │         │
│  │  agency/                                             │         │
│  │    ├── seo-integration/                              │         │
│  │    └── white-label/                                  │         │
│  │                                                       │         │
│  │  enterprise/                                         │         │
│  │    ├── team-management/                              │         │
│  │    ├── workflows/                                    │         │
│  │    └── analytics/                                    │         │
│  └──────────────────────────────────────────────────────┘         │
└───────────────────────────────────────────────────────────────────┘
                        ▲                         ▲
                        │                         │
                        │                         │
          ┌─────────────┴────────┐    ┌──────────┴────────────┐
          │ License Server       │    │ Premium Feature CDN   │
          │ (Separate Project)   │    │ (Private Storage)     │
          │                      │    │                       │
          │ • Validate licenses  │    │ • Feature ZIPs        │
          │ • Check expiry       │    │ • Checksums           │
          │ • Return tier info   │    │ • Version control     │
          │ • Track activations  │    │ • Secure downloads    │
          │ • Revoke access      │    │                       │
          └──────────────────────┘    └───────────────────────┘
                   ▲                              ▲
                   │                              │
                   │                              │
                   └──────────┬───────────────────┘
                              │
                  ┌───────────┴────────────────┐
                  │ Private GitHub Repo        │
                  │ wordpress-translator-      │
                  │ premium                    │
                  │                            │
                  │ • Premium feature code     │
                  │ • Build scripts            │
                  │ • Creates distributable    │
                  │   ZIP packages             │
                  │ • manifest.json files      │
                  └────────────────────────────┘
```

---

## Component Architecture

### 1. Free Plugin (Public Repository)

#### Core Translation System

```
includes/
├── core/
│   ├── class-provider-factory.php       # Creates provider instances
│   ├── class-translation-manager.php    # Orchestrates translation with fallback
│   └── class-post-translator.php        # WordPress post translation logic
│
├── providers/
│   ├── interface-translation-provider.php
│   ├── abstract-translation-provider.php
│   ├── class-deepl-provider.php         # Full implementation ✅
│   ├── class-azure-provider.php         # Stub (TODO)
│   └── class-aws-provider.php           # Stub (TODO)
│
├── key-management/
│   ├── interface-key-manager.php
│   ├── class-deepl-key-manager.php      # Smart rotation ✅
│   ├── class-azure-key-manager.php      # Basic stub
│   └── class-aws-key-manager.php        # Basic stub
│
└── database/
    ├── class-installer.php              # Creates 5 tables
    └── class-database.php               # CRUD operations
```

#### Licensing Framework (Client-Side Only)

```
includes/
└── licensing/
    ├── class-license-storage.php        # Read/write license data locally
    ├── class-license-validator.php      # Calls external server API
    ├── class-license-manager.php        # Orchestrates validation
    ├── class-tier-manager.php           # Enforces tier limits
    ├── class-feature-downloader.php     # Downloads premium ZIPs
    ├── class-feature-loader.php         # Loads downloaded features
    └── class-quota-notifier.php         # API quota alerts
```

**Key Point**: Free plugin contains the **framework** to validate licenses and download features, but does NOT contain premium feature implementations.

#### Admin Interface

```
admin/
├── class-admin.php                      # Menu pages, metabox, AJAX
├── partials/
│   ├── settings-page.php                # Language/provider settings
│   ├── keys-page.php                    # API key management (TODO)
│   └── license-page.php                 # License activation (TODO)
└── js/
    └── admin.js                          # AJAX translation handling
```

#### Integration Points

```
includes/integrations/
└── class-rest-api.php                   # REST API endpoints

cli/
└── class-cli.php                        # WP-CLI commands

public/
└── class-public.php                     # Frontend filtering
```

---

### 2. Premium Features (Private Repository)

**NOT in current repository. Structure for future implementation:**

```
wordpress-translator-premium/
│
├── translation-memory/                  # Pro+
│   ├── manifest.json
│   ├── class-translation-memory.php
│   ├── admin/
│   └── database/
│
├── smart-rotation/                      # Pro+
│   ├── manifest.json
│   └── class-smart-rotation.php
│
├── glossary/                            # Pro+
│   ├── manifest.json
│   ├── class-glossary.php
│   └── admin/
│
├── seo-integration/                     # Agency+
│   ├── manifest.json
│   ├── class-seo-yoast.php
│   ├── class-seo-rankmath.php
│   └── class-seo-aioseo.php
│
├── white-label/                         # Agency+
│   ├── manifest.json
│   ├── class-white-label.php
│   └── admin/
│
├── team-management/                     # Enterprise
│   ├── manifest.json
│   └── class-team-management.php
│
├── workflows/                           # Enterprise
│   ├── manifest.json
│   └── class-workflows.php
│
├── analytics/                           # Enterprise
│   ├── manifest.json
│   └── class-analytics.php
│
└── build/
    └── build-features.php               # Creates distributable ZIPs
```

#### Premium Feature Package Structure

Each feature is packaged as a ZIP:

```
translation-memory-1.0.0.zip
├── manifest.json                        # Metadata
│   {
│     "slug": "translation-memory",
│     "version": "1.0.0",
│     "tier": "pro",
│     "requires": "1.0.0",
│     "checksum": "sha256_hash_here"
│   }
│
├── class-feature-main.php               # Entry point
├── includes/                            # Feature logic
├── admin/                               # Admin UI
├── database/                            # Schema updates (if needed)
└── public/                              # Frontend (if needed)
```

---

### 3. License Server (Separate Project)

**NOT in wordpress-translator repository. Separate project entirely.**

**Recommended Tech Stack**: Laravel or Node.js

```
license-server/
│
├── API Endpoints:
│   ├── POST /api/licenses/activate
│   ├── POST /api/licenses/validate
│   ├── POST /api/licenses/deactivate
│   ├── GET  /api/features/available
│   └── GET  /api/features/download/{slug}
│
├── Database:
│   ├── customers
│   ├── licenses
│   ├── activations
│   ├── features
│   └── download_logs
│
└── Integration:
    ├── Payment gateway (Stripe/PayPal)
    ├── Email service
    └── CDN/S3 for feature ZIPs
```

#### License Validation Flow

```
User Site                License Server
    │                           │
    │  POST /licenses/validate  │
    │  { license_key, site_url }│
    ├──────────────────────────>│
    │                           │
    │                           │ 1. Check license exists
    │                           │ 2. Verify not expired
    │                           │ 3. Check activation limit
    │                           │ 4. Validate site_url
    │                           │ 5. Get tier & features
    │                           │
    │     JSON Response          │
    │<────────────────────────── │
    │  {                         │
    │    "valid": true,          │
    │    "tier": "pro",          │
    │    "expiry": "2027-01-01", │
    │    "features": [           │
    │      "translation-memory", │
    │      "glossary"            │
    │    ]                       │
    │  }                         │
    │                           │
    │  GET /features/download/  │
    │       translation-memory   │
    ├──────────────────────────>│
    │                           │
    │     ZIP file + checksum    │
    │<────────────────────────── │
    │                           │
```

---

## Data Flow

### Translation Flow (Free Features)

```
1. User clicks "Translate" in metabox
   └─> AJAX call to admin-ajax.php

2. Admin::ajax_translate_post()
   └─> Post_Translator::translate_post()

3. Post_Translator gets source content
   └─> Translation_Manager::translate()

4. Translation_Manager tries providers
   ├─> Primary provider (e.g., DeepL)
   │   └─> DeepL_Provider::translate()
   │       └─> DeepL_Key_Manager::get_next_key()
   │           └─> Rotates to key with most quota
   │               └─> wp_remote_post() to DeepL API
   │
   └─> If fails, try fallback providers

5. Success: Create new post as draft
   └─> Add post meta (_wpste_lang_code, _wpste_translation_group)
   └─> Store in wpste_translations table
   └─> Return new post ID to AJAX
```

### License Activation Flow (Premium Features)

```
1. User enters license key in admin
   └─> License_Manager::activate()

2. License_Validator calls external server
   ├─> POST https://yourserver.com/api/licenses/validate
   └─> Receives tier info + feature list

3. License_Storage saves license locally
   └─> update_option('wpste_license', $data)

4. Feature_Downloader downloads premium features
   ├─> For each feature in list:
   │   ├─> GET https://cdn.yourserver.com/features/{slug}.zip
   │   ├─> Verify SHA-256 checksum
   │   └─> Extract to includes/features/{tier}/{slug}/
   │
   └─> Register in wpste_features table

5. Feature_Loader loads features on plugin init
   └─> Require includes/features/{tier}/{slug}/class-feature-main.php
```

---

## Database Schema

### Tables Created by Free Plugin

```sql
-- API Keys (all providers)
wp_wpste_api_keys
├── id (primary key)
├── provider (deepl/azure/aws)
├── api_key (encrypted)
├── label
├── usage_count
├── characters_used
├── quota_limit
├── is_active
└── timestamps

-- Translation Records
wp_wpste_translations
├── id (primary key)
├── post_id (translated post)
├── source_post_id (original)
├── lang_code
├── translation_group (UUID)
├── provider_used
├── api_key_id
├── status (draft/published)
├── characters_translated
└── timestamps

-- License Data (local cache)
wp_wpste_licenses
├── id (primary key)
├── license_key (encrypted)
├── tier (free/pro/agency/enterprise)
├── status (inactive/active/expired)
├── site_url
├── activation_date
├── expiry_date
├── last_check
├── next_check
├── license_data (JSON)
└── timestamps

-- Downloaded Features
wp_wpste_features
├── id (primary key)
├── feature_slug
├── tier
├── feature_name
├── version
├── download_url
├── checksum
├── file_path
├── status (inactive/active)
├── downloaded_at
└── timestamps

-- Quota Alerts
wp_wpste_quota_alerts
├── id (primary key)
├── provider
├── api_key_id
├── alert_type (warning/critical)
├── quota_percentage
├── notified
├── resolved
└── timestamps
```

### WordPress Options

```php
wpste_settings = [
    'default_language' => 'en',
    'enabled_languages' => ['en', 'uk', 'de'],
    'primary_provider' => 'deepl',
    'fallback_providers' => ['azure', 'aws'],
    'post_types' => ['post', 'page'],
    'url_structure' => 'subdirectory',
    'cache_ttl' => 300,
];

wpste_license = [
    'tier' => 'free',
    'status' => 'inactive',
    'features' => [],
    'limits' => [],
];

wpste_db_version = '1.0.0';
```

### Post Meta

```php
_wpste_lang_code = 'uk'                    # Post language
_wpste_translation_group = 'uuid-v4'       # Link translations
_wpste_source_post_id = 123                # Original post
```

---

## Tier System

### Feature Matrix

| Feature | Free | Starter | Basic | Plus | Pro | Agency | Enterprise |
|---------|------|---------|-------|------|-----|--------|------------|
| **Languages** | 3 | 4 | 7 | 11 | ∞ | ∞ | ∞ |
| **Providers** | 1 choice | 1 choice | 2 | All 3 | All 3 | All 3 | All 3 |
| **Keys/Provider** | 1 | 2 | 2 | 3 | ∞ | ∞ | ∞ |
| **Bulk Translate** | - | - | 10 | 50 | ∞ | ∞ | ∞ |
| **Translation Memory** | - | - | - | - | ✓ | ✓ | ✓ |
| **Glossary** | - | - | - | - | ✓ | ✓ | ✓ |
| **SEO Integration** | - | - | - | - | - | ✓ | ✓ |
| **White-Label** | - | - | - | - | - | ✓ | ✓ |
| **Team Management** | - | - | - | - | - | - | ✓ |
| **Workflows** | - | - | - | - | - | - | ✓ |
| **Analytics** | - | - | - | - | - | - | ✓ |

### Tier Enforcement

**Free Tier Limits** (enforced in code):
- Language selector validates max 3 languages (JavaScript + server-side)
- Provider selection limited to 1 (UI hides fallback fields)
- Feature checks via `Tier_Manager::has_feature()`

**Premium Tiers** (enforced by feature availability):
- Features only load if downloaded and tier permits
- License validation happens every 7 days (cached)
- 30-day grace period if server unreachable

---

## Security Considerations

### API Key Storage

```php
// Encrypted storage
$key = wp_salt('auth');
$encrypted = openssl_encrypt($api_key, 'AES-256-CBC', $key, 0, substr($key, 0, 16));

// Cached with transients (5 min)
set_transient('wpste_api_keys_deepl', $keys, 300);
```

### License Key Storage

```php
// Encrypted in database
$license_encrypted = openssl_encrypt($license_key, 'AES-256-CBC', wp_salt('auth'), 0, substr(wp_salt('auth'), 0, 16));
```

### Feature Download Verification

```php
// SHA-256 checksum verification
$actual_checksum = hash_file('sha256', $downloaded_file);
if (!hash_equals($expected_checksum, $actual_checksum)) {
    // Reject and delete file
}
```

### WordPress Security

- **Nonces**: All forms use `wp_nonce_field()` / `check_admin_referer()`
- **Capabilities**: Check `current_user_can('manage_options')` / `current_user_can('edit_posts')`
- **Sanitization**: `sanitize_text_field()`, `absint()`, `esc_url()`
- **Escaping**: `esc_html()`, `esc_attr()`, `wp_kses_post()`
- **Prepared Statements**: `$wpdb->prepare()` for all SQL

---

## API Integration

### DeepL API (Fully Implemented)

```php
Endpoint: https://api-free.deepl.com/v2/translate
Method: POST
Auth: DeepL-Auth-Key {api_key}

Request:
{
    "text": ["Hello world"],
    "source_lang": "EN",
    "target_lang": "UK",
    "preserve_formatting": true,
    "tag_handling": "html"
}

Response:
{
    "translations": [{
        "text": "Привіт світ",
        "detected_source_language": "EN"
    }]
}
```

### Azure Translator API (TODO)

```php
Endpoint: https://api.cognitive.microsofttranslator.com/translate?api-version=3.0
Method: POST
Auth: Ocp-Apim-Subscription-Key: {api_key}
```

### AWS Translate API (TODO)

```php
// Uses AWS SDK for PHP
$client = new Aws\Translate\TranslateClient([
    'version' => 'latest',
    'region' => 'us-east-1',
    'credentials' => [...]
]);
```

---

## Deployment Strategy

### Free Version (WordPress.org)

1. Public GitHub repository: `cleargoal/wordpress-translator`
2. WordPress.org SVN repository
3. Automated deployments via GitHub Actions

### Premium Features

1. Private GitHub repository: `cleargoal/wordpress-translator-premium`
2. Build process creates versioned ZIPs
3. Upload to private CDN/S3
4. License server tracks versions and provides download URLs

### License Server

1. Separate hosting (not WordPress)
2. Laravel/Node.js application
3. PostgreSQL/MySQL database
4. Integration with payment gateway
5. Email service for notifications

---

## File Organization Summary

### Public Repository (`wordpress-translator`)

```
✅ Currently in repository:
- Core translation system
- Provider interfaces & abstracts
- DeepL provider (full)
- Key management
- Database layer
- Licensing framework (client-side only)
- Admin UI
- REST API
- WP-CLI
- Configuration files

❌ NOT in repository:
- License server code
- Premium feature implementations
- Payment processing
```

### Private Repository (`wordpress-translator-premium`)

```
📦 Future implementation:
- Translation Memory
- Smart Rotation (enhanced)
- Glossary
- SEO Integration
- White-Label
- Team Management
- Approval Workflows
- Advanced Analytics
- Build scripts
```

### Separate Project (License Server)

```
🔐 Separate Laravel/Node.js app:
- License validation API
- Customer management
- Payment integration
- Feature distribution
- Analytics & reporting
```

---

## Key Architectural Decisions

1. **Split Licensing**: Free (GPL) + Premium (Proprietary) for competitive advantage
2. **Feature Downloads**: Premium features downloaded on-demand, not bundled
3. **Client-Side Validation**: License validation cached locally (7 days)
4. **Provider Abstraction**: Easy to add new translation providers
5. **Smart Key Rotation**: Quota-based rotation for optimal key usage
6. **WordPress Native**: Minimal external dependencies, uses WordPress APIs
7. **Security First**: Encryption, nonces, capabilities, prepared statements
8. **Extensible**: Hooks and filters throughout for customization

---

## References

- **Public Repo**: https://github.com/cleargoal/wordpress-translator
- **Private Repo**: https://github.com/cleargoal/wordpress-translator-premium (future)
- **WordPress.org**: Coming soon
- **License Server**: Separate project (not yet implemented)

---

**Version**: 1.0.0-dev
**Status**: Core functional, premium architecture defined
**Last Updated**: 2026-03-28
