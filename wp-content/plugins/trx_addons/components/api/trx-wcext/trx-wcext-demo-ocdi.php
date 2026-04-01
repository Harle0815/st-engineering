<?php
/**
 * Plugin support: ThemeRex WooCommerce Extensions
 *
 * @package ThemeREX Addons
 * @since v2.39.0
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}

if ( ! function_exists( 'trx_addons_ocdi_trx_wcext_set_options' ) ) {
	add_filter( 'trx_addons_filter_ocdi_options', 'trx_addons_ocdi_trx_wcext_set_options' );
	/**
	 * Set plugin's specific importer options for OCDI
	 * 
	 * @hooked trx_addons_filter_ocdi_options
	 *
	 * @param array $ocdi_options	Options to export
	 * 
	 * @return array				Modified options
	 */
	function trx_addons_ocdi_trx_wcext_set_options( $ocdi_options ) {
		$ocdi_options['import_trx_wcext_file_url'] = 'trx-wcext.txt';
		return $ocdi_options;		
	}
}

if ( ! function_exists( 'trx_addons_ocdi_trx_wcext_export' ) ) {
	add_filter( 'trx_addons_filter_ocdi_export_files', 'trx_addons_ocdi_trx_wcext_export' );
	/**
	 * Export trx_wcext data and options
	 * 
	 * @hooked trx_addons_filter_ocdi_export_files
	 *
	 * @param array $output		Export data
	 * 
	 * @return array			Modified output
	 */
	function trx_addons_ocdi_trx_wcext_export( $output ) {
		$list = array();
		if ( trx_addons_exists_trx_wcext() && in_array( 'trx-wcext', trx_addons_ocdi_options( 'required_plugins' ) ) ) {
			// Get plugin data from database
			$options = array(
				'trx_wcext_options'
			);
			$list = trx_addons_ocdi_export_options( $options, $list );
			
			// Save as file
			$file_path = TRX_ADDONS_PLUGIN_OCDI . "export/trx-wcext.txt";
			trx_addons_fpc( trx_addons_get_file_dir( $file_path ), serialize( $list ) );
			
			// Return file path
			$output .= '<h4><a href="' . trx_addons_get_file_url( $file_path ) . '" download>' . esc_html__( 'ThemeRex WooCommerce Extensions', 'trx_addons' ) . '</a></h4>';
		}
		return $output;
	}
}

if ( ! function_exists( 'trx_addons_ocdi_trx_wcext_import_field' ) ) {
	add_filter( 'trx_addons_filter_ocdi_import_fields', 'trx_addons_ocdi_trx_wcext_import_field' );
	/**
	 * Add plugin to the import list for the OCDI
	 * 
	 * @hooked trx_addons_filter_ocdi_import_fields
	 *
	 * @param string $output		Import fields HTML
	 * 
	 * @return string				Modified output
	 */
	function trx_addons_ocdi_trx_wcext_import_field( $output ) {
		if ( trx_addons_exists_trx_wcext() && in_array( 'trx-wcext', trx_addons_ocdi_options( 'required_plugins' ) ) ) {
			$output .= '<label><input type="checkbox" name="trx_wcext" value="trx_wcext">' . esc_html__( 'ThemeRex WooCommerce Extensions', 'trx_addons' ) . '</label><br/>';
		}
		return $output;
	}
}

if ( ! function_exists( 'trx_addons_ocdi_trx_wcext_import' ) ) {
	add_action( 'trx_addons_action_ocdi_import_plugins', 'trx_addons_ocdi_trx_wcext_import', 10, 1 );
	/**
	 * Import trx_wcext data and options
	 * 
	 * @hooked trx_addons_action_ocdi_import_plugins
	 *
	 * @param array $import_plugins		List of the plugins to import
	 */
	function trx_addons_ocdi_trx_wcext_import( $import_plugins ) {
		if ( trx_addons_exists_trx_wcext() && in_array( 'trx-wcext', $import_plugins ) ) {
			trx_addons_ocdi_import_dump( 'trx-wcext' );
			echo esc_html__( 'ThemeRex WooCommerce Extensions import complete.', 'trx_addons' ) . "\r\n";
		}
	}
}
