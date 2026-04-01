<?php
/**
 * Generate custom CSS for theme hovers
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */

// Theme init priorities:
// 3 - add/remove Theme Options elements
if ( ! function_exists( 'elementra_hovers_theme_setup3' ) ) {
	add_action( 'after_setup_theme', 'elementra_hovers_theme_setup3', 3 );
	function elementra_hovers_theme_setup3() {
		// Add 'Image hover' option
		elementra_storage_set_array_after(
			'options', 'general_misc_info', array(
				'image_hover'  => array(
					'title'    => esc_html__( "Image hover", 'elementra' ),
					'desc'     => wp_kses_data( __( 'Select a hover effect for theme images', 'elementra' ) ),
					'std'      => 'default',
					'options'  => elementra_get_list_hovers(),
					'type'     => 'select',
				),
			)
		);
	}
}

// Theme init priorities:
// 9 - register other filters (for installer, etc.)
if ( ! function_exists( 'elementra_hovers_theme_setup9' ) ) {
	add_action( 'after_setup_theme', 'elementra_hovers_theme_setup9', 9 );
	function elementra_hovers_theme_setup9() {
		add_action( 'wp_enqueue_scripts', 'elementra_hovers_frontend_styles', 1100 );       // Priority 1100 -  after theme/skin styles (1050)
		add_filter( 'elementra_filter_merge_styles', 'elementra_hovers_merge_styles' );
		add_action( 'elementra_action_add_hover_icons','elementra_hovers_add_icons', 10, 2 );
	}
}

// Enqueue styles for frontend
if ( ! function_exists( 'elementra_hovers_frontend_styles' ) ) {
	//Handler of the add_action( 'wp_enqueue_scripts', 'elementra_hovers_frontend_styles', 1100 );
	function elementra_hovers_frontend_styles() {
		if ( elementra_is_on( elementra_get_theme_option( 'debug_mode' ) ) ) {
			$elementra_url = elementra_get_file_url( 'theme-specific/theme-hovers/theme-hovers.css' );
			if ( '' != $elementra_url ) {
				wp_enqueue_style( 'elementra-hovers', $elementra_url, array(), null );
			}
		}
	}
}

// Merge hover effects into single css
if ( ! function_exists( 'elementra_hovers_merge_styles' ) ) {
	//Handler of the add_filter( 'elementra_filter_merge_styles', 'elementra_hovers_merge_styles' );
	function elementra_hovers_merge_styles( $list ) {
		$list[ 'theme-specific/theme-hovers/theme-hovers.css' ] = true;
		return $list;
	}
}

// Add hover icons on the featured image
if ( ! function_exists( 'elementra_hovers_add_icons' ) ) {
	//Handler of the add_action( 'elementra_action_add_hover_icons','elementra_hovers_add_icons', 10, 2 );
	function elementra_hovers_add_icons( $hover, $args = array() ) {

		// Additional parameters
		$args = array_merge(
			array(
				'cat'        => '',
				'image'      => null,
				'no_links'   => false,
				'link'       => '',
				'post_info'  => '',
				'meta_parts' => ''
			), $args
		);

		$post_link = empty( $args['no_links'] )
						? ( ! empty( $args['link'] )
							? $args['link']
							: apply_filters( 'elementra_filter_get_post_link', get_permalink() )
							)
						: '';
		$no_link   = 'javascript:void(0)';
		$target    = ! empty( $post_link ) && elementra_is_external_url( $post_link ) && function_exists( 'elementra_external_links_target' ) ? elementra_external_links_target() : '';

		if ( 'default' == $hover ) {
			// Hover style 'Default'
			if ( ! empty( $args['post_info'] ) ) {
				elementra_show_layout( $args['post_info'] );
			}
			?>
			<a href="<?php echo ! empty( $post_link ) ? esc_url( $post_link ) : $no_link; ?>" <?php elementra_show_layout( $target ); ?> aria-hidden="true" class="cover-link"></a>
			<?php

		} elseif ( 'dots' == $hover ) {
			// Hover style 'Dots'
			if ( ! empty( $args['post_info'] ) ) {
				elementra_show_layout( $args['post_info'] );
			}
			?>
			<a href="<?php echo ! empty( $post_link ) ? esc_url( $post_link ) : $no_link; ?>" <?php elementra_show_layout( $target ); ?> aria-hidden="true" class="icons"><span></span><span></span><span></span></a>
			<?php

		} else {

			do_action( 'elementra_action_custom_hover_icons', $args, $hover );

			if ( ! empty( $args['post_info'] ) ) {
				elementra_show_layout( $args['post_info'] );
			}
			if ( ! empty( $post_link ) ) {
				?>
				<a href="<?php echo esc_url( $post_link ); ?>" <?php elementra_show_layout( $target ); ?> aria-hidden="true" class="icons"></a>
				<?php
			}
		}
	}
}
