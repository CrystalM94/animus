# Security checklist

Work through this before launch and re-review after any significant change. Items marked **code** are already implemented in this project; the rest are environment and operational responsibilities.

## Application code

- [x] **code** — All admin form submissions verify a nonce (`wp_verify_nonce` / `check_admin_referer`)
- [x] **code** — Every privileged action checks a capability (`manage_woocommerce`, `edit_shop_orders`, `edit_posts`) rather than assuming an admin context
- [x] **code** — All input is sanitised on the way in (`sanitize_text_field`, `absint`, `sanitize_key`, `wp_kses_post` for rich text)
- [x] **code** — All output is escaped at the point of output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)
- [x] **code** — Database access uses `$wpdb->prepare` or the WooCommerce/WordPress CRUD APIs; no interpolated SQL
- [x] **code** — The RUO acknowledgement is validated server-side; a client bypass cannot create an order without it
- [x] **code** — Public lot verification exposes only approved, published batches — draft and unapproved batches are invisible
- [x] **code** — Lot lookups are rate limited (30 per 5 minutes per IP) to prevent lot-number enumeration
- [x] **code** — Batch document uploads are restricted to PDFs and checked server-side
- [x] **code** — Privileged and compliance actions are written to an append-only audit log
- [x] **code** — No secrets, API keys, or credentials in theme or plugin files; gateway credentials live in the database via the gateway's settings screen
- [x] **code** — WordPress and WooCommerce core files are unmodified

## wp-config.php

```php
define( 'DISALLOW_FILE_EDIT', true );   // no theme/plugin editor in admin
define( 'DISALLOW_FILE_MODS', true );   // no installs/updates from the dashboard (deploy instead)
define( 'FORCE_SSL_ADMIN', true );
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );

// Secure cookies
define( 'COOKIE_DOMAIN', 'your-domain.example' );
@ini_set( 'session.cookie_httponly', true );
@ini_set( 'session.cookie_secure', true );
```

- [ ] Unique auth salts generated per environment (never copy staging salts to production)
- [ ] Database credentials unique per environment
- [ ] File permissions: directories `755`, files `644`, `wp-config.php` `600`, owned by a non-web-server user where possible
- [ ] PHP execution denied in `wp-content/uploads/` at the web server level

## HTTPS and headers

- [ ] Valid TLS certificate, HTTP redirected to HTTPS
- [ ] HSTS enabled (`Strict-Transport-Security: max-age=31536000; includeSubDomains`)
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `X-Frame-Options: SAMEORIGIN` (or a `frame-ancestors` CSP)
- [ ] `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] Content Security Policy reviewed once the final asset origins (CDN, gateway scripts) are known
- [ ] Session cookies flagged `Secure`, `HttpOnly`, and `SameSite=Lax`

## Accounts and access

- [ ] No account named `admin`; the original installer account renamed or removed
- [ ] One administrator account per person — no shared logins
- [ ] Staff use `shop_manager`, not `administrator`, unless they genuinely need it
- [ ] Strong unique passwords enforced, stored in a password manager
- [ ] **Two-factor authentication enabled for every administrator and shop manager.** WordPress has no built-in 2FA; install a maintained plugin (Two-Factor, WP 2FA, or your identity provider's SSO plugin) and require it for all privileged roles.
- [ ] XML-RPC disabled unless something specifically needs it
- [ ] `/wp-login.php` rate limited or behind an allowlist; login attempt limiting in place
- [ ] User enumeration via REST API and author archives restricted
- [ ] Admin sessions reviewed and stale accounts removed quarterly

## Rate limiting and abuse

- [x] **code** — Lot verification lookups rate limited
- [ ] Web server or WAF rate limiting on `/wp-login.php`, `/wp-admin/admin-ajax.php`, and the WooCommerce Store API
- [ ] Bot protection on the account registration and contact forms
- [ ] Fail2ban or equivalent on repeated authentication failures

## Uploads and documents

- [x] **code** — COA/SDS uploads restricted to PDF
- [ ] Uploads directory served without PHP execution
- [ ] Antivirus scanning on uploaded files if staff upload documents received from suppliers
- [ ] Consider whether COA/SDS PDFs should be publicly reachable by direct URL, or served through an authenticated handler

## Monitoring

- [ ] File integrity monitoring on `wp-content/`
- [ ] Off-site log shipping for web server, PHP error, and audit logs
- [ ] Alerting on new administrator account creation and on plugin/theme activation
- [ ] Uptime and TLS expiry monitoring
- [ ] Audit log reviewed on a schedule, not just after an incident

## Backups

- [ ] Daily automated database and uploads backups (`docs/05-backup-and-restore.md`)
- [ ] Off-site, encrypted at rest
- [ ] Restore rehearsed into staging within the last 30 days

## Data protection

- [ ] Privacy Policy reflects what is actually collected, including the checkout acknowledgement record
- [ ] A documented process for handling data export and erasure requests that still preserves compliance records
- [ ] Payment card data never touches your server — use a gateway that tokenises or redirects
- [ ] Third-party scripts kept to a minimum; each one reviewed for whether its terms allow this product category
