<?php
/**
 * Lot / batch records: COA + SDS documents, testing data, approval status.
 *
 * Batches are a private custom post type so that document access always
 * runs through our own capability and approval checks rather than being
 * publicly queryable.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Batches {

	const POST_TYPE = 'animus_batch';
	const NONCE     = 'animus_batch_nonce';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );

		// Restrict uploaded document types.
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'validate_document_upload' ) );
	}

	/**
	 * Register the batch post type.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'Batches', 'animus-labs-core' ),
					'singular_name'      => __( 'Batch', 'animus-labs-core' ),
					'add_new_item'       => __( 'Add batch', 'animus-labs-core' ),
					'edit_item'          => __( 'Edit batch', 'animus-labs-core' ),
					'search_items'       => __( 'Search batches', 'animus-labs-core' ),
					'not_found'          => __( 'No batches found.', 'animus-labs-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false, // Surfaced under our own menu.
				'supports'        => array( 'title' ),
				'capability_type' => array( 'shop_order', 'shop_orders' ),
				'map_meta_cap'    => true,
				'has_archive'     => false,
				'rewrite'         => false,
				'show_in_rest'    => false,
			)
		);
	}

	/**
	 * Top-level Animus Labs menu + batch screens.
	 */
	public static function menu() {
		add_menu_page(
			__( 'Animus Labs', 'animus-labs-core' ),
			__( 'Animus Labs', 'animus-labs-core' ),
			'manage_woocommerce',
			'animus-labs',
			array( __CLASS__, 'dashboard' ),
			'dashicons-analytics',
			56
		);

		add_submenu_page(
			'animus-labs',
			__( 'Batches', 'animus-labs-core' ),
			__( 'Batches', 'animus-labs-core' ),
			'manage_woocommerce',
			'edit.php?post_type=' . self::POST_TYPE
		);

		add_submenu_page(
			'animus-labs',
			__( 'Add batch', 'animus-labs-core' ),
			__( 'Add batch', 'animus-labs-core' ),
			'manage_woocommerce',
			'post-new.php?post_type=' . self::POST_TYPE
		);

		add_submenu_page(
			'animus-labs',
			__( 'Audit log', 'animus-labs-core' ),
			__( 'Audit log', 'animus-labs-core' ),
			'manage_options',
			'animus-labs-audit',
			array( __CLASS__, 'audit_screen' )
		);
	}

	/**
	 * Overview screen.
	 */
	public static function dashboard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'animus-labs-core' ) );
		}

		$approved = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'meta_query'     => array( array( 'key' => '_animus_batch_approved', 'value' => 'yes' ) ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$pending = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'OR',
					array( 'key' => '_animus_batch_approved', 'value' => 'yes', 'compare' => '!=' ),
					array( 'key' => '_animus_batch_approved', 'compare' => 'NOT EXISTS' ),
				),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Animus Labs', 'animus-labs-core' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Lot documentation, verification and compliance controls for the store.', 'animus-labs-core' ); ?></p>

			<div class="animus-admin-cards">
				<div class="animus-admin-card">
					<strong><?php echo esc_html( number_format_i18n( $approved->found_posts ) ); ?></strong>
					<span><?php esc_html_e( 'Approved batches', 'animus-labs-core' ); ?></span>
				</div>
				<div class="animus-admin-card">
					<strong><?php echo esc_html( number_format_i18n( $pending->found_posts ) ); ?></strong>
					<span><?php esc_html_e( 'Batches awaiting approval', 'animus-labs-core' ); ?></span>
				</div>
			</div>

			<h2><?php esc_html_e( 'Quick links', 'animus-labs-core' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>"><?php esc_html_e( 'Manage batches, COAs and SDS documents', 'animus-labs-core' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Manage products and specifications', 'animus-labs-core' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-orders' ) ); ?>"><?php esc_html_e( 'Orders, lot assignments and refunds', 'animus-labs-core' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=animus-labs-settings' ) ); ?>"><?php esc_html_e( 'Compliance language and gate settings', 'animus-labs-core' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/verify/' ) ); ?>"><?php esc_html_e( 'Public lot verification page', 'animus-labs-core' ); ?></a></li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Audit log screen.
	 */
	public static function audit_screen() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'animus-labs-core' ) );
		}
		$rows = Animus_Audit::recent( 300 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Audit log', 'animus-labs-core' ); ?></h1>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'When (UTC)', 'animus-labs-core' ); ?></th>
						<th><?php esc_html_e( 'User', 'animus-labs-core' ); ?></th>
						<th><?php esc_html_e( 'Action', 'animus-labs-core' ); ?></th>
						<th><?php esc_html_e( 'Object', 'animus-labs-core' ); ?></th>
						<th><?php esc_html_e( 'Detail', 'animus-labs-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No entries yet.', 'animus-labs-core' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->created_at ); ?></td>
							<td><?php echo esc_html( $row->user_id ? get_the_author_meta( 'user_login', $row->user_id ) : '—' ); ?></td>
							<td><code><?php echo esc_html( $row->action ); ?></code></td>
							<td><?php echo esc_html( $row->object_type ? $row->object_type . ' #' . $row->object_id : '—' ); ?></td>
							<td><code><?php echo esc_html( wp_trim_words( (string) $row->detail, 20 ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Admin CSS/JS for batch screens.
	 *
	 * @param string $hook Current admin page.
	 */
	public static function admin_assets( $hook ) {
		wp_enqueue_style( 'animus-admin', ANIMUS_CORE_URL . 'assets/css/admin.css', array(), ANIMUS_CORE_VERSION );

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && self::POST_TYPE === $screen->post_type ) {
			wp_enqueue_media();
			wp_enqueue_script( 'animus-batch-admin', ANIMUS_CORE_URL . 'assets/js/batch-admin.js', array( 'jquery' ), ANIMUS_CORE_VERSION, true );
			wp_localize_script(
				'animus-batch-admin',
				'animusBatchAdmin',
				array(
					'chooseCoa' => __( 'Select certificate of analysis (PDF)', 'animus-labs-core' ),
					'chooseSds' => __( 'Select safety data sheet (PDF)', 'animus-labs-core' ),
					'use'       => __( 'Use this document', 'animus-labs-core' ),
				)
			);
		}
	}

	/**
	 * Batch meta boxes.
	 */
	public static function meta_boxes() {
		add_meta_box(
			'animus-batch-details',
			__( 'Batch details', 'animus-labs-core' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'animus-batch-qr',
			__( 'Verification QR code', 'animus-labs-core' ),
			array( __CLASS__, 'render_qr_box' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Batch fields.
	 *
	 * @param WP_Post $post Batch post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );

		$lot        = get_post_meta( $post->ID, '_animus_batch_lot', true );
		$product_id = (int) get_post_meta( $post->ID, '_animus_batch_product', true );
		$purity     = get_post_meta( $post->ID, '_animus_batch_purity', true );
		$tested_on  = get_post_meta( $post->ID, '_animus_batch_tested_on', true );
		$retest_on  = get_post_meta( $post->ID, '_animus_batch_retest_on', true );
		$method     = get_post_meta( $post->ID, '_animus_batch_method', true );
		$lab        = get_post_meta( $post->ID, '_animus_batch_lab', true );
		$quantity   = get_post_meta( $post->ID, '_animus_batch_quantity', true );
		$storage    = get_post_meta( $post->ID, '_animus_batch_storage', true );
		$coa_id     = (int) get_post_meta( $post->ID, '_animus_batch_coa_id', true );
		$sds_id     = (int) get_post_meta( $post->ID, '_animus_batch_sds_id', true );
		$approved   = get_post_meta( $post->ID, '_animus_batch_approved', true );
		$is_current = get_post_meta( $post->ID, '_animus_batch_is_current', true );
		?>
		<table class="form-table animus-batch-form">
			<tr>
				<th><label for="animus_batch_lot"><?php esc_html_e( 'Lot / batch number', 'animus-labs-core' ); ?> <span class="required">*</span></label></th>
				<td>
					<input type="text" id="animus_batch_lot" name="animus_batch_lot" value="<?php echo esc_attr( $lot ); ?>" class="regular-text code" required>
					<p class="description"><?php esc_html_e( 'Must be unique. This is the value customers enter on the verification page.', 'animus-labs-core' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="animus_batch_product"><?php esc_html_e( 'Product', 'animus-labs-core' ); ?> <span class="required">*</span></label></th>
				<td>
					<select id="animus_batch_product" name="animus_batch_product" required>
						<option value=""><?php esc_html_e( '— Select product —', 'animus-labs-core' ); ?></option>
						<?php
						$products = get_posts(
							array(
								'post_type'      => 'product',
								'posts_per_page' => 500,
								'orderby'        => 'title',
								'order'          => 'ASC',
								'post_status'    => array( 'publish', 'draft', 'private' ),
							)
						);
						foreach ( $products as $p ) {
							printf(
								'<option value="%d" %s>%s</option>',
								esc_attr( $p->ID ),
								selected( $product_id, $p->ID, false ),
								esc_html( $p->post_title )
							);
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="animus_batch_purity"><?php esc_html_e( 'Verified purity (%)', 'animus-labs-core' ); ?></label></th>
				<td><input type="text" id="animus_batch_purity" name="animus_batch_purity" value="<?php echo esc_attr( $purity ); ?>" class="small-text"></td>
			</tr>
			<tr>
				<th><label for="animus_batch_quantity"><?php esc_html_e( 'Quantity per unit', 'animus-labs-core' ); ?></label></th>
				<td><input type="text" id="animus_batch_quantity" name="animus_batch_quantity" value="<?php echo esc_attr( $quantity ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="animus_batch_tested_on"><?php esc_html_e( 'Testing date', 'animus-labs-core' ); ?></label></th>
				<td><input type="date" id="animus_batch_tested_on" name="animus_batch_tested_on" value="<?php echo esc_attr( $tested_on ); ?>"></td>
			</tr>
			<tr>
				<th><label for="animus_batch_retest_on"><?php esc_html_e( 'Retest / expiration date', 'animus-labs-core' ); ?></label></th>
				<td><input type="date" id="animus_batch_retest_on" name="animus_batch_retest_on" value="<?php echo esc_attr( $retest_on ); ?>"></td>
			</tr>
			<tr>
				<th><label for="animus_batch_method"><?php esc_html_e( 'Testing method', 'animus-labs-core' ); ?></label></th>
				<td><input type="text" id="animus_batch_method" name="animus_batch_method" value="<?php echo esc_attr( $method ); ?>" class="regular-text" placeholder="HPLC-UV, LC-MS"></td>
			</tr>
			<tr>
				<th><label for="animus_batch_lab"><?php esc_html_e( 'Testing laboratory / provider', 'animus-labs-core' ); ?></label></th>
				<td><input type="text" id="animus_batch_lab" name="animus_batch_lab" value="<?php echo esc_attr( $lab ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="animus_batch_storage"><?php esc_html_e( 'Storage requirements', 'animus-labs-core' ); ?></label></th>
				<td><textarea id="animus_batch_storage" name="animus_batch_storage" rows="2" class="large-text"><?php echo esc_textarea( $storage ); ?></textarea></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Certificate of analysis', 'animus-labs-core' ); ?></th>
				<td>
					<input type="hidden" id="animus_batch_coa_id" name="animus_batch_coa_id" value="<?php echo esc_attr( $coa_id ); ?>">
					<button type="button" class="button" data-animus-upload="coa"><?php esc_html_e( 'Select COA (PDF)', 'animus-labs-core' ); ?></button>
					<span class="animus-doc-name" data-animus-name="coa">
						<?php echo $coa_id ? esc_html( get_the_title( $coa_id ) ) : esc_html__( 'No document selected', 'animus-labs-core' ); ?>
					</span>
					<?php if ( $coa_id ) : ?>
						<a href="<?php echo esc_url( wp_get_attachment_url( $coa_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'animus-labs-core' ); ?></a>
						<button type="button" class="button-link animus-doc-clear" data-animus-clear="coa"><?php esc_html_e( 'Remove', 'animus-labs-core' ); ?></button>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Safety data sheet', 'animus-labs-core' ); ?></th>
				<td>
					<input type="hidden" id="animus_batch_sds_id" name="animus_batch_sds_id" value="<?php echo esc_attr( $sds_id ); ?>">
					<button type="button" class="button" data-animus-upload="sds"><?php esc_html_e( 'Select SDS (PDF)', 'animus-labs-core' ); ?></button>
					<span class="animus-doc-name" data-animus-name="sds">
						<?php echo $sds_id ? esc_html( get_the_title( $sds_id ) ) : esc_html__( 'No document selected', 'animus-labs-core' ); ?>
					</span>
					<?php if ( $sds_id ) : ?>
						<a href="<?php echo esc_url( wp_get_attachment_url( $sds_id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'animus-labs-core' ); ?></a>
						<button type="button" class="button-link animus-doc-clear" data-animus-clear="sds"><?php esc_html_e( 'Remove', 'animus-labs-core' ); ?></button>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'animus-labs-core' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="animus_batch_approved" value="yes" <?php checked( 'yes', $approved ); ?>>
						<?php esc_html_e( 'Approved for public verification', 'animus-labs-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Only approved batches appear on the public verification page or expose documents.', 'animus-labs-core' ); ?></p>
					<label>
						<input type="checkbox" name="animus_batch_is_current" value="yes" <?php checked( 'yes', $is_current ); ?>>
						<?php esc_html_e( 'This is the current shipping lot for the product', 'animus-labs-core' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Setting this clears the flag on other batches of the same product. Historical order assignments are never changed.', 'animus-labs-core' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * QR code box.
	 *
	 * @param WP_Post $post Batch post.
	 */
	public static function render_qr_box( $post ) {
		$lot = get_post_meta( $post->ID, '_animus_batch_lot', true );
		if ( ! $lot ) {
			echo '<p>' . esc_html__( 'Save the batch with a lot number to generate its QR code.', 'animus-labs-core' ) . '</p>';
			return;
		}
		$url = animus_lot_url( $lot );
		wp_enqueue_script( 'animus-qrcode', ANIMUS_CORE_URL . 'assets/js/qrcode.min.js', array(), '1.4.4', true );
		wp_enqueue_script( 'animus-qr-render', ANIMUS_CORE_URL . 'assets/js/qr-render.js', array( 'animus-qrcode' ), ANIMUS_CORE_VERSION, true );
		?>
		<div class="animus-qr" data-animus-qr="<?php echo esc_attr( $url ); ?>"></div>
		<p class="description"><?php esc_html_e( 'Print this on the lot label. It resolves to:', 'animus-labs-core' ); ?></p>
		<p><code><?php echo esc_html( $url ); ?></code></p>
		<p><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open verification page', 'animus-labs-core' ); ?></a></p>
		<?php
	}

	/**
	 * Save batch meta.
	 *
	 * @param int     $post_id Batch ID.
	 * @param WP_Post $post    Batch post.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$lot        = isset( $_POST['animus_batch_lot'] ) ? sanitize_text_field( wp_unslash( $_POST['animus_batch_lot'] ) ) : '';
		$product_id = isset( $_POST['animus_batch_product'] ) ? absint( $_POST['animus_batch_product'] ) : 0;

		// Enforce lot uniqueness.
		if ( $lot ) {
			$existing = self::find_batch_id_by_lot( $lot );
			if ( $existing && $existing !== $post_id ) {
				$lot = $lot . '-DUP-' . $post_id;
			}
		}

		update_post_meta( $post_id, '_animus_batch_lot', $lot );
		update_post_meta( $post_id, '_animus_batch_product', $product_id );
		update_post_meta( $post_id, '_animus_batch_purity', isset( $_POST['animus_batch_purity'] ) ? sanitize_text_field( wp_unslash( $_POST['animus_batch_purity'] ) ) : '' );
		update_post_meta( $post_id, '_animus_batch_quantity', isset( $_POST['animus_batch_quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['animus_batch_quantity'] ) ) : '' );
		update_post_meta( $post_id, '_animus_batch_tested_on', isset( $_POST['animus_batch_tested_on'] ) ? self::sanitize_date( wp_unslash( $_POST['animus_batch_tested_on'] ) ) : '' );
		update_post_meta( $post_id, '_animus_batch_retest_on', isset( $_POST['animus_batch_retest_on'] ) ? self::sanitize_date( wp_unslash( $_POST['animus_batch_retest_on'] ) ) : '' );
		update_post_meta( $post_id, '_animus_batch_method', isset( $_POST['animus_batch_method'] ) ? sanitize_text_field( wp_unslash( $_POST['animus_batch_method'] ) ) : '' );
		update_post_meta( $post_id, '_animus_batch_lab', isset( $_POST['animus_batch_lab'] ) ? sanitize_text_field( wp_unslash( $_POST['animus_batch_lab'] ) ) : '' );
		update_post_meta( $post_id, '_animus_batch_storage', isset( $_POST['animus_batch_storage'] ) ? sanitize_textarea_field( wp_unslash( $_POST['animus_batch_storage'] ) ) : '' );

		foreach ( array( 'coa', 'sds' ) as $doc ) {
			$field = 'animus_batch_' . $doc . '_id';
			$id    = isset( $_POST[ $field ] ) ? absint( $_POST[ $field ] ) : 0;
			if ( $id && 'attachment' !== get_post_type( $id ) ) {
				$id = 0;
			}
			update_post_meta( $post_id, '_animus_batch_' . $doc . '_id', $id );
		}

		$approved = ! empty( $_POST['animus_batch_approved'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_animus_batch_approved', $approved );

		$is_current = ! empty( $_POST['animus_batch_is_current'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_animus_batch_is_current', $is_current );

		if ( 'yes' === $is_current && $product_id ) {
			self::clear_other_current_flags( $product_id, $post_id );
		}

		Animus_Audit::log(
			'batch_saved',
			'batch',
			$post_id,
			array( 'lot' => $lot, 'product' => $product_id, 'approved' => $approved, 'current' => $is_current )
		);
	}

	/**
	 * Only one batch per product may be flagged current.
	 *
	 * @param int $product_id Product ID.
	 * @param int $keep_id    Batch to keep flagged.
	 */
	private static function clear_other_current_flags( $product_id, $keep_id ) {
		$others = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'post__not_in'   => array( $keep_id ),
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_animus_batch_product', 'value' => $product_id ),
					array( 'key' => '_animus_batch_is_current', 'value' => 'yes' ),
				),
			)
		);
		foreach ( $others as $other_id ) {
			update_post_meta( $other_id, '_animus_batch_is_current', 'no' );
		}
	}

	/**
	 * Validate date strings to Y-m-d.
	 *
	 * @param string $value Raw date.
	 * @return string
	 */
	private static function sanitize_date( $value ) {
		$value = sanitize_text_field( $value );
		$date  = DateTime::createFromFormat( 'Y-m-d', $value );
		return ( $date && $date->format( 'Y-m-d' ) === $value ) ? $value : '';
	}

	/**
	 * Reject non-PDF uploads made from the batch screen.
	 *
	 * @param array $file Upload array.
	 * @return array
	 */
	public static function validate_document_upload( $file ) {
		if ( ! isset( $_REQUEST['post_id'] ) ) {
			return $file;
		}
		$parent = absint( $_REQUEST['post_id'] );
		if ( ! $parent || self::POST_TYPE !== get_post_type( $parent ) ) {
			return $file;
		}

		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( 'application/pdf' !== $check['type'] ) {
			$file['error'] = __( 'Batch documents must be PDF files.', 'animus-labs-core' );
		}
		return $file;
	}

	/**
	 * Batch list columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$new = array(
			'cb'             => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'          => __( 'Batch', 'animus-labs-core' ),
			'animus_lot'     => __( 'Lot', 'animus-labs-core' ),
			'animus_product' => __( 'Product', 'animus-labs-core' ),
			'animus_purity'  => __( 'Purity', 'animus-labs-core' ),
			'animus_dates'   => __( 'Tested / retest', 'animus-labs-core' ),
			'animus_docs'    => __( 'Documents', 'animus-labs-core' ),
			'animus_status'  => __( 'Status', 'animus-labs-core' ),
			'date'           => isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'animus-labs-core' ),
		);
		return $new;
	}

	/**
	 * Batch list column content.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Batch ID.
	 */
	public static function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'animus_lot':
				$lot = get_post_meta( $post_id, '_animus_batch_lot', true );
				echo $lot ? '<code>' . esc_html( $lot ) . '</code>' : '—';
				break;

			case 'animus_product':
				$pid = (int) get_post_meta( $post_id, '_animus_batch_product', true );
				if ( $pid ) {
					printf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $pid ) ), esc_html( get_the_title( $pid ) ) );
				} else {
					echo '—';
				}
				break;

			case 'animus_purity':
				$purity = get_post_meta( $post_id, '_animus_batch_purity', true );
				echo $purity ? esc_html( $purity . '%' ) : '—';
				break;

			case 'animus_dates':
				$tested = get_post_meta( $post_id, '_animus_batch_tested_on', true );
				$retest = get_post_meta( $post_id, '_animus_batch_retest_on', true );
				echo esc_html( ( $tested ? $tested : '—' ) . ' / ' . ( $retest ? $retest : '—' ) );
				break;

			case 'animus_docs':
				$out = array();
				foreach ( array( 'coa' => 'COA', 'sds' => 'SDS' ) as $key => $label ) {
					$id = (int) get_post_meta( $post_id, '_animus_batch_' . $key . '_id', true );
					if ( $id ) {
						$out[] = sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( wp_get_attachment_url( $id ) ), esc_html( $label ) );
					}
				}
				echo $out ? wp_kses_post( implode( ' · ', $out ) ) : '—';
				break;

			case 'animus_status':
				$approved = 'yes' === get_post_meta( $post_id, '_animus_batch_approved', true );
				$current  = 'yes' === get_post_meta( $post_id, '_animus_batch_is_current', true );
				printf(
					'<span class="animus-pill animus-pill--%1$s">%2$s</span>',
					esc_attr( $approved ? 'ok' : 'pending' ),
					esc_html( $approved ? __( 'Approved', 'animus-labs-core' ) : __( 'Pending', 'animus-labs-core' ) )
				);
				if ( $current ) {
					echo ' <span class="animus-pill animus-pill--current">' . esc_html__( 'Current lot', 'animus-labs-core' ) . '</span>';
				}
				break;
		}
	}

	/* ---------------------------------------------------------------------
	 * Data access
	 * ------------------------------------------------------------------- */

	/**
	 * Find a batch post ID by lot number.
	 *
	 * @param string $lot Lot number.
	 * @return int
	 */
	public static function find_batch_id_by_lot( $lot ) {
		$found = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_animus_batch_lot', 'value' => $lot ),
				),
			)
		);
		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Normalised batch record.
	 *
	 * @param int $batch_id Batch post ID.
	 * @return array|null
	 */
	public static function get_batch( $batch_id ) {
		if ( ! $batch_id || self::POST_TYPE !== get_post_type( $batch_id ) ) {
			return null;
		}

		$coa_id = (int) get_post_meta( $batch_id, '_animus_batch_coa_id', true );
		$sds_id = (int) get_post_meta( $batch_id, '_animus_batch_sds_id', true );

		return array(
			'id'         => (int) $batch_id,
			'lot'        => (string) get_post_meta( $batch_id, '_animus_batch_lot', true ),
			'product_id' => (int) get_post_meta( $batch_id, '_animus_batch_product', true ),
			'purity'     => (string) get_post_meta( $batch_id, '_animus_batch_purity', true ),
			'quantity'   => (string) get_post_meta( $batch_id, '_animus_batch_quantity', true ),
			'tested_on'  => (string) get_post_meta( $batch_id, '_animus_batch_tested_on', true ),
			'retest_on'  => (string) get_post_meta( $batch_id, '_animus_batch_retest_on', true ),
			'method'     => (string) get_post_meta( $batch_id, '_animus_batch_method', true ),
			'lab'        => (string) get_post_meta( $batch_id, '_animus_batch_lab', true ),
			'storage'    => (string) get_post_meta( $batch_id, '_animus_batch_storage', true ),
			'coa_url'    => $coa_id ? (string) wp_get_attachment_url( $coa_id ) : '',
			'sds_url'    => $sds_id ? (string) wp_get_attachment_url( $sds_id ) : '',
			'approved'   => 'yes' === get_post_meta( $batch_id, '_animus_batch_approved', true ),
			'is_current' => 'yes' === get_post_meta( $batch_id, '_animus_batch_is_current', true ),
		);
	}

	/**
	 * Approved batch record for a lot number, or null.
	 *
	 * @param string $lot Lot number.
	 * @return array|null
	 */
	public static function get_approved_batch_by_lot( $lot ) {
		$batch_id = self::find_batch_id_by_lot( $lot );
		if ( ! $batch_id ) {
			return null;
		}
		$batch = self::get_batch( $batch_id );
		if ( ! $batch || ! $batch['approved'] || 'publish' !== get_post_status( $batch_id ) ) {
			return null;
		}
		return $batch;
	}

	/**
	 * Current shipping batch for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array|null
	 */
	public static function get_current_batch( $product_id ) {
		$found = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => '_animus_batch_product', 'value' => absint( $product_id ) ),
					array( 'key' => '_animus_batch_is_current', 'value' => 'yes' ),
					array( 'key' => '_animus_batch_approved', 'value' => 'yes' ),
				),
			)
		);
		return $found ? self::get_batch( (int) $found[0] ) : null;
	}

	/**
	 * All approved batches for a product, newest tested first.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int,array>
	 */
	public static function get_batches_for_product( $product_id ) {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'orderby'        => 'meta_value',
				'meta_key'       => '_animus_batch_tested_on',
				'order'          => 'DESC',
				'meta_query'     => array(
					array( 'key' => '_animus_batch_product', 'value' => absint( $product_id ) ),
					array( 'key' => '_animus_batch_approved', 'value' => 'yes' ),
				),
			)
		);

		$out = array();
		foreach ( $ids as $id ) {
			$batch = self::get_batch( $id );
			if ( $batch ) {
				$out[] = $batch;
			}
		}
		return $out;
	}
}
