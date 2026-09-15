<?php
/**
 * Post card in listings.
 *
 * @package Animus_Labs
 */
?>
<article <?php post_class( 'animus-card' ); ?>>
	<a class="animus-card__link" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="animus-card__media"><?php the_post_thumbnail( 'animus-card' ); ?></figure>
		<?php endif; ?>
		<span class="animus-card__rule" aria-hidden="true"></span>
		<h3 class="animus-card__title"><?php the_title(); ?></h3>
	</a>
	<p class="animus-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
	<a class="animus-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read &rarr;', 'animus-labs' ); ?></a>
</article>
