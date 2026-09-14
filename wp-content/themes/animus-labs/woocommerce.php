<?php
/**
 * WooCommerce fallback template (shop/cart/checkout/my-account pages).
 *
 * @package Animus_Labs
 */

get_header();
?>
<main id="primary" class="animus-wrap animus-main">
	<?php woocommerce_content(); ?>
</main>
<?php
get_footer();
