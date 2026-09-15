<?php
/**
 * Archive template.
 *
 * @package Animus_Labs
 */

get_header();
?>
<main id="primary" class="animus-wrap animus-main">
	<header class="animus-pagehead">
		<h1><?php the_archive_title(); ?></h1>
		<?php the_archive_description( '<div class="animus-content">', '</div>' ); ?>
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
