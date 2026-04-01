/* global jQuery, ELEMENTRA_STORAGE */

( function() {

	"use strict";

	var $window   = jQuery( window ),
		$document = jQuery( document );

	// Init skin-specific actions on first run
	// Attention! Don't forget to add the class "inited" and check it to prevent re-initialize the elements
	$document.on( 'action.ready_elementra', function() {

	} );

	// Init skin-specific hidden elements when their parent container becomes visible
	// Attention! Don't forget to add the class "inited" and check it to prevent re-initialize the elements
	$document.on( 'action.init_hidden_elements', function() {

	} );

	// Skin-specific scroll actions
	$document.on( 'action.scroll_elementra', function() {

	} );

	// Skin-specific resize actions
	$document.on( 'action.resize_elementra', function() {

	} );

	// Skin-specific actions after all elemnts are loaded
	$window.on( 'load', function() {

	} );

	// Add new style 'Extra' to the Google maps
	$document.on( 'action.add_googlemap_styles', function( e ) {
		if ( typeof TRX_ADDONS_STORAGE == 'undefined' ) return;
		TRX_ADDONS_STORAGE['googlemap_styles']['extra'] = [{"featureType": "water", "elementType": "geometry", "stylers": [{"color": "#e9e9e9"}, {"lightness": 17}]}, {"featureType": "landscape", "elementType": "geometry", "stylers": [{"color": "#f5f5f5"}, {"lightness": 20}]}, {"featureType": "road.highway", "elementType": "geometry.fill", "stylers": [{"color": "#ffffff"}, {"lightness": 17}]}, {"featureType": "road.highway", "elementType": "geometry.stroke", "stylers": [{"color": "#ffffff"}, {"lightness": 29}, {"weight": 0.2}]}, {"featureType": "road.arterial", "elementType": "geometry", "stylers": [{"color": "#ffffff"}, {"lightness": 18}]}, {"featureType": "road.local", "elementType": "geometry", "stylers": [{"color": "#ffffff"}, {"lightness": 16}]}, {"featureType": "poi", "elementType": "geometry", "stylers": [{"color": "#f5f5f5"}, {"lightness": 21}]}, {"featureType": "poi.park", "elementType": "geometry", "stylers": [{"color": "#dedede"}, {"lightness": 21}]}, {"elementType": "labels.text.stroke", "stylers": [{"visibility": "on"}, {"color": "#ffffff"}, {"lightness": 16}]}, {"elementType": "labels.text.fill", "stylers": [{"saturation": 36}, {"color": "#333333"}, {"lightness": 40}]}, {"elementType": "labels.icon", "stylers": [{"visibility": "off"}]}, {"featureType": "transit", "elementType": "geometry", "stylers": [{"color": "#f2f2f2"}, {"lightness": 19}]}, {"featureType": "administrative", "elementType": "geometry.fill", "stylers": [{"color": "#fefefe"}, {"lightness": 20}]}, {"featureType": "administrative", "elementType": "geometry.stroke", "stylers": [{"color": "#fefefe"}, {"lightness": 17}, {"weight": 1.2}]}];
	} );

} )();