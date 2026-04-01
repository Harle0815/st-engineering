<?php
// Add theme-specific CSS-animations
if ( ! function_exists( 'elementra_elm_add_theme_animations' ) ) {
	add_filter( 'elementor/controls/animations/additional_animations', 'elementra_elm_add_theme_animations' );
	function elementra_elm_add_theme_animations( $animations ) {
		/* To add a theme-specific animations to the list:
			1) Merge to the array 'animations': array(
													esc_html__( 'Theme Specific', 'elementra' ) => array(
														'ta_custom_1' => esc_html__( 'Custom 1', 'elementra' )
													)
												)
			2) Add a CSS rules for the class '.ta_custom_1' to create a custom entrance animation
		*/
		$animations = array_merge(
						$animations,
						array(
							esc_html__( 'Theme Specific', 'elementra' ) => array(
																			'ta_fadeinup' 		=> esc_html__( 'Fade In Up (Short)', 'elementra' ),
																			'ta_fadeinright'	=> esc_html__( 'Fade In Right (Short)', 'elementra' ),
																			'ta_fadeinleft'		=> esc_html__( 'Fade In Left (Short)', 'elementra' ),
																			'ta_fadeindown'		=> esc_html__( 'Fade In Down (Short)', 'elementra' ),
																			'ta_fadein' 		=> esc_html__( 'Fade In (Short)', 'elementra' ),
																			'ta_popup' 			=> esc_html__( 'Pop Up', 'elementra' ),
																			'ta_infiniterotate' => esc_html__( 'Infinite Rotate', 'elementra' ),
																			)
							)
						);
		return $animations;
	}
}
