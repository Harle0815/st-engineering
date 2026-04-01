<?php
/**
 * Quick Setup Section in the Theme Panel
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.48
 */


if ( ! function_exists( 'elementra_options_qsetup_add_scripts' ) ) {
	add_action("admin_enqueue_scripts", 'elementra_options_qsetup_add_scripts');
	/**
	 * Load required styles and scripts for admin mode for the 'Quick Setup' section in the Theme Panel.
	 * 
	 * @hooked 'admin_enqueue_scripts'
	 */
	function elementra_options_qsetup_add_scripts() {
		if ( ! ELEMENTRA_THEME_FREE ) {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
			if ( is_object( $screen ) && ! empty( $screen->id ) && false !== strpos($screen->id, 'page_trx_addons_theme_panel') ) {
				wp_enqueue_style( 'elementra-fontello', elementra_get_file_url( 'css/font-icons/css/fontello.css' ), array(), null );
				wp_enqueue_script( 'jquery-ui-tabs', false, array( 'jquery', 'jquery-ui-core' ), null, true );
				wp_enqueue_script( 'jquery-ui-accordion', false, array( 'jquery', 'jquery-ui-core' ), null, true );
				wp_enqueue_script( 'elementra-options', elementra_get_file_url( 'theme-options/theme-options.js' ), array( 'jquery' ), null, true );
				wp_localize_script( 'elementra-options', 'elementra_dependencies', elementra_get_theme_dependencies() );
				wp_localize_script(	'elementra-options', 'elementra_options_vars', apply_filters(
					'elementra_filter_options_vars', array(
						'max_load_fonts'            => elementra_get_theme_setting( 'max_load_fonts' ),
						'save_only_changed_options' => elementra_get_theme_setting( 'save_only_changed_options' ),
					)
				) );
			}
		}
	}
}


if ( ! function_exists( 'elementra_options_qsetup_theme_panel_steps' ) ) {
	add_filter( 'trx_addons_filter_theme_panel_steps', 'elementra_options_qsetup_theme_panel_steps' );
	/**
	 * Add the step with the 'Quick Setup' section to the Theme Panel steps.
	 * 
	 * @hooked 'trx_addons_filter_theme_panel_steps'
	 * 
	 * @param array $steps  Array of steps in the Theme Panel
	 * 
	 * @return array  Modified array of steps with the 'Quick Setup' step added
	 */
	function elementra_options_qsetup_theme_panel_steps( $steps ) {
		if ( ! ELEMENTRA_THEME_FREE ) {
			$steps = elementra_array_merge( $steps, array( 'qsetup' => esc_html__( 'Start customizing your theme.', 'elementra' ) ) );
		}
		return $steps;
	}
}


if ( ! function_exists( 'elementra_options_qsetup_theme_panel_tabs' ) ) {
	add_filter( 'trx_addons_filter_theme_panel_tabs', 'elementra_options_qsetup_theme_panel_tabs' );
	/**
	 * Add a tab link 'Quick Setup' to the Theme Panel tabs.
	 * 
	 * @hooked 'trx_addons_filter_theme_panel_tabs'
	 * 
	 * @param array $tabs  Array of tabs in the Theme Panel
	 * 
	 * @return array  Modified array of tabs with the 'Quick Setup' tab added
	 */
	function elementra_options_qsetup_theme_panel_tabs( $tabs ) {
		if ( ! ELEMENTRA_THEME_FREE ) {
			elementra_array_insert_after( $tabs, 'plugins', array( 'qsetup' => esc_html__( 'Quick Setup', 'elementra' ) ) );
		}
		return $tabs;
	}
}

if ( ! function_exists( 'elementra_options_qsetup_add_accent_colors' ) ) {
	add_filter( 'elementra_filter_qsetup_options', 'elementra_options_qsetup_add_accent_colors' );
	/**
	 * Add accent colors to the 'Quick Setup' section in the Theme Panel
	 * 
	 * @hooked 'elementra_filter_qsetup_options'
	 * 
	 * @param array $options  Array of options in the 'Quick Setup' section
	 * 
	 * @return array  Modified array of options with accent colors added
	 */
	function elementra_options_qsetup_add_accent_colors( $options ) {
		$colors = apply_filters( 'elementra_filter_qsetup_colors', array(
			'text_link',
			'text_hover',
			'text_link2',
			'text_hover2',
			'text_link3',
			'text_hover3',
		) );
		if ( is_array( $colors ) && count( $colors ) > 0 ) {
			$names = elementra_storage_get( 'scheme_color_names' );
			$list = array(
				'colors_info'        => array(
					'title'    => esc_html__( 'Theme Colors', 'elementra' ),
					'desc'     => '',
					'qsetup'   => esc_html__( 'General', 'elementra' ),
					'type'     => 'info',
				),
			);
			foreach ( $colors as $color ) {
				if ( empty( $names[ $color ] ) ) {
					continue;
				}
				$list[ 'colors_' . elementra_get_scheme_color_name( $color ) ] = array(
					'title'    => esc_html( $names[ $color ]['title'] ),
					'desc'     => wp_kses_data( $names[ $color ]['description'] ),
					'std'      => '',
					'val'      => elementra_get_scheme_color( $color ),
					'qsetup'   => esc_html__( 'General', 'elementra' ),
					'type'     => 'color',
				);
			}
			$options = elementra_array_merge( $list, $options );
		}
		return $options;
	}
}

