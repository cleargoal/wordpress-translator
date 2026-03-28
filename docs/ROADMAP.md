# WP Smart Translation Engine - Feature Roadmap

## Current Status

**Version**: 1.0.0-dev
**Status**: Core translation working, testing phase
**Last Updated**: 2026-03-27

---

## Completed Features ✅

### Core Functionality
- [x] Multi-provider support (DeepL, Azure, AWS)
- [x] Smart key rotation per provider
- [x] Post/page translation (title, content, excerpt)
- [x] Translation metabox in post editor
- [x] Draft status for reviewed translations
- [x] Translation groups (link translations together)
- [x] Language-specific post metadata

### Admin UI
- [x] Settings page with language selection
- [x] API Keys management per provider
- [x] Quota tracking (local counting)
- [x] Provider selection (Primary + Fallbacks)
- [x] Checkbox-based language selector with validation
- [x] Table layout for language selection with default radio
- [x] JavaScript validation for tier limits

### Licensing System
- [x] 7-tier structure (Free, Starter, Basic, Plus, Pro, Agency, Enterprise)
- [x] Free tier: 3 languages, 1 provider
- [x] Tier-based feature gating
- [x] License activation UI
- [x] Downloadable premium features architecture

### Developer Features
- [x] REST API endpoints
- [x] WP-CLI commands
- [x] Action/filter hooks
- [x] Translation status tracking in database

---

## Planned Features 📋

### High Priority

#### 1. Taxonomy Translation (Categories & Tags)
**Status**: Planned
**Estimated Time**: 1 week
**Tier**: Free (built-in taxonomies), Pro (custom taxonomies)

**Requirements**:
- New database table: `wp_wpste_term_translations`
- Admin UI: "Translate" button on term edit screens
- Bulk translate: Translate all terms at once
- Frontend: Filter `get_terms()`, `get_the_terms()`, `the_category()`, `the_tags()`
- Settings: Enable/disable per taxonomy
- Support for hierarchical terms (parent-child relationships)

**Implementation Details**:
```sql
CREATE TABLE wp_wpste_term_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    term_id BIGINT UNSIGNED NOT NULL,
    lang_code VARCHAR(10) NOT NULL,
    translated_name VARCHAR(255) NOT NULL,
    translated_slug VARCHAR(255) NOT NULL,
    translated_description TEXT,
    translation_group VARCHAR(36),
    provider_used VARCHAR(50),
    translated_at DATETIME,
    INDEX idx_term_lang (term_id, lang_code),
    INDEX idx_group (translation_group)
);
```

**Files to Create**:
- `includes/core/class-taxonomy-translator.php`
- `admin/partials/term-metabox.php`
- `admin/js/taxonomy-admin.js`
- `public/class-taxonomy-frontend.php`

**Files to Modify**:
- `includes/database/class-installer.php` - Add term_translations table
- `admin/class-admin.php` - Add term edit hooks
- `public/class-public.php` - Add term filters
- `admin/partials/settings-page.php` - Add taxonomy selection

---

#### 2. Custom Fields Translation
**Status**: Planned
**Tier**: Plus (basic), Pro (full)

**Features**:
- ACF integration
- Custom Field Suite integration
- Meta Box integration
- Manual field selection
- Field type detection (text, textarea, wysiwyg)

---

#### 3. Menu Translation
**Status**: Planned
**Tier**: Basic+

**Features**:
- Translate menu items
- Link menus by language
- Auto-switch menu based on current language

---

#### 4. Widget Translation
**Status**: Planned
**Tier**: Basic+

**Features**:
- Translate widget text
- Show/hide widgets by language
- Language-specific widget areas

---

### Medium Priority

#### 5. Translation Memory
**Status**: Architecture ready, implementation pending
**Tier**: Pro+

**Features**:
- Store translated segments with TM scoring
- Fuzzy matching (70%+ similarity)
- Exact match reuse
- Cost savings tracking
- TMX import/export

---

#### 6. Glossary
**Status**: Architecture ready, implementation pending
**Tier**: Pro+

**Features**:
- Term consistency enforcement
- Per-language glossaries
- CSV import/export
- Auto-apply during translation

---

#### 7. SEO Integration
**Status**: Architecture ready, implementation pending
**Tier**: Agency+

**Plugins to Support**:
- Yoast SEO
- Rank Math
- All in One SEO

**Features**:
- Meta title/description translation
- Focus keyword translation
- Schema markup translation
- OpenGraph tags

---

