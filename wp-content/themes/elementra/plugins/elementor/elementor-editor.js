/* global jQuery, elementor */

jQuery( document ).ready(
	function() {
		"use strict";

		// Add color_style to the titles
		elementra_add_filter('trx_addons_filter_sc_classes', function(classes, settings) {
			if (typeof settings.scheme != 'undefined' && settings.scheme != 'inherit' ) {
				classes += ' scheme_' + settings.scheme;
			}
			if (typeof settings.color_style != 'undefined') {
				classes += ' color_style_' + settings.color_style;
			}
			return classes;
		});

		// Reload preview after any page setting is changed
		setTimeout( function() {
			if ( window.elementor !== undefined ) {
				var timer        = null;
				var section_name = '';

				// Save options after 3 sec
				var save_options = _.throttle( function() {
					var section = section_name;
					// Save options
					elementor.saver.doAutoSave();
					// Refresh Preview area and restore active tab
					if ( section !== '' ) {
						if ( timer !== null ) {
							clearTimeout( timer );
						}
						timer = setTimeout( function() {
							// Reload preview
							elementor.reloadPreview();
							// Restore active tab and section after the AJAX-call 'Save page options' appear
							elementor.once( 'preview:loaded', function() {
								// Restore active tab and section after 3 sec then the page reloaded
								setTimeout( function() {
									elementor
										.getPanelView()
										.setPage( 'page_settings' )
										.activateTab( 'advanced' )
										.activateSection( section )
										._renderChildren(); 
								}, 3000 );
							} );
						}, 1000 );	// Reload page after 1 sec after the AJAX-call 'Save page options' appear
					}

				}, 3000, {leading: false} );

				// On change any page setting - save options after 3 sec and reload preview
				jQuery( '#elementor-panel' )
					.on( 'input change', '[data-setting^="elementra_options_"]', function (e) {
						var section         = jQuery( this ).parents( '.elementor-control' ).prevAll( '.elementor-control-type-section' ),
							section_classes = section.length > 0 ? section.attr( 'class' ).split( ' ' ) : [];
						for (var i = 0; i < section_classes.length; i++) {
							if (section_classes[i].indexOf( 'elementor-control-section_' ) >= 0) {
								section_name = section_classes[i].replace( 'elementor-control-', '' );
								break;
							}
						}
						// Trigger Elementor's save procedure after 3 sec 
						save_options();					// Save options after 3 sec

						// Refresh link 'xxx_post_editor'
						var link = jQuery( this ).parents( '.elementor-control' ).find( 'a.elementra_post_editor' );
						if ( link.length > 0 ) {
							elementra_change_post_edit_link_elementor( link );
						}
					} )
					.on( 'click', '.elementra_post_editor', function(e) {
						elementra_change_post_edit_link_elementor( jQuery(this) );
						if (jQuery(this).hasClass('elementra_hidden' )) {
							e.preventDefault();
							return false;
						}
					});
			}
			
		}, 1000 );

		function elementra_change_post_edit_link_elementor(a) {
			if (a.length > 0) {
				var sel = a.parents('.elementor-control').find('select'),
					val = sel.val();
				if (sel.length === 0 || val === null || val == 'inherit') {
					a.addClass( 'elementra_hidden' );
				} else {
					var id = ('' + val).split( '-' ).pop();
					a.attr( 'href', a.attr( 'href' ).replace( /post=[0-9]{1,5}/, "post=" + id ) );
					if ( id === 0 || id == 'none' || ( '' + val ).indexOf( '--' ) != -1 ) {
						a.addClass( 'elementra_hidden' );
					} else {
						a.removeClass( 'elementra_hidden' );
					}
				}
			}
		}
	}
);
