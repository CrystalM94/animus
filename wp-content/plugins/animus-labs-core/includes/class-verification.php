<?php
/**
 * Public lot verification: /verify/ lookup form and /verify/{lot}/ pages.
 *
 * Only approved, published batches resolve. Lookups are rate limited and
 * logged so abuse is visible in the audit trail.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Verification {

	const QUERY_VAR    = 'animus_lot';
	const RATE_LIMIT   = 30;   // Lookups...
	const RATE_WINDOW  = 300;  // ...per this many seconds, per IP.

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ) );
		add_shortcode( 'animus_lot_verification', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * /verify/ and /verify/{lot}/ routes.
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule( '^verify/?$', 'index.php?' . self::QUERY_VAR . '=__form__', 'top' );
		add_rewrite_rule( '^verify/([^/]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/**
	 * Register our query var.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Render the verification page when our route matched.
	 */
	public static function maybe_render() {
		$lot = get_query_var( self::QUERY_VAR );
		if ( ! $lot ) {
			return;
		}

		status_header( 200 );
		nocache_headers();

		$requested = '__form__' === $lot ? '' : sanitize_text_field( rawurldecode( $lot ) );

		// A submitted form also arrives here via ?lot=.
		if ( ! $requested && isset( $_GET['lot'] ) ) {
			$requested = sanitize_text_field( wp_unslash( $_GET['lot'] ) );
		}

		$batch      = null;
		$rate_limit = false;

		if ( $requested ) {
			if ( self::is_rate_limited() ) {
				$rate_limit = true;
			} else {
				$batch = Animus_Batches::get_approved_batch_by_lot( $requested );
				Animus_Audit::log( 'lot_lookup', 'batch', $batch ? $batch['id'] : 0, array( 'lot' => $requested, 'found' => (bool) $batch ) );
			}
		}

		self::render_page( $requested, $batch, $rate_limit );
		exit;
	}

	/**
	 * Simple per-IP rate limit using transients.
	 *
	 * @return bool True when the caller should be throttled.
	 */
	private static function is_rate_limited() {
		$ip = Animus_Audit::client_ip();
		if ( ! $ip ) {
			return false;
		}
		$key   = 'animus_lot_rl_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT ) {
			return true;
		}
		set_transient( $key, $count + 1, self::RATE_WINDOW );
		return false;
	}

	/**
	 * Output the full verification page.
	 *
	 * @param string     $requested  Lot the visitor asked for.
	 * @param array|null $batch      Resolved batch.
	 * @param bool       $rate_limit Whether the request was throttled.
	 */
	private static function render_page( $requested, $batch, $rate_limit = false ) {
		get_header();
		echo '<main id="primary" class="animus-wrap animus-main"><div class="animus-verify">';

		echo '<header class="animus-pagehead animus-pagehead--center">';
		echo '<p class="animus-kicker">' . esc_html__( 'Batch verification', 'animus-labs-core' ) . '</p>';
		echo '<h1>' . esc_html__( 'Verify a lot', 'animus-labs-core' ) . '</h1>';
		echo '<span class="animus-pagehead__rule" aria-hidden="true"></span>';
		echo '</header>';

		echo '<p class="animus-verify__intro">' . esc_html( animus_core_setting( 'verification_intro' ) ) . '</p>';

		self::render_form( $requested );

		if ( $rate_limit ) {
			echo '<div class="animus-verify-card animus-verify-card--invalid">';
			echo '<span class="animus-verify-card__status">' . esc_html__( 'Too many requests', 'animus-labs-core' ) . '</span>';
			echo '<p>' . esc_html__( 'Please wait a few minutes before verifying more lot numbers.', 'animus-labs-core' ) . '</p>';
			echo '</div>';
		} elseif ( $requested && $batch ) {
			self::render_result( $batch );
		} elseif ( $requested ) {
			echo '<div class="animus-verify-card animus-verify-card--invalid">';
			echo '<span class="animus-verify-card__status">' . esc_html__( 'Not found', 'animus-labs-core' ) . '</span>';
			echo '<h2>' . esc_html__( 'No approved batch matches this lot number.', 'animus-labs-core' ) . '</h2>';
			printf(
				'<p>%s <code>%s</code></p>',
				esc_html__( 'Requested lot:', 'animus-labs-core' ),
				esc_html( $requested )
			);
			echo '<p>' . esc_html__( 'Check the lot number printed on your label. If it still does not resolve, contact us before using the material.', 'animus-labs-core' ) . '</p>';
			$supplier_url = animus_core_setting( 'supplier_verify_url', '' );
			if ( '' !== $supplier_url ) {
				printf(
					'<p><a class="animus-btn animus-btn--solid" href="%s" target="_blank" rel="noopener">%s</a></p>',
					esc_url( $supplier_url ),
					esc_html__( 'Verify with our lab partner', 'animus-labs-core' )
				);
			}
			printf(
				'<p><a class="animus-btn animus-btn--ghost" href="%s">%s</a></p>',
				esc_url( home_url( '/contact/' ) ),
				esc_html__( 'Contact us', 'animus-labs-core' )
			);
			echo '</div>';
		}

		echo '</div></main>';
		get_footer();
	}

	/**
	 * Lookup form.
	 *
	 * @param string $requested Current value.
	 */
	private static function render_form( $requested ) {
		printf(
			'<form class="animus-verify__form" method="get" action="%s">',
			esc_url( home_url( '/verify/' ) )
		);
		printf(
			'<label class="screen-reader-text" for="animus-lot">%s</label>',
			esc_html__( 'Lot number', 'animus-labs-core' )
		);
		printf(
			'<input type="text" id="animus-lot" name="lot" value="%s" placeholder="%s" autocomplete="off" spellcheck="false" required>',
			esc_attr( $requested ),
			esc_attr__( 'e.g. AL-2026-0417', 'animus-labs-core' )
		);
		printf(
			'<button type="submit" class="animus-btn animus-btn--solid">%s</button>',
			esc_html__( 'Verify', 'animus-labs-core' )
		);
		echo '</form>';
	}

	/**
	 * Verified batch card.
	 *
	 * @param array $batch Batch record.
	 */
	private static function render_result( $batch ) {
		$product = $batch['product_id'] ? wc_get_product( $batch['product_id'] ) : null;

		echo '<div class="animus-verify-card animus-verify-card--valid">';
		echo '<span class="animus-verify-card__status">' . esc_html__( 'Verified batch', 'animus-labs-core' ) . '</span>';

		if ( $product ) {
			printf(
				'<h2><a href="%s">%s</a></h2>',
				esc_url( get_permalink( $product->get_id() ) ),
				esc_html( $product->get_name() )
			);
		} else {
			echo '<h2>' . esc_html__( 'Batch record', 'animus-labs-core' ) . '</h2>';
		}

		$rows = array(
			__( 'Lot number', 'animus-labs-core' )   => $batch['lot'],
			__( 'Product SKU', 'animus-labs-core' )  => $product ? $product->get_sku() : '',
			__( 'Verified purity', 'animus-labs-core' ) => $batch['purity'] ? $batch['purity'] . '%' : '',
			__( 'Quantity', 'animus-labs-core' )     => $batch['quantity'],
			__( 'Testing date', 'animus-labs-core' ) => $batch['tested_on'],
			__( 'Retest / expiration', 'animus-labs-core' ) => $batch['retest_on'],
			__( 'Testing method', 'animus-labs-core' ) => $batch['method'],
			__( 'Testing laboratory', 'animus-labs-core' ) => $batch['lab'],
			__( 'Storage', 'animus-labs-core' )      => $batch['storage'],
		);

		if ( $product ) {
			foreach ( array( 'molecular_formula' => __( 'Molecular formula', 'animus-labs-core' ), 'molecular_weight' => __( 'Molecular weight', 'animus-labs-core' ), 'cas_number' => __( 'CAS number', 'animus-labs-core' ) ) as $key => $label ) {
				$value = get_post_meta( $product->get_id(), '_animus_' . $key, true );
				if ( $value ) {
					$rows[ $label ] = $value;
				}
			}
		}

		echo '<div class="animus-specs"><dl>';
		foreach ( $rows as $label => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			printf( '<dt>%s</dt><dd>%s</dd>', esc_html( $label ), esc_html( $value ) );
		}
		echo '</dl></div>';

		echo '<div class="animus-verify-docs">';
		if ( $batch['coa_url'] ) {
			printf(
				'<a class="animus-btn animus-btn--solid" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $batch['coa_url'] ),
				esc_html__( 'View certificate of analysis', 'animus-labs-core' )
			);
		}
		if ( $batch['sds_url'] ) {
			printf(
				'<a class="animus-btn animus-btn--ghost" href="%s" target="_blank" rel="noopener">%s</a>',
				esc_url( $batch['sds_url'] ),
				esc_html__( 'View safety data sheet', 'animus-labs-core' )
			);
		}
		echo '</div>';

		// QR code for this batch.
		wp_enqueue_script( 'animus-qrcode', ANIMUS_CORE_URL . 'assets/js/qrcode.min.js', array(), '1.4.4', true );
		wp_enqueue_script( 'animus-qr-render', ANIMUS_CORE_URL . 'assets/js/qr-render.js', array( 'animus-qrcode' ), ANIMUS_CORE_VERSION, true );
		printf(
			'<div class="animus-verify-qr"><div class="animus-qr" data-animus-qr="%s"></div><p>%s</p></div>',
			esc_attr( animus_lot_url( $batch['lot'] ) ),
			esc_html__( 'This code links to this batch record.', 'animus-labs-core' )
		);

		echo '<p class="animus-verify-card__ruo">' . esc_html( animus_core_setting( 'ruo_notice' ) ) . '</p>';
		echo '</div>';
	}

	/**
	 * [animus_lot_verification] — embeddable lookup form.
	 *
	 * @return string
	 */
	public static function shortcode() {
		ob_start();
		echo '<div class="animus-verify">';
		echo '<p>' . esc_html( animus_core_setting( 'verification_intro' ) ) . '</p>';
		self::render_form( '' );
		echo '</div>';
		return (string) ob_get_clean();
	}
}
