<?php
/**
 * The default template to display the content of the single post or attachment
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */
?>
<article id="post-<?php the_ID(); ?>"
	<?php
	post_class( 'post_item_single'
		. ' post_type_' . esc_attr( get_post_type() ) 
		. ' post_format_' . esc_attr( str_replace( 'post-format-', '', get_post_format() ) )
	);
	elementra_add_seo_itemprops();
	?>
>
<?php

	do_action( 'elementra_action_before_post_data' );
	elementra_add_seo_snippets();
	do_action( 'elementra_action_after_post_data' );

	do_action( 'elementra_action_before_post_content' );

	// Post content
	?>
	<div class="post_content post_content_single entry-content"<?php
		if ( elementra_is_on( elementra_get_theme_option( 'seo_snippets' ) ) ) {
			?> itemprop="mainEntityOfPage"<?php
		}
	?>>
		<?php
		the_content();
		?>
	</div>
	<?php

	do_action( 'elementra_action_after_post_content' );
	
	// Post footer: Tags, likes, share, author, prev/next links and comments
	do_action( 'elementra_action_before_post_footer' );
	?>
	<div class="post_footer post_footer_single entry-footer">
		<?php
		elementra_show_post_pagination();
		if ( elementra_is_single() && ! is_attachment() ) {
			elementra_show_post_footer();
		}
		?>
	</div>
	<?php
	do_action( 'elementra_action_after_post_footer' );
	?>
</article>
