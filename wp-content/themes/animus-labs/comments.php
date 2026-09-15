<?php
/**
 * Comments — stores typically keep these disabled; minimal markup.
 *
 * @package Animus_Labs
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="animus-comments">
	<?php if ( have_comments() ) : ?>
		<h2><?php comments_number(); ?></h2>
		<ol class="comment-list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
