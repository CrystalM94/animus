<?php
/**
 * Shared helpers for Animus Labs Core.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a plugin setting with fallback.
 *
 * @param string $key     Setting key.
 * @param string $default Fallback value.
 * @return string
 */
function animus_core_setting( $key, $default = '' ) {
	$opts = get_option( 'animus_labs_settings', array() );
	if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
		return $opts[ $key ];
	}
	$defaults = animus_core_default_settings();
	return isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default;
}

/**
 * All default settings — also the canonical list of editable compliance strings.
 *
 * @return array<string,string>
 */
function animus_core_default_settings() {
	return array(
		'topbar_notice'       => __( 'For laboratory research use only — not for human or veterinary use.', 'animus-labs-core' ),
		'ruo_notice'          => __( 'All products are sold strictly for laboratory research use only. Not for human or veterinary use, consumption, or diagnostic purposes.', 'animus-labs-core' ),
		'checkout_ack'        => __( 'I confirm that I am purchasing these products exclusively for legitimate laboratory research purposes, that they will not be used in or on humans or animals, and that I have read and agree to the Research Use Policy.', 'animus-labs-core' ),
		'checkout_ack_sub'    => __( 'Your acknowledgement, timestamp, and the current policy version are recorded with this order.', 'animus-labs-core' ),
		'policy_version'      => '1.0',
		'verification_intro'  => __( 'Enter the lot number printed on your product label to view its documentation.', 'animus-labs-core' ),
		'order_lot_note'      => __( 'The lot number shipped with each item is recorded permanently on this order.', 'animus-labs-core' ),
	);
}

/**
 * Public URL for a lot verification page.
 *
 * @param string $lot Lot number.
 * @return string
 */
function animus_lot_url( $lot ) {
	return home_url( '/verify/' . rawurlencode( $lot ) . '/' );
}
