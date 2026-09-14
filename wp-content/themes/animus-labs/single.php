<?php
/**
 * Single post template.
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
				<p class="animus-kicker"><?php echo esc_html( get_the_date() ); ?></p>
				<h1><?php the_title(); ?></h1>
				<span class="animus-pagehead__rule" aria-hidden="true"></span>
			</header>
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="animus-page__media"><?php the_post_thumbnail( 'animus-hero' ); ?></figure>
			<?php endif; ?>
			<div class="animus-content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
