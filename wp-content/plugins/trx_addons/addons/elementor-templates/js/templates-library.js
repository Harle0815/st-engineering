/* global elementor, elementorCommon, TRX_ADDONS_STORAGE */
/* eslint-disable */

window.trx_addons_elementor_templates_library = window.trx_addons_elementor_templates_library || {};

typeof jQuery != 'undefined' &&	! ( function() {
	jQuery( function() {
		var $library = false;

		function templatesLibrary() {
			const insertIndex = jQuery(this).parents(".elementor-section-wrap").length ? jQuery(this).parents(".elementor-add-section").index() : -1;
			window.trx_addons_elementor_templates_library.insertIndex = insertIndex;

			elementorCommon
			&& ( window.trx_addons_elementor_templates_library.modal
				|| ( ( window.trx_addons_elementor_templates_library.modal = elementorCommon.dialogsManager.createWidget( "lightbox", {
						id: "trx_addons_elementor_templates_library_modal",
						headerMessage: '<span class="trx_addons_elementor_templates_library_logo"></span>'
										+ '<span class="trx_addons_elementor_templates_library_title">'
											+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_title']
										+ '</span>',
						message: "",
						hide: {
							auto: false,
							onClick: false,
							onOutsideClick: false,
							onOutsideContextMenu: false,
							onBackgroundClick: true
						},
						position: {
							my: "center",
							at: "center"
						},
						onShow: function() {
							var content = window.trx_addons_elementor_templates_library.modal.getElements( 'content' );
							if ( content.find( '#trx_addons_elementor_templates_library' ).length > 0 ) {
								return;
							}
							var navi_style = TRX_ADDONS_STORAGE['elementor_templates_library_navigation_style'];
							var html = '<div id="trx_addons_elementor_templates_library" class="wrap trx_addons_elementor_templates_library_navigation_style_' + navi_style + '">'
											+ '<a href="#" class="trx_addons_elementor_templates_library_close trx_addons_button_close" title="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_close'] + '">'
												+ '<span class="trx_addons_button_close_icon"></span>'
											+ '</a>'
											+ '<a href="#" class="trx_addons_elementor_templates_library_refresh" title="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_refresh_title'] + '">'
												+ '<span class="trx_addons_elementor_templates_library_refresh_icon"></span>'
												+ '<span class="trx_addons_elementor_templates_library_refresh_text">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_refresh'] + '</span>'
											+ '</a>';
							// Tabs
							html += '<div class="trx_addons_elementor_templates_library_tabs">';
							var i = 0;
							for (var tab in TRX_ADDONS_STORAGE['elementor_templates_library_tabs'] ) {
								html += '<a href="#" class="trx_addons_elementor_templates_library_tab' + ( i++ === 0 ? ' trx_addons_elementor_templates_library_tab_active' : '' ) + '" data-tab="' + tab + '">' + TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['title'] + '</a>';
							}
							html += '</div>';
							html += '<div class="trx_addons_elementor_templates_library_content">';
							i = 0;
							for ( tab in TRX_ADDONS_STORAGE['elementor_templates_library_tabs'] ) {
								html += '<div class="trx_addons_elementor_templates_library_tab_content' + ( i++ === 0 ? ' trx_addons_elementor_templates_library_tab_content_active' : '' ) + '" data-tab="' + tab + '">';
								// Toolbar or Sidebar navigation
								html += '<div class="trx_addons_elementor_templates_library_' + navi_style + '">'
								// Search
								html += '<div class="trx_addons_elementor_templates_library_search_wrap">'
											+ '<div class="trx_addons_elementor_templates_library_search">'
												+ '<span tabindex="0" class="trx_addons_elementor_templates_library_search_icon eicon-search"></span>'
												+ '<input type="text" placeholder="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_search'] + '">'
											+ '</div>'
											+ ( TRX_ADDONS_STORAGE['elementor_templates_library_ai_allowed'] > 0 && tab == 'block'
												? '<span tabindex="0" class="trx_addons_elementor_templates_library_image_to_layout_open" title="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_description'] + '">'
														+ '<span class="trx_addons_elementor_templates_library_image_to_layout_open_inner">'
															+ '<span class="trx_addons_elementor_templates_library_image_to_layout_open_icon trx_addons_icon-image-tick"></span>'
															+ '<span class="trx_addons_elementor_templates_library_image_to_layout_open_text">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout'] + '</span>'
														+ '</span>'
													+ '</span>'
												: ''
											)
										+ '</div>';
								// Categories
								var cats = getCategoriesList( tab );
								if ( cats ) {
									html += '<div class="trx_addons_elementor_templates_library_categories">' + cats + '</div>';
								}
								// Close Toolbar or Sidebar navigation
								html += '</div>';
								// Items list
								html += '<div class="trx_addons_elementor_templates_library_items"></div>';
								// Filter by Image with Layout
								if ( TRX_ADDONS_STORAGE['elementor_templates_library_ai_allowed'] > 0 && tab == 'block' ) {
									html += '<div id="itlPanel" class="trx_addons_elementor_templates_library_image_to_layout">'
												+ '<div class="trx_addons_elementor_templates_library_image_to_layout_upload_container itl_step1">'
													// Upload image
													+ '<h5 class="trx_addons_elementor_templates_library_image_to_layout_title">'
														+ '<span class="eicon-site-identity"></span>'
														+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_step1'].replace( "\n", "<br>")
													+ '</h5>'
													+ '<div tabindex="0" class="trx_addons_elementor_templates_library_image_to_layout_upload_area" id="itlUploadArea">'
														+ '<div class="trx_addons_elementor_templates_library_image_to_layout_upload_controls">'
															+ '<svg class="trx_addons_elementor_templates_library_image_to_layout_upload_icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
																+ '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />'
															+ '</svg>'
															+ '<div class="trx_addons_elementor_templates_library_image_to_layout_upload_text">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_drag'] + '</div>'
															+ '<div class="trx_addons_elementor_templates_library_image_to_layout_upload_hint">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_paste'] + '</div>'
															+ '<button class="trx_addons_elementor_templates_library_image_to_layout_select_btn" id="itlSelectBtn">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_select'] + '</button>'
															+ '<input type="file" id="itlFileInput" accept="image/*" />'
														+ '</div>'
														+ '<div class="trx_addons_elementor_templates_library_image_to_layout_preview_area" id="itlPreviewArea">'
															+ '<div class="trx_addons_elementor_templates_library_image_to_layout_image_preview">'
																+ '<button class="trx_addons_elementor_templates_library_image_to_layout_close_btn eicon-editor-close" id="itlCloseBtn"></button>'
																+ '<img id="itlPreviewImg" src="" alt="">'
																+ '<div class="trx_addons_elementor_templates_library_image_to_layout_image_info">'
																	+ '<span id="itlFileName">image.png</span>'
																	+ '<span id="itlFileSize">0 KB</span>'
																+ '</div>'
															+ '</div>'
														+ '</div>'
													+ '</div>'
												+ '</div>'
												// Accuracy slider
												+ '<div class="trx_addons_elementor_templates_library_image_to_layout_accuracy_wrapper itl_step2">'
													+ '<h5 class="trx_addons_elementor_templates_library_image_to_layout_title">'
														+ '<span class="eicon-user-preferences"></span>'
														+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_step2'].replace( "\n", "<br>")
													+ '</h5>'
													+ '<div class="trx_addons_elementor_templates_library_image_to_layout_accuracy_slider">'
														+ '<span class="trx_addons_elementor_templates_library_image_to_layout_accuracy_line"></span>'
														+ '<span tabindex="0" class="trx_addons_elementor_templates_library_image_to_layout_accuracy" data-accuracy="75" title="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_accuracy'].replace( '%d', 75 ) + '">'
															+ '<span class="trx_addons_elementor_templates_library_image_to_layout_accuracy_handler"></span>'
															+ '<span class="trx_addons_elementor_templates_library_image_to_layout_accuracy_value">75%</span>'
														+ '</span>'
													+ '</div>'
												+ '</div>'
												// Filter templates by
												+ '<div class="trx_addons_elementor_templates_library_image_to_layout_filter itl_step3">'
													+ '<h5 class="trx_addons_elementor_templates_library_image_to_layout_title">'
														+ '<span class="eicon-accordion"></span>'
														+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_step3'].replace( "\n", "<br>")
													+ '</h5>'
													+ '<div class="trx_addons_elementor_templates_library_image_to_layout_sections_list">'
													+ '</div>'
												+ '</div>'
												// Hidden input to catch paste event
												+ '<input type="text" class="trx_addons_elementor_templates_library_image_to_layout_paste_handler" />'
											+ '</div>';
								}
								html += '</div>';
							}
							html += '</div></div>';
							content.append( html );
							$library = jQuery( '#trx_addons_elementor_templates_library' );

							// Add items
							for ( tab in TRX_ADDONS_STORAGE['elementor_templates_library_tabs'] ) {
								updateItems( tab );
								break;
							}

							// Add event handlers
							var updateItemsThrottle = trx_addons_throttle( function() {
								var columns = getComputedStyle( $library.get(0) ).getPropertyValue('--trx-addons-elementor-templates-library-columns');
								if ( $library && $library.data( 'columns') != columns ) {
									updateItems( $library.find('.trx_addons_elementor_templates_library_tab_active').data('tab') );
								}
							}, 100 );
							jQuery(window).on( 'resize', updateItemsThrottle );

							// var event = new Event( 'modal-close' );
							$library
								// Close the modal window
								.on( 'click', '.trx_addons_elementor_templates_library_close', function( e ) {
									// document.dispatchEvent( event );
									e.preventDefault();
									window.trx_addons_elementor_templates_library.modal.hide();
									return false;
								} )
								// Refresh the library
								.on( 'click', '.trx_addons_elementor_templates_library_refresh', function( e ) {
									e.preventDefault();
									refreshItems( jQuery(this) );
									return false;
								} )
								// Switch tabs
								.on( 'click', '.trx_addons_elementor_templates_library_tab', function( e ) {
									e.preventDefault();
									var $self = jQuery(this),
										tab = $self.data('tab');
									if ( ! $self.hasClass('trx_addons_elementor_templates_library_tab_active') ) {
										updateItems( tab );
										jQuery('.trx_addons_elementor_templates_library_tab').removeClass('trx_addons_elementor_templates_library_tab_active');
										$self.addClass('trx_addons_elementor_templates_library_tab_active');
										jQuery('.trx_addons_elementor_templates_library_tab_content').removeClass('trx_addons_elementor_templates_library_tab_content_active');
										jQuery('.trx_addons_elementor_templates_library_tab_content[data-tab="' + tab + '"]').addClass('trx_addons_elementor_templates_library_tab_content_active');
									}
									return false;
								} )
								// Switch categories (sidebar style)
								.on( 'click', '.trx_addons_elementor_templates_library_sidebar .trx_addons_elementor_templates_library_category', function( e ) {
									e.preventDefault();
									var $self = jQuery(this);
									if ( ! $self.hasClass('trx_addons_elementor_templates_library_category_active') ) {
										$self.parents('.trx_addons_elementor_templates_library_categories').find('.trx_addons_elementor_templates_library_category_active').removeClass('trx_addons_elementor_templates_library_category_active');
										$self.addClass('trx_addons_elementor_templates_library_category_active');
										updateItems( $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
									}
									return false;
								} )
								// Switch categories (toolbar style)
								.on( 'change', '.trx_addons_elementor_templates_library_categories_list', function( e ) {
									e.preventDefault();
									var $self = jQuery(this);
									// Mark the selected option with the active class
									// $self.find('option').removeClass('trx_addons_elementor_templates_library_category_active');
									// $self.find('option:selected').addClass('trx_addons_elementor_templates_library_category_active');
									updateItems( $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
									return false;
								} )
								// Switch favorites (toolbar style)
								.on( 'click', '.trx_addons_elementor_templates_library_toolbar .trx_addons_elementor_templates_library_category_favorites', function( e ) {
									e.preventDefault();
									var $self = jQuery(this);
									$self.toggleClass( 'trx_addons_elementor_templates_library_category_active' );
									updateItems( $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
									return false;
								} )
								// Switch pages
								.on( 'click', '.trx_addons_elementor_templates_library_page', function( e ) {
									e.preventDefault();
									var $self = jQuery(this),
										page = $self.data('page');
									if ( ! $self.hasClass('trx_addons_elementor_templates_library_page_active') ) {
										$self.parents('.trx_addons_elementor_templates_library_pagination').find('.trx_addons_elementor_templates_library_page_active').removeClass('trx_addons_elementor_templates_library_page_active');
										$self.addClass('trx_addons_elementor_templates_library_page_active');
										updateItems( $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
									}
									return false;
								} )
								// Search
								.on( 'input', '.trx_addons_elementor_templates_library_search input', function( e ) {
									updateItems( jQuery(this).parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
								} )
								// Filter by category on click on the category name in the item footer
								.on( 'click', '.trx_addons_elementor_templates_library_item_category', function( e ) {
									e.preventDefault();
									var $self = jQuery(this),
										category = $self.parents('.trx_addons_elementor_templates_library_item').data('template-category'),
										$tab_content = $self.parents('.trx_addons_elementor_templates_library_tab_content');
									// Trigger click on the category in the categories list in the sidebar
									var $list =  $tab_content.find(' .trx_addons_elementor_templates_library_sidebar .trx_addons_elementor_templates_library_category[data-category="' + category + '"]');
									if ( $list.length > 0 ) {
										$list.trigger('click');
									} else {
										// or select the category in the categories list in the toolbar
										$list = $tab_content.find( '.trx_addons_elementor_templates_library_categories_list' );
										if ( $list.length > 0 ) {
											$list.val( category ).trigger('change');
										}
									}
									return false;
								} )
								// Mark as favorite
								.on( 'click', '.trx_addons_elementor_templates_library_item_favorite', function( e ) {
									e.preventDefault();
									var $self = jQuery(this),
										template = $self.data('template'),
										state = $self.hasClass( 'trx_addons_elementor_templates_library_item_favorite_on' ),
										$item = $self.parents('.trx_addons_elementor_templates_library_item');
									// Toggle favorite state
									$self.toggleClass( 'trx_addons_elementor_templates_library_item_favorite_on' );
									state = ! state;
									TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ template ] = state;
									// Update the state in the item
									$item.data( 'template-favorite', state );
									// Update the counter in the category list
									var $counter = $self.parents('.trx_addons_elementor_templates_library_tab_content').find('.trx_addons_elementor_templates_library_category_favorites .trx_addons_elementor_templates_library_category_total'),
										count = parseInt( $counter.text(), 10 );
									if ( isNaN( count ) ) {
										count = 0;
									}
									$counter.text( count + ( state ? 1 : -1 ) );
									// Send AJAX request to mark/unmark as favorite
									jQuery.post( TRX_ADDONS_STORAGE['ajax_url'], {
										action: 'trx_addons_elementor_templates_library_item_favorite',
										nonce: TRX_ADDONS_STORAGE['ajax_nonce'],
										template_name: template,
										favorite: $self.hasClass( 'trx_addons_elementor_templates_library_item_favorite_on' ) ? 1 : 0,
										is_admin_request: 1
									}, function( response ) {
										var rez = trx_addons_parse_ajax_response( response );
										if ( rez.error ) {
											showMessage( rez.error );
										}
									} );
									// Update the items in the current tab if current category is "Favorites" and the item is unmarked as favorite
									var navi_style = TRX_ADDONS_STORAGE['elementor_templates_library_navigation_style'],
										tab = $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab'),
										$tab_content = jQuery( '#trx_addons_elementor_templates_library .trx_addons_elementor_templates_library_tab_content[data-tab="' + tab + '"]' ),
										is_favorites = navi_style == 'sidebar'
														? $tab_content.find('.trx_addons_elementor_templates_library_category_active').data('category').toLowerCase() == 'favorites'
														: $tab_content.find( '.trx_addons_elementor_templates_library_category_favorites' ).hasClass( 'trx_addons_elementor_templates_library_category_active' );
									if ( ! state && is_favorites ) {
										updateItems( tab );
									}
									return false;
								} )
								// Preview template
								.on( 'click', '.trx_addons_elementor_templates_library_item_preview', function( e ) {
									e.preventDefault();
									var $self = jQuery(this),
										$item = $self.parents('.trx_addons_elementor_templates_library_item'),
										template = $item.data('template-name'),
										tab = $item.parents('.trx_addons_elementor_templates_library_tab_content').data('tab');
									if ( TRX_ADDONS_STORAGE['elementor_templates_library'][ template ] ) {
										templatesPreview( template, tab );
									}
									return false;
								} )
								// Import template
								.on( 'click', '.trx_addons_elementor_templates_library_item_import', function( e ) {
									e.preventDefault();
									var $self = jQuery(this),
										template = $self.data('template'),
										tab = $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab');
									if ( TRX_ADDONS_STORAGE['elementor_templates_library'][ template ]['attention'] ) {
										if ( ! confirm(
											TRX_ADDONS_STORAGE['elementor_templates_library'][ template ]['attention'].replace( /<br[\s]*\/?>/gi, "\n" )
											+ "\n"
											+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_import_confirm'] )
										) {
											return false;
										}
									}
									if ( $self ) {
										$self.addClass( 'trx_addons_loading' );
										$self.parents( '.trx_addons_elementor_templates_library_item' ).addClass( 'trx_addons_elementor_templates_library_item_loading' );
									}
									jQuery.post( TRX_ADDONS_STORAGE['ajax_url'], {
											action: 'trx_addons_elementor_templates_library_item_import',
											nonce: TRX_ADDONS_STORAGE['ajax_nonce'],
											template_name: template,
											template_type: tab,
											is_admin_request: 1
										}, function( response ) {
											if ( $self ) {
												$self.removeClass( 'trx_addons_loading' );
												$self.parents('.trx_addons_elementor_templates_library_item').removeClass( 'trx_addons_elementor_templates_library_item_loading' );
											}
											var rez = trx_addons_parse_ajax_response( response );
											if ( rez.error ) {
												showMessage( rez.error );
											} else {
												regenerateIds( rez.data.content );
												insertContent( rez.data.content, tab );
												if ( ! jQuery( '#trx_addons_elementor_templates_library_modal' ).hasClass( 'image_to_layout_opened' ) 
													|| jQuery( '.trx_addons_elementor_templates_library_image_to_layout_sections_list input[type="radio"]').length == 1
													|| jQuery( '#itl_section_whole').is(':checked')
												) {
													window.trx_addons_elementor_templates_library.modal.hide();
												}
											}
										} );
									return false;
								} );

							// Filter by Image with Layout handlers
							//-----------------------------------------------------

							// Open/Close Filter by Image with Layout panel
							$library
								.on( 'click', '.trx_addons_elementor_templates_library_image_to_layout_open', function( e ) {
									e.preventDefault();
									jQuery( '#trx_addons_elementor_templates_library_modal' ).toggleClass( 'image_to_layout_opened' );
									// Put focus to the paste handler to enable paste from clipboard
									jQuery( '.trx_addons_elementor_templates_library_image_to_layout_paste_handler').focus();
									return false;
								} )
								.on( 'click', '.trx_addons_elementor_templates_library_image_to_layout', function( e ) {
									// Put focus to the paste handler to enable paste from clipboard
									jQuery( '.trx_addons_elementor_templates_library_image_to_layout_paste_handler').focus();
								} );

							$library.on( 'focus', '.trx_addons_elementor_templates_library_image_to_layout_paste_handler', function() {
								// Clear the field value to allow paste the same image again
								jQuery( this ).val( '' );
							} );

							// Select Image: Open file selector on click the button
							$library
								.on( 'click', '#itlSelectBtn', function( e ) {
									e.preventDefault();
									e.stopPropagation();
									jQuery( '#itlFileInput' ).eq(0).trigger( 'click' );
								} )
								// Select Image: Open file selector on click the upload area
								.on( 'click', '#itlUploadArea', function( e ) {
									// Avoid recursion triggering
									if ( jQuery( e.target ).is( '#itlFileInput') || jQuery( e.target ).is( '#itlSelectBtn') || jQuery( e.target ).closest( '#itlSelectBtn' ).length ) {
										return;
									}
									jQuery( '#itlFileInput' ).eq(0).trigger( 'click' );
								} )
								// Select Image: Selected file processing
								.on( 'change', '#itlFileInput', function( e ) {
									const file = e.target.files[0];
									if ( file && file.type.startsWith( 'image/' ) ) {
										handleImage( file );
									} else {
										showMessage( TRX_ADDONS_STORAGE['msg_elementor_templates_library_unsupported_image_type'] );
									}
								} );

							// Drag & Drop
							$library
								.on( 'dragover', '#itlUploadArea', function( e ) {
									e.preventDefault();
									jQuery( this ).addClass( 'dragover' );
								} )
								.on( 'dragleave', '#itlUploadArea', function() {
									jQuery( this ).removeClass( 'dragover' );
								} )
								.on( 'drop', '#itlUploadArea', function( e ) {
									e.preventDefault();
									jQuery( this ).removeClass('dragover');
									const file = e.originalEvent.dataTransfer.files[0];
									if ( file && file.type.startsWith( 'image/' ) ) {
										handleImage( file );
									} else {
										showMessage( TRX_ADDONS_STORAGE['msg_elementor_templates_library_unsupported_image_type'] );
									}
								} );

							// Paste from clipboard
							jQuery( document ).on( 'paste', function( e ) {
								if ( ! jQuery( '#trx_addons_elementor_templates_library_modal' ).hasClass( 'image_to_layout_opened' ) ) {
									return;
								}
								e.stopPropagation();
								e.stopImmediatePropagation();
								e.preventDefault();
								const items = e.originalEvent.clipboardData.items;
								for ( let i = 0; i < items.length; i++ ) {
									if ( items[ i ].type.startsWith( 'image/' ) ) {
										const file = items[ i ].getAsFile();
										handleImage( file );
										break;
									}
								}
							} );

							// Selected image processing
							function handleImage( file ) {
								const reader = new FileReader();
								reader.onload = function(e) {
									jQuery( '#itlPreviewImg' ).attr( 'src', e.target.result );
									jQuery( '#itlFileName' ).text( file.name );
									jQuery( '#itlFileSize').text( trx_addons_size2kilo( file.size ) );
									jQuery( '#itlPanel').addClass( 'trx_addons_elementor_templates_library_image_to_layout_selected' );
									uploadToServer( file );
								};
								reader.readAsDataURL( file );
							}

							// Upload image to server and filter a list of templates by the layout of the image
							function uploadToServer( file ) {
								var $preview = jQuery( '#itlPreviewArea' ),
									$field = jQuery( '#itlFileInput' );

								$preview.addClass( 'trx_addons_loading' );
								$field.data( 'layout', '' );

								// Send request via AJAX
								var formData = new FormData();
								formData.append( 'nonce', TRX_ADDONS_STORAGE['ajax_nonce'] );
								formData.append( 'action', 'trx_addons_elementor_templates_library_image_to_layout' );
								formData.append( 'upload_image', file, file.name );

								jQuery.ajax( {
									url: TRX_ADDONS_STORAGE['ajax_url'],
									type: "POST",
									data: formData,
									processData: false,		// Don't process fields to the string
									contentType: false,		// Prevent content type header
									success: function( response ) {
										// Prepare response
										var rez = trx_addons_parse_ajax_response( response );
										if ( rez.error ) {
											showMessage( rez.error );
										} else if ( rez.data ) {
											$field.data( 'layout', rez.data );
											if ( rez.data ) {
												fillSectionsList( rez.data );
											}
											updateItems( $field.parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
										}
									},
									error: function( jqXHR, textStatus, errorThrown ) {
										showMessage( TRX_ADDONS_STORAGE['msg_ajax_error'] );
									},
									complete: function() {
										$preview.removeClass( 'trx_addons_loading' );
									}
								} );
							}

							// Clear selected image
							$library.on( 'click', '#itlCloseBtn', function(e) {
								e.stopPropagation();
								jQuery( '#itlPreviewImg' ).attr( 'src', '' );
								jQuery( '#itlFileInput' ).val( '' ).data( 'layout', '' );
								jQuery( '#itlPanel').removeClass( 'trx_addons_elementor_templates_library_image_to_layout_selected' );
								fillSectionsList( '' );
								updateItems( jQuery(this).parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
								// Put focus to the paste handler to enable paste from clipboard
								jQuery( '.trx_addons_elementor_templates_library_image_to_layout_paste_handler').focus();
							} );

							// Init accuracy slider to filter by image with layout
							if ( jQuery.ui && jQuery.ui.slider ) {
								$library.find('.trx_addons_elementor_templates_library_image_to_layout_accuracy:not(.inited)').each( function () {
									var $self = jQuery(this);
									$self.addClass('inited').draggable( {
										axis: 'x',
										containment: 'parent',
										drag: function( event, ui ) {
											var hw  = $self.outerWidth(),
												pw  = $self.parent().width() - hw,
												prc = Math.max( 0, Math.min( 100, Math.round( ui.position.left / pw * 100 ) ) );
											$self
												.data( 'accuracy', prc )
												.attr( 'title', TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_accuracy'].replace( '%d', prc ) )
												.find( '.trx_addons_elementor_templates_library_image_to_layout_accuracy_value' )
													.text( prc + '%' );
											updateItems( $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
										}
									} );
								} );
							}

							// Filter by sections
							$library.on( 'change', '.trx_addons_elementor_templates_library_image_to_layout_sections_list input[name="itl_section"]', function() {
								var $self = jQuery(this);
								$self.closest( '.trx_addons_elementor_templates_library_image_to_layout_sections_list' )
									.find( '.trx_addons_elementor_templates_library_image_to_layout_section_option' )
										.removeClass( 'itl_active' );
								$self.parent().addClass( 'itl_active' );
								jQuery( '#itlFileInput' ).data( 'layout', $self.val() );
								updateItems( $self.parents('.trx_addons_elementor_templates_library_tab_content').data('tab') );
							} );

							// Fill the sections list (radio buttons) by layout string.
							// Add "Whole Image" option always first. Add other sections if layout contain more than one section.
							// If layout is empty - clear the sections list.
							function fillSectionsList( layout ) {
								var $list = jQuery( '.trx_addons_elementor_templates_library_image_to_layout_sections_list' );
								$list.empty();
								jQuery( '#itlPanel').removeClass( 'trx_addons_elementor_templates_library_image_to_layout_multisections' );
								if ( layout ) {
									// Mark panel to show that layout contain more than one section
									jQuery( '#itlPanel').addClass( 'trx_addons_elementor_templates_library_image_to_layout_multisections' );
									var sections = parseLayout( layout );
									// Add 'Whole Layout' option if there are more than one section
									if ( sections.length > 1 ) {
										$list.append(
											'<div class="trx_addons_elementor_templates_library_image_to_layout_section_option itl_active">'
												+ '<input type="radio" id="itl_section_whole" name="itl_section" value="' + layout + '" checked="checked">'
												+ '<label for="itl_section_whole">'
													+ '<img src="' + jQuery( '#itlPreviewImg' ).attr( 'src' ) + '" alt="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_whole_image'] + '" />'
													+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_whole_image']
												+ '</label>'
											+ '</div>'
										);
									}
									sections.forEach( function( section, index ) {
										$list.append(
											'<div class="trx_addons_elementor_templates_library_image_to_layout_section_option' + ( index == 0 &&  sections.length == 1 ? ' itl_active' : '' ) + '">'
												+ '<input type="radio" id="itl_section_' + index + '" name="itl_section" value="' + section.original + '"' + ( index == 0 &&  sections.length == 1 ? ' checked="checked"' : '' ) + '>'
												+ '<label for="itl_section_' + index + '">'
													+ getSectionLayout( section )
													// + TRX_ADDONS_STORAGE['msg_elementor_templates_library_image_to_layout_section'] + ' ' + ( index + 1 )
													+ '</label>'
												+ '</div>'
										);
									} );
								}
							}

							// Return a layout of the section:
							// - each column must be wrapped to <span class="itl-column">...</span>
							// - each widget inside column must be wrapped to <span class="itl-widget">widget-slug</span>
							function getSectionLayout( section ) {
								var layout = '';
								section.columns.forEach( function( column ) {
									var col_content = '';
									column.forEach( function( widget ) {
										col_content += '<span class="itl-widget itl-widget-type-' + widget + '">'
															// + widget
															+ '<img src="' + TRX_ADDONS_STORAGE['elementor_templates_library_images_url'] + '/layout/' + widget + '.png" alt="' + widget + '"/>'
														+ '</span>';
									} );
									layout += '<span class="itl-column">' + col_content + '</span>';
								} );
								return layout;
							}

						},
						onHide: function() {}
					} ) ),
//					window.trx_addons_elementor_templates_library.modal.getElements( 'header' ).remove(),
					window.trx_addons_elementor_templates_library.modal.getElements( 'message' ).append( window.trx_addons_elementor_templates_library.modal.addElement( 'content' ) ) ),
					window.trx_addons_elementor_templates_library.modal.show()
				);
		}

		// Get the html layout with the categories list
		function getCategoriesList( tab, force_all ) {
			var navi_style = TRX_ADDONS_STORAGE['elementor_templates_library_navigation_style'];
			var cats = '', total = 0, favorites = 0;
			var $tab_content = jQuery( '#trx_addons_elementor_templates_library .trx_addons_elementor_templates_library_tab_content[data-tab="' + tab + '"]' );
			var cat_active = $tab_content.length === 0 || force_all
								? ''
								: ( navi_style == 'sidebar'
									? $tab_content.find('.trx_addons_elementor_templates_library_category_active').data('category')
									: $tab_content.find('.trx_addons_elementor_templates_library_categories_list').val()
									);
			for ( var cat in TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'] ) {
				if ( navi_style == 'sidebar' ) {
					cats += '<a href="#" class="trx_addons_elementor_templates_library_category' + ( cat == cat_active ? ' trx_addons_elementor_templates_library_category_active' : '' ) + '" data-category="' + cat + '">'
								+ TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][cat]['title']
								+ '<span class="trx_addons_elementor_templates_library_category_total">' + TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][cat]['total'] + '</span>'
							+ '</a>';
				} else {
					cats += '<option value="' + cat + '"' + ( cat == cat_active ? ' selected="selected"' : '' ) + '>'
								+ TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][cat]['title']
								+ ' (' + TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][cat]['total'] + ')'
							+ '</option>';
				}
				total += TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][cat]['total'];
			}
			for ( var tpl in TRX_ADDONS_STORAGE['elementor_templates_library'] ) {
				var template = TRX_ADDONS_STORAGE['elementor_templates_library'][tpl];
				if ( template.type != tab ) {
					continue;
				}
				if ( TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ tpl ] ) {
					favorites++;
				}
			}
			if ( total > 0 ) {
				if ( navi_style == 'sidebar' ) {
					cats = 
							// Category "All"
							'<a href="#" class="trx_addons_elementor_templates_library_category trx_addons_elementor_templates_library_category_all' + ( ! cat_active ? ' trx_addons_elementor_templates_library_category_active' : '' ) + '" data-category="all">'
								+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_category_all']
								+ '<span class="trx_addons_elementor_templates_library_category_total">' + total + '</span>'
							+ '</a>'
							// Favorites
							+ '<a href="#" class="trx_addons_elementor_templates_library_category trx_addons_elementor_templates_library_category_favorites" data-category="favorites">'
								+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_category_favorites']
								+ '<span class="trx_addons_elementor_templates_library_category_total">' + favorites + '</span>'
							+ '</a>'
							// Other categories
							+ cats;
				} else {
					cats = '<select class="trx_addons_elementor_templates_library_categories_list">'
							+ '<option value="all"' + ( ! cat_active ? ' selected="selected"' : '' ) + '>' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_category_all'] + ' (' + total + ')' + '</option>'
							+ cats
						+ '</select>'
						// Favorites
						+ '<a href="#" class="trx_addons_elementor_templates_library_category trx_addons_elementor_templates_library_category_favorites">'
							+ '<span class="trx_addons_elementor_templates_library_category_icon eicon-heart-o"></span>'
							+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_category_favorites']
							+ ' (<span class="trx_addons_elementor_templates_library_category_total">' + favorites + '</span>)'
						+ '</a>';
				}
			}
			return cats;
		}

		// Preview the template
		function templatesPreview( template, tab ) {
			elementorCommon
			&& ( window.trx_addons_elementor_templates_library.preview
				|| ( ( window.trx_addons_elementor_templates_library.preview = elementorCommon.dialogsManager.createWidget( "lightbox", {
						id: "trx_addons_elementor_templates_library_preview",
						headerMessage: '<span class="trx_addons_elementor_templates_library_title">'
											+ '<span class="trx_addons_elementor_templates_library_item_back">'
												+ '<span class="trx_addons_elementor_templates_library_item_back_icon eicon-chevron-left"></span>'
												+ '<span class="trx_addons_elementor_templates_library_item_back_text">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_preview_back'] + '</span>'
											+ '</span>'
											+ '<a href="#" class="trx_addons_elementor_templates_library_item_import trx_addons_icon-download elementor-button" data-template="' + template + '">'
												+ TRX_ADDONS_STORAGE['msg_elementor_templates_library_import_template']
											+ '</a>'
											+ '<span class="trx_addons_elementor_templates_library_item_title">'
												+ TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].title
											+ '</span>'
											+ ' / '
											+ '<span class="trx_addons_elementor_templates_library_item_category">'
												+ TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].category]['title']
											+ '</span>'
											+ '<span class="trx_addons_elementor_templates_library_item_favorite'
													+ ( TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ template ] ? ' trx_addons_elementor_templates_library_item_favorite_on' : '' )
												+ '" data-template="' + template + '"'
											+'>'
												+ '<span class="trx_addons_elementor_templates_library_item_favorite_icon eicon-heart-o"></span>'
											+ '</span>'
										+ '</span>',
						message: "",
						hide: {
							auto: false,
							onClick: false,
							onOutsideClick: false,
							onOutsideContextMenu: false,
							onBackgroundClick: true
						},
						position: {
							my: "center",
							at: "center"
						},
						onShow: function() {
							var template = window.trx_addons_elementor_templates_library.preview.template;
							var tpl_title   = TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].title;
							var tpl_content = TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].content;
							var tpl_category = TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].category]['title'];
							var tpl_image   = TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].demo_url
												? '<iframe src="' + trx_addons_add_to_url( TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].demo_url, { 'utm-source': 'elementor-templates-library-preview', 'utm-source-type': TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].category } ) + '" frameborder="0" allowfullscreen></iframe>'
												: '<img src="' + TRX_ADDONS_STORAGE['elementor_templates_library_url'] + '/' + template + '/' + template + ( TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].animated ? '.gif' : '.jpg' ) + '" alt="' + tpl_title + '">';
							var content = window.trx_addons_elementor_templates_library.preview.getElements( 'content' );
							var title_area = jQuery( '#trx_addons_elementor_templates_library_preview .trx_addons_elementor_templates_library_title' );
							var widget_content = jQuery( '#trx_addons_elementor_templates_library_preview .dialog-widget-content' );
							if ( TRX_ADDONS_STORAGE['elementor_templates_library'][ template ].demo_url ) {
								widget_content.addClass( 'with_demo_url' );
							} else {
								widget_content.removeClass( 'with_demo_url' );
							}
							if ( content.find( '#trx_addons_elementor_templates_library_preview_wrap' ).length > 0 ) {
								// Dialog already exists - replace the content and header title
								content.find( '.trx_addons_elementor_templates_library_preview_content' ).html( tpl_image );
								title_area.find('.trx_addons_elementor_templates_library_item_title').text( tpl_title );
								title_area.find('.trx_addons_elementor_templates_library_item_category').text( tpl_category );
								title_area.find('.trx_addons_elementor_templates_library_item_import').data( 'template', template );
								title_area.find('.trx_addons_elementor_templates_library_item_favorite')
									.data( 'template', template )
									.toggleClass( 'trx_addons_elementor_templates_library_item_favorite_on', TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ template ] === true );
							} else {
								// Create the dialog content
								var html = '<div id="trx_addons_elementor_templates_library_preview_wrap" class="wrap">'
												+ '<a href="#" class="trx_addons_elementor_templates_library_close trx_addons_button_close" title="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_preview_close'] + '">'
													+ '<span class="trx_addons_button_close_icon"></span>'
												+ '</a>'
												+ '<div class="trx_addons_elementor_templates_library_preview_content">'
													+ tpl_image
												+ '</div>'
											+ '</div>';
								content.append( html );

								jQuery( '#trx_addons_elementor_templates_library_preview' )
									// Close the preview window on click of the close button or back button
									.on( 'click', '.trx_addons_elementor_templates_library_close,.trx_addons_elementor_templates_library_item_back', function( e ) {
										// document.dispatchEvent( event );
										e.preventDefault();
										window.trx_addons_elementor_templates_library.preview.hide();
										return false;
									} )
									// Import template
									.on( 'click', '.trx_addons_elementor_templates_library_item_import', function( e ) {
										e.preventDefault();
										window.trx_addons_elementor_templates_library.preview.hide();
										$library.find( '.trx_addons_elementor_templates_library_item[data-template-name="' + window.trx_addons_elementor_templates_library.preview.template + '"] .trx_addons_elementor_templates_library_item_import' ).trigger( 'click' );
										return false;
									} )
									// Mark/unmark as favorite
									.on( 'click', '.trx_addons_elementor_templates_library_item_favorite', function( e ) {
										e.preventDefault();
										jQuery( this ).toggleClass( 'trx_addons_elementor_templates_library_item_favorite_on' );
										$library.find( '.trx_addons_elementor_templates_library_item[data-template-name="' + window.trx_addons_elementor_templates_library.preview.template + '"] .trx_addons_elementor_templates_library_item_favorite' ).trigger( 'click' );
										return false;
									} );
							}
						},
						onHide: function() {}
					} ) ),
