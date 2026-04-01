<?php
/**
 * Plugin support: ThemeRex WooCommerce Extensions (Importer support)
 *
 * @package ThemeREX Addons
 * @since v2.39.0
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}


if ( ! function_exists( 'trx_addons_trx_wcext_importer_required_plugins' ) ) {
	add_filter( 'trx_addons_filter_importer_required_plugins',	'trx_addons_trx_wcext_importer_required_plugins', 10, 2 );
	/**
	 * Check if this plugin is required and installed
	 * 
	 * @hooked trx_addons_filter_importer_required_plugins
	 *
	 * @param string $not_installed  Not installed plugins list
	 * @param string $list           List of required plugins
	 * 
	 * @return string                Not installed plugins list
	 */
	function trx_addons_trx_wcext_importer_required_plugins( $not_installed = '', $list = '' ) {
		if ( strpos( $list, 'trx-wcext' ) !== false && ! trx_addons_exists_trx_wcext() ) {
			$not_installed .= '<br>' . esc_html__( 'ThemeRex WooCommerce Extensions', 'trx_addons' );
		}
		return $not_installed;
	}
}

if ( ! function_exists( 'trx_addons_trx_wcext_importer_set_options' ) ) {
	add_filter( 'trx_addons_filter_importer_options', 'trx_addons_trx_wcext_importer_set_options' );
	/**
	 * Add plugin's specific options to the export options list
	 * 
	 * @hooked trx_addons_filter_importer_options
	 *
	 * @param array $options		Options to export
	 * 
	 * @return array				Modified options
	 */
	function trx_addons_trx_wcext_importer_set_options( $options = array() ) {
		if ( trx_addons_exists_trx_wcext() && in_array( 'trx-wcext', $options['required_plugins'] ) ) {
			$options['additional_options'][] = 'trx_wcext_options';
		}
		return $options;
	}
}

if ( ! function_exists( 'trx_addons_trx_wcext_importer_check_options' ) ) {
	add_filter( 'trx_addons_filter_import_theme_options', 'trx_addons_trx_wcext_importer_check_options', 10, 4 );
	/**
	 * Prevent to import plugin's specific options if plugin is not installed
	 * 
	 * @hooked trx_addons_filter_import_theme_options
	 *
	 * @param boolean $allow		Allow import or not
	 * @param string $k				Option name
	 * @param mixed $v				Option value. Not used in this hook
	 * @param array $options		Options of the current import
	 * 
	 * @return boolean				Allow import or not
	 */
	function trx_addons_trx_wcext_importer_check_options( $allow, $k, $v, $options ) {
		if ( $allow && strpos( $k, 'trx_wcext_' ) === 0 ) {
			$allow = trx_addons_exists_trx_wcext() && in_array( 'trx-wcext', $options['required_plugins'] );
		}
		return $allow;
	}
}

if ( ! function_exists( 'trx_addons_trx_wcext_importer_show_params' ) ) {
	add_action( 'trx_addons_action_importer_params', 'trx_addons_trx_wcext_importer_show_params', 10, 1 );
	/**
	 * Add plugin to the list with plugins for the importer
	 * 
	 * @hooked trx_addons_action_importer_params
	 *
	 * @param object $importer		Importer object
	 */
	function trx_addons_trx_wcext_importer_show_params( $importer ) {
		if ( trx_addons_exists_trx_wcext() && in_array( 'trx-wcext', $importer->options['required_plugins'] ) ) {
			$importer->show_importer_params( array(
				'slug' => 'trx-wcext',
				'title' => esc_html__('Import ThemeRex WooCommerce Extensions', 'trx_addons'),
				'part' => 1,
				'need_posts' => 1
			) );
		}
	}
}
