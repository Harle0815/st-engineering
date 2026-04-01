<?php
/**
 * The template to display default site footer
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.10
 */

$elementra_footer_id = elementra_get_custom_footer_id();
$elementra_footer_meta = elementra_get_custom_layout_meta( $elementra_footer_id );
if ( ! empty( $elementra_footer_meta['margin'] ) ) {
	elementra_add_inline_css( sprintf( '.page_content_wrap{padding-bottom:%s}', esc_attr( elementra_prepare_css_value( $elementra_footer_meta['margin'] ) ) ) );
}
?>
<footer class="footer_wrap footer_custom footer_custom_<?php echo esc_attr( $elementra_footer_id ); ?> footer_custom_<?php echo esc_attr( sanitize_title( get_the_title( $elementra_footer_id ) ) ); ?>">
	<?php
	// Custom footer's layout
	do_action( 'elementra_action_show_layout', $elementra_footer_id );
	?>
</footer>