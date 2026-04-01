/* global jQuery, ELEMENTRA_STORAGE */

jQuery( window ).on( 'load', function() {

	"use strict";

	var $editor_wrapper = jQuery( '#editor,#site-editor,#widgets-editor' ).eq(0);
	var editor_selector = {
		'post':    '.edit-post-visual-editor',
		'site':    '.edit-site-visual-editor',
		'widgets': '.edit-widgets-block-editor'
	};
	if ( $editor_wrapper.length ) {
		var editor_type = $editor_wrapper.attr( 'id' ) == 'widgets-editor'
							? 'widgets'
							: ( $editor_wrapper.attr( 'id' ) == 'site-editor'
								? 'site'
								: 'post'
								);
		var $skeleton_content = false;
		if ( typeof window.MutationObserver !== 'undefined' ) {
			// Create the observer to reinit visual editor after switch from code editor to visual editor
			elementra_create_observer( 'elementra-check-visual-editor-wrapper', $editor_wrapper, function( mutationsList ) {
				elementra_gutenberg_editor_init();
			} );
		} else {
		 	elementra_gutenberg_editor_init();
		}
	}

	// Return Gutenberg editor object
	function elementra_gutenberg_editor_object() {
		var editor = {
			$editor: false,
			$frame: false,
			$styles_wrapper: false,
			$writing_flow: false,
			$root_container: false
		};
		if ( ! $skeleton_content || ! $skeleton_content.length ) {
			$skeleton_content = $editor_wrapper.find( '.interface-interface-skeleton__content' ).eq(0);
		}
		if ( $skeleton_content.length ) {
			var $editor = $skeleton_content.find( '>' + editor_selector[editor_type] ).eq( 0 );
			if ( $editor.length ) {
				editor.$editor = $editor;
				editor.$frame = $editor.find( 'iframe[name="editor-canvas"]' );
				if ( editor.$frame.length ) {
					if ( editor.$frame.get(0).contentDocument ) {
						editor.$styles_wrapper = jQuery( editor.$frame.get(0).contentDocument.body );
						if ( editor.$styles_wrapper.hasClass( 'elementra_inited' ) ) {
							editor.$editor = editor.$frame = editor.$styles_wrapper = false;
						}
					} else {
						editor.$frame = false;
					}
				} else {
					if ( ! editor.$editor.hasClass( 'elementra_inited' ) ) {
						editor.$writing_flow = $editor.find( '.block-editor-writing-flow' );
						if ( ! editor.$writing_flow.length ) {
							editor.$writing_flow = false;
						}
						editor.$styles_wrapper = editor.$writing_flow && editor.$writing_flow.hasClass( 'editor-styles-wrapper' )
													? editor.$writing_flow
													: $editor.find( '.editor-styles-wrapper' );
						if ( ! editor.$styles_wrapper.length ) {
							editor.$styles_wrapper = false;
						}
						editor.$root_container = editor.$writing_flow && editor.$writing_flow.hasClass( 'is-root-container' )
													? editor.$writing_flow
													: $editor.find( '.is-root-container' );
						if ( ! editor.$root_container.length ) {
							editor.$root_container = false;
						}
					} else {
						editor.$editor = false;
					}
				}
			}
		}
		return editor;
	}

	// Init on page load
	function elementra_gutenberg_editor_init() {

		// Get Gutenberg editor object
		var editor = elementra_gutenberg_editor_object();
		if ( ! editor.$editor ) {
			return;
		}

		// Common actions
		//-----------------------------------------------------------
		function add_class_with_color_scheme( $obj ) {
			if ( ! $obj.hasClass( 'scheme_' + ELEMENTRA_STORAGE['color_scheme'] ) ) {
				$obj.addClass( 'scheme_' + ELEMENTRA_STORAGE['color_scheme'] );
			}
		}

		function add_class_with_overridden_options( $obj ) {
			for ( var i in ELEMENTRA_STORAGE['override_classes'] ) {
				$obj.addClass( ELEMENTRA_STORAGE['override_classes'][i].replace( '%s', ELEMENTRA_STORAGE[ i ] ) );
			}
		}

		// Add color scheme to the writing_flow
		if ( editor.$writing_flow ) {
			add_class_with_color_scheme( editor.$writing_flow );
		}
		// Add color scheme to the styles_wrapper
		if ( editor.$styles_wrapper ) {
			editor.$styles_wrapper.each( function() {
				add_class_with_color_scheme( jQuery( this ) );
			} );
		}
		// Add color scheme to the styles_wrapper
		if ( editor.$root_container ) {
			editor.$root_container.each( function() {
				add_class_with_color_scheme( jQuery( this ) );
			} );
		}

		// Post Editor
		//----------------------------------------------------------
		if ( editor_type == 'post' && editor.$styles_wrapper ) {

			// Add a class with a post type to the styles_wrapper
			editor.$styles_wrapper.addClass( elementra_get_class_by_prefix( editor.$editor.attr( 'class' ), 'post-type-' ) );

			// Add the sidebar and the body style classes to the styles wrapper
			add_class_with_overridden_options( editor.$styles_wrapper );

			// Add a sidebar placeholder
			if ( editor.$writing_flow && ELEMENTRA_STORAGE['sidebar_position'] != 'hide' ) {
				if ( elementra_apply_filters( 'elementra_filter_wrap_gutenberg_editor_with_sidebar', false ) ) {
					// New way: Wrap the root container with the sidebar wrapper and add the sidebar placeholder to the sidebar wrapper
					editor.$writing_flow.addClass( 'editor-post-sidebar-wrapper-present' );
					editor.$writing_flow.find( '>.is-root-container' ).wrap( '<div class="editor-post-sidebar-wrapper"></div>' );
					editor.$writing_flow.find( '>.editor-post-sidebar-wrapper' ).append( '<div class="editor-post-sidebar-holder"></div>' );
				} else {
					// Old way: Add a sidebar placeholder to the writing_flow after the root container (in the same level)
					editor.$writing_flow.append( '<div class="editor-post-sidebar-holder"></div>' );
				}
			}

		}

		// Site Editor
		//----------------------------------------------------------
		if ( editor_type == 'site' && editor.$styles_wrapper ) {
			// Add the sidebar and the body style classes to the styles wrapper
			add_class_with_overridden_options( editor.$styles_wrapper );
			// Mark as inited
			editor.$styles_wrapper.addClass( 'elementra_inited' );
		}

		// Widgets Editor
		//----------------------------------------------------------
		if ( editor_type == 'widgets' && editor.$writing_flow ) {
			if ( elementra_apply_filters( 'elementra_filter_add_scheme_to_widgets_editor', true ) ) {
				// Create an observer to add the class 'scheme_xxx' to the each new sidebar
				elementra_remove_observer( 'elementra-check-editor-styles-wrapper' );
				elementra_create_observer( 'elementra-check-editor-styles-wrapper', editor.$writing_flow, function( mutationsList ) {
					editor.$styles_wrapper = editor.$writing_flow.find( '.editor-styles-wrapper:not([class*="scheme_"])' );
					editor.$styles_wrapper.each( function() {
						add_class_with_color_scheme( jQuery( this ) );
					} );
				} );
			}
		}

		// Finish actions
		//----------------------------------------------------------

		// Mark the editor as inited
		editor.$editor.addClass( 'elementra_inited' );
	}
} );
