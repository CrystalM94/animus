# Animus Labs

Research-use-only peptide and laboratory research materials storefront, built on WordPress + WooCommerce.

WooCommerce handles the catalog, cart, checkout, accounts, orders, inventory, coupons, taxes, shipping, refunds, and order history. This repository adds a custom theme and one custom plugin on top. WordPress and WooCommerce core files are not modified.

## Contents

```
wp-content/themes/animus-labs/        Storefront theme
wp-content/plugins/animus-labs-core/  Batches, COA/SDS, verification, traceability, compliance
scripts/setup-store.php               Idempotent WP-CLI provisioning
data/catalog.json                     101-SKU supplier catalog (retail prices only)
data/pages/                            Policy and content page copy
docs/                                  Installation through launch
```

## Documentation

| Document | Covers |
| --- | --- |
| [01-installation.md](docs/01-installation.md) | Requirements, install, provisioning |
| [02-database.md](docs/02-database.md) | Database setup, schema, meta reference |
| [03-woocommerce-configuration.md](docs/03-woocommerce-configuration.md) | Payments, shipping, tax, accounts, compliance settings |
| [04-deployment.md](docs/04-deployment.md) | Staging → production, smoke test, rollback |
| [05-backup-and-restore.md](docs/05-backup-and-restore.md) | Backup schedule, restore, disaster recovery |
| [06-security-checklist.md](docs/06-security-checklist.md) | Security checklist |
| [07-launch-checklist.md](docs/07-launch-checklist.md) | Launch checklist |
| [08-staff-operations.md](docs/08-staff-operations.md) | Day-to-day staff procedures |

## What the plugin adds

**Product specifications** — purity, molecular formula, molecular weight, CAS number, storage requirements, quantity, testing method, and testing laboratory, as a specification table on the product page. SKU, price, and stock stay native to WooCommerce.

**Batch records** — the `animus_batch` post type holds lot number, purity, testing date, retest date, method, laboratory, storage, and COA/SDS PDF attachments. A batch is publicly visible only once approved; one batch per product is flagged current.

**Public lot verification** — `/verify/` accepts a lot number and returns the matching product with its documentation and a QR code linking back to that lot's page. Lookups are restricted to approved batches, rate limited, and audit logged.

**Lot traceability** — the current lot is snapshotted onto each order line item at purchase, so historical orders keep resolving to the lot that actually shipped after new inventory arrives. Staff can reassign a lot per line item from the order screen.

**RUO compliance** — a restricted-access gate, RUO notices throughout the storefront, and a required checkout acknowledgement enforced server-side. Each order records the acknowledgement, a UTC timestamp, the policy version, and a copy of the exact text shown. All compliance language is editable in WP admin.

**Audit log** — append-only record of acknowledgements, batch approvals, lot assignments, and lot lookups.

## Payments

**PAYMENT PROVIDER COMPLIANCE REVIEW REQUIRED.** Stripe, PayPal, Square, and WooPayments prohibit this product category. The store ships with WooCommerce's built-in offline methods (bank transfer / eCheck enabled by default). Checkout is gateway-agnostic — the acknowledgement is a WooCommerce additional checkout field validated independently of the payment method, so an approved high-risk gateway drops in without rebuilding checkout. See [03-woocommerce-configuration.md](docs/03-woocommerce-configuration.md).

## Quick start

```bash
wp core download && wp config create --dbname=animus --dbuser=animus --dbpass='...'
wp core install --url=... --title='Animus Labs' --admin_user=... --admin_password=... --admin_email=...
wp plugin install woocommerce --activate

rsync -a wp-content/themes/animus-labs/  /path/to/wp/wp-content/themes/animus-labs/
rsync -a wp-content/plugins/animus-labs-core/ /path/to/wp/wp-content/plugins/animus-labs-core/
wp theme activate animus-labs && wp plugin activate animus-labs-core

wp eval-file scripts/setup-store.php
wp rewrite structure '/%postname%/' --hard
```

Full detail in [01-installation.md](docs/01-installation.md).

## Compliance scope

This project is built for research-use-only sale. It deliberately contains no dosing, injection, protocol, cycle, or human-use content, and no tooling intended to bypass payment-provider restrictions. Regulatory and payment-processor approval are business prerequisites, not software features — see the blocking items in [07-launch-checklist.md](docs/07-launch-checklist.md).
