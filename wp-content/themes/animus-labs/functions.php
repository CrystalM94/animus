<?php
/**
 * Animus Labs theme bootstrap.
 *
 * @package Animus_Labs
 */

defined( 'ABSPATH' ) || exit;

define( 'ANIMUS_THEME_VERSION', '1.0.0' );
define( 'ANIMUS_THEME_DIR', get_template_directory() );
define( 'ANIMUS_THEME_URI', get_template_directory_uri() );

require_once ANIMUS_THEME_DIR . '/inc/helpers.php';
require_once ANIMUS_THEME_DIR . '/inc/woocommerce.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'primary'      => __( 'Primary menu', 'animus-labs' ),
				'footer'       => __( 'Footer menu', 'animus-labs' ),
				'footer-legal' => __( 'Footer legal menu', 'animus-labs' ),
			)
		);

		add_image_size( 'animus-hero', 1920, 1080, true );
		add_image_size( 'animus-card', 800, 800, true );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'animus-fonts',
			'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'animus-main', ANIMUS_THEME_URI . '/assets/css/main.css', array(), ANIMUS_THEME_VERSION );
		wp_enqueue_script( 'animus-main', ANIMUS_THEME_URI . '/assets/js/main.js', array(), ANIMUS_THEME_VERSION, true );
	}
);

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_section(
			'animus_hero',
			array(
				'title'    => __( 'Animus Labs — Homepage hero', 'animus-labs' ),
				'priority' => 30,
			)
		);
		foreach ( array(
			'animus_hero_kicker' => array( __( 'Hero kicker', 'animus-labs' ), 'ANIMUS LABORATORIES' ),
			'animus_hero_title'  => array( __( 'Hero title', 'animus-labs' ), 'Precision Compounds for Rigorous Research' ),
			'animus_hero_sub'    => array( __( 'Hero subtitle', 'animus-labs' ), 'Independently verified purity. Fully documented lots. Research use only.' ),
		) as $id => $field ) {
			$wp_customize->add_setting( $id, array( 'default' => $field[1], 'sanitize_callback' => 'sanitize_text_field' ) );
			$wp_customize->add_control( $id, array( 'section' => 'animus_hero', 'label' => $field[0], 'type' => 'text' ) );
		}
	}
);

add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'animus-dark';
		return $classes;
	}
);
