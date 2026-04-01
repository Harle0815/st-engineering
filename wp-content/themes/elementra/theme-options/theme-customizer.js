/* global elementra_color_schemes, elementra_dependencies, Color */

/**
 * Add a listener to the Color Scheme control to update other color controls to new values/defaults.
 * Also trigger an update of the Color Scheme CSS when a color is changed.
 */

( function( api ) {

	"use strict";

	var cssTemplate = {},
		updateCSS   = true,
		htmlEncoder = document.createElement( 'div' );

	// Add Templates with color schemes
	for (var i in elementra_color_schemes) {
		cssTemplate['scheme_' + i] = wp.template( 'elementra-color-scheme-' + i );
	}
	// Add Template with theme fonts
	cssTemplate['theme_fonts'] = wp.template( 'elementra-fonts' );
	// Add Template with theme vars
	cssTemplate['theme_vars'] = wp.template( 'elementra-vars' );

	// Allow an alpha channel in the color picker
	if ( elementra_customizer_vars['colorpicker_allow_alpha'] ) {
		var $colorpicker = jQuery( '#tmpl-customize-control-color-content' ),
			html = $colorpicker.html();
		if ( html ) {
			$colorpicker.html( html.replace(
								' class="color-picker-hex',
								' data-alpha-enabled="true" data-alpha-color-type="hex" class="color-picker-hex'
			) );
		}
	}

	// Set initial state of controls
	api.bind( 
		'ready', function() {

			// Add 'reset' button
			jQuery( '#customize-header-actions #save' )
				.before( '<input type="button" class="button customize-action-reset" value="' + elementra_customizer_vars['msg_reset'] + '">' );
			jQuery( '#customize-header-actions .customize-action-reset' )
				.on( 'click', function(e) {
					if (confirm( elementra_customizer_vars['msg_reset_confirm'] )) {
						api( 'reset_options' ).set( 1 );
						jQuery( '#customize-header-actions #save' ).removeAttr( 'disabled' ).trigger( 'click' );
						setTimeout( function() { location.reload( true ); }, 1000 );
					}
				} );

			// Add 'Refresh' button
			jQuery( '#customize-header-actions .spinner' )
				.after( '<button class="button customize-action-refresh icon-spin3" title="' + elementra_customizer_vars['msg_reload_title'] + '">' + elementra_customizer_vars['msg_reload'] + '</button>' );
			jQuery( '#customize-header-actions .customize-action-refresh' )
				.on( 'click', function(e) {
					api.previewer.send( 'refresh-preview' );
					setTimeout( function() { api.previewer.refresh(); }, 500 );
					e.preventDefault();
					return false;
				} );

			// Add suffix after the theme's name
			if (elementra_customizer_vars && elementra_customizer_vars['theme_name_suffix']) {
				jQuery( '#customize-info .site-title' ).append( elementra_customizer_vars['theme_name_suffix'] );
			}

			// Blur the "load fonts" fields - regenerate options lists in the font-family controls
			jQuery( '#customize-theme-controls [id^="customize-control-load_fonts"]' )
				.on( 'change', elementra_customizer_update_load_fonts );

			// Move all descriptions after the fields
			jQuery( '#customize-theme-controls .customize-control:not(.customize-control-info)' )
				.each( function() {
					var $self = jQuery( this ),
						$note = $self.find('.customize-control-notifications-container'),
						$desc = $self.find('.customize-control-description');
					if ( $note.length ) {
						$note.appendTo( $self );
					}
					if ( $desc.length ) {
						$desc.appendTo( $self );
					}
				} );

			// Click on the actions button
			jQuery( '#customize-theme-controls .control-section .customize-control-button input[type="button"]' )
				.on( 'click', elementra_customizer_actions );

			// Check dependencies in the each section on ready
			jQuery( '#customize-theme-controls .control-section' )
				.each( function () {
					elementra_customizer_check_dependencies( jQuery( this ) );
				} );

			// Check dependencies in the each section before open (for dinamically created sections)
			jQuery( '#customize-theme-controls .control-section > .accordion-section-title' )
				.on( 'click', function() {
					var id = jQuery( this ).parent().attr( 'aria-owns' );
					if (id !== '') {
						var section = jQuery( '#' + id );
						if (section.length > 0) {
							elementra_customizer_check_dependencies( section );
						}
					}
				} );

			// Decorate subsections in the panel 'Typography'
			if ( elementra_customizer_vars['decorate_fonts'] ) {

				jQuery('body').addClass( 'elementra_decorate_fonts_section' );

				// Prepare section for open on title is clicked
				var elementra_customizer_title_clicked = function( title ) {
					var id = title.parent().attr( 'aria-owns' );
					elementra_customizer_close_opened_sections( title.parents( '#sub-accordion-panel-fonts' ), id );
					var section = jQuery( '#' + id ),
						section_height = section.outerHeight(),
						title_height = title.outerHeight() + 6,
						pos = title.position().top + title_height,
						sidebar = jQuery('.wp-full-overlay-sidebar-content'),
						sidebar_height = sidebar.height();
					section.css( 'margin-top', pos + 'px' );
					setTimeout( function() {
						jQuery( '#customize-theme-controls [id^="sub-accordion-section-"][id$="_font_section"]:not(.open)' ).css('margin-top', 0);
						sidebar.scrollLeft(0);
						if ( pos + section_height > sidebar.scrollTop() + sidebar_height ) {
							sidebar.scrollTop( section_height < sidebar_height ? pos + section_height - sidebar_height : pos - title_height );
						}
						jQuery( '#customize-theme-controls [id^="sub-accordion-section-"][id$="_font_section"].open [data-customize-setting-link]' ).eq(0).focus();
					}, 330 );
				};

				// Set y position for opened section in the font settings
				jQuery( '#customize-theme-controls [id^="accordion-section-"][id$="_font_section"] .accordion-section-title:not(.inited)' )
					.each ( function() {
						var $self = jQuery(this).addClass('inited');
						$self
							.on( 'keydown', function(e) {
								if ( e.which == 13 ) {
									elementra_customizer_title_clicked( $self );
								}
							} )
							.on( 'click', function() {
								elementra_customizer_title_clicked( $self );
							} );
					} );

				// Close all opened sections in the specified panel
				var elementra_customizer_close_opened_sections = function( panel, current_id ) {
					if ( panel.length ) {
						panel.find( '.control-section[aria-owns]' ).each( function() {
							var $self = jQuery( this ),
								id = $self.attr( 'aria-owns' );
							if ( id != current_id ) {
								jQuery( '#' + id + '.open .customize-section-back' ).trigger( 'click' );
							}
						} );
					}
				};

				// Close opened section in the font settings on click button '.customize-panel-back'
				jQuery( '#customize-theme-controls #sub-accordion-panel-fonts .customize-panel-back' )
					.on( 'keydown', function(e) {
						if ( e.which == 13 ) {
							elementra_customizer_close_opened_sections( jQuery(this).parents('#sub-accordion-panel-fonts') );
						}
					} )
					.on( 'click', function() {
						elementra_customizer_close_opened_sections( jQuery(this).parents('#sub-accordion-panel-fonts') );
					} );
			}
		}
	);

	// On change any control - check for dependencies
	api.bind( 'change', function(obj) {
		// Correct value for the added color schemes
		var id = obj.id, val = obj();
		if (val === undefined && id.substr( -7 ) == '_scheme') {
			val = jQuery( '[data-customize-setting-link="' + id + '"]:checked' ).val();
			if (val !== undefined) {
				obj.set( val );
				return;
			}
		}
		elementra_customizer_check_dependencies( jQuery( '#customize-theme-controls #customize-control-' + obj.id ).parents( '.control-section' ) );
		elementra_customizer_refresh_preview( obj );
	} );

	// On add/delete scheme
	api.bind( 'refresh_schemes', function() {
		var i = '', tpl_idx = '', tpl_content = '';
		// Remove templates
		for (i in cssTemplate) {
			if (('' + i).indexOf( 'scheme_' ) !== 0) {
				continue;
			}
			i = i.replace( 'scheme_', '' );
			if (typeof elementra_color_schemes[i] === 'undefined') {
				delete cssTemplate['scheme_' + i];
				jQuery( '#tmpl-elementra-color-scheme-' + i ).remove();
			} else if (tpl_idx == '') {
				tpl_idx     = i;
				tpl_content = jQuery( '#tmpl-elementra-color-scheme-' + i ).html().trim();
			}
		}
		// Add new templates
		var regex = new RegExp( "\.scheme_" + tpl_idx, "g" );
		for (i in elementra_color_schemes) {
			if (typeof cssTemplate['scheme_' + i] === 'undefined') {
				jQuery( '#tmpl-elementra-color-scheme-' + tpl_idx )
				.clone()
				.attr( 'id', 'tmpl-elementra-color-scheme-' + i )
				.html( tpl_content.replace( regex, '.scheme_' + i ) )
				.insertAfter( jQuery( '#tmpl-elementra-color-scheme-' + tpl_idx ) );
				cssTemplate['scheme_' + i] = wp.template( 'elementra-color-scheme-' + i );
			}
		}
	} );

	// Disable/Enable update CSS
	api.bind( 'lock_css', function(lock) {
		updateCSS = ! lock;
	} );

	// Open specified url on expand section or panel
	for (var action in elementra_customizer_vars['actions']['expand']) {
		if (action == 'length') {
			continue;
		}
		if (elementra_customizer_vars['actions']['expand'][action]['type'] == 'panel') {
			api.panel(
				action, function( panel ) {
					panel.expanded.bind(
						function( isExpanded ) {
							if ( isExpanded ) {
								var data = elementra_customizer_vars['actions']['expand'][panel.id];
								if (typeof data['url'] !== 'undefined' && data['url'] !== '') {
									api.previewer.previewUrl.set( data['url'] );
								}
								if (typeof data['callback'] !== 'undefined' && data['callback'] !== '' && typeof window[data['callback']] === 'function') {
									window[data['callback']]();
								}
							}
						}
					);
				}
			);
		} else {
			api.section(
				action, function( section ) {
					section.expanded.bind(
						function( isExpanded ) {
							if ( isExpanded ) {
								var data = elementra_customizer_vars['actions']['expand'][section.id];
								if (typeof data['url'] !== 'undefined' && data['url'] !== '') {
									api.previewer.previewUrl.set( data['url'] );
								}
								if (typeof data['callback'] !== 'undefined' && data['callback'] !== '' && typeof window[data['callback']] === 'function') {
									window[data['callback']]();
								}
							}
						}
					);
				}
			);
		}
	}

	// Return value of the control
	function elementra_customizer_get_field_value(fld) {
		var ctrl = fld.parents( '.customize-control' );
		var val  = fld.attr( 'type' ) == 'checkbox' || fld.attr( 'type' ) == 'radio'
					? (ctrl.find( '[data-customize-setting-link]:checked' ).length > 0
						? (ctrl.find( '[data-customize-setting-link]:checked' ).val() !== ''
							&& '' + ctrl.find( '[data-customize-setting-link]:checked' ).val() != '0'
								? ctrl.find( '[data-customize-setting-link]:checked' ).val()
								: 1
							)
						: 0
						)
					: fld.val();
		if (val === undefined || val === null) {
			val = '';
		}
		return val;
	}

	// Check for dependencies
	function elementra_customizer_check_dependencies(cont) {
		if ( typeof elementra_dependencies == 'undefined' || ELEMENTRA_STORAGE['check_dependencies_now'] ) return;
		ELEMENTRA_STORAGE['check_dependencies_now'] = true;
		cont.find( '.customize-control' ).each(
			function() {
				var ctrl = jQuery( this ), id = ctrl.attr( 'id' );
				if (id == undefined) {
					return;
				}
				id         = id.replace( 'customize-control-', '' );
				var fld    = null, val = '', i;
				var depend = false;
				for (fld in elementra_dependencies) {
					if (fld == id) {
						depend = elementra_dependencies[id];
						break;
					}
				}
				if (depend) {
					var dep_cnt    = 0, dep_all = 0;
					var dep_cmp    = typeof depend.compare != 'undefined' ? depend.compare.toLowerCase() : 'and';
					var dep_strict = typeof depend.strict != 'undefined';
					for (i in depend) {
						if (i == 'compare' || i == 'strict') {
							continue;
						}
						dep_all++;
						fld = cont.find( '[data-customize-setting-link="' + i + '"]' );
						if (fld.length > 0) {
							val = elementra_customizer_get_field_value( fld );
							if ( val == 'inherit' ) {
								dep_cnt = 0;
								dep_all = 1;
								var tag;
								if ( ctrl.hasClass( 'customize-control-select' ) ) {
									tag = ctrl.find('select');
									if ( tag.find('option[value="inherit"]').length && tag.val() != 'inherit' ) {
										tag.val('inherit').trigger('change');
									}
								} else if ( ctrl.hasClass( 'customize-control-radio' ) ) {
									tag = ctrl.find('input[type="radio"][value="inherit"]');
									if ( tag.length && ! tag.get(0).checked ) {
										ctrl.find('input[type="radio"]:checked').get(0).checked = false;
										tag.get(0).checked = true;
										tag.trigger('change');
									}
								}
								break;
							} else {
								if ( typeof depend[i] != 'object' && typeof depend[i] != 'array' ) {
									depend[i] = { '0': depend[i] };
								}
								for (var j in depend[i]) {
									if (
										(depend[i][j] == 'not_empty' && val !== '')      // Main field value is not empty - show current field
										|| (depend[i][j] == 'is_empty' && val === '')    // Main field value is empty - show current field
										|| (val !== '' && ( ! isNaN( depend[i][j] )      // Main field value equal to specified value - show current field
															? val == depend[i][j]
															: (dep_strict
																	? val == depend[i][j]
																	: ('' + val).indexOf( depend[i][j] ) === 0
																)
														)
											)
										|| (val !== '' && ('' + depend[i][j]).charAt( 0 ) == '^' && ('' + val).indexOf( depend[i][j].substr( 1 ) ) == -1)	// Main field value not equal to specified value - show current field
									) {
										dep_cnt++;
										break;
									}
								}
							}
						} else {
							dep_all--;
						}
						if (dep_cnt > 0 && dep_cmp == 'or') {
							break;
						}
					}
					if (((dep_cnt > 0 || dep_all === 0) && dep_cmp == 'or') || (dep_cnt == dep_all && dep_cmp == 'and')) {
						ctrl.slideDown().removeClass( 'elementra_options_no_use' );
					} else {
						ctrl.slideUp().addClass( 'elementra_options_no_use' );
					}
				}

				// Individual dependencies
				//------------------------------------

				// Disable color schemes less then main scheme!
				// Not need for version with sorted schemes
				if (false && id == 'color_scheme') {
					fld = ctrl.find( '[data-customize-setting-link="' + id + '"]' );
					if (fld.length > 0) {
						val     = elementra_customizer_get_field_value( fld );
						var num = 0;
						for (i in elementra_color_schemes) {
							num++;
							if (i == val) {
								break;
							}
						}
						cont.find( '.customize-control' ).each(
							function() {
								var ctrl2 = jQuery( this ), id2 = ctrl2.attr( 'id' );
								if (id2 == undefined) {
									return;
								}
								id2 = id2.replace( 'customize-control-', '' );
								if (id2 == id || id2.substr( -7 ) != '_scheme') {
									return;
								}
								var fld2 = ctrl2.find( '[data-customize-setting-link="' + id2 + '"]' );
								if (fld2.attr( 'type' ) != 'radio') {
									fld2 = fld2.find( 'option' );
								}
								fld2.each(
									function(idx2) {
										var dom_obj      = jQuery( this ).get( 0 );
										dom_obj.disabled = idx2 !== 0 && idx2 < num;
										if (dom_obj.disabled) {
											if (jQuery( this ).val() == api( id2 )()) {
												api( id2 ).set( 'inherit' );
											}
										}
									}
								);
							}
						);
					}
				}
			}
		);
		ELEMENTRA_STORAGE['check_dependencies_now'] = false;
	}

	// Refresh preview area on change any control
	function elementra_customizer_refresh_preview(obj) {
		var id = obj.id, val = obj(), opt = '', rule = '';
		if (obj.transport != 'postMessage' && id.indexOf( 'load_fonts-' ) == -1) {
			return;
		}
		var processed = false, forceUpdateCSS = false;
		// Update the CSS whenever a color setting is changed.
		if (updateCSS) {
			// Any color in the scheme_storage is changed
			if (id == 'scheme_storage') {
				processed = true;

				// Any color in the scheme_storage is changed
			} else if (Object.values( elementra_sorted_schemes ).indexOf( id ) >= 0) {
				forceUpdateCSS = true;

				// If section Front page section 'About' need page content - refresh preview area
			} else if (id == 'front_page_about_content' && val.indexOf( '%%CONTENT%%' ) >= 0) {
				api.previewer.send( 'refresh-preview' );
				setTimeout( function() { api.previewer.refresh(); }, 500 );
				processed = true;

				// If control from the theme vars
			} else if (jQuery( '[data-customize-setting-link="' + id + '"]' ).length > 0) {
				var var_name = jQuery( '[data-customize-setting-link="' + id + '"]' ).data( 'var_name' );
				if (var_name !== undefined) {
					// Store new value to the vars table
					elementra_customizer_update_theme_vars( jQuery( '[data-customize-setting-link="' + id + '"]' ).data( 'var_name' ), val );
					processed = true;
				}

				// Any theme fonts parameter is changed
			} else {
				for (opt in elementra_theme_fonts) {
					for (rule in elementra_theme_fonts[opt]) {
						if (opt + '_' + rule.replace( ':', '-' ) == id) {
							// Store new value to the fonts table
							elementra_customizer_update_theme_fonts( opt, rule, val );
							processed = true;
							break;
						}
					}
					if (processed) {
						break;
					}
				}
			}
			// Refresh CSS
			if (processed || forceUpdateCSS) {
				elementra_customizer_update_css();
			}
		}
		// If not catch change above - send message to previewer
		if ( ! processed) {
			api.previewer.send( 'refresh-other-controls', {id: id, value: val} );
		}
	}

	// Actions buttons
	function elementra_customizer_actions(e) {
		var action = jQuery( this ).data( 'action' );
		if (action == 'refresh') {
			api.previewer.send( 'refresh-preview' );
			setTimeout( function() { api.previewer.refresh(); }, 500 );
		}
	}

	// Store new value in the theme vars
	function elementra_customizer_update_theme_vars(opt, value) {
		elementra_theme_vars[opt] = parseFloat( value );
	}

	// Store new value in the theme fonts
	function elementra_customizer_update_theme_fonts(opt, rule, value) {
		elementra_theme_fonts[opt][rule] = value;
	}

	// Change theme fonts options if load fonts is changed
	function elementra_customizer_update_load_fonts() {
		var opt_list = [], i, tag, sel, opt, name = '', family = '', val = '', new_val = '', sel_idx = 0;
		updateCSS    = false;
		for (i = 1; i <= elementra_customizer_vars['max_load_fonts']; i++) {
			name = api( 'load_fonts-' + i + '-name' )();
			if (name === '') {
				continue;
			}
			family = api( 'load_fonts-' + i + '-family' )();
			opt_list.push( [name, family] );
		}
		for (tag in elementra_theme_fonts) {
			sel = api.control( tag + '_font-family' ).container.find( 'select' );
			if (sel.length == 1) {
				opt     = sel.find( 'option' );
				sel_idx = sel.find( ':selected' ).index();
				// Remove empty options
				if (opt_list.length < opt.length - 1) {
					for (i = opt.length - 1; i > opt_list.length; i--) {
						opt.eq( i ).remove();
					}
				}
				// Add new options
				if (opt_list.length >= opt.length) {
					for (i = opt.length - 1; i <= opt_list.length - 1; i++) {
						val = elementra_get_load_fonts_family_string( opt_list[i][0], opt_list[i][1] );
						sel.append( '<option value="' + val + '">' + opt_list[i][0] + '</option>' );
					}
				}
				// Set new value
				new_val = '';
				for (i = 0; i < opt_list.length; i++) {
					val = elementra_get_load_fonts_family_string( opt_list[i][0], opt_list[i][1] );
					if (sel_idx - 1 == i) {
						new_val = val;
					}
					opt.eq( i + 1 ).val( val ).text( opt_list[i][0] );
				}
				api( tag + '_font-family' ).set( sel_idx > 0 && sel_idx <= opt_list.length && new_val ? new_val : 'inherit' );
			}
		}
		updateCSS = true;
	}

	// Generate the CSS for the current Color Scheme and send it to the preview window
	function elementra_customizer_update_css() {

		if ( ! updateCSS) {
			return;
		}
		var css = '';

		// Add theme specific vars
		var vars = elementra_customizer_add_theme_vars( elementra_theme_vars );

		// Make styles and add its to the css
		css += elementra_customizer_prepare_html_value( cssTemplate['theme_vars']( vars ) );

		// Add theme specific fonts rules
		var fonts = elementra_customizer_add_theme_fonts( elementra_theme_fonts );

		// Make styles and add its to the css
		css += elementra_customizer_prepare_html_value( cssTemplate['theme_fonts']( fonts ) );

		// Add colors
		var sorted = {}, scheme = '';
		for (var param in elementra_sorted_schemes) {
			if (param == 'length') {
				continue;
			}
			scheme = api( elementra_sorted_schemes[param] )();
			if (scheme && scheme !== 'inherit') {
				sorted[scheme] = 1;
			}
		}
		for (scheme in elementra_color_schemes) {
			sorted[scheme] = 1;
		}
		for (scheme in sorted) {
			var i, colors = [];
			// Copy all colors to the temp var
			for (i in elementra_color_schemes[scheme].colors) {
				colors[i] = elementra_color_schemes[scheme].colors[i];
			}
			// Add theme specific colors and tints
			colors = elementra_customizer_add_theme_colors( colors );

			// Make styles and add its to the css

			// Attention! This way generate error 'Maximum call stack size exceeded' in Chrome!
			// Commented: css += cssTemplate['scheme_'+scheme]( colors );

			// This way work correctly in any browser
			var tmpl = jQuery( '#tmpl-elementra-color-scheme-' + scheme ).html().trim();
			for (i in colors) {
				var regex = new RegExp( "{{ data\." + i + " }}", "g" );
				tmpl      = tmpl.replace( regex, colors[i] );
			}
			css += tmpl;
		}
		api.previewer.send( 'refresh-color-scheme-css', css );
	}

	// Additional (calculated) theme-specific colors
	function elementra_customizer_add_theme_colors(colors) {
		if (elementra_additional_colors) {
			var clr = '', v = '';
			for (var k in elementra_additional_colors) {
				v   = elementra_additional_colors[k];
				clr = colors[v['color']];
				if ( clr.slice( 0, 3 ) == 'rgb' ) {
					clr = elementra_rgba2hex( clr );
				}
				if (typeof v['hue'] != 'undefined' || typeof v['saturation'] != 'undefined' || typeof v['brightness'] != 'undefined') {
					clr = elementra_hsb2hex(
						elementra_hex2hsb(
							clr,
							typeof v['hue'] != 'undefined' ? v['hue'] : 0,
							typeof v['saturation'] != 'undefined' ? v['saturation'] : 0,
							typeof v['brightness'] != 'undefined' ? v['brightness'] : 0
						)
					);
				}
				if (typeof v['alpha'] != 'undefined') {
					clr = Color( clr ).toCSS( 'rgba', v['alpha'] );
				}
				colors[k] = clr;
			}
		}
		return colors;
	}

	// Add custom theme fonts rules
	function elementra_customizer_add_theme_fonts(fonts) {
		var rez = [];
		var css_name, css_value, parts;
		for (var tag in fonts) {
			rez[tag] = fonts[tag];
			for (var css_prop in fonts[tag]) {
				if ( ['title', 'description'].indexOf( css_prop ) >= 0 ) {
					continue;
				}
				css_value = fonts[tag][css_prop];
				if ( css_prop.indexOf( ':' ) > 0 ) {
					css_name = css_prop.replace( ':', '-' );
					parts = css_prop.split( ':' );
					css_prop = parts[0];
				} else {
					css_name = css_prop;
				}
				rez[tag + '_' + css_name] = css_value !== '' && css_value != 'inherit'
												? css_prop + ':' + ( ['font-size', 'letter-spacing', 'margin-top', 'margin-bottom', 'border-width', 'border-radius'].indexOf(css_prop) >= 0
																		? elementra_customizer_prepare_css_value( css_value )
																		: css_value
																		)
																	+ ';'
												: '';
			}
		}
		return rez;
	}

	// Add custom theme vars rules
	function elementra_customizer_add_theme_vars(vars) {
		var rez = [];
		if ( typeof vars['rad'] != 'undefined' ) {
			if (vars['rad'] == '') {
				vars['rad'] = 0;
			}
			rez['rad']      = elementra_customizer_prepare_css_value( vars['rad'] );
			rez['rad_koef'] = vars['rad'] > 0 ? 1 : 0;
		}
		if ( typeof vars['page_width'] != 'undefined' ) {
			vars['page_width'] = parseInt( vars['page_width'], 10 );
			if ( isNaN( vars['page_width'] ) || vars['page_width'] === 0) {
				vars['page_width'] = elementra_customizer_vars['page_width_default'];
			}
			vars['sidebar_width']       = parseInt( vars['sidebar_width'], 10 );
			vars['sidebar_gap']         = parseInt( vars['sidebar_gap'], 10 );

			rez['page_width']          = elementra_customizer_prepare_css_value( vars['page_width'] );
			rez['page_boxed_extra']     = elementra_customizer_prepare_css_value( vars['page_boxed_extra'] );
			rez['page_fullwide_extra']  = elementra_customizer_prepare_css_value( vars['page_fullwide_extra'] );
			rez['page_fullwide_max']    = elementra_customizer_prepare_css_value( vars['page_fullwide_max'] );
			rez['grid_gap']             = elementra_customizer_prepare_css_value( vars['grid_gap'] );
			rez['sidebar_prc']          = vars['sidebar_width'] / vars['page_width'];
			rez['sidebar_gap_prc']      = vars['sidebar_gap'] / vars['page_width'];
			rez['sidebar_width']        = elementra_customizer_prepare_css_value( vars['sidebar_width'] );
			rez['sidebar_gap']          = elementra_customizer_prepare_css_value( vars['sidebar_gap'] );
			rez['sidebar_gap_width']    = elementra_customizer_prepare_css_value( vars['sidebar_gap'] );
			rez['sidebar_proportional'] = typeof vars['sidebar_proportional'] == 'undefined' || vars['sidebar_proportional'] == 1 ? 1 : 0;
		}
		return rez;
	}

	// Add ed to css value
	function elementra_customizer_prepare_css_value(val) {
		if (val !== '' && val != 'inherit') {
			var ed = ('' + val).slice( -1 );
			if ('0' <= ed && ed <= '9') {
				val += 'px';
			}
		}
		return val;
	}

	// Convert HTML entities in the css value
	function elementra_customizer_prepare_html_value(val) {
		return val.replace( /\&quot\;/g, '"' );
	}

} )( wp.customize );
