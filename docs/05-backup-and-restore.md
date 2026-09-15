# Backup and restore

Order records, batch records, COA/SDS documents, and compliance acknowledgements are business records you may need to produce years later. Treat backups as a compliance requirement, not just disaster recovery.

## What must be backed up

| Item | Location | Why |
| --- | --- | --- |
| Database | MySQL/MariaDB schema | Orders, lot assignments, acknowledgements, batches, audit log, settings |
| Uploads | `wp-content/uploads/` | COA and SDS PDFs, product imagery |
| `wp-config.php` | WordPress root | Salts and database credentials |
| Theme and plugin | in this repository | Code — already versioned |

WordPress core and WooCommerce are reinstallable and do not need backing up.

## Schedule

- **Database**: daily, plus immediately before any deploy or update
- **Uploads**: daily incremental, weekly full
- **Retention**: 30 daily, 12 monthly, and one yearly archive kept for the retention period your counsel specifies for product documentation
- **Off-site**: at least one copy in different infrastructure from the web server, encrypted at rest
- **Verification**: restore into staging at least monthly — an unverified backup is not a backup

## Taking a backup

```bash
cd /var/www/animus
STAMP=$(date -u +%Y%m%dT%H%M%SZ)

wp db export "/var/backups/animus/db-${STAMP}.sql"
gzip "/var/backups/animus/db-${STAMP}.sql"

tar -czf "/var/backups/animus/uploads-${STAMP}.tar.gz" -C wp-content uploads
cp wp-config.php "/var/backups/animus/wp-config-${STAMP}.php"
```

Store `wp-config-*.php` with the same protection as a credential — it contains the database password and the auth salts.

## Restoring

Restore into staging first and confirm the site is healthy before touching production.

```bash
cd /var/www/animus

# 1. Database
gunzip -c /var/backups/animus/db-20260914T000000Z.sql.gz > /tmp/restore.sql
wp db reset --yes            # destroys the current schema — be certain of the target environment
wp db import /tmp/restore.sql

# 2. Uploads
tar -xzf /var/backups/animus/uploads-20260914T000000Z.tar.gz -C wp-content

# 3. Code
#    redeploy the matching revision of the theme and plugin (see docs/04-deployment.md)

# 4. Rebuild derived state
wp rewrite flush --hard
wp cache flush
wp transient delete --all
```

If restoring a production backup into staging, correct the URLs afterwards:

```bash
wp search-replace 'https://your-domain.example' 'https://staging.your-domain.example' --skip-columns=guid
wp option update blog_public 0
```

Never point a staging restore at live payment credentials or a live mail relay.

## Post-restore verification

- `/shop/` and a product page render
- `/verify/` resolves a known approved lot
- A recent order still shows its original lot number, acknowledgement timestamp, and policy version
- COA and SDS PDFs open from a batch record
- Admin can log in and the audit log has its history

## Disaster recovery outline

1. Provision a host meeting the requirements in `docs/01-installation.md`
2. Install WordPress core and WooCommerce at the versions recorded in the release notes
3. Restore the database, uploads, and `wp-config.php`
4. Deploy the theme and plugin at the matching revision
5. Reconfigure the payment gateway credentials (they live only in the database — verify they restored correctly and rotate if the backup's confidentiality is in any doubt)
6. Run the post-deploy smoke test from `docs/04-deployment.md`
