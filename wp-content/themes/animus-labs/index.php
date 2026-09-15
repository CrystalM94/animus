<?php
/**
 * Fallback template.
 *
 * @package Animus_Labs
 */

get_header();
?>
<main id="primary" class="animus-wrap animus-main">
	<?php if ( have_posts() ) : ?>
		<?php if ( is_home() && ! is_front_page() ) : ?>
			<header class="animus-pagehead">
				<h1><?php single_post_title(); ?></h1>
			</header>
		<?php endif; ?>

		<div class="animus-posts">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'excerpt' );
			endwhile;
			?>
		</div>

		<?php the_posts_pagination( array( 'mid_size' => 2, 'prev_text' => __( '&larr; Newer', 'animus-labs' ), 'next_text' => __( 'Older &rarr;', 'animus-labs' ) ) ); ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</main>
<?php
get_footer();
