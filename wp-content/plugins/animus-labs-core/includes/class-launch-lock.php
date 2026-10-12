<?php
/**
 * Pre-launch checkout lock. The storefront stays fully browsable while
 * purchasing is disabled until launch.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Launch_Lock {

	public static function init() {
		if ( 'yes' !== animus_core_setting( 'checkout_locked', 'no' ) ) {
			return;
		}
		add_filter( 'woocommerce_is_purchasable', '__return_false' );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'notice' ), 25 );
		add_action( 'wp_loaded', array( __CLASS__, 'purge_carts' ) );
	}

	/**
	 * Notice shown where the add-to-cart button would be.
	 */
	public static function notice() {
		echo '<p class="animus-launch-lock-notice">' . esc_html( animus_core_setting( 'checkout_locked_msg' ) ) . '</p>';
	}

	/**
	 * Carts created before the lock can't hold anything — WC also rejects
	 * orders on non-purchasable items, this just keeps the cart clean.
	 */
	public static function purge_carts() {
		if ( function_exists( 'WC' ) && WC()->cart && ! is_admin() ) {
			WC()->cart->empty_cart();
		}
	}
}
