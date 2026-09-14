# Installation

Animus Labs is a WordPress + WooCommerce site made of two components in this repository:

| Component | Path | Purpose |
| --- | --- | --- |
| Theme | `wp-content/themes/animus-labs` | Standalone theme: storefront design, templates, WooCommerce overrides |
| Plugin | `wp-content/plugins/animus-labs-core` | Product specification fields, batch/COA/SDS records, lot verification, lot traceability, RUO compliance, audit log |

Neither component modifies WordPress or WooCommerce core.

## Requirements

- PHP 8.1 or newer, with `mbstring`, `gd` (or `imagick`), `curl`, `zip`, `intl`
- MySQL 5.7+ or MariaDB 10.4+
- WordPress 6.7+ (developed against 7.1)
- WooCommerce 9.0+ (developed against 11.1)
- HTTPS in staging and production
- WP-CLI (required for the provisioning script)

## 1. Database

```sql
CREATE DATABASE animus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
CREATE USER 'animus'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON animus.* TO 'animus'@'localhost';
FLUSH PRIVILEGES;
```

Use a database user dedicated to this site. Do not reuse a shared or root account.

## 2. WordPress core

```bash
wp core download --path=/var/www/animus
cd /var/www/animus
wp config create --dbname=animus --dbuser=animus --dbpass='REPLACE_WITH_STRONG_PASSWORD' --dbhost=localhost
wp core install --url='https://your-domain.example' --title='Animus Labs' \
  --admin_user='REPLACE_ADMIN' --admin_password='REPLACE_STRONG_PASSWORD' --admin_email='ops@your-domain.example'
```

Then apply the hardening constants from `docs/06-security-checklist.md` to `wp-config.php` before going any further.

## 3. WooCommerce

```bash
wp plugin install woocommerce --activate
```

## 4. Animus Labs theme and plugin

Copy both directories out of this repository into the WordPress installation:

```bash
rsync -a wp-content/themes/animus-labs/  /var/www/animus/wp-content/themes/animus-labs/
rsync -a wp-content/plugins/animus-labs-core/ /var/www/animus/wp-content/plugins/animus-labs-core/

cd /var/www/animus
wp theme activate animus-labs
wp plugin activate animus-labs-core
```

Do not use `rsync --delete` against `wp-content/` as a whole — that deletes WooCommerce and the uploads directory. Sync each component directory individually, as shown above.

## 5. Provision the store

The provisioning script is idempotent: it creates anything missing and updates anything that has drifted, so it is safe to re-run after a deploy.

```bash
cd /var/www/animus
wp eval-file /path/to/repo/scripts/setup-store.php
```

It creates:

- the content and policy pages (Contact, FAQ, Privacy Policy, Terms, Shipping Policy, Refund Policy, Research Use Policy, Quality Standards)
- the four product categories
- all 101 catalog products from `data/catalog.json`, with specification meta
- six sample approved batches with lot numbers, marked as current
- WooCommerce base settings (currency, accounts, stock, tax posture)
- a US shipping zone with standard and expedited flat rates
- the primary and footer legal navigation menus

The script reads `data/catalog.json` and `data/pages/*.html` relative to the repository, so keep the repository accessible when running it.

## 6. Product imagery

The repository ships one placeholder vial rendering. Import it and assign it wherever a product or category has no image yet:

```bash
wp media import /path/to/repo/data/product-vial.png --title='Animus product vial'
# note the returned attachment ID, then:
wp eval '$id=ATTACHMENT_ID; foreach(wc_get_products(array("limit"=>-1,"return"=>"ids")) as $p){ if(!get_post_meta($p,"_thumbnail_id",true)) update_post_meta($p,"_thumbnail_id",$id); }'
```

Replace it with real product photography before launch.

## 7. Permalinks

Lot verification relies on rewrite rules, so pretty permalinks are mandatory:

```bash
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard
```

Verify `https://your-domain.example/verify/` loads the lookup form and `/verify/AL-2026-1001/` resolves a batch.

## 8. Payment gateway

**PAYMENT PROVIDER COMPLIANCE REVIEW REQUIRED.** See `docs/03-woocommerce-configuration.md` before enabling any card processor.

## Local development

For a quick local instance without a web server, WP-CLI's built-in server is enough:

```bash
wp server --host=127.0.0.1 --port=8088
```

This is for development only. Never expose `wp server` publicly.
