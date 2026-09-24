# Deployment

## Environments

| Environment | Purpose |
| --- | --- |
| Local | Development. WP-CLI server or a container stack. |
| Staging | Mandatory rehearsal for every change. Same PHP/MySQL versions as production, its own database and credentials, search engine indexing off, HTTP auth in front of it. |
| Production | Live store. |

Every change goes local → staging → production. Never edit theme or plugin files on production.

## What is deployed

Only two directories come from this repository:

```
wp-content/themes/animus-labs/
wp-content/plugins/animus-labs-core/
```

WordPress core, WooCommerce, uploads, and `wp-config.php` are **not** in the repository and must never be overwritten by a deploy.

## Deploying

```bash
# from a checkout of the target revision
rsync -a --delete \
  wp-content/themes/animus-labs/ \
  user@host:/var/www/animus/wp-content/themes/animus-labs/

rsync -a --delete \
  wp-content/plugins/animus-labs-core/ \
  user@host:/var/www/animus/wp-content/plugins/animus-labs-core/
```

`--delete` is safe **scoped to these two component directories** — it removes files deleted from the repo. It is never safe one level up at `wp-content/`, which would delete WooCommerce and all uploads.

Then, on the target:

```bash
cd /var/www/animus
wp eval-file /path/to/repo/scripts/setup-store.php   # idempotent; re-run only when catalog/pages changed
wp rewrite flush --hard
wp cache flush
```

If a page or object cache plugin or a CDN is in front of the site, purge it after deploying.

## Deployment order for a release

1. Take a backup of production (`docs/05-backup-and-restore.md`) and verify it exists.
2. Deploy to staging, run the smoke test below, and confirm.
3. Put production into maintenance mode if the release changes checkout or the batch schema.
4. Deploy to production.
5. Flush rewrites and caches, purge the CDN.
6. Run the smoke test against production.
7. Leave maintenance mode.

## Post-deploy smoke test

Run against staging first, then production:

- Homepage loads; restricted-access gate appears and accepts entry
- `/shop/` lists products with images, prices, and working pagination
- A product page shows the full specification table and its documentation tab
- Add to cart → cart → checkout completes with the RUO acknowledgement **required** (verify an order cannot be placed with it unchecked)
- The completed order shows the correct lot number, linked to its verification page
- `/verify/` resolves a known approved lot and rejects an unknown one
- Admin: order screen shows the acknowledgement with timestamp and policy version, plus the lot assignment and shipment panels
- Order emails are received

## Updates

WordPress, WooCommerce, and PHP updates are applied on staging first and only promoted after the smoke test passes. Automatic updates for major versions should stay disabled — WooCommerce major releases occasionally change checkout internals that the acknowledgement field hooks into.

## Rollback

Theme and plugin code roll back by redeploying the previous revision:

```bash
git checkout <previous-tag>
# re-run the rsync commands above
```

A code rollback does not undo database changes. If a release changed data, restore the database from the pre-release backup as described in `docs/05-backup-and-restore.md`. Orders placed after the backup would be lost, so for anything touching order data, take the store offline for the release window.
