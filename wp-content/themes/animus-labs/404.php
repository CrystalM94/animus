<?php
/**
 * 404 template.
 *
 * @package Animus_Labs
 */

get_header();
?>
<main id="primary" class="animus-wrap animus-main">
	<header class="animus-pagehead animus-pagehead--center">
		<p class="animus-kicker"><?php esc_html_e( 'Error 404', 'animus-labs' ); ?></p>
		<h1><?php esc_html_e( 'This record does not exist.', 'animus-labs' ); ?></h1>
		<span class="animus-pagehead__rule" aria-hidden="true"></span>
	</header>
	<div class="animus-content animus-content--center">
		<p><?php esc_html_e( 'The page or lot record you requested could not be found. Try searching or verify a lot number directly.', 'animus-labs' ); ?></p>
		<?php get_search_form(); ?>
		<p><a class="animus-btn animus-btn--ghost" href="<?php echo esc_url( home_url( '/verify/' ) ); ?>"><?php esc_html_e( 'Verify a lot', 'animus-labs' ); ?></a></p>
	</div>
</main>
<?php
get_footer();