//					window.trx_addons_elementor_templates_library.preview.getElements( 'header' ).remove(),
					window.trx_addons_elementor_templates_library.preview.getElements( 'message' ).append( window.trx_addons_elementor_templates_library.preview.addElement( 'content' ) ) ),
					window.trx_addons_elementor_templates_library.preview.template = template,
					window.trx_addons_elementor_templates_library.preview.show()
				);
		}

		// Check for updates in the library server
		function refreshItems( $bt ) {
			var $icon = $bt.find('.trx_addons_elementor_templates_library_refresh_icon').addClass( 'trx_addons_loading' );
			jQuery.post( TRX_ADDONS_STORAGE['ajax_url'], {
				action: 'trx_addons_elementor_templates_library_refresh',
				nonce: TRX_ADDONS_STORAGE['ajax_nonce'],
				is_admin_request: 1
			}, function( response ) {
				$icon.removeClass( 'trx_addons_loading' );
				var rez = trx_addons_parse_ajax_response( response );
				if ( rez.error ) {
					showMessage( rez.error );
				} else {
					if ( rez.data && rez.data.templates && rez.data.tabs ) {
						TRX_ADDONS_STORAGE['elementor_templates_library'] = rez.data.templates;
						TRX_ADDONS_STORAGE['elementor_templates_library_tabs'] = rez.data.tabs;
						var tab = $bt.parents('#trx_addons_elementor_templates_library').find('.trx_addons_elementor_templates_library_tab_active').data('tab');
						// Update the categories list in the current tab
						var cats = getCategoriesList( tab, true );
						if ( cats ) {
							jQuery( '#trx_addons_elementor_templates_library .trx_addons_elementor_templates_library_tab_content[data-tab="' + tab + '"]' )
								.find('.trx_addons_elementor_templates_library_categories')
								.html( cats );
						}
						// Clear data-attributes for layout filtering to force re-calculate similarity
						jQuery( '#trx_addons_elementor_templates_library .trx_addons_elementor_templates_library_tab_content' )
							.data( 'layout', '' );
						// Update the items in the current tab
						updateItems( tab );
					} else {
						showMessage( TRX_ADDONS_STORAGE['msg_ajax_error'] );
					}
				}
			} );
		}

		// Repaint items in the specified tab
		function updateItems( tab ) {
			var $library = jQuery( '#trx_addons_elementor_templates_library');
			var navi_style = TRX_ADDONS_STORAGE['elementor_templates_library_navigation_style'];
			var html = '';
			var items = [];
			var column = 0;
			var columns = getComputedStyle( $library.get(0) ).getPropertyValue('--trx-addons-elementor-templates-library-columns');
			var items_in_page = TRX_ADDONS_STORAGE['elementor_templates_library_pagination_items'][tab] || 20;
			var templates_url = TRX_ADDONS_STORAGE['elementor_templates_library_url'];
			var $tab_content = jQuery( '#trx_addons_elementor_templates_library .trx_addons_elementor_templates_library_tab_content[data-tab="' + tab + '"]' );
			var search = $tab_content.find('.trx_addons_elementor_templates_library_search input').val().toLowerCase();
			var layout = $tab_content.find('#itlFileInput').data( 'layout' ) || '';
			var accuracy = $tab_content.find('.trx_addons_elementor_templates_library_image_to_layout_accuracy').data( 'accuracy' );
			if ( accuracy === undefined ) {
				accuracy = 75;
			}
			var similarity;
			var cat = navi_style == 'sidebar'
						? $tab_content.find('.trx_addons_elementor_templates_library_category_active').data('category').toLowerCase()
						: $tab_content.find('.trx_addons_elementor_templates_library_categories_list').val().toLowerCase();
			var is_favorites = navi_style == 'sidebar' ? cat == 'favorites' : $tab_content.find( '.trx_addons_elementor_templates_library_category_favorites' ).hasClass( 'trx_addons_elementor_templates_library_category_active' );
			var page = $tab_content.find('.trx_addons_elementor_templates_library_page_active').data('page') || 1;
			var pages = 1;
			var new_pagination = false;
			var idx = 0;
			var tpl;
			var tpl_keys = Object.keys( TRX_ADDONS_STORAGE['elementor_templates_library'] );
			var i, j;
			var time_new = trx_addons_get_sql_date( new Date().getTime() - 2 * 7 * 24 * 60 * 60 * 1000 ); // Two weeks before the current time

			// Prepare a similarity for all templates if layout is selected
			// and push a new templates to top the list
			if ( layout != '' && accuracy > 0 && ( $tab_content.data( 'layout' ) != layout || $tab_content.data( 'accuracy' ) != accuracy ) ) {
				for ( tpl in TRX_ADDONS_STORAGE['elementor_templates_library'] ) {
					similarity = getLayoutsSimilarity( TRX_ADDONS_STORAGE['elementor_templates_library'][tpl].layout, layout, accuracy );
					TRX_ADDONS_STORAGE['elementor_templates_library'][tpl].similar = similarity.similar;
					TRX_ADDONS_STORAGE['elementor_templates_library'][tpl].similarity = similarity.similarity;
				}
			}
			// Sort templates by similarity
			if ( layout != '' && accuracy > 0 ) {
				tpl_keys = tpl_keys.sort( function( a, b ) {
					return TRX_ADDONS_STORAGE['elementor_templates_library'][a].similar && TRX_ADDONS_STORAGE['elementor_templates_library'][b].similar
							? TRX_ADDONS_STORAGE['elementor_templates_library'][b].similarity - TRX_ADDONS_STORAGE['elementor_templates_library'][a].similarity
							: ( TRX_ADDONS_STORAGE['elementor_templates_library'][a].similar ? -1 : ( TRX_ADDONS_STORAGE['elementor_templates_library'][b].similar ? 1 : 0 ) );
				} );

			// Push new templates to top the list
			} else {
				tpl_keys = tpl_keys.sort( function( a, b ) {
					return TRX_ADDONS_STORAGE['elementor_templates_library'][a].uploaded && TRX_ADDONS_STORAGE['elementor_templates_library'][a].uploaded > time_new
							&& TRX_ADDONS_STORAGE['elementor_templates_library'][b].uploaded && TRX_ADDONS_STORAGE['elementor_templates_library'][b].uploaded > time_new
							? 0
							: ( TRX_ADDONS_STORAGE['elementor_templates_library'][a].uploaded && TRX_ADDONS_STORAGE['elementor_templates_library'][a].uploaded > time_new
								? -1
								: ( TRX_ADDONS_STORAGE['elementor_templates_library'][b].uploaded && TRX_ADDONS_STORAGE['elementor_templates_library'][b].uploaded > time_new
									? 1
									: 0
								)
							);
				} );
			}

			// Check if we need a new pagination (if a new search or category selected)
			if ( $tab_content.data( 'search' ) != search
				|| $tab_content.data( 'layout' ) != layout
				|| $tab_content.data( 'accuracy' ) != accuracy
				|| $tab_content.data( 'cat' ) != cat
				|| $tab_content.data( 'favorites' ) != ( is_favorites ? 1 : 0 )
			) {
				$tab_content.data( 'search', search );
				$tab_content.data( 'layout', layout );
				$tab_content.data( 'accuracy', accuracy );
				$tab_content.data( 'cat', cat );
				$tab_content.data( 'favorites', is_favorites ? 1 : 0 );
				$tab_content.data( 'page', 1 );
				page = 1;
				new_pagination = true;
			}
			// Count favorites in the current category if navigation style is 'toolbar'
			if ( navi_style == 'toolbar' ) {
				var favorites = 0;
				for ( tpl in TRX_ADDONS_STORAGE['elementor_templates_library'] ) {
					var template = TRX_ADDONS_STORAGE['elementor_templates_library'][tpl];
					if ( template.type != tab
						|| ( cat != 'all' && ( ',' + template.category + ',').indexOf( ',' + cat + ',' ) < 0 )
					) {
						continue;
					}
					if ( TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ tpl ] ) {
						favorites++;
					}
				}
				// Update the counter in the toolbar
				$tab_content.find('.trx_addons_elementor_templates_library_category_favorites .trx_addons_elementor_templates_library_category_total').text( favorites );
			}
			// Init items array
			for ( i = 0; i < columns; i++ ) {
				items.push( '' );
			}
			// Fill items array by columns
			for ( j = 0; j < tpl_keys.length; j++ ) {
				tpl = tpl_keys[ j ];
				var template = TRX_ADDONS_STORAGE['elementor_templates_library'][tpl];
				if ( template.type != tab
					|| ( is_favorites && ! TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ tpl ] )
					|| ( cat != 'all' && cat != 'favorites' && ( ',' + template.category + ',').indexOf( ',' + cat + ',' ) < 0 )
					|| ( search != '' && template.keywords.indexOf( search ) < 0 && template.title.indexOf( search ) < 0 && template.category.indexOf( search ) < 0 )
					|| ( layout != '' && accuracy > 0 && ! template.similar )
				) {
					continue;
				}
				idx++;
				if ( idx < items_in_page * ( page - 1 ) + 1 || idx > items_in_page * page ) {
					continue;
				}
				items[ column++ % columns ] += '<div class="trx_addons_elementor_templates_library_item"'
							+ ' data-template-name="' + tpl + '"'
							+ ' data-template-category="' + template.category + '"'
							+ ' data-template-keywords="' + template.keywords + '"'
							+ ' data-template-favorite="' + ( TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ tpl ] ? 1 : 0 ) + '"'
						+ '>'
							+ ( template.uploaded && template.uploaded > time_new
								? '<span class="trx_addons_elementor_templates_library_item_new">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_item_new'] + '</span>'
								: '' )
							+ '<div class="trx_addons_elementor_templates_library_item_body">'
								+ '<img src="' + templates_url + '/' + tpl + '/' + tpl + '-small.jpg" alt="' + template.title + '">'
								+ '<div class="trx_addons_elementor_templates_library_item_preview">'
									// Icon "Zoom" at the center of overlay
									+ '<span class="eicon-zoom-in-bold" aria-hidden="true"></span>'
								+ '</div>'
							+ '</div>'
							+ '<div class="trx_addons_elementor_templates_library_item_footer">'
								+ '<a href="#" class="trx_addons_elementor_templates_library_item_import trx_addons_icon-download elementor-button" data-template="' + tpl + '">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_import_template'] + '</a>'
								+ '<span class="trx_addons_elementor_templates_library_item_title">'
									+ template.title
									+ ( cat == 'all'
										? ' / ' + '<a href="#" class="trx_addons_elementor_templates_library_item_category" title="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_filter_by_category'] + '">'
											+ TRX_ADDONS_STORAGE['elementor_templates_library_tabs'][tab]['category'][template.category]['title']
											+ '</a>'
										: ''
										)
									// Show similarity percentage if layout is selected and accuracy > 0
									// + ( layout != '' && accuracy > 0 ? ' (' + Math.round( template.similarity ) + '%)' : '' )
								+ '</span>'
								+ '<span class="trx_addons_elementor_templates_library_item_favorite'
										+ ( TRX_ADDONS_STORAGE['elementor_templates_library_favorites'][ tpl ] ? ' trx_addons_elementor_templates_library_item_favorite_on' : '' )
									+ '" data-template="' + tpl + '"'
								+'>'
									+ '<span class="trx_addons_elementor_templates_library_item_favorite_icon eicon-heart-o"></span>'
								+ '</span>'
							+ '</div>'
						+ '</div>';
			}

			if ( ! items[0] ) {
				html += '<div class="trx_addons_elementor_templates_library_empty">' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_empty'] + '</div>';
			} else {
				html += '<div class="trx_addons_elementor_templates_library_list">';
				for ( var i = 0; i < columns; i++ ) {
					html += '<div class="trx_addons_elementor_templates_library_column">' + items[ i ] + '</div>';
				}
				html += '</div>';
			}
			$tab_content.find('.trx_addons_elementor_templates_library_items').html( html );
			$library.data( 'columns', columns );
			// Pagination
			if ( new_pagination ) {
				html = '';
				pages = Math.ceil( idx / items_in_page );
				if ( pages > 1 ) {
					html += '<div class="trx_addons_elementor_templates_library_pagination">';
					for ( var i = 1; i <= pages; i++ ) {
						html += '<a href="#" class="trx_addons_elementor_templates_library_page' + ( i == page ? ' trx_addons_elementor_templates_library_page_active' : '' ) + '" data-page="' + i + '">' + i + '</a>';
					}
					html += '</div>';
				}
				$tab_content.find('.trx_addons_elementor_templates_library_pagination').remove();
				$tab_content.append( html ).toggleClass( 'with_pagination', pages > 1 );
			}
		}

		function regenerateIds( content ) {
			for ( var i = 0; i < content.length; i++ ) {
				if ( content[ i ].id ) {
					content[ i ].id = typeof elementorCommon != "undefined" 
										? elementorCommon.helpers.getUniqueId().toString()
										: trx_addons_get_unique_id();
					if ( content[ i ].elements ) {
						regenerateIds( content[ i ].elements );
					}
				}
			}
		}

		function insertContent( content ) {
			var context = arguments.length > 1 && void 0 !== arguments[1] ? arguments[1] : "blocks",
				contextText = context === "blocks"
								? TRX_ADDONS_STORAGE['msg_elementor_templates_library_type_block']
								: TRX_ADDONS_STORAGE['msg_elementor_templates_library_type_page'];
			var insertIndex = window.trx_addons_elementor_templates_library && typeof window.trx_addons_elementor_templates_library.insertIndex != 'undefined'
								? window.trx_addons_elementor_templates_library.insertIndex
								: -1;
			if ( typeof $e != "undefined" ) {
				var historyId = $e.internal( "document/history/start-log", {
					type: "add",
					title: "".concat( TRX_ADDONS_STORAGE['msg_elementor_templates_library_add_template'], " " ).concat( contextText )
				} );
				var insertOptions = {
					// clone: true		// To regenerate unique IDs for the new elements and clear some unique parameters like CSS ID
				};
				for ( var i = 0; i < content.length; i++ ) {
					if ( insertIndex >= 0 ) {
						insertOptions.at = insertIndex++;
					}
					$e.run( "document/elements/create", {
						container: elementor.getPreviewContainer(),
						model: content[ i ],
						options: insertOptions
					} );
				}
				$e.internal( "document/history/end-log", {
					id: historyId
				} );
			} else {
				var model = new Backbone.Model( {
					getTitle: function() {
						return TRX_ADDONS_STORAGE['msg_elementor_templates_library_title']
					}
				} );
				elementor.channels.data.trigger( "template:before:insert", model );
				for ( var _i = 0; _i < json.data.content.length; _i++ ) {
					elementor.getPreviewView().addChildElement( content[ _i ], insertIndex >= 0 ? { at: insertIndex++ } : null );
				}
				elementor.channels.data.trigger( "template:after:insert", {} )
			}
		}

		function showMessage( message ) {
			if ( typeof trx_addons_msgbox_warning == 'function' ) {
				trx_addons_msgbox_warning( message, TRX_ADDONS_STORAGE['msg_elementor_templates_library_title'] );
			} else {
				alert( message );
			}
		}

		function getLayoutsSimilarity( layout1, layout2, accuracy ) {
			var result = {
				similar: false,
				similarity: 0
			};
			// If any layout is empty ot undefined, they are not similar
			if ( ! layout1 || ! layout2 ) {
				return result;
			}
			// If layouts are identical, they are similar
			if ( layout1 == layout2 ) {
				result.similar = true;
				result.similarity = 100;
				return result;
			}
			// If accuracy is 100%, layouts must be identical (on the previous check)
			if ( accuracy >= 100 ) {
				return result;
			}
			// If accuracy is 0%, layouts are always similar
			if ( accuracy <= 0 ) {
				result.similar = true;
				return result;
			}

			// Parse layouts into structured objects
			const sections1 = parseLayout( layout1 );
			const sections2 = parseLayout( layout2 );
			const bigger = sections1.length > sections2.length ? sections1 : sections2;
			const smaller = sections1.length > sections2.length ? sections2 : sections1;
			
			// Check if the number of sections is the same
			if ( accuracy >= 80 && sections1.length !== sections2.length ) {
				return result;
			}
			
			// Compare each section
			let sections_result;
			for ( let i = 0; i < smaller.length; i++ ) {
				let found = false;
				for ( let j = 0; j < bigger.length; j++ ) {
					sections_result = getSectionsSimilarity( smaller[i], bigger[j], accuracy );
					if ( sections_result.similar ) {
						found = true;
						result.similarity += sections_result.similarity;
						break;
					}
				}
				if ( ! found ) {
					return result;
				}
			}
			
			result.similar = true;
			result.similarity = result.similarity / bigger.length;
			return result;
		}

		function parseLayout( layoutStr ) {
			// Split layout into sections by '|'
			const sectionStrings = layoutStr.split('|');
			
			return sectionStrings.map( sectionStr => {
				const section = {
					layout: '',
					original: sectionStr,
					is_repeated: true,
					columns: []
				};
				
				// Split section into parts by ';col' (was by ';' and then check key starts with 'col', but can be broken by extra ';' in the value)
				const parts = sectionStr.split(';col');
				let last_value = '';
				
				parts.forEach( part => {
					let [key, value] = part.split(':');
					if ( key === 'layout' ) {
						section.layout = value;
					} else if ( parseInt( key, 10 ) > 0 && value !== undefined && value !== '' && value !== 'empty' ) {	//key.startsWith('col')
						value = value.trim().replace( ';', ',' );	// Fix for possible extra ';' in the value
						if ( last_value && value != last_value ) {
							section.is_repeated = false;
						}
						last_value = value;
						// Split column blocks by ','
						const blocks = value.split( ',' )
											.map( block => block.trim() )
											.filter( block => block !== '' && ['accordion', 'audio', 'button', 'empty', 'form', 'icon', 'image', 'link', 'logo', 'menu', 'subtitle', 'text', 'title', 'video', 'vmenu'].indexOf( block ) >= 0 );
						// Add a new column with its blocks or merge with the existing one if a column number is less than current columns count
						const col_idx = parseInt( key.slice(3) ) - 1;
						if ( section.columns.length > col_idx ) {
							section.columns[ col_idx ] = section.columns[ col_idx ].concat( blocks );
						} else {
							section.columns.push( blocks );
						}
					}
				} );

				if ( section.columns.length == 1 ) {
					section.is_repeated = false;
				}
				
				return section;
			} );
		}

		function getSectionsSimilarity( section1, section2, accuracy ) {
			var result = {
				similar: false,
				similarity: 0
			};
			// Check same layout repeated status
			if ( section1.is_repeated !== section2.is_repeated ) {
				return result;
			}

			// Check same number of columns (allow two columns difference for repeated layouts)
			if ( Math.abs( section1.columns.length - section2.columns.length ) > ( section1.is_repeated ? 2 : 0 ) ) {
				return result;
			}

			// Compare each column
			let similarity = 0,
				columns_min = Math.min( section1.columns.length, section2.columns.length ),
				columns_max = Math.min( section1.columns.length, section2.columns.length ),
				match = 0;
			for ( let i = 0; i < columns_min; i++ ) {
				similarity = getColumnsSimilarity( section1.columns[i], section2.columns[i] );
				result.similarity += similarity;
				if ( similarity == 0 ) {
					return result;
				} else if ( similarity >= accuracy ) {
					match++;
				}
			}
			result.similar = match >= columns_min / 2;
			result.similarity = result.similarity / columns_max;
			return result;
		}

		function areColumnsSimilar( col1, col2, accuracy ) {
			return getColumnsSimilarity( col1, col2 ) >= accuracy;
		}

		function getColumnsSimilarity( col1, col2 ) {
			const maxLength = Math.max( col1.length, col2.length );
			
			// If both columns are empty, consider them similar
			if ( maxLength === 0 ) {
				return true;
			}
			
			let matchCount = 0;
			
			// Compare blocks in the columns
			for ( let i = 0, j = 0; i < col1.length && j < col2.length; i++, j++ ) {
				if ( col1[i] === col2[j] ) {
					matchCount++;
				} else if ( ['logo', 'image', 'icon'].indexOf( col1[i] ) >= 0 && ['logo', 'image', 'icon'].indexOf( col2[j] ) >= 0 ) {				// Treat 'logo', 'image' and 'icon' as similar
					matchCount++;
				} else if ( ['subtitle', 'link', 'button'].indexOf( col1[i] ) >= 0 && ['subtitle', 'link', 'button'].indexOf( col2[j] ) >= 0 ) {	// Treat 'subtitle', 'link' and 'button' as similar
					matchCount++;
				} else if ( ['subtitle', 'link', 'button'].indexOf( col1[i] ) >= 0 && col2[j] == 'title' && col1.length > i + 1 && col1[i + 1] == col2[j] ) {	// Skip 'subtitle'/'button' block in the first column
					i++;
					matchCount++;
				} else if ( ['subtitle', 'link', 'button'].indexOf( col2[i] ) >= 0 && col1[j] == 'title' && col2.length > j + 1 && col1[i] == col2[j + 1] ) {	// Skip 'subtitle'/'button' block in the second column
					j++;
					matchCount++;
				} else if ( ['subtitle', 'title', 'text'].indexOf( col1[i] ) >= 0 && ['subtitle', 'title', 'text'].indexOf( col2[j] ) >= 0 ) {		// Treat 'subtitle', 'title' and 'text' as similar
					matchCount++;
				}
			}
			
			// Calculate similarity percentage
			const similarityPercent = ( matchCount / maxLength ) * 100;

			return similarityPercent;
		}

		window.trx_addons_elementor_templates_library.modal = null;
		window.trx_addons_elementor_templates_library.preview = null;


		// Add the button to the Elementor editor and animate it
		//--------------------------------------------------------
		const template = jQuery( '#tmpl-elementor-add-section' );

		if ( template.length && typeof elementor !== undefined) {
			// Add the button to the Elementor editor
			var text = template.text();

			text = text.replace(
				'<div class="elementor-add-section-drag-title',
				'<div class="elementor-add-section-area-button elementor-add-trx-addons-elementor-templates-library-button" title="' + TRX_ADDONS_STORAGE['msg_elementor_templates_library_button_title'] + '">'
					// + '<i class="eicon-posts-justified"></i>'
				+ '</div>'
				+ '<div class="elementor-add-section-drag-title'
			);

			template.text( text );

			elementor.on( 'preview:loaded', function() {
				jQuery( elementor.$previewContents[0].body ).on( 'click', '.elementor-add-trx-addons-elementor-templates-library-button', templatesLibrary );
			} );

			// Animate the button
			function animateButton(button) {
				setTimeout(() => {
					button.classList.add( 'sonar' );
					setTimeout(() => {
						button.classList.remove( 'sonar' );
					}, 6000);		// Remove animation after 6 seconds (3 cycles of 2 seconds)
				}, 500);			// Start animation after 500ms (after end scrolling)
			}

			var hasAnimated = false;

			var observerOptions = {
				root: null,
				rootMargin: '-200px',
				threshold: 0.1
			};

			var observer = new IntersectionObserver( ( entries ) => {
				entries.forEach( entry => {
					if ( entry.isIntersecting && ! hasAnimated ) {
						animateButton( entry.target );
						hasAnimated = true;
						observer.unobserve( entry.target );
					}
				} );
			}, observerOptions);

			elementor.on( 'frontend:init', function() {
				setTimeout( function() {
					var button = jQuery( elementor.$previewContents[0].body ).find( '.elementor-add-trx-addons-elementor-templates-library-button' );
					if ( button.length ) {
						observer.observe( button.get(0) );
					}
				}, 10000 );
			} );
		}
	} );
} )();
