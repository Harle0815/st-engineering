<?php
/**
 * Admin utilities
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.1
 */

// Disable direct call
if ( ! defined( 'ABSPATH' ) ) {
	exit; }


//-------------------------------------------------------
//-- Theme init
//-------------------------------------------------------

// Theme init priorities:
// 1 - register filters to add/remove lists items in the Theme Options
// 2 - create Theme Options
// 3 - add/remove Theme Options elements
// 5 - load Theme Options
// 9 - register other filters (for installer, etc.)
//10 - standard Theme init procedures (not ordered)

if ( ! function_exists( 'elementra_admin_theme_setup' ) ) {
	add_action( 'after_setup_theme', 'elementra_admin_theme_setup' );
	function elementra_admin_theme_setup() {
		// Add theme icons
		add_action( 'admin_footer', 'elementra_admin_footer' );

		// Enqueue scripts and styles for admin
		add_action( 'admin_enqueue_scripts', 'elementra_admin_scripts' );
		add_action( 'admin_footer', 'elementra_admin_localize_scripts' );

		// Show admin notice with control panel
		add_action( 'admin_notices', 'elementra_admin_notice' );
		add_action( 'wp_ajax_elementra_hide_admin_notice', 'elementra_callback_hide_admin_notice' );

		// Show admin notice with "Rate Us" panel
		add_action( 'admin_notices', 'elementra_rate_notice' );
		add_action( 'wp_ajax_elementra_hide_rate_notice', 'elementra_callback_hide_rate_notice' );

		// After switch or update theme
		add_action( 'after_switch_theme', 'elementra_save_activation_date' );
		add_action( 'after_switch_theme', 'elementra_regenerate_merged_files' );
		add_action( 'admin_init', 'elementra_check_theme_version' );

		// TGM Activation plugin
		add_action( 'tgmpa_register', 'elementra_register_plugins' );

		// Init internal admin messages
		elementra_init_admin_messages();
	}
}


//-------------------------------------------------------
//-- After switch theme
//-------------------------------------------------------

if ( ! function_exists( 'elementra_save_activation_date' ) ) {
	/**
	 * Save the date with the theme activation
	 * 
	 * @hooked 'after_switch_theme'
	 */
	function elementra_save_activation_date() {
		$theme_time = (int) get_option( 'elementra_theme_activated' );
		if ( 0 == $theme_time ) {
			$theme_slug      = get_template();
			$stylesheet_slug = get_stylesheet();
			if ( $theme_slug == $stylesheet_slug ) {
				update_option( 'elementra_theme_activated', time() );
			}
		}
	}
}

if ( ! function_exists( 'elementra_regenerate_merged_files' ) ) {
	/**
	 * Regenerate merged files with styles and scripts after the current theme is switched
	 * 
	 * @hooked 'after_switch_theme'
	 */
	function elementra_regenerate_merged_files() {
		// Set a flag to regenerate styles and scripts on first run
		if ( apply_filters( 'elementra_filter_regenerate_merged_files_after_switch_theme', true ) ) {
			elementra_set_action_save_options();
		}
	}
}

if ( ! function_exists( 'elementra_check_theme_version' ) ) {
	/** 
	 * Regenerate merged files with styles and scripts after the current theme is updated
	 * 
	 * @hooked 'admin_init'
	 */
	function elementra_check_theme_version() {
		if ( ! wp_doing_ajax() ) {
			$theme_slug  = get_template();
			$theme       = wp_get_theme( $theme_slug );
			$version     = $theme->get( 'Version' );
			$cur_version = get_option( 'elementra_theme_version' );
			// If the theme was updated manually
			if ( $cur_version != $version ) {
				// Set a flag to regenerate styles and scripts on first run
				if ( apply_filters( 'elementra_filter_regenerate_merged_files_after_update_theme', true ) ) {
					elementra_set_action_save_options();
				}
				// Trigger action for a new version
				do_action( 'elementra_action_is_new_version_of_theme', $version, $cur_version );
				// Save current version
				update_option( 'elementra_theme_version', $version );
			}
		}
	}
}


