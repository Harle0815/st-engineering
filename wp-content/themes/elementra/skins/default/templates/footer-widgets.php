<?php
/**
 * The template to display the widgets area in the footer
 *
 * @package ELEMENTRA
 * @since ELEMENTRA 1.0.10
 */

// Footer sidebar
$elementra_footer_name    = elementra_get_theme_option( 'footer_widgets' );
$elementra_footer_present = ! elementra_is_off( $elementra_footer_name ) && is_active_sidebar( $elementra_footer_name );
if ( $elementra_footer_present ) {
	elementra_storage_set( 'current_sidebar', 'footer' );
	ob_start();
	if ( is_active_sidebar( $elementra_footer_name ) ) {
		dynamic_sidebar( $elementra_footer_name );
	}
	$elementra_out = trim( ob_get_contents() );
	ob_end_clean();
	if ( ! empty( $elementra_out ) ) {
		$elementra_out          = preg_replace( "/<\\/aside>[\r\n\s]*<aside/", '</aside><aside', $elementra_out );
		$elementra_need_columns = true;   //or check: strpos($elementra_out, 'columns_wrap')===false;
		if ( $elementra_need_columns ) {
			$elementra_columns = max( 0, (int) elementra_get_theme_option( 'footer_columns' ) );			
			if ( 0 == $elementra_columns ) {
				$elementra_columns = min( 4, max( 1, elementra_tags_count( $elementra_out, 'aside' ) ) );
			}
			if ( $elementra_columns > 1 ) {
				$elementra_out = preg_replace( '/<aside([^>]*)class="widget/', '<aside$1class="column-1_' . esc_attr( $elementra_columns ) . ' widget', $elementra_out );
			} else {
				$elementra_need_columns = false;
			}
		}
		?>
		<div class="footer_widgets_wrap widget_area sc_layouts_row">
			<?php do_action( 'elementra_action_before_sidebar_wrap', 'footer' ); ?>
			<div class="footer_widgets_inner widget_area_inner">
				<div class="content_wrap">
					<?php
					if ( $elementra_need_columns ) {
						?>
						<div class="columns_wrap">
						<?php
					}
					do_action( 'elementra_action_before_sidebar', 'footer' );
					elementra_show_layout( $elementra_out );
					do_action( 'elementra_action_after_sidebar', 'footer' );
					if ( $elementra_need_columns ) {
						?>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php do_action( 'elementra_action_after_sidebar_wrap', 'footer' ); ?>
		</div>
		<?php
	}
}
