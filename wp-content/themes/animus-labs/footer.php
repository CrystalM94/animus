<?php
/**
 * Site footer.
 *
 * @package Animus_Labs
 */
?>
<footer class="animus-footer">
	<div class="animus-wrap">
		<div class="animus-footer__grid">
			<div class="animus-footer__brand">
				<span class="animus-brand__mark animus-brand__mark--footer" aria-hidden="true">&#x039B;</span>
				<p class="animus-footer__name">ANIMUS LABS</p>
				<p class="animus-footer__tag"><?php esc_html_e( 'Laboratory research materials, documented to the lot.', 'animus-labs' ); ?></p>
			</div>

			<nav class="animus-footer__col" aria-label="<?php esc_attr_e( 'Company', 'animus-labs' ); ?>">
				<h4><?php esc_html_e( 'Explore', 'animus-labs' ); ?></h4>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'animus-footer__menu',
						'fallback_cb'    => 'animus_footer_fallback',
					)
				);
				?>
			</nav>

			<nav class="animus-footer__col" aria-label="<?php esc_attr_e( 'Policies', 'animus-labs' ); ?>">
				<h4><?php esc_html_e( 'Policies', 'animus-labs' ); ?></h4>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer-legal',
						'container'      => false,
						'menu_class'     => 'animus-footer__menu',
						'fallback_cb'    => 'animus_footer_legal_fallback',
					)
				);
				?>
			</nav>

			<div class="animus-footer__col animus-footer__verify">
				<h4><?php esc_html_e( 'Verification', 'animus-labs' ); ?></h4>
				<p><?php esc_html_e( 'Every lot ships with a certificate of analysis and a verifiable batch record.', 'animus-labs' ); ?></p>
				<a class="animus-btn animus-btn--ghost" href="<?php echo esc_url( home_url( '/verify/' ) ); ?>"><?php esc_html_e( 'Verify a lot number', 'animus-labs' ); ?></a>
			</div>
		</div>

		<div class="animus-footer__ruo">
			<?php animus_ruo_notice( 'banner' ); ?>
		</div>

		<p class="animus-footer__disclaimer"><?php esc_html_e( 'Animus Labs products are sold for research use only (RUO). Not for human or animal consumption. Products have not been evaluated by the FDA and are not intended to diagnose, treat, cure, or prevent any disease. Animus Labs is a research materials supplier and is not a pharmacy, compounding facility, or outsourcing facility under sections 503A or 503B of the FD&C Act. Buyers are solely responsible for ensuring lawful use under all applicable federal, state, local, and international regulations.', 'animus-labs' ); ?></p>

		<div class="animus-footer__bar">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'Animus Labs. All rights reserved.', 'animus-labs' ); ?></p>
			<p class="animus-footer__legal-note"><?php esc_html_e( 'Not for human or veterinary use. Not evaluated by the FDA.', 'animus-labs' ); ?></p>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
<?php

function animus_footer_fallback() {
	foreach ( array(
		__( 'Shop', 'animus-labs' )            => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
		__( 'Quality Standards', 'animus-labs' ) => home_url( '/quality-standards/' ),
		__( 'FAQ', 'animus-labs' )             => home_url( '/faq/' ),
		__( 'Contact', 'animus-labs' )         => home_url( '/contact/' ),
	) as $label => $url ) {
		printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
}

function animus_footer_legal_fallback() {
	foreach ( array(
		__( 'Research Use Policy', 'animus-labs' ) => home_url( '/research-use-policy/' ),
		__( 'Terms', 'animus-labs' )             => home_url( '/terms/' ),
		__( 'Privacy Policy', 'animus-labs' )    => home_url( '/privacy-policy/' ),
		__( 'Shipping Policy', 'animus-labs' )   => home_url( '/shipping-policy/' ),
		__( 'Refund Policy', 'animus-labs' )     => home_url( '/refund-policy/' ),
	) as $label => $url ) {
		printf( '<li class="menu-item"><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
}
