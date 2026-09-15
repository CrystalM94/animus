<?php
/**
 * Search results.
 *
 * @package Animus_Labs
 */

get_header();
?>
<main id="primary" class="animus-wrap animus-main">
	<header class="animus-pagehead">
		<h1>
			<?php
			/* translators: %s: search query */
			printf( esc_html__( 'Results for &ldquo;%s&rdquo;', 'animus-labs' ), esc_html( get_search_query() ) );
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="animus-posts">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'excerpt' );
			endwhile;
			?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</main>
<?php
get_footer();
