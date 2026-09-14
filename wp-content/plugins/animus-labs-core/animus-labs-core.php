<?php
/**
 * Plugin Name:       Animus Labs Core
 * Description:       Product scientific fields, lot/batch & COA/SDS management, public lot verification with QR codes, order lot traceability, and research-use-only compliance for the Animus Labs WooCommerce store.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * WC requires at least: 8.0
 * Author:            Animus Labs
 * License:           GPL-2.0-or-later
 * Text Domain:       animus-labs-core
 */

defined( 'ABSPATH' ) || exit;

define( 'ANIMUS_CORE_VERSION', '1.0.0' );
define( 'ANIMUS_CORE_FILE', __FILE__ );
define( 'ANIMUS_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANIMUS_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once ANIMUS_CORE_DIR . 'includes/helpers.php';
require_once ANIMUS_CORE_DIR . 'includes/class-audit.php';
require_once ANIMUS_CORE_DIR . 'includes/class-settings.php';
require_once ANIMUS_CORE_DIR . 'includes/class-product-meta.php';
require_once ANIMUS_CORE_DIR . 'includes/class-batches.php';
require_once ANIMUS_CORE_DIR . 'includes/class-verification.php';
require_once ANIMUS_CORE_DIR . 'includes/class-lot-tracking.php';
require_once ANIMUS_CORE_DIR . 'includes/class-compliance.php';
require_once ANIMUS_CORE_DIR . 'includes/class-coming-soon.php';

register_activation_hook( __FILE__, 'animus_core_activate' );

/**
 * Activation: create audit table, register CPT + rewrites, seed settings.
 */
function animus_core_activate() {
	Animus_Audit::create_table();
	Animus_Batches::register_post_type();
	Animus_Verification::add_rewrite_rules();
	Animus_Settings::seed_defaults();
	flush_rewrite_rules();
}

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

add_action(
	'plugins_loaded',
	function () {
		Animus_Settings::init();
		Animus_Product_Meta::init();
		Animus_Batches::init();
		Animus_Verification::init();
		Animus_Lot_Tracking::init();
		Animus_Compliance::init();
		Animus_Coming_Soon::init();
	}
);
