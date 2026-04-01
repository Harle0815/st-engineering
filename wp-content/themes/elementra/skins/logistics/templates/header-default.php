<?php
/**
 * The template to display default site header
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

$elementra_header_css   = '';
$elementra_header_image = get_header_image();
$elementra_header_video = elementra_get_header_video();
if ( ! empty( $elementra_header_image ) && elementra_trx_addons_featured_image_override( elementra_is_singular() || elementra_storage_isset( 'blog_archive' ) || is_category() ) ) {
	$elementra_header_image = elementra_get_current_mode_image( $elementra_header_image );
}
?><header class="top_panel top_panel_default
	<?php
	echo ! empty( $elementra_header_image ) || ! empty( $elementra_header_video ) ? ' with_bg_image' : ' without_bg_image';
	if ( '' != $elementra_header_video ) {
		echo ' with_bg_video';
	}
	if ( '' != $elementra_header_image ) {
		echo ' ' . esc_attr( elementra_add_inline_css_class( 'background-image: url(' . esc_url( $elementra_header_image ) . ');' ) );
	}
	if ( elementra_is_singular() && has_post_thumbnail() ) {
		echo ' with_featured_image';
	}
	?>
">
	<?php

	// Background video
	if ( ! empty( $elementra_header_video ) ) {
		get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/header-video' ) );
	}

	// Main menu
	get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/header-navi' ) );

	// Page title and breadcrumbs area
	if ( ! elementra_is_single() ) {
		get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/header-title' ) );
	}
	?>
</header>
