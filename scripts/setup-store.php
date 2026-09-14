<?php
/**
 * Animus Labs store provisioning script.
 *
 * Run through WP-CLI against an installed WordPress + WooCommerce site:
 *
 *   wp eval-file scripts/setup-store.php
 *
 * Idempotent: safe to re-run. Creates policy pages, product categories,
 * the catalog from data/catalog.json, shipping zones, and sample batches.
 *
 * @package Animus_Labs
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "This script must be run through WP-CLI: wp eval-file scripts/setup-store.php\n" );
	exit( 1 );
}

if ( ! class_exists( 'WooCommerce' ) ) {
	WP_CLI::error( 'WooCommerce is not active.' );
}

$repo_root = dirname( __DIR__ );

/* -------------------------------------------------------------------------
 * 1. Pages
 * ---------------------------------------------------------------------- */

/**
 * Create or update a page by slug.
 *
 * @param string $slug    Page slug.
 * @param string $title   Page title.
 * @param string $content Page content.
 * @return int Page ID.
 */
function animus_upsert_page( $slug, $title, $content ) {
	$existing = get_page_by_path( $slug );
	$args     = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);

	if ( $existing ) {
		$args['ID'] = $existing->ID;
		wp_update_post( $args );
		return $existing->ID;
	}

	return (int) wp_insert_post( $args );
}

$pages_dir = $repo_root . '/data/pages';
$page_map  = array();

foreach ( glob( $pages_dir . '/*.html' ) as $file ) {
	$slug  = basename( $file, '.html' );
	$html  = file_get_contents( $file );
	$title = '';

	// First line is an HTML comment holding the title: <!-- title: ... -->
	if ( preg_match( '/^<!--\s*title:\s*(.+?)\s*-->\s*/', $html, $m ) ) {
		$title = $m[1];
		$html  = preg_replace( '/^<!--\s*title:\s*.+?-->\s*/', '', $html );
	}
	if ( ! $title ) {
		$title = ucwords( str_replace( '-', ' ', $slug ) );
	}

	$page_map[ $slug ] = animus_upsert_page( $slug, $title, $html );
	WP_CLI::log( "Page: {$title} (#{$page_map[ $slug ]})" );
}

// Home page.
$home_id = animus_upsert_page( 'home', 'Animus Labs', '' );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

// Terms page for WooCommerce checkout.
if ( isset( $page_map['terms'] ) ) {
	update_option( 'woocommerce_terms_page_id', $page_map['terms'] );
}
if ( isset( $page_map['privacy-policy'] ) ) {
	update_option( 'wp_page_for_privacy_policy', $page_map['privacy-policy'] );
}

/* -------------------------------------------------------------------------
 * 2. Product categories
 * ---------------------------------------------------------------------- */

$categories = array(
	'Peptides'                => 'Lyophilised research peptides, documented to the lot.',
	'Research Blends'         => 'Pre-combined research formulations supplied as single vials.',
	'Oral Research Compounds' => 'Tableted and encapsulated research compounds.',
	'Reagents & Solvents'     => 'Reconstitution solvents and laboratory reagents.',
);

foreach ( $categories as $name => $description ) {
	$term = get_term_by( 'name', $name, 'product_cat' );
	if ( ! $term ) {
		$result = wp_insert_term( $name, 'product_cat', array( 'description' => $description ) );
		if ( ! is_wp_error( $result ) ) {
			WP_CLI::log( "Category: {$name}" );
		}
	} else {
		wp_update_term( $term->term_id, 'product_cat', array( 'description' => $description ) );
	}
}

/* -------------------------------------------------------------------------
 * 3. Catalog
 * ---------------------------------------------------------------------- */

$catalog_file = $repo_root . '/data/catalog.json';
if ( ! file_exists( $catalog_file ) ) {
	WP_CLI::error( "Catalog file missing: {$catalog_file}" );
}

