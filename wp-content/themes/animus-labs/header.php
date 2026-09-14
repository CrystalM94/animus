<?php
/**
 * Site header.
 *
 * @package Animus_Labs
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="animus-skiplink" href="#primary"><?php esc_html_e( 'Skip to content', 'animus-labs' ); ?></a>

<?php animus_topbar(); ?>

<header class="animus-header" id="site-header">
	<div class="animus-wrap animus-header__inner">
		<a class="animus-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="animus-brand__mark" aria-hidden="true">&#x039B;</span>
				<span class="animus-brand__name">ANIMUS <em>LABS</em></span>
			<?php endif; ?>
		</a>

		<button class="animus-nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu" data-nav-toggle>
			<span class="animus-nav-toggle__bar"></span>
			<span class="animus-nav-toggle__bar"></span>
			<span class="animus-nav-toggle__bar"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'animus-labs' ); ?></span>
		</button>

		<nav class="animus-nav" aria-label="<?php esc_attr_e( 'Primary', 'animus-labs' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_id'        => 'primary-menu',
					'menu_class'     => 'animus-menu',
					'fallback_cb'    => 'animus_fallback_menu',
				)
			);
			?>
		</nav>

		<div class="animus-header__actions">
			<?php if ( function_exists( 'wc_get_account_endpoint_url' ) ) : ?>
				<a class="animus-header__link" href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>">
					<?php esc_html_e( 'Account', 'animus-labs' ); ?>
				</a>
			<?php endif; ?>
			<?php if ( function_exists( 'WC' ) && WC()->cart ) : ?>
				<a class="animus-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'animus-labs' ); ?>">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L5 3H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
					<span class="animus-header__cart-count"><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>
<?php

/**
 * Fallback nav when no menu is assigned yet.
 */
function animus_fallback_menu() {
	$items = array(
		__( 'Shop', 'animus-labs' )    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		__( 'Verify Lot', 'animus-labs' ) => home_url( '/verify/' ),
		__( 'Quality', 'animus-labs' ) => home_url( '/quality-standards/' ),
		__( 'FAQ', 'animus-labs' )     => home_url( '/faq/' ),
		__( 'Contact', 'animus-labs' ) => home_url( '/contact/' ),
	);
	echo '<ul id="primary-menu" class="animus-menu">';
	foreach ( $items as $label => $url ) {
		printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}
