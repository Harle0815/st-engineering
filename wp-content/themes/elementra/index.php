<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: //codex.wordpress.org/Template_Hierarchy
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

$elementra_template = apply_filters( 'elementra_filter_get_template_part', elementra_blog_archive_get_template() );

if ( ! empty( $elementra_template ) && 'index' != $elementra_template ) {

	get_template_part( $elementra_template );

} else {

	elementra_storage_set( 'blog_archive', true );

	get_header();

	if ( have_posts() ) {

		// Query params
		$elementra_stickies   = is_home()
								|| ( in_array( elementra_get_theme_option( 'post_type' ), array( '', 'post' ) )
									&& (int) elementra_get_theme_option( 'parent_cat' ) == 0
									)
										? get_option( 'sticky_posts' )
										: false;
		$elementra_post_type  = elementra_get_theme_option( 'post_type' );
		$elementra_args       = array(
								'blog_style'     => elementra_get_theme_option( 'blog_style' ),
								'post_type'      => $elementra_post_type,
								'taxonomy'       => elementra_get_post_type_taxonomy( $elementra_post_type ),
								'parent_cat'     => elementra_get_theme_option( 'parent_cat' ),
								'posts_per_page' => elementra_get_theme_option( 'posts_per_page' ),
								'sticky'         => elementra_get_theme_option( 'sticky_style', 'inherit' ) == 'columns'
															&& is_array( $elementra_stickies )
															&& count( $elementra_stickies ) > 0
															&& get_query_var( 'paged' ) < 1
								);

		elementra_blog_archive_start();

		do_action( 'elementra_action_blog_archive_start' );

		if ( is_author() ) {
			do_action( 'elementra_action_before_page_author' );
			get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/author-page' ) );
			do_action( 'elementra_action_after_page_author' );
		}

		if ( elementra_get_theme_option( 'show_filters', 0 ) ) {
			do_action( 'elementra_action_before_page_filters' );
			elementra_show_filters( $elementra_args );
			do_action( 'elementra_action_after_page_filters' );
		} else {
			do_action( 'elementra_action_before_page_posts' );
			elementra_show_posts( array_merge( $elementra_args, array( 'cat' => $elementra_args['parent_cat'] ) ) );
			do_action( 'elementra_action_after_page_posts' );
		}

		do_action( 'elementra_action_blog_archive_end' );

		elementra_blog_archive_end();

	} else {

		if ( is_search() ) {
			get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/content', 'none-search' ), 'none-search' );
		} else {
			get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/content', 'none-archive' ), 'none-archive' );
		}
	}

	get_footer();
}
