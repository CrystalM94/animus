# Staff operations guide

Everything below happens in WordPress admin. No code changes are required for day-to-day operations.

## Receiving a new lot

When new inventory arrives from the supplier:

1. **Animus Labs → Batches → Add New**
2. Enter the lot number exactly as printed on the label — this is what customers will type into `/verify/`
3. Select the product it belongs to
4. Fill in verified purity, quantity per unit, testing date, retest/expiration date, testing method, testing laboratory, and storage requirements
5. Upload the **COA** and the **SDS** (PDF only)
6. Tick **Approved** once you have checked the documents. Only approved batches are publicly verifiable.
7. Tick **Current** to make this the lot that ships with new orders. This automatically clears the flag from the previous lot for that product.
8. Publish

The batch page shows a QR code that links to that lot's verification page — print it on the label or the packing slip.

Never edit the lot number on an existing batch after orders have shipped against it. Create a new batch instead.

## Fulfilling an order

1. **WooCommerce → Orders**, open the order
2. The **Animus lot assignment** panel lists each line item with the lot that was recorded at purchase. It defaults to whatever was current when the order was placed — change it here if you physically ship a different lot.
3. Enter carrier, tracking number, and tracking URL in the **Shipment** panel, and set the status (`preparing`, `shipped`, `in_transit`, `delivered`, `exception`)
4. Update the order status to Processing, then Completed when it ships

The lot recorded on the order is permanent. When new stock arrives, old orders keep showing the lot that actually shipped — the customer's order history stays accurate forever.

## Checking a compliance acknowledgement

Open any order. Below the billing address, the **Research-use acknowledgement** block shows whether it was confirmed, the UTC timestamp, the policy version in force at the time, and the exact wording the customer agreed to.

If it says "Not recorded", that order did not go through the standard checkout — investigate before shipping.

## Refunds

Use WooCommerce's native refund flow: open the order → **Refund** → enter the amount or line items → choose whether to restock. Refunds against a payment gateway are processed through that gateway; offline payment methods must be refunded manually and recorded here.

Refunding does not remove the lot record or the acknowledgement, and should not.

## Editing compliance language

**Animus Labs → Settings** controls all of it:

- RUO notice shown across the storefront
- Checkout acknowledgement text and sub-text
- Policy version — **bump this whenever you change the acknowledgement wording**, so past orders remain attributable to the text those customers actually saw
- Restricted-access gate: enable/disable, minimum age, title, body

Never change acknowledgement wording without bumping the version.

## Inventory

Stock is managed per product under **Products → edit → Inventory**. Set a product out of stock when it has no current approved batch — selling something you cannot document defeats the whole traceability system.

## Coupons

**Marketing → Coupons**, standard WooCommerce behaviour. Avoid promotional language that implies human use.

## Looking up a lot as a customer sees it

Visit `/verify/` and enter a lot number. Only approved, published batches resolve. Lookups are rate limited and recorded in the audit log.

## Audit log

Privileged and compliance-relevant actions — acknowledgements, batch approvals, lot assignments, lot lookups — are recorded permanently. Review it periodically, not just after something goes wrong.
