<?php
/**
 * Static page template.
 *
 * @package Animus_Labs
 */

get_header();
?>
<main id="primary" class="animus-wrap animus-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'animus-page' ); ?>>
			<header class="animus-pagehead">
				<h1><?php the_title(); ?></h1>
				<span class="animus-pagehead__rule" aria-hidden="true"></span>
			</header>
			<div class="animus-content">
				<?php
				the_content();
				wp_link_pages( array( 'before' => '<nav class="animus-pagelinks">', 'after' => '</nav>' ) );
				?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
