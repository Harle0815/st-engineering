<?php
/**
 * Gutenberg Full-Site Editor (FSE) support functions.
 */

if ( ! defined( 'ELEMENTRA_FSE_TEMPLATE_PART_PT' ) ) define( 'ELEMENTRA_FSE_TEMPLATE_PART_PT', 'wp_template_part' );

if ( ! defined( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_HEADER' ) )  define( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_HEADER',  defined( 'WP_TEMPLATE_PART_AREA_HEADER' )  ? WP_TEMPLATE_PART_AREA_HEADER  : 'header' );
if ( ! defined( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_FOOTER' ) )  define( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_FOOTER',  defined( 'WP_TEMPLATE_PART_AREA_FOOTER' )  ? WP_TEMPLATE_PART_AREA_FOOTER  : 'footer' );
if ( ! defined( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_SIDEBAR' ) ) define( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_SIDEBAR', defined( 'WP_TEMPLATE_PART_AREA_SIDEBAR' ) ? WP_TEMPLATE_PART_AREA_SIDEBAR : 'sidebar' );
if ( ! defined( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_UNCATEGORIZED' ) ) define( 'ELEMENTRA_FSE_TEMPLATE_PART_AREA_UNCATEGORIZED', defined( 'WP_TEMPLATE_PART_AREA_UNCATEGORIZED' ) ? WP_TEMPLATE_PART_AREA_UNCATEGORIZED : 'uncategorized' );

// Load additional files with FSE support
require_once elementra_get_file_dir( 'plugins/gutenberg/gutenberg-fse-lists.php' );
require_once elementra_get_file_dir( 'plugins/gutenberg/gutenberg-fse-templates.php' );


if ( ! function_exists( 'elementra_gutenberg_fse_loader' ) ) {
	add_action( 'wp_loaded', 'elementra_gutenberg_fse_loader', 1 );
	/**
	 * Turn off a Gutenberg (plugin) templates on frontend
	 * if a theme-specific frontend editor is active.
	 */
	function elementra_gutenberg_fse_loader() {
		// 
		if ( elementra_exists_gutenberg_fse()
			&& get_option( 'show_on_front' ) == 'page'
			&& (int) get_option('page_on_front') > 0
			&& elementra_is_on( elementra_get_theme_option( 'front_page_enabled', false ) )
			&& elementra_get_current_url() == '/'
		) {
		 	remove_action( 'wp_loaded', 'gutenberg_add_template_loader_filters' );
		}
	}
}

if ( ! function_exists( 'elementra_gutenberg_is_fse_wp_support' ) ) {
	/**
	 * Check if WordPress with FSE support installed
	 * (WordPress 5.9+ Site Editor is present).
	 */
	function elementra_gutenberg_is_fse_wp_support() {
		return function_exists( 'wp_is_block_theme' );
	}
}

if ( ! function_exists( 'elementra_gutenberg_is_fse_gb_support' ) ) {
	/**
	 * Check if a plugin "Gutenberg" with FSE support is installed.
	 */
	function elementra_gutenberg_is_fse_gb_support() {
		return function_exists( 'gutenberg_is_fse_theme' );
	}
}

if ( ! function_exists( 'elementra_exists_gutenberg_fse' ) ) {
	/**
	 * Check if FSE is available
	 * (WordPress 5.9+ or the plugin "Gutenberg" are installed).
	 */
	function elementra_exists_gutenberg_fse() {
		return elementra_exists_gutenberg() && ( elementra_gutenberg_is_fse_wp_support() || elementra_gutenberg_is_fse_gb_support() );
	}
}

if ( ! function_exists( 'elementra_gutenberg_is_fse_theme' ) ) {
	/**
	 * Check if the current theme supports a FSE behaviour.
	 */
	function elementra_gutenberg_is_fse_theme() {
		static $fse = -1;
		if ( $fse == -1 ) {
			if ( elementra_gutenberg_is_fse_wp_support() ) {                                    // WordPress 5.9+ FSE
				$fse = (bool) wp_is_block_theme();
			} else if ( elementra_gutenberg_is_fse_gb_support() ) {                             // Plugin Gutenberg FSE
				$fse = (bool) gutenberg_is_fse_theme();
			} else {
				$fse = is_readable( ELEMENTRA_THEME_DIR . 'templates/index.html' )              // WordPress 5.9+ FSE
						|| ( ELEMENTRA_THEME_DIR != ELEMENTRA_CHILD_DIR && is_readable( ELEMENTRA_CHILD_DIR . 'templates/index.html' ) )
						|| is_readable( ELEMENTRA_THEME_DIR . 'block-templates/index.html' )    // Plugin Gutenberg FSE
						|| ( ELEMENTRA_THEME_DIR != ELEMENTRA_CHILD_DIR && is_readable( ELEMENTRA_CHILD_DIR . 'block-templates/index.html' ) );
			}
		}
		return $fse;
	}
}

if ( ! function_exists( 'elementra_gutenberg_is_fse_enabled' ) ) {
	/**
	 * Check if the current theme supports FSE behaviour and FSE is installed and activated
	 */
	function elementra_gutenberg_is_fse_enabled() {
		return elementra_exists_gutenberg_fse() && elementra_gutenberg_is_fse_theme();
	}
}

if ( ! function_exists( 'elementra_gutenberg_fse_skin_updated' ) ) {
	add_action( 'elementra_action_theme_updated', 'elementra_gutenberg_fse_skin_updated' );
	add_action( 'elementra_action_skin_updated', 'elementra_gutenberg_fse_skin_updated', 10, 1 );
	/**
	 * Copy FSE-required folders from an updated skin's subfolder '_fse' to the theme's root folder.
	 * 
	 * @hooked 'elementra_action_theme_updated'
	 * @hooked 'elementra_action_skin_updated'
	 */
	function elementra_gutenberg_fse_skin_updated( $skin = '' ) {
		// Get an active skin name if empty
		if ( current_action() == 'elementra_action_theme_updated' && empty( $skin ) ) {
			$skin = elementra_skins_get_active_skin_name();
		}
		if ( elementra_skins_get_current_skin_name() == $skin ) {
			elementra_gutenberg_fse_copy_from_skin( $skin );
			elementra_gutenberg_fse_update_theme_json( $skin );
		}
	}
}

if ( ! function_exists( 'elementra_gutenberg_fse_copy_from_skin' ) ) {
	add_action( 'elementra_action_skin_switched', 'elementra_gutenberg_fse_copy_from_skin', 10, 2 );
	/**
	 * Copy FSE-required folders from a new skin's subfolder '_fse' to the theme's root folder.
	 * 
	 * @hooked 'elementra_action_skin_switched'
	 */
	function elementra_gutenberg_fse_copy_from_skin( $new_skin, $old_skin = '' ) {
		$theme_templates_dir = elementra_prepare_path( ELEMENTRA_THEME_DIR . ( elementra_gutenberg_is_fse_wp_support() ? 'templates/' : 'block-templates/' ) );
		$theme_template_parts_dir = elementra_prepare_path( ELEMENTRA_THEME_DIR . ( elementra_gutenberg_is_fse_wp_support() ? 'parts/' : 'block-template-parts/' ) );
		$theme_json = elementra_prepare_path( ELEMENTRA_THEME_DIR . 'theme.json' );
		$skin_templates_dir = elementra_prepare_path( ELEMENTRA_THEME_DIR . 'skins/' . $new_skin . '/_fse' . ( elementra_gutenberg_is_fse_wp_support() ? '/templates/' : '/block-templates/' ) );
		$skin_template_parts_dir = elementra_prepare_path( ELEMENTRA_THEME_DIR . 'skins/' . $new_skin . '/_fse' . ( elementra_gutenberg_is_fse_wp_support() ? '/parts/' : '/block-template-parts/' ) );
		// Remove old templates from the stylesheet dir (if exists)
		if ( is_dir( $theme_templates_dir ) ) {
			elementra_unlink( $theme_templates_dir );
		}
		if ( is_dir( $theme_template_parts_dir ) ) {
			elementra_unlink( $theme_template_parts_dir );
		}
		if ( file_exists( $theme_json ) ) {
			elementra_unlink( $theme_json );
		}
		// If a new skin is a FSE compatible - copy two folders with block templates and a file 'theme.json'
		// from a new skin's subfolder '_fse' to the theme's root folder
		if ( is_dir( $skin_templates_dir ) ) {
			elementra_copy( $skin_templates_dir, $theme_templates_dir );
		}
		if ( is_dir( $skin_template_parts_dir ) ) {
			elementra_copy( $skin_template_parts_dir, $theme_template_parts_dir );
		}
	}
}

if ( ! function_exists( 'elementra_gutenberg_fse_update_theme_json' ) ) {
	add_action( 'elementra_action_skin_switched', 'elementra_gutenberg_fse_update_theme_json', 30, 2 );
	add_action( 'elementra_action_save_options', 'elementra_gutenberg_fse_update_theme_json', 30 );
	add_action( 'trx_addons_action_save_options', 'elementra_gutenberg_fse_update_theme_json', 30 );
	/**
	 * Update the file 'theme.json' after the current skin is switched and/or theme options are saved.
	 * 
	 * @hooked 'elementra_action_skin_switched', 30
	 * @hooked 'elementra_action_save_options', 30
	 * @hooked 'trx_addons_action_save_options', 30
	 * 
	 * Trigger filter 'elementra_filter_gutenberg_fse_theme_json_data' to allow other modules modify this data before saving.
	 */
	function elementra_gutenberg_fse_update_theme_json( $new_skin = '', $old_skin = '' ) {
		// Get an active skin name if empty
		if ( in_array( current_action(), array( 'elementra_action_save_options', 'trx_addons_action_save_options' ) ) && empty( $new_skin ) ) {
			$new_skin = elementra_skins_get_active_skin_name();
		}
		// If the current skin supports FSE
		if ( ! empty( $new_skin ) ) {	// && is_dir( ELEMENTRA_THEME_DIR . elementra_skins_get_current_skin_dir( $new_skin ) . '_fse' ) ) {
			$theme_json_data = elementra_gutenberg_fse_theme_json_data();
			if ( ! empty( $theme_json_data ) && is_array( $theme_json_data ) ) {
				elementra_fpc( elementra_prepare_path( ELEMENTRA_THEME_DIR . 'theme.json' ), json_encode( $theme_json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			}
		}
	}
}

if ( ! function_exists( 'elementra_gutenberg_fse_theme_json_data' ) ) {
	/**
	 * Return a default data to save to the file 'theme.json'
	 * 
	 * Trigger filter 'elementra_filter_gutenberg_fse_theme_json_data' to allow other modules change data.
	 * 
	 * @return array  Data for the 'theme.json'
	 */
	function elementra_gutenberg_fse_theme_json_data() {

		$data = array(

					"\$schema" => "https://schemas.wp.org/trunk/theme.json",

					"version" => 2,
					
					// General settings
					"settings" => array(

						"appearanceTools" => true,

						"spacing" => array(
							"units" => array( "%", "px", "em", "rem", "vh", "vw" ),
							"blockGap" => null,
						),

						// Theme colors
						"color" => array(
							"palette"   => array(),		// Should be added later from a color scheme
							"duotone"   => array(),		// Should be added later from a color scheme
							"gradients" => array(),		// Should be added later from a color scheme
						),

						// Typography
						"typography" => array(
							"fluid"   =>  true,
							"dropCap" => true,
						),

						// Layout dimensions
						// Should be added later from a theme vars
						"layout" => array(),

						// Custom styles - can be used below as CSS-vars.
						"custom" => array(
							// Spacing - can be used as a value for a padding or a margin
							// For example: var(--wp--custom--spacing--xxx), where 'xxx' is a key of the array 'spacing'
							"spacing" => array(
								"tiny"   => "var(--sc-space-tiny,   1rem)",      //"max( 1rem, 3vw )",
								"small"  => "var(--sc-space-small,  2rem)",      //"max( 1.25rem, 5vw )",
								"medium" => "var(--sc-space-medium, 3.3333rem)", //"clamp( 2rem, 8vw, calc( 4 * var(--wp--style--block-gap) ) )",
								"large"  => "var(--sc-space-large,  6.6667rem)", //"clamp( 4rem, 10vw, 8rem )",
								"huge"   => "var(--sc-space-huge,   8.6667rem)",
							),
						),
					),

					// General styles
					"styles" => array(
						// Gap between blocks
						"spacing" => array(
							"blockGap" => 0,
						),
						// Core blocks decoration
						"blocks" => array(
							"core/button" => array(
								"border" => array(
									"radius" => "0",
								),
								"color" => array(
									"background" => "var(--theme-color-text_link)",
									"text"       => "var(--theme-color-inverse_link)",
								),
								"typography" => array(
									"fontFamily" => "var(--theme-font-button_font-family)",
									"fontWeight" => "var(--theme-font-button_font-weight)",
									"fontSize"   => "var(--theme-font-button_font-size)",
									"lineHeight" => "var(--theme-font-button_line-height)",
								),
							),
							"core/post-comments" => array(
								"spacing" => array(
									"padding" => array(
										"top" => "var(--wp--custom--spacing--small)",
									),
								),
							),
							"core/pullquote" => array(
								"border" => array(
									"width" => "1px 0",
								),
							),
							"core/quote" => array(
								"border" => array(
									"width" => "1px",
								),
							),
						),
					),
				);

		// Add palette: all colors from the scheme 'default'
		$scheme = elementra_get_scheme_colors();
		$groups = elementra_storage_get( 'scheme_color_groups' );
		$names  = elementra_storage_get( 'scheme_color_names' );
		$prefix = apply_filters( 'elementra_filter_gutenberg_fse_theme_json_data_add_scheme_color_prefix', false ) ? 'theme-color-' : '';
		foreach( $groups as $g => $group ) {
			foreach( $names as $n => $name ) {
				$c = 'main' == $g ? ( 'text' == $n ? 'text_color' : $n ) : $g . '_' . str_replace( 'text_', '', $n );
				if ( isset( $scheme[ $c ] ) ) {
					$data['settings']['color']['palette'][] = array(
						'slug'  => $prefix . preg_replace( '/([a-z])([0-9])+/', '$1-$2', str_replace( '_', '-', $c ) ),
						'name'  => ( 'main' == $g ? '' : $group['title'] . ' ' ) . $name['title'],
						'color' => $scheme[ $c ],
					);
				}
			}
			// Add only one group of colors
			// Delete next condition (or add false && to them) to add all groups
			if ( 'main' == $g ) {
				break;
			}
		}

		// Add duotones (a two colors combination)
		if ( ! empty( $scheme[ elementra_get_scheme_color_name( 'bg_color' ) ] ) && ! empty( $scheme[ elementra_get_scheme_color_name( 'text' ) ] ) ) {
			$data['settings']['color']['duotone'][] = array(
				"colors" => array( $scheme[ elementra_get_scheme_color_name( 'bg_color' ) ], $scheme[ elementra_get_scheme_color_name( 'text' ) ] ),
				"slug"   => 'bg-and-text',
				"name"   => esc_html__( 'Background and text color', 'elementra' )
			);
		}
		if ( ! empty( $scheme[ elementra_get_scheme_color_name( 'bg_color' ) ] ) && ! empty( $scheme[ elementra_get_scheme_color_name( 'text_dark' ) ] ) ) {
			$data['settings']['color']['duotone'][] = array(
				"colors" => array( $scheme[ elementra_get_scheme_color_name( 'bg_color' ) ], $scheme[ elementra_get_scheme_color_name( 'text_dark' ) ] ),
				"slug"   => 'bg-and-dark',
				"name"   => esc_html__( 'Background and dark color', 'elementra' )
			);
		}
		if ( ! empty( $scheme[ elementra_get_scheme_color_name( 'bg_color' ) ] ) && ! empty( $scheme[ elementra_get_scheme_color_name( 'text_link' ) ] ) ) {
			$data['settings']['color']['duotone'][] = array(
				"colors" => array( $scheme[ elementra_get_scheme_color_name( 'bg_color' ) ], $scheme[ elementra_get_scheme_color_name( 'text_link' ) ] ),
				"slug"   => 'bg-and-link',
				"name"   => esc_html__( 'Background and link color', 'elementra' )
			);
		}

		// Add gradients
		if ( ! empty( $scheme[ elementra_get_scheme_color_name( 'text_hover' ) ] ) && ! empty( $scheme[ elementra_get_scheme_color_name( 'text_link' ) ] ) ) {
			$data['settings']['color']['gradients'][] = array(
				"slug"     => 'vertical-link-to-hover',
				"gradient" => 'linear-gradient(to bottom,var(--theme-color-' . elementra_get_scheme_color_name( 'text_link' ) . ') 0%,var(--theme-color-' . elementra_get_scheme_color_name( 'text_hover' ) . ') 100%)',
				"name"     => esc_html__( 'Vertical from link color to hover color', 'elementra' ),
			);
			$data['settings']['color']['gradients'][] = array(
				"slug"     => 'diagonal-link-to-hover',
				"gradient" => 'linear-gradient(to bottom right,var(--theme-color-' . elementra_get_scheme_color_name( 'text_link' ) . ') 0%,var(--theme-color-' . elementra_get_scheme_color_name( 'text_hover' ) . ') 100%)',
				"name"     => esc_html__( 'Diagonal from link color to hover color', 'elementra' ),
			);
		}

		// Add fonts
		$fonts = elementra_get_theme_fonts();
		$added = array();
		foreach( $fonts as $tag => $font ) {
			if ( ! in_array( $font['font-family'], $added ) ) {
				$added[] = $font['font-family'];
				$data['settings']['typography']['fontFamilies'][] = array(
					"fontFamily" => $font['font-family'],
					"name"       => $font['font-family'], //$font['title'],
					"slug"       => "{$tag}-font",
				);
			}
		}

		// Layout dimensions
		$vars = elementra_get_theme_vars();
		$data['settings']['layout']['contentSize'] = ( $vars['page_width'] - $vars['sidebar_width'] - $vars['sidebar_gap'] ) . 'px';
		$data['settings']['layout']['wideSize']    = $vars['page_width'] . 'px';

		// Add templates for FSE themes
		if ( elementra_gutenberg_is_fse_theme() ) {
			// Theme-specific templates for posts, pages, etc.
			$data['customTemplates'] = array(
				array(
					"name"      => "blog",
					"title"     => esc_html__( "Blog archive", 'elementra' ),
					"postTypes" => array(
						"page"
					)
				)
			);

			// Theme-specific template parts
			$data['templateParts'] = array(
				array(
					"name"  => "header",
					"title" => esc_html__( "Header for FSE", 'elementra' ),
					"area"  => ELEMENTRA_FSE_TEMPLATE_PART_AREA_HEADER,
				),
				array(
					"name"  => "sidebar",
					"title" => esc_html__( "Sidebar for FSE", 'elementra' ),
					"area"  => ELEMENTRA_FSE_TEMPLATE_PART_AREA_SIDEBAR,
				),
				array(
					"name"  => "content-404",
					"title" => esc_html__( "Page 404 content", 'elementra' ),
					"area"  => WP_TEMPLATE_PART_AREA_UNCATEGORIZED,
				),
				array(
					"name"  => "none-archive",
					"title" => esc_html__( "No posts found", 'elementra' ),
					"area"  => WP_TEMPLATE_PART_AREA_UNCATEGORIZED,
				),
				array(
					"name"  => "none-search",
					"title" => esc_html__( "Nothing matched search", 'elementra' ),
					"area"  => WP_TEMPLATE_PART_AREA_UNCATEGORIZED,
				),
				array(
					"name"  => "blog-post-standard",
					"title" => esc_html__( "Blog post item: Standard", 'elementra' ),
					"area"  => WP_TEMPLATE_PART_AREA_UNCATEGORIZED,
				),
				array(
					"name"  => "blog-post-header",
					"title" => esc_html__( "Blog post header", 'elementra' ),
					"area"  => WP_TEMPLATE_PART_AREA_UNCATEGORIZED,
				),
				array(
					"name"  => "blog-pagination",
					"title" => esc_html__( "Blog pagination", 'elementra' ),
					"area"  => WP_TEMPLATE_PART_AREA_UNCATEGORIZED,
				),
				array(
					"name"  => "footer",
					"title" => esc_html__( "Footer for FSE", 'elementra' ),
					"area"  => WP_TEMPLATE_PART_AREA_FOOTER,
				),
			);
		}

		return apply_filters( 'elementra_filter_gutenberg_fse_theme_json_data', $data );
	}
}