if ( ! function_exists( 'elementra_options_qsetup_theme_panel_section' ) ) {
	add_action( 'trx_addons_action_theme_panel_section', 'elementra_options_qsetup_theme_panel_section', 10, 2);
	/**
	 * Display 'Quick Setup' section in the Theme Panel
	 * 
	 * @hooked 'trx_addons_action_theme_panel_section'
	 * 
	 * @param string $tab_id  ID of the current tab
	 * @param array  $theme_info  Information about the theme
	 */
	function elementra_options_qsetup_theme_panel_section( $tab_id, $theme_info ) {
		if ( 'qsetup' !== $tab_id ) return;
		?>
		<div id="trx_addons_theme_panel_section_<?php echo esc_attr($tab_id); ?>" class="trx_addons_tabs_section">

			<?php do_action('trx_addons_action_theme_panel_section_start', $tab_id, $theme_info); ?>
			
			<div class="trx_addons_theme_panel_section_content trx_addons_theme_panel_qsetup">

				<?php do_action('trx_addons_action_theme_panel_before_section_title', $tab_id, $theme_info); ?>

				<h1 class="trx_addons_theme_panel_section_title">
					<?php esc_html_e( 'Quick Setup', 'elementra' ); ?>
				</h1>

				<?php do_action('trx_addons_action_theme_panel_after_section_title', $tab_id, $theme_info); ?>
				
				<div class="trx_addons_theme_panel_section_description">
					<p>
						<?php
						echo wp_kses_data( __( 'Here you can customize the basic settings of your website.', 'elementra' ) )
							. ' '
							. wp_kses_data( sprintf(
								__( 'For a detailed customization, go to %s.', 'elementra' ),
								'<a href="' . esc_url(admin_url() . 'customize.php') . '">' . esc_html__( 'Customizer', 'elementra' ) . '</a>'
								. ( ELEMENTRA_THEME_FREE 
									? ''
									: ' ' . esc_html__( 'or', 'elementra' ) . ' ' . '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=trx_addons_theme_panel' ) ) . '">' . esc_html__( 'Theme Options', 'elementra' ) . '</a>'
									)
								)
							);
						echo ' ' . wp_kses_data( __( "If you've imported the demo data, you may skip this step, since all the necessary settings have already been applied.", 'elementra' ) );
						?>
					</p>
				</div>

				<?php
				do_action('trx_addons_action_theme_panel_before_qsetup', $tab_id, $theme_info);

				elementra_options_qsetup_show();

				do_action('trx_addons_action_theme_panel_after_qsetup', $tab_id, $theme_info);

				do_action('trx_addons_action_theme_panel_after_section_data', $tab_id, $theme_info);
				?>

			</div>

			<?php do_action('trx_addons_action_theme_panel_section_end', $tab_id, $theme_info); ?>

		</div>
		<?php
	}
}

if ( ! function_exists( 'elementra_options_qsetup_show' ) ) {
	/**
	 * Display options in the 'Quick Setup' section of the Theme Panel.
	 */
	function elementra_options_qsetup_show() {
		$tabs_titles  = array();
		$tabs_content = array();
		$options      = apply_filters( 'elementra_filter_qsetup_options', elementra_storage_get( 'options' ) );
		// Show fields
		$cnt = 0;
		foreach ( $options as $k => $v ) {
			if ( empty( $v['qsetup'] ) ) {
				continue;
			}
			if ( is_bool( $v['qsetup'] ) ) {
				$v['qsetup'] = esc_html__( 'General', 'elementra' );
			}
			if ( ! isset( $tabs_titles[ $v['qsetup'] ] ) ) {
				$tabs_titles[ $v['qsetup'] ]  = $v['qsetup'];
				$tabs_content[ $v['qsetup'] ] = '';
			}
			if ( 'info' !== $v['type'] ) {
				$cnt++;
				if ( ! empty( $v['class'] ) ) {
					$v['class'] = str_replace( array( 'elementra_column-1_2', 'elementra_new_row' ), '', $v['class'] );
				}
				$v['class'] = ( ! empty( $v['class'] ) ? $v['class'] . ' ' : '' ) . 'elementra_column-1_2' . ( $cnt % 2 == 1 ? ' elementra_new_row' : '' );
			} else {
				$cnt = 0;
			}
			$tabs_content[ $v['qsetup'] ] .= elementra_options_show_field( $k, $v );
		}
		if ( count( $tabs_titles ) > 0 ) {
			?>
			<div class="elementra_options elementra_options_qsetup">
				<form action="<?php echo esc_url( get_admin_url( null, 'admin.php?page=trx_addons_theme_panel' ) ); ?>" class="trx_addons_theme_panel_section_form" name="trx_addons_theme_panel_qsetup_form" method="post">
					<input type="hidden" name="qsetup_options_nonce" value="<?php echo esc_attr( wp_create_nonce( admin_url() ) ); ?>" />
					<?php
					if ( count( $tabs_titles ) > 1 ) {
						?>
						<div id="elementra_options_tabs" class="elementra_tabs">
							<ul>
								<?php
								$cnt = 0;
								foreach ( $tabs_titles as $k => $v ) {
									$cnt++;
									?>
									<li><a href="#elementra_options_<?php echo esc_attr( $cnt ); ?>"><?php echo esc_html( $v ); ?></a></li>
									<?php
								}
								?>
							</ul>
							<?php
							$cnt = 0;
							foreach ( $tabs_content as $k => $v ) {
								$cnt++;
								?>
								<div id="elementra_options_<?php echo esc_attr( $cnt ); ?>" class="elementra_tabs_section elementra_options_section">
									<?php elementra_show_layout( $v ); ?>
								</div>
								<?php
							}
							?>
						</div>
						<?php
					} else {
						?>
						<div class="elementra_options_section">
							<?php elementra_show_layout( elementra_array_get_first( $tabs_content, false ) ); ?>
						</div>
						<?php
					}
					?>
					<div class="elementra_options_buttons trx_buttons">
						<a href="#" role="button" class="elementra_options_button_submit trx_addons_button trx_addons_button_accent" tabindex="0"><?php esc_html_e( 'Save Options', 'elementra' ); ?></a>
					</div>
				</form>
			</div>
			<?php
		}
	}
}


