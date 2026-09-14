# WooCommerce configuration guide

`scripts/setup-store.php` applies the baseline automatically. This document explains each decision so it can be reviewed and adjusted.

## Payments

> ## PAYMENT PROVIDER COMPLIANCE REVIEW REQUIRED
>
> No card processor may be enabled for this store until the provider has reviewed the product category in writing and approved the account. Stripe, PayPal, Square, and WooPayments prohibit this category; enabling them results in frozen funds and account termination.

### What ships enabled

WooCommerce's built-in offline methods, which require no underwriting:

- **Bank transfer / eCheck (ACH)** (`bacs`) — enabled by default. Orders are created `on-hold` and released manually once payment clears.
- **Cash on delivery** (`cod`) — available but disabled.
- **Check payments** (`cheque`) — available but disabled.

Configure the receiving account details at **WooCommerce → Settings → Payments → Bank transfer**. Never place account numbers in theme or plugin files.

### Adding a card or crypto gateway later

Checkout is not customised per gateway. The RUO acknowledgement is registered as a WooCommerce additional checkout field and validated server-side, independently of the payment method, so a gateway can be swapped without touching checkout:

1. Obtain written approval from a high-risk processor that accepts research chemicals / RUO peptides.
2. Install that provider's official WooCommerce gateway plugin (or an NMI/PayTrace-compatible one).
3. Enter credentials in the gateway's settings screen — never in code, never in the repository.
4. Place a live test order end-to-end and confirm the acknowledgement, lot snapshot, and order emails all still fire.
5. Leave `bacs` enabled as a fallback for when card processing is interrupted.

Do not build or install anything intended to disguise the product category or bypass a provider's restrictions.

## Products and catalog

- **Shop page display**: products (not categories) — `woocommerce_shop_page_display` is empty
- **Category archive display**: products — `woocommerce_category_archive_display` is empty
- **Products per page**: 12, in a three-column grid
- **Reviews**: disabled. Customer reviews on RUO products invite human-use claims that create regulatory exposure.
- **Stock management**: enabled, with per-product stock quantities
- **Featured products**: the six documented sample products, shown on the homepage

## Accounts

- Guest checkout: **disabled**. Every order is tied to an account, which keeps order history, lot records, and acknowledgements attached to an identifiable customer.
- Account creation during checkout: enabled
- Account erasure requests: review manually — compliance acknowledgements and lot records must be preserved even when personal data is removed.

## Taxes

Taxes are enabled with prices entered exclusive of tax. Rates are intentionally **not** preloaded: nexus determination is a business decision. Before launch, either enter rates under **WooCommerce → Settings → Tax** or connect an automated tax service, and confirm the treatment of research chemicals in each state where you have nexus.

## Shipping

A US zone is created with two flat rates:

| Method | Cost |
| --- | --- |
| Standard shipping (tracked) | $12.00 |
| Expedited shipping | $28.00 |

To restrict where you ship, use **WooCommerce → Settings → Shipping**:

- **Country / state**: add zones per country or state, and set *Shipping location(s)* to "Ship to specific countries only".
- **Region / postal code**: a zone can match postal codes and wildcards (`78*`) — use this to exclude jurisdictions that restrict these materials.
- Any destination with no matching zone gets no shipping methods, which blocks checkout for that address.

Review restricted jurisdictions with counsel and reflect them in both the shipping zones and the Shipping Policy page.

### Tracking and shipment status

Each order has an Animus shipment panel for carrier, tracking number, tracking URL, and status (`preparing`, `shipped`, `in_transit`, `delivered`, `exception`). The tracking number and status appear on the customer's order detail view.

## Emails

Set **WooCommerce → Settings → Emails** "From" address to a mailbox on your own domain and send through an authenticated SMTP relay with SPF, DKIM, and DMARC configured. Order emails carry lot numbers, so delivery reliability matters.

## Compliance settings

**Animus Labs → Settings** holds all customer-facing compliance language, editable without touching code:

- RUO notice text used across storefront, cart, checkout, and order pages
- Checkout acknowledgement text and sub-text
- Policy version string — recorded on every order alongside the acknowledgement, so bump it whenever the acknowledgement wording changes
- Restricted-access gate: on/off, minimum age, title, body copy

The acknowledgement is a required checkbox in the block checkout and is enforced server-side; an order cannot be created without it. Each order stores the acknowledgement flag, a UTC timestamp, the policy version, and a copy of the exact text shown, visible on the admin order screen.

## Analytics

`woocommerce_allow_tracking` is disabled. Do not add third-party marketing pixels without confirming that the vendor's terms permit this product category.