#### 8. URL Structure Options
**Status**: Partially implemented
**Current**: Setting exists but not enforced
**Options**:
- Subdirectory: `/en/post-name/`, `/uk/post-name/`
- Subdomain: `en.example.com`, `uk.example.com`
- Query parameter: `?lang=en`

---

### Low Priority

#### 9. White-Label
**Status**: Architecture ready
**Tier**: Agency+

**Features**:
- Custom plugin name
- Custom logo
- Custom CSS
- Text replacement
- Hide branding

---

#### 10. Team Management
**Status**: Architecture ready
**Tier**: Enterprise

**Features**:
- Role-based access control
- User assignment per language
- Activity logging
- Permission matrix

---

#### 11. Approval Workflows
**Status**: Architecture ready
**Tier**: Enterprise

**Features**:
- Multi-stage review (draft → review → approved)
- Email notifications
- Status tracking
- Workflow customization

---

#### 12. Advanced Analytics
**Status**: Architecture ready
**Tier**: Enterprise

**Features**:
- Cost tracking per language
- Provider performance metrics
- Team productivity reports
- ROI calculations
- Export to PDF/CSV

---

## Bug Fixes & Improvements

### High Priority
- [ ] Remove debug logging before production
- [ ] Add error logging to WordPress debug.log properly
- [ ] Optimize database queries (add indexes)
- [ ] Add loading states to admin UI
- [ ] Better error messages in metabox (show specific API errors)

### Medium Priority
- [ ] Add "Test Connection" button for API keys
- [ ] Show character usage chart/graph
- [ ] Add "Duplicate post" option when translating
- [ ] Preview translation before saving
- [ ] Undo translation option

### Low Priority
- [ ] Add onboarding wizard for first-time setup
- [ ] Add tooltips/help text throughout admin
- [ ] Keyboard shortcuts in editor
- [ ] Dark mode for admin UI

---

## WordPress.org Release Checklist

### Assets Needed
- [ ] Banner graphics (banner-772x250.png, banner-1544x500.png)
- [ ] Icon graphics (icon-128x128.png, icon-256x256.png)
- [ ] 6 screenshots per SCREENSHOTS_GUIDE.md

### Code Requirements
- [ ] Remove temporary test code (all providers in free tier)
- [ ] Revert to DeepL-only for free tier OR keep flexible 1-provider choice
- [ ] Remove debug console.log statements
- [ ] WordPress coding standards compliance check
- [ ] Security audit (nonces, capabilities, sanitization, escaping)
- [ ] Performance testing (query optimization)
- [ ] Browser compatibility testing (Chrome, Firefox, Safari, Edge)

### Documentation
- [ ] README.txt for WordPress.org
- [ ] FAQ section
- [ ] Installation instructions
- [ ] Upgrade guide
- [ ] Changelog

### Testing
- [ ] Fresh installation testing
- [ ] Upgrade path testing
- [ ] Multisite compatibility (if supporting)
- [ ] PHP 7.4, 8.0, 8.1, 8.2 compatibility
- [ ] WordPress 6.0, 6.1, 6.2, 6.3, 6.4, 6.5 compatibility
- [ ] Theme compatibility (major themes)
- [ ] Plugin conflict testing (popular plugins)

---

## Version Planning

### Version 1.0.0 (Initial Release)
- Core post/page translation
- 3 providers (DeepL, Azure, AWS)
- Basic admin UI
- Free tier working
- WordPress.org submission

### Version 1.1.0
- **Taxonomy translation** (categories, tags)
- Improved error handling
- Better admin UX

### Version 1.2.0
- Custom fields translation (ACF, etc.)
- Menu translation
- Widget translation

### Version 1.3.0
- Translation Memory
- Glossary
- Smart rotation improvements

### Version 2.0.0
- URL structure enforcement
- SEO integration (Yoast, Rank Math)
- Advanced analytics
- Major UI redesign

---

## Community Feedback Integration

**Note**: After WordPress.org release, prioritize features based on:
1. User requests (support forum, reviews)
2. Download/active install metrics
3. Competitive analysis
4. Technical feasibility

---

## Notes

- All features follow the tier restrictions defined in `includes/licensing/class-tier-manager.php`
- Premium features are downloadable modules (not bundled in free version)
- License validation happens every 7 days with 30-day grace period
- Feature requests can be submitted via GitHub issues

---

**Next Milestone**: WordPress.org submission (Version 1.0.0)
**Next Feature**: Taxonomy Translation (Version 1.1.0)
