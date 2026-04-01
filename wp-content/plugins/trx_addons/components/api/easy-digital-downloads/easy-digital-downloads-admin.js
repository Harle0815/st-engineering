/**
 * Themes market: Admin utils
 *
 * @package ThemeREX Addons
 * @since v2.38.2
 */

/* global TRX_ADDONS_STORAGE */

jQuery(document).ready(function() {
	"use strict";
	

	// Disable hiding an empty metabox with the order details
	trx_addons_add_filter( TRX_ADDONS_STORAGE['theme_slug'] + '_filter_hide_empty_meta_boxes', function( hide, $box ) {
		if ( $box.attr('id') === 'edd-order-overview' || $box.hasClass( 'edd-order-overview' ) ) {
			hide = false;
		}
		return hide;
	} );

});