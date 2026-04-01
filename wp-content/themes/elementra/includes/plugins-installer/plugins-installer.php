<?php
/**
 * Plugin install helper.
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.31
 */


if ( ! function_exists( 'elementra_plugins_installer_get_button_html' ) ) {
	/**
	 * Return the button (link) to install/activate plugin
	 * 
	 * @param string $slug  The plugin slug
	 * @param bool   $show  If true, show the button, otherwise - return the button HTML
	 *
	 * @return string  The button HTML
	 */
	function elementra_plugins_installer_get_button_html( $slug, $show = true ) {
		$output = '';
		if ( ! empty( $slug ) ) {
			$state = elementra_plugins_installer_check_plugin_state( $slug );
			switch ( $state ) {
				case 'install':
					if ( class_exists( 'TGM_Plugin_Activation' ) ) {
						$instance = call_user_func( array( get_class( $GLOBALS['tgmpa'] ), 'get_instance' ) );
						$nonce    = wp_nonce_url(
							add_query_arg(
								array(
									'plugin'        => urlencode( $slug ),
									'tgmpa-install' => 'install-plugin',
								),
								$instance->get_tgmpa_url()
							),
							'tgmpa-install',
							'tgmpa-nonce'
						);
					} else {
						$nonce = wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'install-plugin',
									'from'   => 'import',
									'plugin' => urlencode( $slug ),
								),
								network_admin_url( 'update.php' )
							),
							'install-plugin_' . trim( $slug )
						);
					}
					$output .= '<a class="elementra_about_block_link elementra_plugins_installer_link button button-primary install-now"'
									. ' href="' . esc_url( $nonce ) . '"'
									. ' data-slug="' . esc_attr( $slug ) . '"'
									. ' data-name="' . esc_attr( $slug ) . '"'
									. ' data-processing="' . esc_attr__( 'Installing ...', 'elementra' ) . '"'
									// Translators: Add the plugin's slug to the 'aria-label'
									. ' aria-label="' . esc_attr( sprintf( __( 'Install %s', 'elementra' ), $slug ) ) . '"'
								. '>'
									. esc_html__( 'Install', 'elementra' )
								. '</a>';
					break;

				case 'activate':
					if ( class_exists( 'TGM_Plugin_Activation' ) ) {
						$instance = call_user_func( array( get_class( $GLOBALS['tgmpa'] ), 'get_instance' ) );
						$nonce    = wp_nonce_url(
							add_query_arg(
								array(
									'plugin'         => urlencode( $slug ),
									'tgmpa-activate' => 'activate-plugin',
								),
								$instance->get_tgmpa_url()
							),
							'tgmpa-activate',
							'tgmpa-nonce'
						);
					} else {
						$plugin_link = $slug . '/' . $slug . '.php';
						$nonce       = add_query_arg(
							array(
								'action'        => 'activate',
								'plugin'        => rawurlencode( $plugin_link ),
								'plugin_status' => 'all',
								'paged'         => '1',
								'_wpnonce'      => wp_create_nonce( 'activate-plugin_' . $plugin_link ),
							),
							network_admin_url( 'plugins.php' )
						);
					}
					$output .= '<a class="elementra_about_block_link elementra_plugins_installer_link button button-primary activate-now"'
									. ' href="' . esc_url( $nonce ) . '"'
									. ' data-slug="' . esc_attr( $slug ) . '"'
									. ' data-name="' . esc_attr( $slug ) . '"'
									. ' data-processing="' . esc_attr__( 'Activating ...', 'elementra' ) . '"'
									// Translators: Add the plugin's slug to the 'aria-label'
									. ' aria-label="' . esc_attr( sprintf( __( 'Activate %s', 'elementra' ), $slug ) ) . '"'
								. '>'
									. esc_html__( 'Activate', 'elementra' )
								. '</a>';
					break;
			}
		}
		if ( $show ) {
			elementra_show_layout( $output );
		}
		return $output;
	}
}

if ( ! function_exists( 'elementra_plugins_installer_check_plugin_state' ) ) {
	/**
	 * Return the plugin's state:
	 * - 'install': if the plugin is not installed,
	 * - 'activate': if the plugin is installed but not activated,
	 * - 'deactivate': if the plugin is installed and activated.
	 * 
	 * @param string $slug  The plugin slug
	 * 
	 * @return string  The plugin's state
	 */
	function elementra_plugins_installer_check_plugin_state( $slug ) {
		$state = 'install';
		if ( file_exists( ABSPATH . 'wp-content/plugins/' . $slug . '/' . $slug . '.php' ) ) {
			$state = is_plugin_inactive( $slug . '/' . $slug . '.php' ) ? 'activate' : 'deactivate';
		} elseif ( file_exists( ABSPATH . 'wp-content/plugins/' . $slug . '/index.php' ) ) {
			$state = is_plugin_inactive( $slug . '/index.php' ) ? 'activate' : 'deactivate';
		}
		return $state;
	}
}

if ( ! function_exists( 'elementra_plugins_installer_enqueue_scripts' ) ) {
	/**
	 * Enqueue scripts for the plugins installer
	 */
	function elementra_plugins_installer_enqueue_scripts() {
		wp_enqueue_script( 'plugin-install' );
		wp_enqueue_script( 'updates' );
		wp_enqueue_script( 'elementra-plugins-installer', elementra_get_file_url( 'includes/plugins-installer/plugins-installer.js' ), array( 'jquery' ), null, true );
	}
}
