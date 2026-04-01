<?php
/**
 * The Header: Logo and main menu
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js<?php
	// Class scheme_xxx need in the <html> as context for the <body>!
	echo ' scheme_' . esc_attr( elementra_get_theme_option( 'color_scheme' ) );
?>">

<head>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<?php
	if ( function_exists( 'wp_body_open' ) ) {
		wp_body_open();
	} else {
		do_action( 'wp_body_open' );
	}

	$elementra_full_post_loading = ( elementra_is_singular( 'post' ) || elementra_is_singular( 'attachment' ) ) && elementra_get_value_gp( 'action' ) == 'full_post_loading';
	$elementra_prev_post_loading = ( elementra_is_singular( 'post' ) || elementra_is_singular( 'attachment' ) ) && elementra_get_value_gp( 'action' ) == 'prev_post_loading';

	// Don't display the short links while actions 'full_post_loading' and 'prev_post_loading'
	if ( ! $elementra_full_post_loading && ! $elementra_prev_post_loading ) {
		// Short links to fast access to the content, sidebar and footer from the keyboard
		?><a class="skip-link elementra_skip_link skip_to_content_link" href="#content_skip_link_anchor" tabindex="<?php echo esc_attr( apply_filters( 'elementra_filter_skip_links_tabindex', 0 ) ); ?>"><?php esc_html_e( "Skip to content", 'elementra' ); ?></a><?php
		if ( elementra_sidebar_present() ) {
			?><a class="skip-link elementra_skip_link skip_to_sidebar_link" href="#sidebar_skip_link_anchor" tabindex="<?php echo esc_attr( apply_filters( 'elementra_filter_skip_links_tabindex', 0 ) ); ?>"><?php esc_html_e( "Skip to sidebar", 'elementra' ); ?></a><?php
		}
		?><a class="skip-link elementra_skip_link skip_to_footer_link" href="#footer_skip_link_anchor" tabindex="<?php echo esc_attr( apply_filters( 'elementra_filter_skip_links_tabindex', 0 ) ); ?>"><?php esc_html_e( "Skip to footer", 'elementra' ); ?></a><?php
	}

	do_action( 'elementra_action_before_body' );
	?>

	<div class="<?php echo esc_attr( apply_filters( 'elementra_filter_body_wrap_class', 'body_wrap' ) ); ?>" <?php do_action('elementra_action_body_wrap_attributes'); ?>>

		<?php do_action( 'elementra_action_before_page_wrap' ); ?>

		<div class="<?php echo esc_attr( apply_filters( 'elementra_filter_page_wrap_class', 'page_wrap' ) ); ?>" <?php do_action('elementra_action_page_wrap_attributes'); ?>>

			<?php do_action( 'elementra_action_page_wrap_start' ); ?>

			<?php

			// Don't display the header elements while actions 'full_post_loading' and 'prev_post_loading'
			if ( ! $elementra_full_post_loading && ! $elementra_prev_post_loading ) {

				do_action( 'elementra_action_before_header' );

				// Header
				$elementra_header_type = elementra_get_theme_option( 'header_type' );
				if ( 'custom' == $elementra_header_type && ! elementra_is_layouts_available() ) {
					$elementra_header_type = 'default';
				}
				get_template_part( apply_filters( 'elementra_filter_get_template_part', "templates/header-" . sanitize_file_name( $elementra_header_type ) ) );

				// Side menu
				if ( in_array( elementra_get_theme_option( 'menu_side', 'none' ), array( 'left', 'right' ) ) ) {
					get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/header-navi-side' ) );
				}

				// Mobile menu
				if ( apply_filters( 'elementra_filter_use_navi_mobile', elementra_sc_layouts_showed( 'menu_button' ) || $elementra_header_type == 'default' ) ) {
					get_template_part( apply_filters( 'elementra_filter_get_template_part', 'templates/header-navi-mobile' ) );
				}

				do_action( 'elementra_action_after_header' );

			}
			?>

			<?php do_action( 'elementra_action_before_page_content_wrap' ); ?>

			<div class="page_content_wrap<?php
				if ( elementra_is_off( elementra_get_theme_option( 'remove_margins' ) ) ) {
					if ( empty( $elementra_header_type ) ) {
						$elementra_header_type = elementra_get_theme_option( 'header_type' );
					}
					if ( 'custom' == $elementra_header_type && elementra_is_layouts_available() ) {
						$elementra_header_id = elementra_get_custom_header_id();
						if ( $elementra_header_id > 0 ) {
							$elementra_header_meta = elementra_get_custom_layout_meta( $elementra_header_id );
							if ( ! empty( $elementra_header_meta['margin'] ) ) {
								?> page_content_wrap_custom_header_margin<?php
							}
						}
					}
					$elementra_footer_type = elementra_get_theme_option( 'footer_type' );
					if ( 'custom' == $elementra_footer_type && elementra_is_layouts_available() ) {
						$elementra_footer_id = elementra_get_custom_footer_id();
						if ( $elementra_footer_id ) {
							$elementra_footer_meta = elementra_get_custom_layout_meta( $elementra_footer_id );
							if ( ! empty( $elementra_footer_meta['margin'] ) ) {
								?> page_content_wrap_custom_footer_margin<?php
							}
						}
					}
				}
				do_action( 'elementra_action_page_content_wrap_class', $elementra_prev_post_loading );
				?>"<?php
				if ( apply_filters( 'elementra_filter_is_prev_post_loading', $elementra_prev_post_loading ) ) {
					?> data-single-style="<?php echo esc_attr( elementra_get_theme_option( 'single_style' ) ); ?>"<?php
				}
				do_action( 'elementra_action_page_content_wrap_data', $elementra_prev_post_loading );
			?>>
				<?php
				do_action( 'elementra_action_page_content_wrap', $elementra_full_post_loading || $elementra_prev_post_loading );

				// Single posts banner
				if ( apply_filters( 'elementra_filter_single_post_header', elementra_is_singular( 'post' ) || elementra_is_singular( 'attachment' ) ) ) {
					if ( $elementra_prev_post_loading ) {
						if ( elementra_get_theme_option( 'posts_navigation_scroll_which_block', 'article' ) != 'article' ) {
							do_action( 'elementra_action_between_posts' );
						}
					}
					// Single post thumbnail and title
					$elementra_path = apply_filters( 'elementra_filter_get_template_part', 'templates/single-styles/' . elementra_get_theme_option( 'single_style' ) );
					if ( elementra_get_file_dir( $elementra_path . '.php' ) != '' ) {
						get_template_part( $elementra_path );
					}
				}

				// Widgets area above page
				$elementra_body_style   = elementra_get_theme_option( 'body_style' );
				$elementra_widgets_name = elementra_get_theme_option( 'widgets_above_page', 'hide' );
				$elementra_show_widgets = ! elementra_is_off( $elementra_widgets_name ) && is_active_sidebar( $elementra_widgets_name );
				if ( $elementra_show_widgets ) {
					if ( 'fullscreen' != $elementra_body_style ) {
						?>
						<div class="content_wrap">
							<?php
					}
					elementra_create_widgets_area( 'widgets_above_page' );
					if ( 'fullscreen' != $elementra_body_style ) {
						?>
						</div>
						<?php
					}
				}

				// Content area
				do_action( 'elementra_action_before_content_wrap' );
				?>
				<div class="content_wrap<?php echo 'fullscreen' == $elementra_body_style ? '_fullscreen' : ''; ?>">

					<?php do_action( 'elementra_action_content_wrap_start' ); ?>

					<div class="content">
						<?php
						do_action( 'elementra_action_page_content_start' );

						// Skip link anchor to fast access to the content from keyboard
						?>
						<span id="content_skip_link_anchor" class="elementra_skip_link_anchor"></span>
						<?php
						// Single posts banner between prev/next posts
						if ( ( elementra_is_singular( 'post' ) || elementra_is_singular( 'attachment' ) )
							&& $elementra_prev_post_loading 
							&& elementra_get_theme_option( 'posts_navigation_scroll_which_block', 'article' ) == 'article'
						) {
							do_action( 'elementra_action_between_posts' );
						}

						// Widgets area above content
						elementra_create_widgets_area( 'widgets_above_content' );

						do_action( 'elementra_action_page_content_start_text' );