$catalog = json_decode( (string) file_get_contents( $catalog_file ), true );
if ( ! is_array( $catalog ) ) {
	WP_CLI::error( 'Could not parse data/catalog.json' );
}

/**
 * Reference specification data keyed by compound name fragment.
 * Values are factual identity data only — no use instructions.
 */
function animus_reference_specs() {
	return array(
		'BPC-157'     => array( 'formula' => 'C62H98N16O22', 'weight' => '1419.53', 'cas' => '137525-51-0' ),
		'TB500'       => array( 'formula' => 'C212H350N56O78S', 'weight' => '4963.44', 'cas' => '77591-33-4' ),
		'Sermorelin'  => array( 'formula' => 'C149H246N44O42S', 'weight' => '3357.93', 'cas' => '86168-78-7' ),
		'CJC-1295'    => array( 'formula' => 'C152H252N44O42', 'weight' => '3367.79', 'cas' => '863288-34-0' ),
		'GHK-Cu'      => array( 'formula' => 'C14H22CuN6O4', 'weight' => '401.91', 'cas' => '89030-95-5' ),
		'Ipamorelin'  => array( 'formula' => 'C38H49N9O5', 'weight' => '711.85', 'cas' => '170851-70-4' ),
		'Semax'       => array( 'formula' => 'C37H51N9O10S', 'weight' => '813.93', 'cas' => '80714-61-0' ),
		'Selank'      => array( 'formula' => 'C33H57N11O9', 'weight' => '751.88', 'cas' => '129954-34-3' ),
		'Epitalon'    => array( 'formula' => 'C14H22N4O9', 'weight' => '390.35', 'cas' => '307297-39-8' ),
		'Tesamorelin' => array( 'formula' => 'C221H366N72O67S', 'weight' => '5135.86', 'cas' => '218949-48-5' ),
		'MOTS-c'      => array( 'formula' => 'C101H152N28O22S2', 'weight' => '2174.58', 'cas' => '1627580-64-6' ),
		'PT-141'      => array( 'formula' => 'C50H68N14O10', 'weight' => '1025.16', 'cas' => '189691-06-3' ),
		'Melanotan 2' => array( 'formula' => 'C50H69N15O9', 'weight' => '1024.18', 'cas' => '121062-08-6' ),
		'DSIP'        => array( 'formula' => 'C35H48N10O15S', 'weight' => '848.88', 'cas' => '62568-57-4' ),
		'AOD9604'     => array( 'formula' => 'C78H123N23O23S2', 'weight' => '1815.06', 'cas' => '221231-10-3' ),
		'SS-31'       => array( 'formula' => 'C32H49N9O5', 'weight' => '639.79', 'cas' => '736992-21-5' ),
		'NAD+'        => array( 'formula' => 'C21H27N7O14P2', 'weight' => '663.43', 'cas' => '53-84-9' ),
		'Glutathione' => array( 'formula' => 'C10H17N3O6S', 'weight' => '307.32', 'cas' => '70-18-8' ),
		'5-amino-1mq' => array( 'formula' => 'C10H13IN2', 'weight' => '288.13', 'cas' => '117704-25-3' ),
		'Methylene Blue' => array( 'formula' => 'C16H18ClN3S', 'weight' => '319.85', 'cas' => '61-73-4' ),
		'Ivermectin'  => array( 'formula' => 'C48H74O14', 'weight' => '875.09', 'cas' => '70288-86-7' ),
		'MK-677'      => array( 'formula' => 'C27H36N4O5S', 'weight' => '528.67', 'cas' => '159752-10-0' ),
		'Cagrilintide' => array( 'formula' => 'C194H308N56O59', 'weight' => '4402.14', 'cas' => '1345050-70-7' ),
		'Hexarelin'   => array( 'formula' => 'C47H58N12O6', 'weight' => '887.03', 'cas' => '140703-51-1' ),
		'GHRP-6'      => array( 'formula' => 'C46H56N12O6', 'weight' => '873.01', 'cas' => '87616-84-0' ),
		'Thymalin'    => array( 'formula' => '', 'weight' => '', 'cas' => '63958-90-7' ),
		'Snap-8'      => array( 'formula' => 'C35H60N12O13', 'weight' => '880.94', 'cas' => '868844-74-0' ),
		'ARA-290'     => array( 'formula' => 'C48H83N13O18', 'weight' => '1146.26', 'cas' => '1208243-50-6' ),
	);
}

