<?php
/**
 * The template to display the logo or the site name and the slogan in the Header
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

$elementra_args = get_query_var( 'elementra_logo_args' );

// Site logo
$elementra_logo_type   = isset( $elementra_args['type'] ) ? $elementra_args['type'] : '';
$elementra_logo_image  = elementra_get_logo_image( $elementra_logo_type );
$elementra_logo_text   = elementra_is_on( elementra_get_theme_option( 'logo_text' ) ) ? get_bloginfo( 'name' ) : '';
$elementra_logo_slogan = get_bloginfo( 'description', 'display' );
if ( ! empty( $elementra_logo_image['logo'] ) || ! empty( $elementra_logo_text ) ) {
	?><a class="sc_layouts_logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php
		if ( ! empty( $elementra_logo_image['logo'] ) ) {
			if ( empty( $elementra_logo_type ) && function_exists( 'the_custom_logo' ) && is_numeric( $elementra_logo_image['logo'] ) && (int) $elementra_logo_image['logo'] > 0 ) {
				the_custom_logo();
			} else {
				$elementra_attr = elementra_getimagesize( $elementra_logo_image['logo'] );
				echo '<img src="' . esc_url( $elementra_logo_image['logo'] ) . '"'
						. ( ! empty( $elementra_logo_image['logo_retina'] ) ? ' srcset="' . esc_url( $elementra_logo_image['logo_retina'] ) . ' 2x"' : '' )
						. ' alt="' . esc_attr( $elementra_logo_text ) . '"'
						. ( ! empty( $elementra_attr[3] ) ? ' ' . wp_kses_data( $elementra_attr[3] ) : '' )
						. '>';
			}
		} else {
			elementra_show_layout( elementra_prepare_macros( $elementra_logo_text ), '<span class="logo_text">', '</span>' );
			elementra_show_layout( elementra_prepare_macros( $elementra_logo_slogan ), '<span class="logo_slogan">', '</span>' );
		}
		?>
	</a>
	<?php
}