//-------------------------------------------------------
//-- Welcome notice
//-------------------------------------------------------

if ( ! function_exists( 'elementra_admin_notice' ) ) {
	/**
	 * Show the admin notice with a welcome message and buttons to redirect to the Theme Options and Customizer
	 * 
	 * @hooked 'admin_notices'
	 */
	function elementra_admin_notice() {
		if ( elementra_exists_trx_addons()
			|| in_array( elementra_get_value_gp( 'action' ), array( 'vc_load_template_preview' ) )
			|| elementra_get_value_gp( 'page' ) == 'elementra_about'
			|| ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		if ( get_transient( 'elementra_hide_notice_admin' ) ) {
			return;
		}
		get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/admin-notice' ) );
	}
}

if ( ! function_exists( 'elementra_callback_hide_admin_notice' ) ) {
	/**
	 * Hide the admin notice for a week
	 * 
	 * @hooked 'wp_ajax_elementra_hide_admin_notice'
	 */
	function elementra_callback_hide_admin_notice() {
		elementra_verify_nonce();
		set_transient( 'elementra_hide_notice_admin', true, 7 * 24 * 60 * 60 );	// 7 days
		elementra_exit();
	}
}


//-------------------------------------------------------
//-- "Rate Us" notice
//-------------------------------------------------------

if ( ! function_exists( 'elementra_rate_notice' ) ) {
	/**
	 * Show "Rate Us" notice
	 * 
	 * @hooked 'admin_notices'
	 */
	function elementra_rate_notice() {
		if ( in_array( elementra_get_value_gp( 'action' ), array( 'vc_load_template_preview' ) ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		// Display the message only on specified screens
		$allowed = array( 'dashboard', 'theme_options', 'trx_addons_options' );
		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( ( is_object( $screen ) && ! empty( $screen->id ) && in_array( $screen->id, $allowed ) ) || in_array( elementra_get_value_gp( 'page' ), $allowed ) ) {
			$show  = get_option( 'elementra_rate_notice' );
			$start = get_option( 'elementra_theme_activated' );
			if ( ( false !== $show && 0 == (int) $show ) || ( $start > 0 && ( time() - $start ) / ( 24 * 3600 ) < 14 ) ) {
				return;
			}
			get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/admin-rate' ) );
		}
	}
}

if ( ! function_exists( 'elementra_callback_hide_rate_notice' ) ) {
	/**
	 * Hide the notice "Rate Us" forever
	 * 
	 * @hooked 'wp_ajax_elementra_hide_rate_notice'
	 */
	function elementra_callback_hide_rate_notice() {
		elementra_verify_nonce();
		update_option( 'elementra_rate_notice', '0' );
		elementra_exit();
	}
}


//-------------------------------------------------------
//-- Internal messages
//-------------------------------------------------------

if ( ! function_exists( 'elementra_init_admin_messages' ) ) {
	/**
	 * Init the internal admin messages system
	 */
	function elementra_init_admin_messages() {
		$msg = get_transient( 'elementra_admin_messages' );
		if ( is_array( $msg ) ) {
			delete_transient( 'elementra_admin_messages' );
		} else {
			$msg = array();
		}
		elementra_storage_set( 'admin_messages', $msg );
	}
}

if ( ! function_exists( 'elementra_add_admin_message' ) ) {
	/**
	 * Add the internal admin message
	 * 
	 * @param string $text  The message text
	 * @param string $type  The message type: 'success', 'info', 'warning', 'error'
	 * @param bool   $cur_session  If true, the message will be added to the current session (not saved in the database)
	 */
	function elementra_add_admin_message( $text, $type = 'success', $cur_session = false ) {
		if ( ! empty( $text ) ) {
			$new_msg = array(
				'message' => $text,
				'type'    => $type,
			);
			if ( $cur_session ) {
				elementra_storage_push_array( 'admin_messages', '', $new_msg );
			} else {
				$msg = get_transient( 'elementra_admin_messages' );
				if ( ! is_array( $msg ) ) {
					$msg = array();
				}
				$msg[] = $new_msg;
				set_transient( 'elementra_admin_messages', $msg, 60 * 60 );
			}
		}
	}
}

if ( ! function_exists( 'elementra_show_admin_messages' ) ) {
	/**
	 * Show internal admin messages (if any). Show them only on the Theme Options page now.
	 */
	function elementra_show_admin_messages() {
		$msg = elementra_storage_get( 'admin_messages' );
		if ( ! is_array( $msg ) || count( $msg ) == 0 ) {
			return;
		}
		?>
		<div class="elementra_admin_messages">
			<?php
			foreach ( $msg as $m ) {
				?>
				<div class="elementra_admin_message_item <?php echo esc_attr( str_replace( 'success', 'updated', $m['type'] ) ); ?>">
					<p><?php echo wp_kses_data( $m['message'] ); ?></p>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
}


//-------------------------------------------------------
//-- Styles and scripts
//-------------------------------------------------------

if ( ! function_exists( 'elementra_admin_footer' ) ) {
	/**
	 * Add the theme icons selector support in the menu items
	 * 
	 * @hooked 'admin_footer'
	 */
	function elementra_admin_footer() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( is_object( $screen ) && 'nav-menus' == $screen->id ) {
			elementra_show_layout(
				elementra_show_custom_field(
					'elementra_icons_popup',
					array(
						'type'   => 'icons',
						'style'  => elementra_get_theme_setting( 'icons_type' ),
						'button' => false,
						'icons'  => true,
					),
					null
				)
			);
		}
	}
}

if ( ! function_exists( 'elementra_admin_scripts' ) ) {
	/**
	 * Load required styles and scripts for admin mode
	 * 
	 * @param bool $all  If true, load styles and scripts for all screens, otherwise - only for the current screen
	 * 
	 * @hooked 'admin_enqueue_scripts'
	 */
	function elementra_admin_scripts( $all = false ) {
	
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;

		// Add theme admin styles
		wp_enqueue_style( 'elementra-admin', elementra_get_file_url( 'css/admin.css' ), array(), null );

		// Load RTL styles
		if ( is_rtl() ) {
			wp_enqueue_style( 'elementra-admin-rtl', elementra_get_file_url( 'css/admin-rtl.css' ), array(), null );
		}

		// Links to selected fonts
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( $all || is_object( $screen ) ) {
			if ( $all || elementra_options_allow_override( ! empty( $screen->post_type ) ? $screen->post_type : $screen->id ) ) {
				// Load font icons
				wp_enqueue_style( 'elementra-fontello', elementra_get_file_url( 'css/font-icons/css/fontello.css' ), array(), null );
				wp_enqueue_style( 'elementra-fontello-animation', elementra_get_file_url( 'css/font-icons/css/animation.css' ), array(), null );
				// Load theme fonts
				$links = elementra_theme_fonts_links();
				if ( count( $links ) > 0 ) {
					foreach ( $links as $slug => $link ) {
						wp_enqueue_style( sprintf( 'elementra-font-%s', $slug ), $link, array(), null );
					}
				}
			} elseif ( apply_filters( 'elementra_filter_allow_theme_icons', is_customize_preview() || in_array( $screen->id, array( 'nav-menus', 'update-core', 'update-core-network' ) ), ! empty( $screen->post_type ) ? $screen->post_type : $screen->id ) ) {
				// Load font icons
				wp_enqueue_style( 'elementra-fontello', elementra_get_file_url( 'css/font-icons/css/fontello.css' ), array(), null );
				wp_enqueue_style( 'elementra-fontello-animation', elementra_get_file_url( 'css/font-icons/css/animation.css' ), array(), null );
			}
		}

		// Add theme scripts
		wp_enqueue_script( 'elementra-utils', elementra_get_file_url( 'js/utils.js' ), array( 'jquery' ), null, true );
		wp_enqueue_script( 'elementra-admin', elementra_get_file_url( 'js/admin.js' ), array( 'jquery' ), null, true );
	}
}

if ( ! function_exists( 'elementra_admin_localize_scripts' ) ) {
	/**
	 * Localize (add js=variables) the admin scripts
	 * 
	 * @hooked 'admin_footer'
	 */
	function elementra_admin_localize_scripts() {
	
		static $loaded = false;
		if ( $loaded ) {
			return;
		}
		$loaded = true;

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		wp_localize_script(
			'elementra-admin', 'ELEMENTRA_STORAGE', apply_filters(
				'elementra_filter_localize_script_admin', array(
					'admin_mode'                 => true,
					'screen_id'                  => is_object( $screen ) ? esc_attr( $screen->id ) : '',
					'user_logged_in'             => true,
					'ajax_url'                   => esc_url( admin_url( 'admin-ajax.php' ) ),
					'ajax_nonce'                 => esc_attr( wp_create_nonce( admin_url( 'admin-ajax.php' ) ) ),
					'msg_ajax_error'             => esc_html__( 'Server response error', 'elementra' ),
					'msg_icon_selector'          => esc_html__( 'Select the icon for this menu item', 'elementra' ),
					'msg_scheme_reset'           => esc_html__( 'Reset all changes of the current color scheme?', 'elementra' ),
					'msg_scheme_copy'            => esc_html__( 'Enter the name for a new color scheme', 'elementra' ),
					'msg_scheme_delete'          => esc_html__( 'Do you really want to delete the current color scheme?', 'elementra' ),
					'msg_scheme_delete_last'     => esc_html__( 'You cannot delete the last color scheme!', 'elementra' ),
					'msg_scheme_delete_internal' => esc_html__( 'You cannot delete the built-in color scheme!', 'elementra' ),
					'msg_reset'                  => esc_html__( 'Reset', 'elementra' ),
					'msg_reset_confirm'          => esc_html__( 'Are you sure you want to reset all Theme Options?', 'elementra' ),
					'msg_export'                 => esc_html__( 'Export', 'elementra' ),
					'msg_export_options'         => esc_html__( 'Copy options and save to the text file.', 'elementra' ),
					'msg_import'                 => esc_html__( 'Import', 'elementra' ),
					'msg_import_options'         => esc_html__( 'Paste previously saved options from the text file.', 'elementra' ),
					'msg_import_error'           => esc_html__( 'Error occurs while import options!', 'elementra' ),
					'msg_presets'                => esc_html__( 'Options presets', 'elementra' ),
					'msg_presets_add'            => esc_html__( 'Specify the name of a new preset:', 'elementra' ),
					'msg_presets_apply'          => esc_html__( 'Apply the selected preset?', 'elementra' ),
					'msg_presets_delete'         => esc_html__( 'Delete the selected preset?', 'elementra' ),
					'msg_exit_not_saved_options' => esc_html__( 'Changes not saved! Are you sure you want to leave this page?', 'elementra' ),
				)
			)
		);
	}
}



//-------------------------------------------------------
//-- TinyMCE editor
//-------------------------------------------------------

if ( ! function_exists( 'elementra_tinymce_init' ) ) {
	add_filter( 'tiny_mce_before_init', 'elementra_skin_tinymce_init', 1000 );
	/**
	 * Add the body class with the current color scheme to the TinyMCE editor
	 * 
	 * @param array $opt  The TinyMCE options
	 * 
	 * @hooked 'tiny_mce_before_init', 1000
	 * 
	 * @return array  The modified TinyMCE options
	 */
	function elementra_skin_tinymce_init( $opt ) {
		$opt['body_class'] = ( ! empty( $opt['body_class'] ) ? $opt['body_class'] . ' ' : '' ) . 'scheme_' . esc_attr( elementra_get_theme_option( 'color_scheme', 'default' ) );
		return $opt;
	}
}



//-------------------------------------------------------
//-- Third party plugins
//-------------------------------------------------------

if ( ! function_exists( 'elementra_register_plugins' ) ) {
	/**
	 * Register the theme-required plugins for the TGM Activation plugin
	 * 
	 * @hooked 'tgmpa_register'
	 * 
	 * @trigger 'elementra_filter_tgmpa_required_plugins'
	 */
	function elementra_register_plugins() {
		tgmpa(
			apply_filters(
				'elementra_filter_tgmpa_required_plugins', array(
				// Plugins to include in the autoinstall queue.
				)
			),
			array(
				'id'           => 'tgmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
				'default_path' => '',                      // Default absolute path to bundled plugins.
				'menu'         => 'tgmpa-install-plugins', // Menu slug.
				'parent_slug'  => 'themes.php',            // Parent menu slug.
				'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
				'has_notices'  => true,                    // Show admin notices or not.
				'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
				'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
				'is_automatic' => false,                   // Automatically activate plugins after installation or not.
				'message'      => '',                      // Message to output right before the plugins table.
			)
		);
	}
}


if ( ! function_exists( 'elementra_add_group_and_logo_to_slave' ) ) {
	/**
	 * Copy a group and a logo from the parent plugin to the slave plugin settings.
	 * 
	 * @param array  $list    The list of plugins
	 * @param string $parent  The parent plugin slug
	 * @param string $slave   The slave plugin slug
	 * 
	 * @return array  The modified list of plugins
	 */
	function elementra_add_group_and_logo_to_slave( $list, $parent, $slave ) {
		$group = ! empty( $list[ $parent ]['group'] )
					? $list[ $parent ]['group']
					: elementra_storage_get_array( 'required_plugins', $parent, 'group' ); 
		if ( ! empty( $group ) ) {
			foreach ( $list as $k => $v ) {
				if ( substr( $k, 0, strlen( $slave ) ) == $slave ) {
					if ( empty( $v['group'] ) ) {
						$list[ $k ]['group'] = $group;
					}
					if ( empty( $v['logo'] ) ) {
						$logo = elementra_get_file_url( "plugins/{$parent}/{$k}.png" );
						$list[ $k ]['logo'] = empty( $logo )
												? ( ! empty( $list[ $parent ]['logo'] )
													? ( elementra_is_url( $list[ $parent ]['logo'] )
														? $list[ $parent ]['logo']
														: elementra_get_file_url( sprintf( 'plugins/%1$s/%2$s', $parent, $list[ $parent ]['logo'] ) )
														)
													: ''
													)
												: $logo;
					}
				}
			}
		}
		return $list;
	}
}


if ( ! function_exists( 'elementra_get_plugin_source_path' ) ) {
	/**
	 * Return a path (local or URL) to the plugin source
	 * 
	 * @param string $path  The plugin path relative to the 'plugins' directory in the theme folder
	 * 
	 * @return string  The local path or URL to the plugin source
	 */
	function elementra_get_plugin_source_path( $path ) {
		$local = elementra_get_file_dir( $path );
		$path  = empty( $local ) && ! elementra_get_theme_setting( 'tgmpa_upload' ) ? elementra_get_plugin_source_url( $path ) : $local;
		return $path;
	}
}


if ( ! function_exists( 'elementra_get_plugin_source_url' ) ) {
	/**
	 * Return URL to the plugin download from the ThemeREX Upgrader server
	 * 
	 * @param string $path  The plugin path relative to the 'plugins' directory in the theme folder
	 * 
	 * @return string  The URL to the plugin source
	 */
	function elementra_get_plugin_source_url( $path ) {
		$code = elementra_get_theme_activation_code();
		$url  = '';
		if ( ! empty( $code ) || elementra_is_theme_activated() || strpos($path, '/trx_addons/') !== false ) {   // Allow to install 'trx_addons' without theme activation
			$url = elementra_get_upgrade_url( array(
				'action' => 'install_plugin',
				'key'    => $code,
				'plugin' => str_replace( 'plugins/', '', $path )
			) );
		}
		return elementra_add_protocol( $url );
	}
}