/**
 * Best-effort spec lookup for a product name.
 *
 * @param string $name Product name.
 * @return array
 */
function animus_specs_for( $name ) {
	foreach ( animus_reference_specs() as $needle => $specs ) {
		if ( false !== stripos( $name, $needle ) ) {
			return $specs;
		}
	}
	return array( 'formula' => '', 'weight' => '', 'cas' => '' );
}

/**
 * Neutral, compliant product description — identity and handling only.
 *
 * @param array $row Catalog row.
 * @return string
 */
function animus_description( $row ) {
	$name = $row['name'];
	$qty  = $row['quantity'];

	$body  = '<p>' . esc_html( $name ) . ' supplied as a research-grade reference material for in vitro and laboratory research applications. ';
	$body .= 'Each unit is released against a lot-specific certificate of analysis confirming identity and purity by third-party analysis.</p>';

	$body .= '<h3>Supply format</h3><ul>';
	if ( $qty ) {
		$body .= '<li>Nominal content: ' . esc_html( $qty ) . ' per unit</li>';
	}
	$body .= '<li>Documentation: lot-specific COA and SDS available on the product page and via lot verification</li>';
	$body .= '<li>Traceability: the lot supplied with each order is recorded on the order permanently</li>';
	$body .= '</ul>';

	$body .= '<h3>Handling and storage</h3><ul>';
	$body .= '<li>Store as stated on the lot documentation; protect from light and moisture</li>';
	$body .= '<li>Handle in accordance with the safety data sheet using appropriate laboratory controls</li>';
	$body .= '</ul>';

	$body .= '<p><strong>Research use only.</strong> This material is not a drug, food, cosmetic, or medical device. ';
	$body .= 'It is not for human or veterinary use, consumption, or diagnostic use, and has not been evaluated by the FDA.</p>';

	return $body;
}

$created = 0;
$updated = 0;

foreach ( $catalog as $row ) {
	$sku = isset( $row['sku'] ) ? $row['sku'] : '';
	if ( ! $sku ) {
		continue;
	}

	$existing_id = wc_get_product_id_by_sku( $sku );
	$product     = $existing_id ? wc_get_product( $existing_id ) : new WC_Product_Simple();

	$product->set_name( $row['name'] );
	$product->set_sku( $sku );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( (string) $row['price'] );
	$product->set_description( animus_description( $row ) );
	$product->set_short_description(
		'<p>Research-grade material supplied with a lot-specific certificate of analysis. Research use only — not for human or veterinary use.</p>'
	);
	$product->set_manage_stock( true );
	$product->set_stock_quantity( 25 );
	$product->set_stock_status( 'instock' );
	$product->set_weight( '0.07' );
	$product->set_length( '2' );
	$product->set_width( '1' );
	$product->set_height( '1' );

	$term = get_term_by( 'name', $row['category'], 'product_cat' );
	if ( $term ) {
		$product->set_category_ids( array( $term->term_id ) );
	}

	$product_id = $product->save();

	// Scientific specification meta.
	$specs = animus_specs_for( $row['name'] );
	update_post_meta( $product_id, '_animus_purity', '99' );
	update_post_meta( $product_id, '_animus_quantity', $row['quantity'] );
	update_post_meta( $product_id, '_animus_source_region', $row['source'] );
	update_post_meta( $product_id, '_animus_storage', 'Store lyophilised material at -20 °C. Protect from light and moisture. Refer to the lot SDS for full handling requirements.' );
	update_post_meta( $product_id, '_animus_testing_method', 'HPLC-UV and LC-MS' );
	update_post_meta( $product_id, '_animus_testing_lab', 'Independent third-party analytical laboratory' );

	if ( $specs['formula'] ) {
		update_post_meta( $product_id, '_animus_molecular_formula', $specs['formula'] );
	}
	if ( $specs['weight'] ) {
		update_post_meta( $product_id, '_animus_molecular_weight', $specs['weight'] );
	}
	if ( $specs['cas'] ) {
		update_post_meta( $product_id, '_animus_cas_number', $specs['cas'] );
	}

	if ( $existing_id ) {
		++$updated;
	} else {
		++$created;
	}
}

