<?php
/**
 * The template to show mobile menu (used only header_style == 'default')
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0
 */
?>
<div class="menu_mobile_overlay"></div>
<div class="menu_mobile menu_mobile_fullscreen">
	<div class="menu_mobile_inner">
		<span class="menu_mobile_close theme_button_close" tabindex="0"><span class="theme_button_close_icon"></span></span>
		<?php
		// Mobile menu
		$elementra_menu_mobile = elementra_get_nav_menu( 'menu_mobile' );
		if ( empty( $elementra_menu_mobile ) ) {
			$elementra_menu_mobile = apply_filters( 'elementra_filter_get_mobile_menu', '' );
			if ( empty( $elementra_menu_mobile ) ) {
				$elementra_menu_mobile = elementra_get_nav_menu( 'menu_main' );
				if ( empty( $elementra_menu_mobile ) ) {
					$elementra_menu_mobile = elementra_get_nav_menu();
				}
			}
		}
		if ( ! empty( $elementra_menu_mobile ) ) {
			// Change attribute 'id' - add prefix 'mobile-' to prevent duplicate id on the page
			$elementra_menu_mobile = preg_replace( '/([\s]*id=")/', '${1}mobile-', $elementra_menu_mobile );
			// Change main menu classes
			$elementra_menu_mobile = str_replace(
				array( 'menu_main',   'sc_layouts_menu_nav', 'sc_layouts_menu ' ),	// , 'sc_layouts_hide_on_mobile', 'hide_on_mobile'
				array( 'menu_mobile', '',                    ' ' ),					// , '',                          ''
				$elementra_menu_mobile
			);
			// Wrap menu to the <nav> if not present
			if ( strpos( $elementra_menu_mobile, '<nav ' ) !== 0 ) {	// condition !== false is not allowed, because menu can contain inner <nav> elements (in the submenu layouts)
				$elementra_menu_mobile = elementra_is_on( elementra_get_theme_option( 'seo_snippets' ) )
					? sprintf( '<nav class="menu_mobile_nav_area" itemscope="itemscope" itemtype="%1$s//schema.org/SiteNavigationElement">%2$s</nav>', esc_attr( elementra_get_protocol( true ) ), $elementra_menu_mobile )
					: sprintf( '<nav class="menu_mobile_nav_area">%s</nav>', $elementra_menu_mobile );
			}
			// Show menu
			elementra_show_layout( apply_filters( 'elementra_filter_menu_mobile_layout', $elementra_menu_mobile ) );
		}
		?>
	</div>
</div>