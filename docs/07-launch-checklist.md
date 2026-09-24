# Launch checklist

## Blocking — do not launch without these

- [ ] **Regulatory review.** A qualified attorney has reviewed the site copy, product pages, policy pages, and the checkout acknowledgement. Nothing on the site implies human use, dosing, or a therapeutic benefit.
- [ ] **PAYMENT PROVIDER COMPLIANCE REVIEW REQUIRED** — a processor has approved this product category in writing, or the store launches on offline payment methods only.
- [ ] Business entity, business bank account, and supplier agreement in place.
- [ ] Real COA and SDS documents uploaded for every lot that is actually sellable. The six batches created by the provisioning script are samples and must be removed or replaced.
- [ ] Every product either has a current approved batch, or is set out of stock.
- [ ] Insurance reviewed (product liability, general liability).
- [ ] `docs/06-security-checklist.md` completed, including 2FA for all privileged accounts.
- [ ] A verified backup exists and a restore has been rehearsed.

## Content

- [ ] Placeholder vial imagery replaced with real product photography
- [ ] Product descriptions reviewed one by one for prohibited claims — no dosing, injection, protocol, cycle, treatment, or human-use language
- [ ] Policy pages populated with your real entity name, address, and contact details (the shipped copies are templates)
- [ ] Contact page routes to a monitored mailbox
- [ ] FAQ answers reviewed for compliance tone
- [ ] Prices confirmed against the supplier catalog and your intended markup
- [ ] Category assignments spot-checked
- [ ] Restricted-access gate copy and minimum age confirmed
- [ ] Coming-soon heading and copy set while the site is pre-launch, and **Coming soon** switched off at launch
- [ ] Checkout acknowledgement text final, and the policy version string set to its launch value

## Commerce

- [ ] Test order placed end-to-end on staging, and again on production once payments are live
- [ ] Acknowledgement cannot be bypassed — confirm the order is refused when the box is unchecked
- [ ] Lot number appears on the order, and on the customer's order history
- [ ] `/verify/` resolves an approved lot and rejects an unknown one
- [ ] Refund tested through WooCommerce
- [ ] Coupon tested
- [ ] Tax rates entered for every state with nexus
- [ ] Shipping zones restrict to the jurisdictions you have decided to ship to, and nowhere else
- [ ] Tracking number and shipment status appear to the customer
- [ ] Stock quantities loaded and matching supplier availability
- [ ] Order confirmation, processing, completed, and refunded emails all received and correctly branded

## Technical

- [ ] HTTPS enforced site-wide, certificate auto-renewing
- [ ] Pretty permalinks active and rewrite rules flushed
- [ ] Page caching and object caching configured, with cart and checkout excluded
- [ ] CDN serving static assets, with the correct cache headers
- [ ] Mobile verified on iPhone (Safari), Android (Chrome), and iPad — storefront and full checkout
- [ ] Core Web Vitals checked on the homepage, shop, and a product page
- [ ] 404 page, search, and pagination all work
- [ ] `robots.txt` and sitemap correct; staging still blocked from indexing
- [ ] Uptime monitoring and error alerting live
- [ ] Audit log confirmed to be recording

## Operational readiness

- [ ] Staff trained on: creating a batch, uploading COA/SDS, approving a batch, marking it current, assigning lots to orders, entering tracking, issuing refunds
- [ ] Documented procedure for what happens when a new lot arrives (create batch → upload documents → approve → mark current)
- [ ] Documented procedure for a customer disputing a lot's documentation
- [ ] Someone owns the compliance mailbox and knows how to respond to a regulatory inquiry
- [ ] Support response time and escalation path defined

## Day-one monitoring

- [ ] Watch the first live orders individually — confirm acknowledgement, lot assignment, and payment settlement on each
- [ ] Check the audit log at the end of the first day
- [ ] Confirm order emails are being delivered, not spam-filed
- [ ] Confirm the backup ran
