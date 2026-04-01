<?php
/**
 * The template to display single post
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

// Full post loading
$full_post_loading          = elementra_get_value_gp( 'action' ) == 'full_post_loading';

// Prev post loading
$prev_post_loading          = elementra_get_value_gp( 'action' ) == 'prev_post_loading';
$prev_post_loading_type     = elementra_get_theme_option( 'posts_navigation_scroll_which_block', 'article' );

// Position of the related posts
$elementra_related_position   = elementra_get_theme_option( 'related_position', 'below_content' );

// Type of the prev/next post navigation
$elementra_posts_navigation   = elementra_get_theme_option( 'posts_navigation' );
$elementra_prev_post          = false;
$elementra_prev_post_same_cat = (int)elementra_get_theme_option( 'posts_navigation_scroll_same_cat', 1 );

// Rewrite style of the single post if current post loading via AJAX and featured image and title is not in the content
if ( ( $full_post_loading 
		|| 
		( $prev_post_loading && 'article' == $prev_post_loading_type )
	) 
	&& 
	! in_array( elementra_get_theme_option( 'single_style' ), array( 'style-6' ) )
) {
	elementra_storage_set_array( 'options_meta', 'single_style', 'style-6' );
}

do_action( 'elementra_action_prev_post_loading', $prev_post_loading, $prev_post_loading_type );

get_header();

while ( have_posts() ) {

	the_post();

	// Type of the prev/next post navigation
	if ( 'scroll' == $elementra_posts_navigation ) {
		$elementra_prev_post = get_previous_post( $elementra_prev_post_same_cat );  // Get post from same category
		if ( ! $elementra_prev_post && $elementra_prev_post_same_cat ) {
			$elementra_prev_post = get_previous_post( false );                    // Get post from any category
		}
		if ( ! $elementra_prev_post ) {
			$elementra_posts_navigation = 'links';
		}
	}

	// Override some theme options to display featured image, title and post meta in the dynamic loaded posts
	if ( $full_post_loading || ( $prev_post_loading && $elementra_prev_post ) ) {
		elementra_sc_layouts_showed( 'featured', false );
		elementra_sc_layouts_showed( 'title', false );
		elementra_sc_layouts_showed( 'postmeta', false );
	}

	// If related posts should be inside the content
	if ( strpos( $elementra_related_position, 'inside' ) === 0 ) {
		ob_start();
	}

	// Display post's content
	get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/content', 'single-' . elementra_get_theme_option( 'single_style' ) ), 'single-' . elementra_get_theme_option( 'single_style' ) );

	// If related posts should be inside the content
	if ( strpos( $elementra_related_position, 'inside' ) === 0 ) {
		$elementra_content = ob_get_contents();
		ob_end_clean();

		ob_start();
		do_action( 'elementra_action_related_posts' );
		$elementra_related_content = ob_get_contents();
		ob_end_clean();

		if ( ! empty( $elementra_related_content ) ) {
			$elementra_related_position_inside = max( 0, min( 9, elementra_get_theme_option( 'related_position_inside' ) ) );
			if ( 0 == $elementra_related_position_inside ) {
				$elementra_related_position_inside = mt_rand( 1, 9 );
			}

			$elementra_p_number         = 0;
			$elementra_related_inserted = false;
			$elementra_in_block         = false;
			$elementra_content_start    = strpos( $elementra_content, '<div class="post_content' );
			$elementra_content_end      = strrpos( $elementra_content, '</div>' );

			for ( $i = max( 0, $elementra_content_start ); $i < min( strlen( $elementra_content ) - 3, $elementra_content_end ); $i++ ) {
				if ( $elementra_content[ $i ] != '<' ) {
					continue;
				}
				if ( $elementra_in_block ) {
					if ( strtolower( substr( $elementra_content, $i + 1, 12 ) ) == '/blockquote>' ) {
						$elementra_in_block = false;
						$i += 12;
					}
					continue;
				} else if ( strtolower( substr( $elementra_content, $i + 1, 10 ) ) == 'blockquote' && in_array( $elementra_content[ $i + 11 ], array( '>', ' ' ) ) ) {
					$elementra_in_block = true;
					$i += 11;
					continue;
				} else if ( 'p' == $elementra_content[ $i + 1 ] && in_array( $elementra_content[ $i + 2 ], array( '>', ' ' ) ) ) {
					$elementra_p_number++;
					if ( $elementra_related_position_inside == $elementra_p_number ) {
						$elementra_related_inserted = true;
						$elementra_content = ( $i > 0 ? substr( $elementra_content, 0, $i ) : '' )
											. $elementra_related_content
											. substr( $elementra_content, $i );
					}
				}
			}
			if ( ! $elementra_related_inserted ) {
				if ( $elementra_content_end > 0 ) {
					$elementra_content = substr( $elementra_content, 0, $elementra_content_end ) . $elementra_related_content . substr( $elementra_content, $elementra_content_end );
				} else {
					$elementra_content .= $elementra_related_content;
				}
			}
		}

		elementra_show_layout( $elementra_content );
	}

	// Comments
	do_action( 'elementra_action_before_comments' );
	comments_template();
	do_action( 'elementra_action_after_comments' );

	// Related posts
	if ( 'below_content' == $elementra_related_position
		&& ( 'scroll' != $elementra_posts_navigation || (int)elementra_get_theme_option( 'posts_navigation_scroll_hide_related', 0 ) == 0 )
		&& ( ! $full_post_loading || (int)elementra_get_theme_option( 'open_full_post_hide_related', 1 ) == 0 )
	) {
		do_action( 'elementra_action_related_posts' );
	}

	// Post navigation: type 'scroll'
	if ( 'scroll' == $elementra_posts_navigation && ! $full_post_loading ) {
		?>
		<div class="nav-links-single-scroll"
			data-post-id="<?php echo esc_attr( get_the_ID( $elementra_prev_post ) ); ?>"
			data-post-link="<?php echo esc_attr( get_permalink( $elementra_prev_post ) ); ?>"
			data-post-title="<?php the_title_attribute( array( 'post' => $elementra_prev_post ) ); ?>"
			data-cur-post-link="<?php echo esc_attr( get_permalink() ); ?>"
			data-cur-post-title="<?php the_title_attribute(); ?>"
			<?php do_action( 'elementra_action_nav_links_single_scroll_data', $elementra_prev_post ); ?>
		></div>
		<?php
	}
}

get_footer();
