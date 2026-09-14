<?php
/**
 * Lot traceability on WooCommerce orders.
 *
 * The lot shipped with each line item is copied onto the order item as
 * immutable meta. Because it lives on the order item rather than being
 * looked up from the product, historical orders keep showing the lot that
 * actually shipped even after new inventory arrives.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Lot_Tracking {

	const ITEM_LOT   = '_animus_lot';
	const ITEM_BATCH = '_animus_batch_id';
	const NONCE      = 'animus_order_lot_nonce';

	public static function init() {
		// Snapshot the current lot at the moment the order is created.
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'snapshot_lot' ), 10, 4 );

		// Admin editing of assignments.
		add_action( 'woocommerce_admin_order_item_headers', array( __CLASS__, 'item_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( __CLASS__, 'item_value' ), 10, 3 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post_shop_order', array( __CLASS__, 'save_assignments' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save_assignments' ) );

		// Customer-facing display.
		add_action( 'woocommerce_order_item_meta_end', array( __CLASS__, 'display_lot_on_item' ), 10, 3 );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'order_lot_note' ) );

		// Shipment tracking.
		add_action( 'add_meta_boxes', array( __CLASS__, 'shipping_meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save_shipment' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_shipment' ), 15 );
	}

	/**
	 * Copy the product's current lot onto the order line item.
	 *
	 * @param WC_Order_Item_Product $item          Line item.
	 * @param string                $cart_item_key Cart key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order.
	 */
	public static function snapshot_lot( $item, $cart_item_key, $values, $order ) {
		$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
		$batch      = Animus_Batches::get_current_batch( $product_id );

		if ( ! $batch && $item->get_variation_id() ) {
			$batch = Animus_Batches::get_current_batch( $item->get_product_id() );
		}
		if ( ! $batch ) {
			return;
		}

		$item->add_meta_data( self::ITEM_LOT, $batch['lot'], true );
		$item->add_meta_data( self::ITEM_BATCH, $batch['id'], true );
	}

	/**
	 * Extra column header in the admin order items table.
	 */
	public static function item_header() {
		echo '<th class="animus-lot-col">' . esc_html__( 'Lot', 'animus-labs-core' ) . '</th>';
	}

	/**
	 * Extra column value in the admin order items table.
	 *
	 * @param WC_Product|null $product Product.
	 * @param WC_Order_Item   $item    Order item.
	 * @param int             $item_id Item ID.
	 */
	public static function item_value( $product, $item, $item_id ) {
		$lot = $item->get_meta( self::ITEM_LOT );
		echo '<td class="animus-lot-col">' . ( $lot ? '<code>' . esc_html( $lot ) . '</code>' : '&mdash;' ) . '</td>';
	}

	/**
	 * Lot assignment meta box.
	 */
	public static function meta_box() {
		$screens = array( 'shop_order', 'woocommerce_page_wc-orders' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'animus-order-lots',
				__( 'Lot assignments', 'animus-labs-core' ),
				array( __CLASS__, 'render_meta_box' ),
				$screen,
				'normal',
				'default'
			);
		}
	}

	/**
	 * Render per-item lot selects.
	 *
	 * @param WP_Post|WC_Order $post_or_order Screen object.
	 */
	public static function render_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			return;
		}

		wp_nonce_field( self::NONCE, self::NONCE );
		echo '<p class="description">' . esc_html__( 'Assign the lot that shipped for each item. Assignments are stored on the order permanently and are not affected by later inventory changes.', 'animus-labs-core' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr>';
		printf( '<th>%s</th>', esc_html__( 'Item', 'animus-labs-core' ) );
		printf( '<th>%s</th>', esc_html__( 'Assigned lot', 'animus-labs-core' ) );
		echo '</tr></thead><tbody>';

		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$batches    = Animus_Batches::get_batches_for_product( $product_id );
			if ( ! $batches && $item->get_variation_id() ) {
				$batches = Animus_Batches::get_batches_for_product( $item->get_product_id() );
			}
			$current = $item->get_meta( self::ITEM_BATCH );

			echo '<tr><td>' . esc_html( $item->get_name() ) . '</td><td>';
			if ( $batches ) {
				printf( '<select name="animus_item_batch[%d]">', esc_attr( $item_id ) );
				printf( '<option value="">%s</option>', esc_html__( '— Not assigned —', 'animus-labs-core' ) );
				foreach ( $batches as $batch ) {
					printf(
						'<option value="%d" %s>%s</option>',
						esc_attr( $batch['id'] ),
						selected( (int) $current, $batch['id'], false ),
						esc_html( $batch['lot'] . ( $batch['tested_on'] ? ' — ' . $batch['tested_on'] : '' ) )
					);
				}
				echo '</select>';
			} else {
				echo '<em>' . esc_html__( 'No approved batches for this product yet.', 'animus-labs-core' ) . '</em>';
			}
			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Persist lot assignments.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function save_assignments( $order_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		if ( ! isset( $_POST['animus_item_batch'] ) || ! is_array( $_POST['animus_item_batch'] ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$assignments = wp_unslash( $_POST['animus_item_batch'] );

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! array_key_exists( $item_id, $assignments ) ) {
				continue;
			}

			$batch_id = absint( $assignments[ $item_id ] );

			if ( ! $batch_id ) {
				$item->delete_meta_data( self::ITEM_LOT );
				$item->delete_meta_data( self::ITEM_BATCH );
				$item->save();
				continue;
			}

			$batch = Animus_Batches::get_batch( $batch_id );
			if ( ! $batch ) {
				continue;
			}

			$item->update_meta_data( self::ITEM_LOT, $batch['lot'] );
			$item->update_meta_data( self::ITEM_BATCH, $batch['id'] );
			$item->save();

			Animus_Audit::log(
				'order_lot_assigned',
				'order',
				$order_id,
				array( 'item' => $item_id, 'lot' => $batch['lot'], 'batch' => $batch['id'] )
			);
		}

		$order->save();
	}

	/**
	 * Show the lot beneath each item on receipts, account pages and emails.
	 *
	 * @param int                   $item_id Item ID.
	 * @param WC_Order_Item_Product $item    Item.
	 * @param WC_Order              $order   Order.
	 */
	public static function display_lot_on_item( $item_id, $item, $order ) {
		$lot = $item->get_meta( self::ITEM_LOT );
		if ( ! $lot ) {
			return;
		}

		$batch = Animus_Batches::get_approved_batch_by_lot( $lot );
		if ( $batch ) {
			printf(
				'<span class="animus-lot-badge">%1$s <a href="%2$s">%3$s</a></span>',
				esc_html__( 'Lot', 'animus-labs-core' ),
				esc_url( animus_lot_url( $lot ) ),
				esc_html( $lot )
			);
		} else {
			printf(
				'<span class="animus-lot-badge">%1$s %2$s</span>',
				esc_html__( 'Lot', 'animus-labs-core' ),
				esc_html( $lot )
			);
		}
	}

	/**
	 * Traceability note under the order table.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function order_lot_note( $order ) {
		$has_lot = false;
		foreach ( $order->get_items() as $item ) {
			if ( $item->get_meta( self::ITEM_LOT ) ) {
				$has_lot = true;
				break;
			}
		}
		if ( ! $has_lot ) {
			return;
		}
		printf(
			'<p class="animus-order-lots__note">%s</p>',
			esc_html( animus_core_setting( 'order_lot_note' ) )
		);
	}

	/* ---------------------------------------------------------------------
	 * Shipment tracking
	 * ------------------------------------------------------------------- */

	/**
	 * Tracking meta box.
	 */
	public static function shipping_meta_box() {
		foreach ( array( 'shop_order', 'woocommerce_page_wc-orders' ) as $screen ) {
			add_meta_box(
				'animus-order-shipment',
				__( 'Shipment', 'animus-labs-core' ),
				array( __CLASS__, 'render_shipment_box' ),
				$screen,
				'side',
				'default'
			);
		}
	}

	/**
	 * Tracking fields.
	 *
	 * @param WP_Post|WC_Order $post_or_order Screen object.
	 */
	public static function render_shipment_box( $post_or_order ) {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
		if ( ! $order ) {
			return;
		}

		$carrier  = $order->get_meta( '_animus_carrier' );
		$tracking = $order->get_meta( '_animus_tracking' );
		$url      = $order->get_meta( '_animus_tracking_url' );
		$status   = $order->get_meta( '_animus_shipment_status' );
		?>
		<p>
			<label for="animus_carrier"><?php esc_html_e( 'Carrier', 'animus-labs-core' ); ?></label>
			<input type="text" id="animus_carrier" name="animus_carrier" value="<?php echo esc_attr( $carrier ); ?>" class="widefat">
		</p>
		<p>
			<label for="animus_tracking"><?php esc_html_e( 'Tracking number', 'animus-labs-core' ); ?></label>
			<input type="text" id="animus_tracking" name="animus_tracking" value="<?php echo esc_attr( $tracking ); ?>" class="widefat">
		</p>
		<p>
			<label for="animus_tracking_url"><?php esc_html_e( 'Tracking URL', 'animus-labs-core' ); ?></label>
			<input type="url" id="animus_tracking_url" name="animus_tracking_url" value="<?php echo esc_attr( $url ); ?>" class="widefat">
		</p>
		<p>
			<label for="animus_shipment_status"><?php esc_html_e( 'Shipment status', 'animus-labs-core' ); ?></label>
			<select id="animus_shipment_status" name="animus_shipment_status" class="widefat">
				<?php
				$statuses = array(
					''           => __( '— None —', 'animus-labs-core' ),
					'preparing'  => __( 'Preparing', 'animus-labs-core' ),
					'shipped'    => __( 'Shipped', 'animus-labs-core' ),
					'in_transit' => __( 'In transit', 'animus-labs-core' ),
					'delivered'  => __( 'Delivered', 'animus-labs-core' ),
					'exception'  => __( 'Exception', 'animus-labs-core' ),
				);
				foreach ( $statuses as $key => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $status, $key, false ), esc_html( $label ) );
				}
				?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save tracking fields (shares the lot-assignment nonce on the same form).
	 *
	 * @param int $order_id Order ID.
	 */
	public static function save_shipment( $order_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$allowed_statuses = array( '', 'preparing', 'shipped', 'in_transit', 'delivered', 'exception' );
		$status           = isset( $_POST['animus_shipment_status'] ) ? sanitize_key( wp_unslash( $_POST['animus_shipment_status'] ) ) : '';

		$order->update_meta_data( '_animus_carrier', isset( $_POST['animus_carrier'] ) ? sanitize_text_field( wp_unslash( $_POST['animus_carrier'] ) ) : '' );
		$order->update_meta_data( '_animus_tracking', isset( $_POST['animus_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['animus_tracking'] ) ) : '' );
		$order->update_meta_data( '_animus_tracking_url', isset( $_POST['animus_tracking_url'] ) ? esc_url_raw( wp_unslash( $_POST['animus_tracking_url'] ) ) : '' );
		$order->update_meta_data( '_animus_shipment_status', in_array( $status, $allowed_statuses, true ) ? $status : '' );
		$order->save();

		Animus_Audit::log( 'order_shipment_saved', 'order', $order_id, array( 'status' => $status ) );
	}

	/**
	 * Show tracking to the customer.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function display_shipment( $order ) {
		$carrier  = $order->get_meta( '_animus_carrier' );
		$tracking = $order->get_meta( '_animus_tracking' );
		$url      = $order->get_meta( '_animus_tracking_url' );
		$status   = $order->get_meta( '_animus_shipment_status' );

		if ( ! $carrier && ! $tracking && ! $status ) {
			return;
		}

		$labels = array(
			'preparing'  => __( 'Preparing', 'animus-labs-core' ),
			'shipped'    => __( 'Shipped', 'animus-labs-core' ),
			'in_transit' => __( 'In transit', 'animus-labs-core' ),
			'delivered'  => __( 'Delivered', 'animus-labs-core' ),
			'exception'  => __( 'Exception', 'animus-labs-core' ),
		);

		echo '<section class="animus-order-lots"><h2>' . esc_html__( 'Shipment', 'animus-labs-core' ) . '</h2>';
		echo '<div class="animus-specs"><dl>';
		if ( $status && isset( $labels[ $status ] ) ) {
			printf( '<dt>%s</dt><dd>%s</dd>', esc_html__( 'Status', 'animus-labs-core' ), esc_html( $labels[ $status ] ) );
		}
		if ( $carrier ) {
			printf( '<dt>%s</dt><dd>%s</dd>', esc_html__( 'Carrier', 'animus-labs-core' ), esc_html( $carrier ) );
		}
		if ( $tracking ) {
			$value = $url
				? sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $url ), esc_html( $tracking ) )
				: esc_html( $tracking );
			printf( '<dt>%s</dt><dd>%s</dd>', esc_html__( 'Tracking', 'animus-labs-core' ), wp_kses_post( $value ) );
		}
		echo '</dl></div></section>';
	}
}
