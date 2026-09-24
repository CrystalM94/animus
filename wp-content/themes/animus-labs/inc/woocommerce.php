<?php
/**
 * WooCommerce integration for the Animus Labs theme.
 * Styling and layout only — no core overrides.
 *
 * @package Animus_Labs
 */

defined( 'ABSPATH' ) || exit;

// Full design control: we ship our own WooCommerce styles.
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

// Products per page / columns.
add_filter( 'loop_shop_per_page', function () { return 12; } );
add_filter( 'loop_shop_columns', function () { return 3; } );

// RUO notices woven into commerce flow.
add_action( 'woocommerce_before_shop_loop', 'animus_ruo_notice', 5 );
add_action( 'woocommerce_before_single_product', function () { animus_ruo_notice( 'product' ); }, 4 );

add_action( 'woocommerce_before_cart', 'animus_ruo_notice', 5 );
add_action( 'woocommerce_before_checkout_form', 'animus_ruo_notice', 5 );
add_action( 'woocommerce_before_thankyou', 'animus_ruo_notice', 5 );

// Remove default chrome we restyle ourselves; woocommerce.php wraps content itself.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

// Keep default Woo sidebar off; our layouts are single column.
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

// Product cards: wrap with lot/spec teaser handled by plugin when present.
add_action( 'woocommerce_shop_loop_item_title', function () {
	echo '<span class="animus-card__rule" aria-hidden="true"></span>';
}, 5 );

// Breadcrumb restyle.
add_filter(
	'woocommerce_breadcrumb_defaults',
	function ( $defaults ) {
		$defaults['delimiter']   = ' / ';
		$defaults['wrap_before'] = '<nav class="animus-breadcrumb" aria-label="Breadcrumb">';
		$defaults['wrap_after']  = '</nav>';
		return $defaults;
	}
);

// Checkout: tighten fields via filters (layout only).
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	if ( isset( $fields['billing']['billing_company'] ) ) {
		$fields['billing']['billing_company']['label']       = __( 'Institution / Laboratory (optional)', 'animus-labs' );
		$fields['billing']['billing_company']['placeholder'] = __( 'Institution or laboratory name', 'animus-labs' );
	}
	return $fields;
} );
