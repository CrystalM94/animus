<?php
/**
 * Empty state.
 *
 * @package Animus_Labs
 */
?>
<section class="animus-empty">
	<p class="animus-kicker"><?php esc_html_e( 'Nothing found', 'animus-labs' ); ?></p>
	<h2><?php esc_html_e( 'No records match.', 'animus-labs' ); ?></h2>
	<p><?php esc_html_e( 'Adjust your search or browse the catalog.', 'animus-labs' ); ?></p>
	<?php get_search_form(); ?>
</section>