WP_CLI::log( "Catalog: {$created} created, {$updated} updated." );

/* -------------------------------------------------------------------------
 * 4. Sample batches for the first few products
 * ---------------------------------------------------------------------- */

$sample_products = get_posts(
	array(
		'post_type'      => 'product',
		'posts_per_page' => 6,
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'fields'         => 'ids',
	)
);

$i = 0;
foreach ( $sample_products as $product_id ) {
	++$i;

	// Homepage features the documented sample products.
	$sample_product = wc_get_product( $product_id );
	if ( $sample_product && ! $sample_product->get_featured() ) {
		$sample_product->set_featured( true );
		$sample_product->save();
	}

	$lot = sprintf( 'AL-%s-%04d', gmdate( 'Y' ), 1000 + $i );

	if ( Animus_Batches::find_batch_id_by_lot( $lot ) ) {
		continue;
	}

	$batch_id = wp_insert_post(
		array(
			'post_type'   => 'animus_batch',
			'post_status' => 'publish',
			'post_title'  => $lot . ' — ' . get_the_title( $product_id ),
		)
	);

	update_post_meta( $batch_id, '_animus_batch_lot', $lot );
	update_post_meta( $batch_id, '_animus_batch_product', $product_id );
	update_post_meta( $batch_id, '_animus_batch_purity', (string) ( 98.6 + ( $i / 10 ) ) );
	update_post_meta( $batch_id, '_animus_batch_quantity', (string) get_post_meta( $product_id, '_animus_quantity', true ) );
	update_post_meta( $batch_id, '_animus_batch_tested_on', gmdate( 'Y-m-d', strtotime( '-' . ( $i * 9 ) . ' days' ) ) );
	update_post_meta( $batch_id, '_animus_batch_retest_on', gmdate( 'Y-m-d', strtotime( '+' . ( 12 + $i ) . ' months' ) ) );
	update_post_meta( $batch_id, '_animus_batch_method', 'HPLC-UV, LC-MS' );
	update_post_meta( $batch_id, '_animus_batch_lab', 'Third-party analytical laboratory' );
	update_post_meta( $batch_id, '_animus_batch_storage', 'Store at -20 °C, protect from light.' );
	update_post_meta( $batch_id, '_animus_batch_approved', 'yes' );
	update_post_meta( $batch_id, '_animus_batch_is_current', 'yes' );

	WP_CLI::log( "Batch: {$lot}" );
}

/* -------------------------------------------------------------------------
 * 5. WooCommerce configuration
 * ---------------------------------------------------------------------- */

update_option( 'woocommerce_store_address', '' );
update_option( 'woocommerce_default_country', 'US:TX' );
update_option( 'woocommerce_currency', 'USD' );
update_option( 'woocommerce_weight_unit', 'kg' );
update_option( 'woocommerce_dimension_unit', 'cm' );
update_option( 'woocommerce_enable_guest_checkout', 'no' );
update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
update_option( 'woocommerce_registration_generate_password', 'yes' );
update_option( 'woocommerce_calc_taxes', 'yes' );
update_option( 'woocommerce_prices_include_tax', 'no' );
update_option( 'woocommerce_tax_based_on', 'shipping' );
update_option( 'woocommerce_enable_reviews', 'no' );
update_option( 'woocommerce_cart_redirect_after_add', 'no' );
update_option( 'woocommerce_manage_stock', 'yes' );
update_option( 'woocommerce_notify_low_stock_amount', '5' );
update_option( 'woocommerce_allow_tracking', 'no' );

