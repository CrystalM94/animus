# Database setup and schema

## Server configuration

- Character set `utf8mb4`, collation `utf8mb4_unicode_520_ci`
- A dedicated database user per environment (staging and production must not share credentials)
- `GRANT ALL` on that one schema only — never global privileges
- Remote access disabled; connect over a socket or localhost where possible
- `wp-config.php` table prefix left at whatever the installer generated; if you change it, change it before any content exists

```sql
CREATE DATABASE animus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
CREATE USER 'animus'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON animus.* TO 'animus'@'localhost';
FLUSH PRIVILEGES;
```

## WooCommerce tables

WooCommerce creates its own schema on activation, including the High-Performance Order Storage (HPOS) order tables when enabled. Nothing in this project writes to WooCommerce tables directly — all order and product access goes through `WC_Order`, `WC_Product`, and the CRUD APIs, which keeps HPOS and the legacy post storage both supported.

## Tables created by animus-labs-core

### `{prefix}animus_audit_log`

Created on plugin activation by `Animus_Audit::install()`. Append-only record of privileged and compliance-relevant actions.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` auto-increment | Primary key |
| `created_at` | `DATETIME` | UTC |
| `user_id` | `BIGINT UNSIGNED` | 0 for unauthenticated actions such as public lot lookups |
| `action` | `VARCHAR` | e.g. `ruo_acknowledged`, `batch_approved`, `lot_lookup`, `lot_assigned` |
| `object_type` | `VARCHAR` | e.g. `order`, `batch`, `lot` |
| `object_id` | `BIGINT UNSIGNED` | Related record |
| `context` | `LONGTEXT` | JSON detail |
| `ip` | `VARCHAR` | Truncated request IP |

Indexed on `created_at`, `action`, and `(object_type, object_id)`.

Retention is not automatic. Compliance records are intentionally kept indefinitely; if a retention policy is required, archive rows out rather than deleting them in place.

## Custom post type

Batches are stored as the `animus_batch` post type rather than a bespoke table, so they inherit WordPress revisioning, capabilities, and backup coverage.

Batch meta keys:

| Meta key | Meaning |
| --- | --- |
| `_animus_batch_lot` | Lot / batch number (unique, used by public verification) |
| `_animus_batch_product` | Related WooCommerce product ID |
| `_animus_batch_purity` | Verified purity (%) |
| `_animus_batch_quantity` | Quantity per unit |
| `_animus_batch_tested_on` | Testing date |
| `_animus_batch_retest_on` | Expiration or retest date |
| `_animus_batch_method` | Testing method |
| `_animus_batch_lab` | Testing laboratory / provider |
| `_animus_batch_storage` | Storage requirements |
| `_animus_batch_coa_id` | COA attachment ID |
| `_animus_batch_sds_id` | SDS attachment ID |
| `_animus_batch_approved` | `yes` once released; only approved batches are publicly verifiable |
| `_animus_batch_is_current` | `yes` for the lot currently shipping for that product |

## Product meta

Specification fields live on the product as meta: `_animus_purity`, `_animus_quantity`, `_animus_storage`, `_animus_testing_method`, `_animus_testing_lab`, `_animus_formula`, `_animus_weight`, `_animus_cas`, `_animus_source_region`. SKU, price, and stock remain native WooCommerce fields.

## Order item meta (lot traceability)

When an order is placed, the current lot is copied onto each order line item:

| Meta key | Meaning |
| --- | --- |
| `_animus_lot` | Lot number shipped for that line |
| `_animus_batch_id` | Batch record ID at time of sale |

Because the lot number is snapshotted onto the order item rather than looked up from the product at display time, historical orders keep showing the lot that actually shipped after new inventory arrives. Never backfill or rewrite these values; correct a mistake by reassigning the lot in the admin order screen, which writes a new value and logs the change.

## Shipment meta

`_animus_carrier`, `_animus_tracking`, `_animus_tracking_url`, and `_animus_shipment_status` (`preparing`, `shipped`, `in_transit`, `delivered`, `exception`) are stored on the order.
