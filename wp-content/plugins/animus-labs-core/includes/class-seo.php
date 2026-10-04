<?php
/**
 * Lightweight SEO layer: meta descriptions, canonical links, and
 * Open Graph / Twitter card tags. WooCommerce already emits JSON-LD
 * product schema; this covers everything it does not.
 *
 * @package Animus_Labs_Core
 */

defined( 'ABSPATH' ) || exit;

class Animus_SEO {

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'head' ), 1 );
	}

	public static function head() {
		$desc  = self::description();
		$title = wp_get_document_title();
		$url   = self::canonical();
		$image = self::image();

		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( $url ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
			echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		}
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( self::og_type() ) . '">' . "\n";
		if ( $desc ) {
			echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
			echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		echo '<meta name="twitter:card" content="' . esc_attr( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		if ( $image ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			global $product;
			if ( $product instanceof WC_Product && '' !== $product->get_price() ) {
				echo '<meta property="product:price:amount" content="' . esc_attr( $product->get_price() ) . '">' . "\n";
				echo '<meta property="product:price:currency" content="' . esc_attr( get_woocommerce_currency() ) . '">' . "\n";
			}
		}
	}

	private static function og_type() {
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'product';
		}
		return is_singular() ? 'article' : 'website';
	}

	private static function canonical() {
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return wc_get_page_permalink( 'shop' );
		}
		if ( is_singular() ) {
			$c = wp_get_canonical_url();
			return $c ? $c : get_permalink();
		}
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		return '';
	}

	private static function image() {
		if ( is_singular() && has_post_thumbnail() ) {
			$src = wp_get_attachment_image_url( get_post_thumbnail_id(), 'large' );
			if ( $src ) {
				return $src;
			}
		}
		return '';
	}

	private static function description() {
		if ( function_exists( 'is_product' ) && is_product() ) {
			global $post;
			$product = wc_get_product( $post );
			if ( ! $product ) {
				return '';
			}
			$desc = $product->get_short_description();
			if ( '' === $desc ) {
				$desc = sprintf(
					/* translators: %s: product name */
					__( '%s — research compound with lot-level COA and SDS documentation, third-party verified. For laboratory research use only.', 'animus-labs-core' ),
					$product->get_name()
				);
			}
			return self::trim( $desc );
		}
		if ( is_singular() ) {
			global $post;
			$text = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			return self::trim( $text );
		}
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			return __( 'Documented research compounds — every lot verified with public COA and SDS records. For laboratory research use only.', 'animus-labs-core' );
		}
		if ( is_front_page() ) {
			$tagline = get_bloginfo( 'description' );
			return $tagline ? $tagline : __( 'Precision compounds for rigorous research — independently verified purity, fully documented lots, research use only.', 'animus-labs-core' );
		}
		return '';
	}

	private static function trim( $text ) {
		return wp_trim_words( wp_strip_all_tags( $text ), 28, '…' );
	}
}
