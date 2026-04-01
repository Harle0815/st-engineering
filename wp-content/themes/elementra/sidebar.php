<?php
/**
 * The Sidebar containing the main widget areas.
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

if ( elementra_sidebar_present() ) {
	
	$elementra_sidebar_type = elementra_get_theme_option( 'sidebar_type' );
	if ( 'custom' == $elementra_sidebar_type && ! elementra_is_layouts_available() ) {
		$elementra_sidebar_type = 'default';
	}
	
	// Catch output to the buffer
	ob_start();
	if ( 'default' == $elementra_sidebar_type ) {
		// Default sidebar with widgets
		$elementra_sidebar_name = elementra_get_theme_option( 'sidebar_widgets' );
		elementra_storage_set( 'current_sidebar', 'sidebar' );
		if ( is_active_sidebar( $elementra_sidebar_name ) ) {
			dynamic_sidebar( $elementra_sidebar_name );
		}
	} else {
		// Custom sidebar from Layouts Builder
		$elementra_sidebar_id = elementra_get_custom_sidebar_id();
		do_action( 'elementra_action_show_layout', $elementra_sidebar_id );
	}
	$elementra_out = trim( ob_get_contents() );
	ob_end_clean();
	
	// If any html is present - display it
	if ( ! empty( $elementra_out ) ) {
		$elementra_sidebar_position    = elementra_get_theme_option( 'sidebar_position' );
		$elementra_sidebar_position_ss = elementra_get_theme_option( 'sidebar_position_ss', 'below' );
		?>
		<div class="sidebar widget_area
			<?php
			echo ' ' . esc_attr( $elementra_sidebar_position );
			echo ' sidebar_' . esc_attr( $elementra_sidebar_position_ss );
			echo ' sidebar_' . esc_attr( $elementra_sidebar_type );

			$elementra_sidebar_scheme = apply_filters( 'elementra_filter_sidebar_scheme', elementra_get_theme_option( 'sidebar_scheme', 'inherit' ) );
			if ( ! empty( $elementra_sidebar_scheme ) && ! elementra_is_inherit( $elementra_sidebar_scheme ) && 'custom' != $elementra_sidebar_type ) {
				echo ' scheme_' . esc_attr( $elementra_sidebar_scheme );
			}
			?>
		" role="complementary">
			<?php

			// Skip link anchor to fast access to the sidebar from keyboard
			?>
			<span id="sidebar_skip_link_anchor" class="elementra_skip_link_anchor"></span>
			<?php

			do_action( 'elementra_action_before_sidebar_wrap', 'sidebar' );

			// Button to show/hide sidebar on mobile
			if ( in_array( $elementra_sidebar_position_ss, array( 'above', 'float' ) ) ) {
				$elementra_title = apply_filters( 'elementra_filter_sidebar_control_title', 'float' == $elementra_sidebar_position_ss ? esc_html__( 'Show Sidebar', 'elementra' ) : '' );
				$elementra_text  = apply_filters( 'elementra_filter_sidebar_control_text', 'above' == $elementra_sidebar_position_ss ? esc_html__( 'Show Sidebar', 'elementra' ) : '' );
				?>
				<a href="#" role="button" class="sidebar_control" title="<?php echo esc_attr( $elementra_title ); ?>"><?php echo esc_html( $elementra_text ); ?></a>
				<?php
			}
			?>
			<div class="sidebar_inner">
				<?php
				do_action( 'elementra_action_before_sidebar', 'sidebar' );
				elementra_show_layout( preg_replace( "/<\/aside>[\r\n\s]*<aside/", '</aside><aside', $elementra_out ) );
				do_action( 'elementra_action_after_sidebar', 'sidebar' );
				?>
			</div>
			<?php

			do_action( 'elementra_action_after_sidebar_wrap', 'sidebar' );

			?>
		</div>
		<div class="clearfix"></div>
		<?php
	}
}
