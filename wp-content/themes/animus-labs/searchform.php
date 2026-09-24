<?php
/**
 * Search form.
 *
 * @package Animus_Labs
 */
?>
<form role="search" method="get" class="animus-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="animus-s"><?php esc_html_e( 'Search', 'animus-labs' ); ?></label>
	<input type="search" id="animus-s" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search the catalog&hellip;', 'animus-labs' ); ?>">
	<button type="submit" class="animus-btn animus-btn--solid"><?php esc_html_e( 'Search', 'animus-labs' ); ?></button>
</form>