// Shipping zone: domestic US.
if ( class_exists( 'WC_Shipping_Zones' ) ) {
	$zones      = WC_Shipping_Zones::get_zones();
	$zone_names = wp_list_pluck( $zones, 'zone_name' );

	if ( ! in_array( 'United States (domestic)', $zone_names, true ) ) {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'United States (domestic)' );
		$zone->add_location( 'US', 'country' );
		$zone->save();

		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		$method      = WC_Shipping_Zones::get_shipping_method( $instance_id );
		if ( $method ) {
			$method->instance_settings['title']      = 'Standard shipping (tracked)';
			$method->instance_settings['cost']       = '12.00';
			$method->instance_settings['tax_status'] = 'taxable';
			update_option( $method->get_instance_option_key(), $method->instance_settings );
		}

		$express_id = $zone->add_shipping_method( 'flat_rate' );
		$express    = WC_Shipping_Zones::get_shipping_method( $express_id );
		if ( $express ) {
			$express->instance_settings['title']      = 'Expedited shipping';
			$express->instance_settings['cost']       = '28.00';
			$express->instance_settings['tax_status'] = 'taxable';
			update_option( $express->get_instance_option_key(), $express->instance_settings );
		}

		WP_CLI::log( 'Shipping zone created: United States (domestic)' );
	}
}

// Menus.
$primary_items = array(
	'shop'                 => 'Catalog',
	'quality-standards'    => 'Quality',
	'research-use-policy'  => 'Research Use',
	'faq'                  => 'FAQ',
	'contact'              => 'Contact',
);

$menu_name = 'Primary';
$menu      = wp_get_nav_menu_object( $menu_name );
if ( ! $menu ) {
	$menu_id = wp_create_nav_menu( $menu_name );

	// Shop page first.
	$shop_id = wc_get_page_id( 'shop' );
	if ( $shop_id > 0 ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'Catalog',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $shop_id,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			)
		);
	}

	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => 'Verify Lot',
			'menu-item-url'    => home_url( '/verify/' ),
			'menu-item-type'   => 'custom',
			'menu-item-status' => 'publish',
		)
	);

	foreach ( array( 'quality-standards', 'faq', 'contact' ) as $slug ) {
		if ( isset( $page_map[ $slug ] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => get_the_title( $page_map[ $slug ] ),
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_map[ $slug ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
	}

	$locations            = get_theme_mod( 'nav_menu_locations', array() );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	WP_CLI::log( 'Primary menu created.' );
}

// Legal footer menu.
if ( ! wp_get_nav_menu_object( 'Footer Legal' ) ) {
	$legal_id = wp_create_nav_menu( 'Footer Legal' );
	foreach ( array( 'research-use-policy', 'terms', 'privacy-policy', 'shipping-policy', 'refund-policy' ) as $slug ) {
		if ( isset( $page_map[ $slug ] ) ) {
			wp_update_nav_menu_item(
				$legal_id,
				0,
				array(
					'menu-item-title'     => get_the_title( $page_map[ $slug ] ),
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_map[ $slug ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
	}
	$locations                 = get_theme_mod( 'nav_menu_locations', array() );
	$locations['footer-legal'] = $legal_id;
	set_theme_mod( 'nav_menu_locations', $locations );
	WP_CLI::log( 'Footer legal menu created.' );
}

flush_rewrite_rules();

WP_CLI::success( 'Animus Labs store provisioning complete.' );
