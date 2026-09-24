<?php
/**
 * Scientific specification fields on WooCommerce products.
 *
 * Stored as product meta with an `_animus_` prefix and surfaced on the
 * product page as a specification table.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_Product_Meta {

	const NONCE = 'animus_product_meta_nonce';

	/**
	 * Field definitions: key => [label, type, description].
	 *
	 * @return array<string,array>
	 */
	public static function fields() {
		return array(
			'purity'            => array( __( 'Purity (%)', 'animus-labs-core' ), 'text', __( 'e.g. 99.2', 'animus-labs-core' ) ),
			'molecular_formula' => array( __( 'Molecular formula', 'animus-labs-core' ), 'text', __( 'e.g. C62H98N16O22', 'animus-labs-core' ) ),
			'molecular_weight'  => array( __( 'Molecular weight (g/mol)', 'animus-labs-core' ), 'text', '' ),
			'cas_number'        => array( __( 'CAS number', 'animus-labs-core' ), 'text', __( 'Leave blank where not applicable', 'animus-labs-core' ) ),
			'sequence'          => array( __( 'Sequence', 'animus-labs-core' ), 'text', __( 'Amino acid sequence, where applicable', 'animus-labs-core' ) ),
			'quantity'          => array( __( 'Quantity per unit', 'animus-labs-core' ), 'text', __( 'e.g. 10 mg lyophilised powder', 'animus-labs-core' ) ),
			'appearance'        => array( __( 'Appearance', 'animus-labs-core' ), 'text', '' ),
			'solubility'        => array( __( 'Solubility', 'animus-labs-core' ), 'text', '' ),
			'storage'           => array( __( 'Storage requirements', 'animus-labs-core' ), 'textarea', __( 'e.g. Store lyophilised at -20 °C, protect from light', 'animus-labs-core' ) ),
			'testing_method'    => array( __( 'Testing method', 'animus-labs-core' ), 'text', __( 'e.g. HPLC-UV, LC-MS', 'animus-labs-core' ) ),
			'testing_lab'       => array( __( 'Testing laboratory / provider', 'animus-labs-core' ), 'text', '' ),
			'source_region'     => array( __( 'Manufacturing source', 'animus-labs-core' ), 'text', __( 'e.g. US Sourced', 'animus-labs-core' ) ),
		);
	}

	public static function init() {
		add_action( 'woocommerce_product_data_tabs', array( __CLASS__, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'panel' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );

		// Front-end output.
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_specs' ), 25 );
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'add_documentation_tab' ) );
	}

	/**
	 * Register the product data tab.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public static function tab( $tabs ) {
		$tabs['animus_specs'] = array(
			'label'    => __( 'Specification', 'animus-labs-core' ),
			'target'   => 'animus_specs_data',
			'class'    => array(),
			'priority' => 21,
		);
		return $tabs;
	}

	/**
	 * Render the product data panel.
	 */
	public static function panel() {
		global $post;
		echo '<div id="animus_specs_data" class="panel woocommerce_options_panel">';
		wp_nonce_field( self::NONCE, self::NONCE );
		echo '<div class="options_group">';

		foreach ( self::fields() as $key => $field ) {
			$value = get_post_meta( $post->ID, '_animus_' . $key, true );
			$args  = array(
				'id'          => '_animus_' . $key,
				'label'       => $field[0],
				'value'       => $value,
				'desc_tip'    => true,
				'description' => $field[2],
			);
			if ( 'textarea' === $field[1] ) {
				woocommerce_wp_textarea_input( $args );
			} else {
				woocommerce_wp_text_input( $args );
			}
		}

		echo '</div>';
		echo '<div class="options_group"><p class="form-field"><em>';
		esc_html_e( 'Lot numbers, COAs, SDS documents, testing dates and retest dates are managed per batch under Animus Labs → Batches.', 'animus-labs-core' );
		echo '</em></p></div>';
		echo '</div>';
	}

	/**
	 * Persist submitted values.
	 *
	 * @param int $post_id Product ID.
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_product', $post_id ) ) {
			return;
		}

		foreach ( self::fields() as $key => $field ) {
			$meta_key = '_animus_' . $key;
			$raw      = isset( $_POST[ $meta_key ] ) ? wp_unslash( $_POST[ $meta_key ] ) : '';
			$value    = 'textarea' === $field[1] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );

			if ( '' === $value ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}

		Animus_Audit::log( 'product_specs_saved', 'product', $post_id );
	}

	/**
	 * Specification table on the single product page.
	 */
	public static function render_specs() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$rows = array();
		if ( $product->get_sku() ) {
			$rows[ __( 'SKU', 'animus-labs-core' ) ] = $product->get_sku();
		}
		foreach ( self::fields() as $key => $field ) {
			$value = get_post_meta( $product->get_id(), '_animus_' . $key, true );
			if ( '' !== $value ) {
				$label = 'purity' === $key ? __( 'Purity', 'animus-labs-core' ) : $field[0];
				$rows[ $label ] = 'purity' === $key ? $value . '%' : $value;
			}
		}

		// Current batch data, if a batch is assigned.
		$batch = Animus_Batches::get_current_batch( $product->get_id() );
		if ( $batch ) {
			$rows[ __( 'Current lot', 'animus-labs-core' ) ]  = $batch['lot'];
			if ( $batch['tested_on'] ) {
				$rows[ __( 'Tested on', 'animus-labs-core' ) ] = $batch['tested_on'];
			}
			if ( $batch['retest_on'] ) {
				$rows[ __( 'Retest / expiry', 'animus-labs-core' ) ] = $batch['retest_on'];
			}
		}

		if ( empty( $rows ) ) {
			return;
		}

		echo '<div class="animus-specs"><h3>' . esc_html__( 'Specification', 'animus-labs-core' ) . '</h3><dl>';
		foreach ( $rows as $label => $value ) {
			printf( '<dt>%s</dt><dd>%s</dd>', esc_html( $label ), esc_html( $value ) );
		}
		echo '</dl>';

		if ( $batch ) {
			echo '<div class="animus-verify-docs">';
			if ( $batch['coa_url'] ) {
				printf(
					'<a class="animus-btn animus-btn--ghost" href="%s" target="_blank" rel="noopener">%s</a>',
					esc_url( $batch['coa_url'] ),
					esc_html__( 'Download COA', 'animus-labs-core' )
				);
			}
			if ( $batch['sds_url'] ) {
				printf(
					'<a class="animus-btn animus-btn--ghost" href="%s" target="_blank" rel="noopener">%s</a>',
					esc_url( $batch['sds_url'] ),
					esc_html__( 'Download SDS', 'animus-labs-core' )
				);
			}
			printf(
				'<a class="animus-btn animus-btn--ghost" href="%s">%s</a>',
				esc_url( animus_lot_url( $batch['lot'] ) ),
				esc_html__( 'Verify this lot', 'animus-labs-core' )
			);
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Documentation tab listing all published batches for the product.
	 *
	 * @param array $tabs Product tabs.
	 * @return array
	 */
	public static function add_documentation_tab( $tabs ) {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return $tabs;
		}
		if ( ! Animus_Batches::get_batches_for_product( $product->get_id() ) ) {
			return $tabs;
		}

		$tabs['animus_docs'] = array(
			'title'    => __( 'Documentation', 'animus-labs-core' ),
			'priority' => 25,
			'callback' => array( __CLASS__, 'render_documentation_tab' ),
		);
		return $tabs;
	}

	/**
	 * Batch/COA table for the documentation tab.
	 */
	public static function render_documentation_tab() {
		global $product;
		$batches = Animus_Batches::get_batches_for_product( $product->get_id() );

		echo '<h2>' . esc_html__( 'Lot documentation', 'animus-labs-core' ) . '</h2>';
		echo '<table class="animus-doc-table"><thead><tr>';
		printf( '<th>%s</th>', esc_html__( 'Lot', 'animus-labs-core' ) );
		printf( '<th>%s</th>', esc_html__( 'Purity', 'animus-labs-core' ) );
		printf( '<th>%s</th>', esc_html__( 'Tested', 'animus-labs-core' ) );
		printf( '<th>%s</th>', esc_html__( 'Retest', 'animus-labs-core' ) );
		printf( '<th>%s</th>', esc_html__( 'Documents', 'animus-labs-core' ) );
		echo '</tr></thead><tbody>';

		foreach ( $batches as $batch ) {
			echo '<tr>';
			printf( '<td><a href="%s">%s</a></td>', esc_url( animus_lot_url( $batch['lot'] ) ), esc_html( $batch['lot'] ) );
			printf( '<td>%s</td>', esc_html( $batch['purity'] ? $batch['purity'] . '%' : '—' ) );
			printf( '<td>%s</td>', esc_html( $batch['tested_on'] ? $batch['tested_on'] : '—' ) );
			printf( '<td>%s</td>', esc_html( $batch['retest_on'] ? $batch['retest_on'] : '—' ) );
			echo '<td>';
			if ( $batch['coa_url'] ) {
				printf( '<a href="%s" target="_blank" rel="noopener">%s</a> ', esc_url( $batch['coa_url'] ), esc_html__( 'COA', 'animus-labs-core' ) );
			}
			if ( $batch['sds_url'] ) {
				printf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $batch['sds_url'] ), esc_html__( 'SDS', 'animus-labs-core' ) );
			}
			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}
}