if ( ! function_exists( 'elementra_options_qsetup_save_options' ) ) {
	add_action( 'after_setup_theme', 'elementra_options_qsetup_save_options', 4 );
	/**
	 * Merge a 'Quick setup' options with the Theme Options and save them.
	 * 
	 * @hooked 'after_setup_theme'
	 */
	function elementra_options_qsetup_save_options() {

		if ( ! isset( $_REQUEST['page'] ) || 'trx_addons_theme_panel' != $_REQUEST['page'] || '' == elementra_get_value_gp( 'qsetup_options_nonce' ) ) {
			return;
		}

		// verify nonce
		if ( ! wp_verify_nonce( elementra_get_value_gp( 'qsetup_options_nonce' ), admin_url() ) ) {
			trx_addons_set_admin_message( esc_html__( 'Bad security code! Options are not saved!', 'elementra' ), 'error', true );
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			trx_addons_set_admin_message( esc_html__( 'Manage options is denied for the current user! Options are not saved!', 'elementra' ), 'error', true );
			return;
		}

		// Prepare colors for Theme Options
		$scheme_storage = get_theme_mod( 'scheme_storage' );
		if ( empty( $scheme_storage ) ) {
			$scheme_storage = elementra_get_scheme_storage();
		}
		if ( ! empty( $scheme_storage ) ) {
			$schemes = elementra_unserialize( $scheme_storage, true );
			if ( is_array( $schemes ) ) {
				$main_scheme = elementra_storage_get_array( 'schemes_sorted', 0 );
				if ( empty( $main_scheme ) ) {
					$main_scheme = 'default';
				}
				$color_scheme = get_theme_mod( $main_scheme, elementra_storage_get_array( 'options', $main_scheme, 'std' ) );
				if ( empty( $color_scheme ) ) {
					$color_scheme = elementra_array_get_first( $schemes );
				}
				if ( ! empty( $schemes[ $color_scheme ] ) ) {
					$schemes_simple = elementra_storage_get( 'schemes_simple' );
					// Get posted data and calculate substitutions
					$need_save = false;
					foreach ( $schemes[ $color_scheme ][ 'colors' ] as $k => $v ) {
						$v2 = elementra_get_value_gp( "elementra_options_field_colors_{$k}" );
						if ( ! empty( $v2 ) && $v != $v2 ) {
							$schemes[ $color_scheme ][ 'colors' ][ $k ] = $v2;
							$need_save = true;
							// Сalculate substitutions
							if ( isset( $schemes_simple[ $k ] ) && is_array( $schemes_simple[ $k ] ) ) {
								foreach ( $schemes_simple[ $k ] as $color => $level ) {
									$new_v2 = $v2;
									// Make color_value darker or lighter
									if ( 1 != $level ) {
										$hsb = elementra_hex2hsb( $new_v2 );
										$hsb[ 'b' ] = min( 100, max( 0, $hsb[ 'b' ] * ( $hsb[ 'b' ] < 70 ? 2 - $level : $level ) ) );
										$new_v2 = elementra_hsb2hex( $hsb );
									}
									$schemes[ $color_scheme ][ 'colors' ][ $color ] = $new_v2;
								}
							}
						}
					}
					// Put new values to the POST
					if ( $need_save ) {
						$_POST[ 'elementra_options_field_scheme_storage' ] = serialize( $schemes );
					}
				}
			}
		}

		// Save options
		elementra_options_update( null, 'elementra_options_field_' );

		// Return result
		trx_addons_set_admin_message( esc_html__( 'Options are saved', 'elementra' ), 'success', true );
		wp_redirect( get_admin_url( null, 'admin.php?page=trx_addons_theme_panel#trx_addons_theme_panel_section_qsetup' ) );
		exit();
	}
}
