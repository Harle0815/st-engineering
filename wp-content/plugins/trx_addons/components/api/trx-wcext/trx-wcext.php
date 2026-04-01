<?php
/**
 * Plugin support: ThemeRex Woocommerce Extensions
 *
 * @package ThemeREX Addons
 * @since v2.39.0
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	exit;
}


if ( ! function_exists( 'trx_addons_exists_trx_wcext' ) ) {
	/**
	 * Check if plugin 'ThemeRex Wcext' is installed and activated
	 * 
	 * @return bool  True if plugin is installed and activated
	 */
	function trx_addons_exists_trx_wcext() {
		return class_exists( 'TrxWcext\Plugin' );
	}
}


// Demo data install
//----------------------------------------------------------------------------

// One-click import support
if ( is_admin() ) {
	require_once TRX_ADDONS_PLUGIN_DIR . TRX_ADDONS_PLUGIN_API . 'trx-wcext/trx-wcext-demo-importer.php';
}

// OCDI support
if ( is_admin() && trx_addons_exists_trx_wcext() && function_exists( 'trx_addons_exists_ocdi' ) && trx_addons_exists_ocdi() ) {
	require_once TRX_ADDONS_PLUGIN_DIR . TRX_ADDONS_PLUGIN_API . 'trx-wcext/trx-wcext-demo-ocdi.php';
}
