<?php
/**
 * The Classic template to display the content
 *
 * Used for index/archive/search.
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

$elementra_template_args = get_query_var( 'elementra_template_args' );

if ( is_array( $elementra_template_args ) ) {
	$elementra_columns       = empty( $elementra_template_args['columns'] ) ? 1 : max( 1, $elementra_template_args['columns'] );
	$elementra_blog_style    = array( $elementra_template_args['type'], $elementra_columns );
	$elementra_columns_class = elementra_get_column_class( 1, $elementra_columns, ! empty( $elementra_template_args['columns_tablet']) ? $elementra_template_args['columns_tablet'] : '', ! empty($elementra_template_args['columns_mobile']) ? $elementra_template_args['columns_mobile'] : '' );
} else {
	$elementra_template_args = array();
	$elementra_blog_style    = explode( '_', elementra_get_theme_option( 'blog_style' ) );
	$elementra_columns       = empty( $elementra_blog_style[1] ) ? 1 : max( 1, $elementra_blog_style[1] );
	$elementra_columns_class = elementra_get_column_class( 1, $elementra_columns );
}
$elementra_expanded   = ! elementra_sidebar_present() && elementra_get_theme_option( 'expand_content' ) == 'expand';

$elementra_post_format = get_post_format();
$elementra_post_format = empty( $elementra_post_format ) ? 'standard' : str_replace( 'post-format-', '', $elementra_post_format );

?><div class="<?php
	if ( ! empty( $elementra_template_args['slider'] ) ) {
		echo ' slider-slide swiper-slide';
	} else {
		echo ( elementra_is_blog_style_use_masonry( $elementra_blog_style[0] )
			? 'masonry_item masonry_item-1_' . esc_attr( $elementra_columns )
			: esc_attr( $elementra_columns_class )
			);
	}
?>"><article id="post-<?php the_ID(); ?>" data-post-id="<?php the_ID(); ?>"
	<?php
	post_class(
		'post_item post_item_container post_format_' . esc_attr( $elementra_post_format )
				. ' post_layout_classic post_layout_classic_' . esc_attr( $elementra_columns )
				. ' post_layout_' . esc_attr( $elementra_blog_style[0] )
				. ' post_layout_' . esc_attr( $elementra_blog_style[0] ) . '_' . esc_attr( $elementra_columns )
	);
	elementra_add_blog_animation( $elementra_template_args );
	?>
>
	<?php

	// Sticky label
	if ( is_sticky() && ! is_paged() ) {
		?><span class="post_label label_sticky"></span><?php
	}

	// Featured image
	$elementra_hover      = ! empty( $elementra_template_args['hover'] ) && ! elementra_is_inherit( $elementra_template_args['hover'] )
							? $elementra_template_args['hover']
							: elementra_get_theme_option( 'image_hover' );

	$elementra_components = ! empty( $elementra_template_args['meta_parts'] )
							? ( is_array( $elementra_template_args['meta_parts'] )
								? $elementra_template_args['meta_parts']
								: array_map( 'trim', explode( ',', $elementra_template_args['meta_parts'] ) )
								)
							: elementra_array_get_keys_by_value( elementra_get_theme_option( 'meta_parts' ) );

	elementra_show_post_featured( apply_filters( 'elementra_filter_args_featured',
		array(
			'thumb_size' => ! empty( $elementra_template_args['thumb_size'] )
								? $elementra_template_args['thumb_size']
								: elementra_get_thumb_size(
									strpos( elementra_get_theme_option( 'body_style' ), 'full' ) !== false
										? ( $elementra_columns > 2 ? 'big' : 'full' )
										: ( $elementra_columns > 2
											? 'med'
											: ( $elementra_expanded || $elementra_columns == 1 ? 
												( $elementra_expanded && $elementra_columns == 1 ? 'huge' : 'big' ) 
												: 'med' 
												)
											)												
								),
			'hover'      => $elementra_hover,
			'meta_parts' => $elementra_components,
			'no_links'   => ! empty( $elementra_template_args['no_links'] ),
		),
		'content-classic',
		$elementra_template_args
	) );

	// Title and post meta
	$elementra_show_title = get_the_title() != '';
	$elementra_show_meta  = count( $elementra_components ) > 0;

	if ( $elementra_show_title ) {
		?><div class="post_header entry-header"><?php
			// Categories
			if ( apply_filters( 'elementra_filter_show_blog_categories', $elementra_show_meta && in_array( 'categories', $elementra_components ), array( 'categories' ), 'classic' ) ) {
				do_action( 'elementra_action_before_post_category' );
				?><div class="post_category"><?php
					elementra_show_post_meta( apply_filters(
														'elementra_filter_post_meta_args',
														array(
															'components' => 'categories',
															'seo'        => false,
															'echo'       => true,
															),
														'hover_' . $elementra_hover, 1
														)
										);
				?></div><?php
				$elementra_components = elementra_array_delete_by_value( $elementra_components, 'categories' );
				do_action( 'elementra_action_after_post_category' );
			}
			// Post title
			if ( apply_filters( 'elementra_filter_show_blog_title', true, 'classic' ) ) {
				do_action( 'elementra_action_before_post_title' );
				if ( empty( $elementra_template_args['no_links'] ) ) {
					the_title( sprintf( '<h3 class="post_title entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' );
				} else {
					the_title( '<h3 class="post_title entry-title">', '</h3>' );
				}
				do_action( 'elementra_action_after_post_title' );
			}
		?></div><?php
	}
	
	// Post meta
	if ( apply_filters( 'elementra_filter_show_blog_meta', $elementra_show_meta, $elementra_components, 'classic' ) ) {
		if ( count( $elementra_components ) > 0 ) {
			do_action( 'elementra_action_before_post_meta' );
			elementra_show_post_meta(
				apply_filters(
					'elementra_filter_post_meta_args', array(
						'components' => join( ',', $elementra_components ),
						'seo'        => false,
						'echo'       => true,
						'author_avatar' => false,
					), $elementra_blog_style[0], $elementra_columns
				)
			);
			do_action( 'elementra_action_after_post_meta' );
		}
	}

	// Post content
	ob_start();
	if ( apply_filters( 'elementra_filter_show_blog_excerpt', ( ! isset( $elementra_template_args['hide_excerpt'] ) || (int)$elementra_template_args['hide_excerpt'] == 0 ) && (int)elementra_get_theme_option( 'excerpt_length' ) > 0, 'classic' ) ) {
		elementra_show_post_content( $elementra_template_args, '<div class="post_content_inner">', '</div>' );
	}
	$elementra_content = ob_get_contents();
	ob_end_clean();

	elementra_show_layout( $elementra_content, '<div class="post_content entry-content">', '</div>' );

		
	// More button
	if ( apply_filters( 'elementra_filter_show_blog_readmore', ! $elementra_show_title || ! empty( $elementra_template_args['more_button'] ), 'classic' ) ) {
		if ( empty( $elementra_template_args['no_links'] ) ) {
			do_action( 'elementra_action_before_post_readmore' );
			elementra_show_post_more_link( $elementra_template_args, '<p>', '</p>' );
			do_action( 'elementra_action_after_post_readmore' );
		}
	}

	?>

</article></div><?php
// Need opening PHP-tag above, because <div> is a inline-block element (used as column)!
