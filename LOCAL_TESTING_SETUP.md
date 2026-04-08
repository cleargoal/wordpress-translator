# Local Testing Setup

## Configure License Server URL

Add to your test site's `wp-config.php`:

```php
// Near the top, after ABSPATH check
define( 'WPSTE_LICENSE_SERVER_URL', 'http://localhost:8016' );
```

**Location**: `/home/yefrem/Local Sites/translation-test/app/public/wp-config.php`

## How It Works

When you click "Upgrade" button:
1. WordPress generates UUID (first time only)
2. Redirects to: `http://localhost:8016/checkout?tier=pro&period=yearly&...`
3. License API shows checkout page
4. On payment success → redirects back with license key

## Test Without Payment

Manually activate a license:

```bash
wp option update wpste_license '{"key":"TEST-PRO-1234","tier":"pro","status":"active"}' --format=json
```

Or via WordPress admin:
1. Go to MySQL database
2. Find `wp_options` table
3. Add/update row:
   - `option_name`: `wpste_license`
   - `option_value`: `{"key":"TEST-PRO-1234","tier":"pro","status":"active"}`
