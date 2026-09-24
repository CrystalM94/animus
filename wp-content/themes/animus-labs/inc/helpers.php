<?php
/**
 * Shared template helpers.
 *
 * @package Animus_Labs
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return a compliance string from the animus-labs-core plugin settings,
 * falling back to a built-in default when the plugin is inactive.
 *
 * @param string $key     Setting key (ruo_notice, checkout_ack, ...).
 * @param string $default Fallback text.
 */
function animus_setting( $key, $default = '' ) {
	if ( function_exists( 'animus_core_setting' ) ) {
		return animus_core_setting( $key, $default );
	}
	return $default;
}

/**
 * Render the research-use-only notice strip.
 *
 * @param string $context 'banner' | 'inline' | 'product'.
 */
function animus_ruo_notice( $context = 'inline' ) {
	$text = animus_setting(
		'ruo_notice',
		__( 'All products are sold strictly for laboratory research use only. Not for human or veterinary use, consumption, or diagnostic purposes.', 'animus-labs' )
	);
	printf(
		'<div class="animus-ruo animus-ruo--%1$s" role="note"><span class="animus-ruo__mark" aria-hidden="true">&#x039B;</span><p>%2$s</p></div>',
		esc_attr( $context ),
		esc_html( $text )
	);
}

/**
 * Print the header's top compliance bar.
 */
function animus_topbar() {
	$text = animus_setting(
		'topbar_notice',
		__( 'For laboratory research use only — not for human or veterinary use.', 'animus-labs' )
	);
	printf( '<div class="animus-topbar"><div class="animus-wrap">%s</div></div>', esc_html( $text ) );
}
