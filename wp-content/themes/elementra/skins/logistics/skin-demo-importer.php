<?php
/**
 * Skin Demo importer
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.76.0
 */


// Theme storage
//-------------------------------------------------------------------------

elementra_storage_set( 'theme_demo_url', '//logistics.elementra.themerex.net' );


//------------------------------------------------------------------------
// One-click import support
//------------------------------------------------------------------------

// Set theme specific importer options
if ( ! function_exists( 'elementra_skin_importer_set_options' ) ) {
	add_filter( 'trx_addons_filter_importer_options', 'elementra_skin_importer_set_options', 9 );
	function elementra_skin_importer_set_options( $options = array() ) {
		if ( is_array( $options ) ) {
			$demo_type = function_exists( 'elementra_skins_get_current_skin_name' ) ? elementra_skins_get_current_skin_name() : 'default';
			if ( 'default' != $demo_type ) {
				$options['demo_type'] = $demo_type;
				$options['files'][ $demo_type ] = $options['files']['default'];	// Copy all settings from 'default' to the new demo type
				unset($options['files']['default']);
			}
			// Override some settings in the new demo type
			$theme_slug = get_template();
			$theme_name = wp_get_theme( $theme_slug )->get( 'Name' );
			$options['files'][ $demo_type ]['title'] = sprintf( esc_html__( '%s Demo', 'elementra' ), $theme_name )
				. ( $demo_type != 'default'
					? '. ' . sprintf( esc_html__( 'Skin %s', 'elementra' ), ucfirst( str_replace( array( '-', '_' ), ' ', $demo_type ) ) )
					: ''
					);
			$options['files'][ $demo_type ]['domain_dev']  = ''; // Developers domain, example: elementra_add_protocol( '//elementra.dev.themerex.net' ); 
			$options['files'][ $demo_type ]['domain_demo'] = elementra_add_protocol( elementra_storage_get( 'theme_demo_url' ) ); // Demo-site domain
		}
		return $options;
	}
}