<?php
/**
 * The template to display custom header from the ThemeREX Addons Layouts
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.06
 */

$elementra_header_css   = '';
$elementra_header_image = get_header_image();
if ( ! empty( $elementra_header_image ) && elementra_trx_addons_featured_image_override( elementra_is_singular() || elementra_storage_isset( 'blog_archive' ) || is_category() ) ) {
	$elementra_header_image = elementra_get_current_mode_image( $elementra_header_image );
}

$elementra_header_id = elementra_get_custom_header_id();
$elementra_header_meta = elementra_get_custom_layout_meta( $elementra_header_id );
if ( ! empty( $elementra_header_meta['margin'] ) ) {
	elementra_add_inline_css( sprintf( '.page_content_wrap{padding-top:%s}', esc_attr( elementra_prepare_css_value( $elementra_header_meta['margin'] ) ) ) );
	elementra_storage_set( 'custom_header_margin', elementra_prepare_css_value( $elementra_header_meta['margin'] ) );
}

?><header class="top_panel top_panel_custom top_panel_custom_<?php echo esc_attr( $elementra_header_id ); ?> top_panel_custom_<?php echo esc_attr( sanitize_title( get_the_title( $elementra_header_id ) ) ); ?>
				<?php
				echo ! empty( $elementra_header_image )
					? ' with_bg_image'
					: ' without_bg_image';
				if ( '' != $elementra_header_image ) {
					echo ' ' . esc_attr( elementra_add_inline_css_class( 'background-image: url(' . esc_url( $elementra_header_image ) . ');' ) );
				}
				if ( elementra_is_single() && has_post_thumbnail() ) {
					echo ' with_featured_image';
				}
				?>
">
	<?php

	// Custom header's layout
	do_action( 'elementra_action_show_layout', $elementra_header_id );

	?>
</header>
