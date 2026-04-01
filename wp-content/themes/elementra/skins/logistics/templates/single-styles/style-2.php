<?php
/**
 * The "Style 2" template to display the post header of the single post or attachment:
 * featured image and title placed in the post header
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.75.0
 */

if ( apply_filters( 'elementra_filter_single_post_header', elementra_is_singular( 'post' ) || elementra_is_singular( 'attachment' ) ) ) {
	$elementra_post_format = str_replace( 'post-format-', '', get_post_format() );

	// Featured image
	ob_start();
	elementra_show_post_featured_image( array(
		'thumb_bg'  => true,
	) );
	$elementra_post_header = ob_get_contents();
	ob_end_clean();

	$elementra_with_featured_image = elementra_is_with_featured_image( $elementra_post_header );

	// Post title and meta
	ob_start();
	elementra_show_post_title_and_meta( array(
										'content_wrap'  => true,
										'share_type'    => 'list',
										'show_labels'   => true,
										'author_avatar' => false,
										'add_spaces'    => false,
										'cat_sep' 	    => false,
										)
									);
	$elementra_post_header .= ob_get_contents();
	ob_end_clean();

	if ( strpos( $elementra_post_header, 'post_featured' ) !== false
		|| strpos( $elementra_post_header, 'post_title' ) !== false
		|| strpos( $elementra_post_header, 'post_meta' ) !== false
	) {
		?>
		<div class="post_header_wrap post_header_wrap_in_header post_header_wrap_style_<?php
			echo esc_attr( elementra_get_theme_option( 'single_style' ) );
			if ( $elementra_with_featured_image ) {
				echo ' with_featured_image';
			}
		?>">
			<?php
			do_action( 'elementra_action_before_post_header' );
			elementra_show_layout( $elementra_post_header );
			do_action( 'elementra_action_after_post_header' );
			?>
		</div>
		<?php
	}
}
