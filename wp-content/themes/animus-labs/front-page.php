<?php
/**
 * Homepage.
 *
 * @package Animus_Labs
 */

get_header();
?>
<main id="primary" class="animus-main">

	<section class="animus-hero">
		<div class="animus-hero__inner animus-wrap">
			<p class="animus-kicker"><?php echo esc_html( get_theme_mod( 'animus_hero_kicker', 'ANIMUS LABORATORIES' ) ); ?></p>
			<h1 class="animus-hero__title"><?php echo esc_html( get_theme_mod( 'animus_hero_title', 'Precision Compounds for Rigorous Research' ) ); ?></h1>
			<p class="animus-hero__sub"><?php echo esc_html( get_theme_mod( 'animus_hero_sub', 'Independently verified purity. Fully documented lots. Research use only.' ) ); ?></p>
			<div class="animus-hero__actions">
				<a class="animus-btn animus-btn--solid" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'Browse the catalog', 'animus-labs' ); ?></a>
				<a class="animus-btn animus-btn--ghost" href="<?php echo esc_url( home_url( '/verify/' ) ); ?>"><?php esc_html_e( 'Verify a lot', 'animus-labs' ); ?></a>
			</div>
			<div class="animus-hero__traits">
				<span>&#x2265;98% verified purity</span>
				<span class="animus-hero__sep" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Third-party HPLC / MS', 'animus-labs' ); ?></span>
				<span class="animus-hero__sep" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Lot-level COA + SDS', 'animus-labs' ); ?></span>
			</div>
		</div>
		<div class="animus-hero__ornament" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
	</section>

	<section class="animus-section animus-wrap">
		<?php animus_ruo_notice( 'banner' ); ?>
	</section>

	<?php if ( function_exists( 'wc_get_products' ) ) : ?>
	<section class="animus-section animus-wrap">
		<header class="animus-section__head">
			<p class="animus-kicker"><?php esc_html_e( 'Catalog', 'animus-labs' ); ?></p>
			<h2><?php esc_html_e( 'Featured compounds', 'animus-labs' ); ?></h2>
			<a class="animus-link" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'View all &rarr;', 'animus-labs' ); ?></a>
		</header>
		<?php echo do_shortcode( '[products limit="9" columns="3" visibility="featured"]' ); ?>
	</section>

	<section class="animus-section animus-wrap">
		<header class="animus-section__head">
			<p class="animus-kicker"><?php esc_html_e( 'Collections', 'animus-labs' ); ?></p>
			<h2><?php esc_html_e( 'Browse by category', 'animus-labs' ); ?></h2>
		</header>
		<?php echo do_shortcode( '[product_categories number="6" columns="3" hide_empty="1"]' ); ?>
	</section>
	<?php endif; ?>

	<section class="animus-section animus-pillars">
		<div class="animus-wrap animus-pillars__grid">
			<article class="animus-pillar">
				<span class="animus-pillar__num">I</span>
				<h3><?php esc_html_e( 'Documented', 'animus-labs' ); ?></h3>
				<p><?php esc_html_e( 'Every lot carries a certificate of analysis and safety data sheet, retrievable at any time through public lot verification.', 'animus-labs' ); ?></p>
			</article>
			<article class="animus-pillar">
				<span class="animus-pillar__num">II</span>
				<h3><?php esc_html_e( 'Traceable', 'animus-labs' ); ?></h3>
				<p><?php esc_html_e( 'The lot shipped with your order is recorded permanently — historical orders always resolve to the lot that actually shipped.', 'animus-labs' ); ?></p>
			</article>
			<article class="animus-pillar">
				<span class="animus-pillar__num">III</span>
				<h3><?php esc_html_e( 'Independent', 'animus-labs' ); ?></h3>
				<p><?php esc_html_e( 'Purity and identity are confirmed by third-party analytical laboratories using HPLC and mass spectrometry.', 'animus-labs' ); ?></p>
			</article>
		</div>
	</section>

	<section class="animus-section animus-wrap animus-cta">
		<h2><?php esc_html_e( 'Questions about a compound or a lot?', 'animus-labs' ); ?></h2>
		<p><?php esc_html_e( 'Our documentation team responds to research-use and verification inquiries.', 'animus-labs' ); ?></p>
		<a class="animus-btn animus-btn--ghost" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact us', 'animus-labs' ); ?></a>
	</section>

</main>
<?php
get_footer();
