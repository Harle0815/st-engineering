<?php
/**
 * Required plugins
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.76.0
 */

// THEME-SUPPORTED PLUGINS
// If plugin not need - remove its settings from next array
//----------------------------------------------------------
if ( ! function_exists( 'elementra_skin_required_plugins' ) ) {
	add_action( 'after_setup_theme', 'elementra_skin_required_plugins', -1 );
	function elementra_skin_required_plugins() {
		$elementra_theme_required_plugins_groups = array(
			'core'          => esc_html__( 'Core', 'elementra' ),
			'page_builders' => esc_html__( 'Page Builders', 'elementra' ),
			'ecommerce'     => esc_html__( 'E-Commerce & Donations', 'elementra' ),
			'socials'       => esc_html__( 'Socials and Communities', 'elementra' ),
			'events'        => esc_html__( 'Events and Appointments', 'elementra' ),
			'content'       => esc_html__( 'Content', 'elementra' ),
			'other'         => esc_html__( 'Other', 'elementra' ),
		);
		$elementra_theme_required_plugins        = array(
			// Core
			'trx_addons'                 => array(
				'title'       => esc_html__( 'ThemeREX Addons', 'elementra' ),
				'description' => esc_html__( "Will allow you to install recommended plugins, demo content, and improve the theme's functionality overall with multiple theme options", 'elementra' ),
				'required'    => true, // Check this plugin in the list on load Theme Dashboard
				'logo'        => 'trx_addons.png',
				'group'       => $elementra_theme_required_plugins_groups['core'],
			),
			// Page Builders
			'elementor'                  => array(
				'title'       => esc_html__( 'Elementor', 'elementra' ),
				'description' => esc_html__( "Is a beautiful PageBuilder, even the free version of which allows you to create great pages using a variety of modules.", 'elementra' ),
				'required'    => false, // Leave this plugin unchecked on load Theme Dashboard
				'logo'        => 'elementor.png',
				'group'       => $elementra_theme_required_plugins_groups['page_builders'],
			),
			'gutenberg'                  => array(
				'title'       => esc_html__( 'Gutenberg', 'elementra' ),
				'description' => esc_html__( "It's a posts editor coming in place of the classic TinyMCE. Can be installed and used in parallel with Elementor", 'elementra' ),
				'required'    => false,
				'install'     => false, // Do not offer installation of the plugin in the Theme Dashboard and TGMPA
				'logo'        => 'gutenberg.png',
				'group'       => $elementra_theme_required_plugins_groups['page_builders'],
			),
			// Content
			'sitepress-multilingual-cms' => array(
				'title'       => esc_html__( 'WPML - Sitepress Multilingual CMS', 'elementra' ),
				'description' => esc_html__( "Allows you to make your website multilingual", 'elementra' ),
				'required'    => false,
				'install'     => false, // Do not offer installation of the plugin in the Theme Dashboard and TGMPA
				'logo'        => 'sitepress-multilingual-cms.png',
				'group'       => $elementra_theme_required_plugins_groups['content'],
			),
			'metform'                    => array(
				'title'       => esc_html__( 'MetForm', 'elementra' ),
				'description' => esc_html__( "Contact Form, Survey, Quiz, & Custom Form Builder for Elementor", 'elementra' ),
				'required'    => false,
				'logo'        => 'metform.png',
				'group'       => $elementra_theme_required_plugins_groups['content'],
			),
			// Other
			'trx_updater'                => array(
				'title'       => esc_html__( 'ThemeREX Updater', 'elementra' ),
				'description' => esc_html__( "Update theme and theme-specific plugins from developer's upgrade server.", 'elementra' ),
				'required'    => false,
				'logo'        => 'trx_updater.png',
				'group'       => $elementra_theme_required_plugins_groups['other'],
			)
		);

		if ( ELEMENTRA_THEME_FREE ) {
			unset( $elementra_theme_required_plugins['sitepress-multilingual-cms'] );
			unset( $elementra_theme_required_plugins['trx_updater'] );
		}

		// Add plugins list to the global storage
		elementra_storage_set( 'required_plugins', $elementra_theme_required_plugins );
	}
}
