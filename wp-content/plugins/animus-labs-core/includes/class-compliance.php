<?php
/**
 * Research-use-only compliance: restricted-access gate, cookie notice,
 * and the checkout acknowledgement recorded on every order.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Compliance {

	const ACK_FIELD   = 'animus_ruo_ack';
	const ACK_META    = '_animus_ruo_ack';
	const ACK_TIME    = '_animus_ruo_ack_time';
	const ACK_VERSION = '_animus_ruo_ack_policy_version';
	const ACK_TEXT    = '_animus_ruo_ack_text';

	public static function init() {
		// Front-end overlays.
		add_action( 'wp_footer', array( __CLASS__, 'render_gate' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'render_cookie_notice' ), 6 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );

		// Checkout acknowledgement.
		add_action( 'woocommerce_review_order_before_submit', array( __CLASS__, 'render_ack_field' ), 9 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_ack' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'store_ack' ), 10, 2 );

		// Store Api / block checkout parity.
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'store_ack_from_request' ), 10, 1 );
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'register_block_checkout_field' ) );
		add_action( 'woocommerce_validate_additional_field', array( __CLASS__, 'validate_block_ack' ), 10, 3 );

		// Admin display of the acknowledgement.
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_display_ack' ) );

		// Shop search field.
		add_action( 'woocommerce_before_shop_loop', array( __CLASS__, 'product_search' ), 4 );

		// Extended legal disclaimer in the footer.
		add_action( 'wp_footer', array( __CLASS__, 'noop' ), 99 );
	}

	/**
	 * Placeholder retained so footer ordering stays stable.
	 */
	public static function noop() {}

	/**
	 * Default gate body copy.
	 *
	 * @return string
	 */
	public static function default_gate_body() {
		return __( 'This site offers research-grade compounds for <strong>research use only</strong>. By entering, you confirm you are at least 21 years of age and a qualified researcher or authorised representative of an institution.', 'animus-labs-core' );
	}

	/**
	 * Enqueue the compliance script.
	 */
	public static function assets() {
		wp_enqueue_script(
			'animus-compliance',
			ANIMUS_CORE_URL . 'assets/js/compliance.js',
			array(),
			ANIMUS_CORE_VERSION,
			true
		);
	}

	/**
	 * Restricted-access gate markup. Visibility is decided client-side from
	 * localStorage so the markup stays fully cacheable.
	 */
	public static function render_gate() {
		if ( 'no' === animus_core_setting( 'age_gate_enabled', 'yes' ) ) {
			return;
		}
		if ( is_admin() || ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url() ) ) {
			// Never block an in-flight checkout.
			return;
		}

		$min   = (int) animus_core_setting( 'age_gate_min', 21 );
		$title = animus_core_setting( 'age_gate_title', __( 'Restricted access', 'animus-labs-core' ) );
		$body  = animus_core_setting( 'age_gate_body', self::default_gate_body() );
		?>
		<div class="animus-gate" data-animus-gate hidden>
			<div class="animus-gate__panel" role="dialog" aria-modal="true" aria-labelledby="animus-gate-title">
				<p class="animus-gate__brand">
					<span class="animus-brand__mark" aria-hidden="true">&#x039B;</span>
					<?php esc_html_e( 'Animus Labs', 'animus-labs-core' ); ?>
				</p>
				<h2 id="animus-gate-title"><?php echo esc_html( $title ); ?></h2>
				<p><?php echo wp_kses_post( $body ); ?></p>
				<div class="animus-gate__actions">
					<button type="button" class="animus-btn animus-btn--solid" data-animus-gate-accept>
						<?php
						/* translators: %d: minimum age */
						printf( esc_html__( 'I am %d+ — Enter', 'animus-labs-core' ), esc_html( $min ) );
						?>
					</button>
					<a class="animus-btn animus-btn--ghost" href="https://www.google.com"><?php esc_html_e( 'Exit', 'animus-labs-core' ); ?></a>
				</div>
				<p class="animus-gate__foot"><?php esc_html_e( 'Research use only · Not for human consumption', 'animus-labs-core' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Cookie notice markup.
	 */
	public static function render_cookie_notice() {
		$privacy = get_privacy_policy_url();
		?>
		<div class="animus-cookie" data-animus-cookie aria-label="<?php esc_attr_e( 'Cookie notice', 'animus-labs-core' ); ?>" hidden>
			<p>
				<?php esc_html_e( 'We use essential cookies to operate this site and remember your preferences.', 'animus-labs-core' ); ?>
				<?php if ( $privacy ) : ?>
					<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy Policy', 'animus-labs-core' ); ?></a>
				<?php endif; ?>
			</p>
			<div class="animus-cookie__actions">
				<button type="button" class="animus-btn animus-btn--ghost" data-animus-cookie-dismiss><?php esc_html_e( 'Essential only', 'animus-labs-core' ); ?></button>
				<button type="button" class="animus-btn animus-btn--solid" data-animus-cookie-dismiss><?php esc_html_e( 'Accept', 'animus-labs-core' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * Product search box above the shop loop.
	 */
	public static function product_search() {
		if ( ! is_shop() && ! is_product_taxonomy() ) {
			return;
		}
		?>
		<div class="animus-product-search">
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="animus-product-s"><?php esc_html_e( 'Search products', 'animus-labs-core' ); ?></label>
				<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				<input type="search" id="animus-product-s" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search compounds&hellip;', 'animus-labs-core' ); ?>">
				<input type="hidden" name="post_type" value="product">
			</form>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Checkout acknowledgement
	 * ------------------------------------------------------------------- */

	/**
	 * Render the required acknowledgement checkbox.
	 */
	public static function render_ack_field() {
		$text = animus_core_setting( 'checkout_ack' );
		$sub  = animus_core_setting( 'checkout_ack_sub' );
		?>
		<div class="animus-ruo-ack">
			<label for="<?php echo esc_attr( self::ACK_FIELD ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( self::ACK_FIELD ); ?>"
					name="<?php echo esc_attr( self::ACK_FIELD ); ?>"
					value="1"
					required
				>
				<span class="animus-ruo-ack__text">
					<?php echo wp_kses_post( $text ); ?>
					<span class="required" aria-hidden="true">*</span>
					<?php if ( $sub ) : ?>
						<small><?php echo wp_kses_post( $sub ); ?></small>
					<?php endif; ?>
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Block checkout unless acknowledged.
	 *
	 * @param array    $data   Posted checkout data.
	 * @param WP_Error $errors Error collector.
	 */
	public static function validate_ack( $data, $errors ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce.
		if ( empty( $_POST[ self::ACK_FIELD ] ) ) {
			$errors->add(
				'animus_ruo_ack_required',
				__( 'You must confirm the research-use-only acknowledgement before placing your order.', 'animus-labs-core' )
			);
		}
	}

	/**
	 * Persist the acknowledgement, timestamp and policy version on the order.
	 *
	 * @param WC_Order $order Order being created.
	 * @param array    $data  Checkout data.
	 */
	public static function store_ack( $order, $data ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce.
		if ( empty( $_POST[ self::ACK_FIELD ] ) ) {
			return;
		}
		self::write_ack( $order );
	}

	/**
	 * Register the acknowledgement as a required checkbox in the block checkout.
	 */
	public static function register_block_checkout_field() {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		woocommerce_register_additional_checkout_field(
			array(
				'id'            => 'animus-labs-core/ruo_ack',
				'label'         => wp_strip_all_tags( (string) animus_core_setting( 'checkout_ack' ) ),
				'location'      => 'order',
				'type'          => 'checkbox',
				'required'      => true,
				'show_in_order_confirmation' => true,
			)
		);
	}

	/**
	 * Server-side enforcement for the block checkout field.
	 *
	 * @param WP_Error $errors Error collector.
	 * @param string   $key    Field key.
	 * @param mixed    $value  Submitted value.
	 */
	public static function validate_block_ack( $errors, $key, $value ) {
		if ( 'animus-labs-core/ruo_ack' === $key && empty( $value ) ) {
			$errors->add(
				'animus_ruo_ack_required',
				__( 'You must confirm the research-use-only acknowledgement before placing your order.', 'animus-labs-core' )
			);
		}
	}

	/**
	 * Store API (block checkout) equivalent. The additional checkout field is
	 * persisted by WooCommerce under its own namespaced meta key, so the
	 * acknowledgement record is mirrored onto the order once it exists.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function store_ack_from_request( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		if ( 'yes' === $order->get_meta( self::ACK_META ) ) {
			return;
		}
		if ( ! $order->get_meta( '_wc_other/animus-labs-core/ruo_ack' ) ) {
			return;
		}

		self::write_ack( $order );
		$order->save();
	}

	/**
	 * Write acknowledgement meta.
	 *
	 * @param WC_Order $order Order.
	 */
	private static function write_ack( $order ) {
		$order->update_meta_data( self::ACK_META, 'yes' );
		$order->update_meta_data( self::ACK_TIME, current_time( 'mysql', true ) );
		$order->update_meta_data( self::ACK_VERSION, animus_core_setting( 'policy_version' ) );
		$order->update_meta_data( self::ACK_TEXT, wp_strip_all_tags( animus_core_setting( 'checkout_ack' ) ) );

		Animus_Audit::log(
			'ruo_acknowledged',
			'order',
			$order->get_id(),
			array( 'policy_version' => animus_core_setting( 'policy_version' ) )
		);
	}

	/**
	 * Show the recorded acknowledgement in the admin order screen.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function admin_display_ack( $order ) {
		$ack = $order->get_meta( self::ACK_META );
		if ( 'yes' !== $ack ) {
			echo '<p><strong>' . esc_html__( 'Research-use acknowledgement:', 'animus-labs-core' ) . '</strong> ' . esc_html__( 'Not recorded', 'animus-labs-core' ) . '</p>';
			return;
		}

		echo '<div class="animus-admin-ack">';
		echo '<p><strong>' . esc_html__( 'Research-use acknowledgement', 'animus-labs-core' ) . '</strong></p>';
		echo '<p>';
		printf(
			/* translators: 1: timestamp, 2: policy version */
			esc_html__( 'Confirmed %1$s UTC under policy version %2$s.', 'animus-labs-core' ),
			esc_html( (string) $order->get_meta( self::ACK_TIME ) ),
			esc_html( (string) $order->get_meta( self::ACK_VERSION ) )
		);
		echo '</p>';
		$text = $order->get_meta( self::ACK_TEXT );
		if ( $text ) {
			echo '<blockquote style="margin:0;color:#666;font-style:italic">' . esc_html( $text ) . '</blockquote>';
		}
		echo '</div>';
	}
}
